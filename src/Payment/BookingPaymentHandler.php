<?php
/**
 * Booking Payment Handler
 * 
 * Handles post-payment processes for activity slot bookings:
 * - User account creation
 * - QR code generation
 * - Confirmation emails
 * - Booking status updates
 * 
 * @package WazaBooking\Payment
 */

namespace WazaBooking\Payment;

use WazaBooking\Email\EmailTemplateManager;
use WazaBooking\QR\QRManager;

class BookingPaymentHandler {
    
    /**
     * Initialize payment handler
     */
    public function __construct() {
        add_action('waza_payment_success', [$this, 'handle_booking_payment_success'], 10, 1);
    }
    
    /**
     * Handle successful booking payment
     */
    public function handle_booking_payment_success($payment_data) {
        // Only handle booking payments (not rentals)
        if (isset($payment_data['type']) && $payment_data['type'] === 'rental') {
            return;
        }
        
        // Extract booking_id from payment_data
        $booking_id = 0;
        if (isset($payment_data['booking_id'])) {
            $booking_id = intval($payment_data['booking_id']);
        } elseif (isset($payment_data['type']) && $payment_data['type'] === 'booking' && isset($payment_data['id'])) {
            $booking_id = intval($payment_data['id']);
        }
        
        if (!$booking_id) {
            error_log('BookingPaymentHandler: No booking_id in payment_data: ' . print_r($payment_data, true));
            return;
        }
        
        $booking_id = intval($payment_data['booking_id']);
        
        error_log('=== BOOKING PAYMENT SUCCESS HANDLER ===');
        error_log('Booking ID: ' . $booking_id);
        
        global $wpdb;
        
        // Get booking details
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT b.*, s.start_datetime, s.end_datetime, s.activity_id,
                    p.post_title as activity_title
             FROM {$wpdb->prefix}waza_bookings b
             LEFT JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
             LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
             WHERE b.id = %d",
            $booking_id
        ));
        
        if (!$booking) {
            error_log('ERROR: Booking not found for ID: ' . $booking_id);
            return;
        }
        
        error_log('Booking found: ' . $booking->user_name . ' (' . $booking->user_email . ')');
        
        // 1. Create user account if needed
        $user_id = $this->create_user_account_if_needed($booking);
        
        // 2. Generate QR code
        $qr_token = $this->generate_qr_code($booking);
        
        // 3. Send confirmation email
        $this->send_confirmation_email($booking, $user_id, $qr_token);
        
        // 4. Update slot booked count
        $this->update_slot_capacity($booking->slot_id, $booking->quantity);
        
        error_log('=== BOOKING PAYMENT PROCESSING COMPLETE ===');
    }
    
    /**
     * Create user account if it doesn't exist
     */
    private function create_user_account_if_needed($booking) {
        // Check if user already exists
        $existing_user = get_user_by('email', $booking->user_email);
        
        if ($existing_user) {
            error_log('User already exists: ' . $existing_user->ID);
            
            // Link booking to user if not already linked
            if (!$booking->user_id) {
                global $wpdb;
                $wpdb->update(
                    $wpdb->prefix . 'waza_bookings',
                    ['user_id' => $existing_user->ID],
                    ['id' => $booking->id],
                    ['%d'],
                    ['%d']
                );
            }
            
            return $existing_user->ID;
        }
        
        // Check for pending account creation info
        $pending_account = get_option("waza_pending_account_{$booking->id}");
        
        if ($pending_account && isset($pending_account['password'])) {
            $password = $pending_account['password'];
        } else {
            // Generate random password
            $password = wp_generate_password(12, false);
        }
        
        // Create username from email
        $username = sanitize_user(strtolower(str_replace('@', '_', $booking->user_email)), true);
        $base_username = $username;
        $counter = 1;
        
        while (username_exists($username)) {
            $username = $base_username . '_' . $counter;
            $counter++;
        }
        
        // Create user
        $user_id = wp_create_user($username, $password, $booking->user_email);
        
        if (is_wp_error($user_id)) {
            error_log('ERROR creating user: ' . $user_id->get_error_message());
            return false;
        }
        
        // Set role and display name
        $user = new \WP_User($user_id);
        $user->set_role('waza_student');
        wp_update_user([
            'ID' => $user_id,
            'display_name' => $booking->user_name,
            'first_name' => $booking->user_name
        ]);
        
        // Update booking with user_id
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'waza_bookings',
            ['user_id' => $user_id],
            ['id' => $booking->id],
            ['%d'],
            ['%d']
        );
        
        // Store password for email
        update_user_meta($user_id, '_waza_initial_password', $password);
        
        // Clean up pending account option
        delete_option("waza_pending_account_{$booking->id}");
        
        error_log('User created successfully: ID ' . $user_id);
        
        return $user_id;
    }
    
    /**
     * Generate QR code for booking
     */
    private function generate_qr_code($booking) {
        // Check if QR already exists
        global $wpdb;
        $existing_qr = $wpdb->get_row($wpdb->prepare(
            "SELECT token FROM {$wpdb->prefix}waza_qr_tokens WHERE booking_id = %d",
            $booking->id
        ));
        
        if ($existing_qr) {
            error_log('QR code already exists for booking: ' . $existing_qr->token);
            return $existing_qr->token;
        }
        
        // Generate unique token
        $token = wp_generate_password(32, false, false);
        $token_hash = hash('sha256', $token);
        
        // Calculate expiry (24 hours after slot end time)
        $expires_at = date('Y-m-d H:i:s', strtotime($booking->end_datetime . ' +24 hours'));
        
        // Insert QR token
        $wpdb->insert(
            $wpdb->prefix . 'waza_qr_tokens',
            [
                'token' => $token,
                'token_hash' => $token_hash,
                'booking_id' => $booking->id,
                'slot_id' => $booking->slot_id,
                'token_type' => 'single',
                'max_uses' => 1,
                'used_count' => 0,
                'expires_at' => $expires_at,
                'is_active' => 1,
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%d', '%d', '%s', '%d', '%d', '%s', '%d', '%s']
        );
        
        if ($wpdb->last_error) {
            error_log('ERROR creating QR token: ' . $wpdb->last_error);
            return false;
        }
        
        error_log('QR code generated: ' . $token);
        
        return $token;
    }
    
    /**
     * Send confirmation email to customer
     */
    private function send_confirmation_email($booking, $user_id, $qr_token) {
        $to = $booking->user_email;
        $subject = sprintf(__('Booking Confirmed - %s', 'waza-booking'), $booking->activity_title);
        
        // Get user password if newly created
        $password = '';
        if ($user_id) {
            $password = get_user_meta($user_id, '_waza_initial_password', true);
        }
        
        // Format datetime
        $date = date_i18n('l, F j, Y', strtotime($booking->start_datetime));
        $time = date_i18n('g:i A', strtotime($booking->start_datetime)) . ' - ' . date_i18n('g:i A', strtotime($booking->end_datetime));
        
        // Generate QR code URL
        $qr_url = home_url('/qr-code/?token=' . $qr_token);
        
        // Build email body
        $message = '<html><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">';
        $message .= '<div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">';
        
        $message .= '<h2 style="color: #2271b1; text-align: center;">🎉 Booking Confirmed!</h2>';
        
        $message .= '<p>Dear ' . esc_html($booking->user_name) . ',</p>';
        $message .= '<p>Your booking has been confirmed! Here are the details:</p>';
        
        $message .= '<div style="background: #f8f9fa; padding: 20px; border-radius: 6px; margin: 20px 0;">';
        $message .= '<strong>Activity:</strong> ' . esc_html($booking->activity_title) . '<br>';
        $message .= '<strong>Date:</strong> ' . $date . '<br>';
        $message .= '<strong>Time:</strong> ' . $time . '<br>';
        $message .= '<strong>Participants:</strong> ' . $booking->quantity . '<br>';
        $message .= '<strong>Total Amount:</strong> ₹' . number_format($booking->total_amount, 2);
        $message .= '</div>';
        
        // QR Code section
        if ($qr_token) {
            $message .= '<div style="background: #d1ecf1; padding: 20px; border-radius: 6px; margin: 20px 0; text-align: center;">';
            $message .= '<h3 style="margin-top: 0; color: #0c5460;">Your QR Code</h3>';
            $message .= '<p>Show this QR code at the venue for check-in:</p>';
            $message .= '<p><a href="' . $qr_url . '" style="display: inline-block; padding: 12px 24px; background: #2271b1; color: white; text-decoration: none; border-radius: 6px;">Download QR Code</a></p>';
            $message .= '</div>';
        }
        
        // Account info section (if new user)
        if ($password) {
            $message .= '<div style="background: #fff3cd; padding: 20px; border-radius: 6px; margin: 20px 0;">';
            $message .= '<h3 style="margin-top: 0; color: #856404;">Your Account Details</h3>';
            $message .= '<p>We\'ve created an account for you to manage your bookings:</p>';
            $message .= '<strong>Username:</strong> ' . esc_html($booking->user_email) . '<br>';
            $message .= '<strong>Password:</strong> ' . esc_html($password) . '<br><br>';
            $message .= '<p><a href="' . wp_login_url() . '" style="color: #2271b1;">Login to your account</a></p>';
            $message .= '<p style="font-size: 12px; color: #666;"><em>Please change your password after first login for security.</em></p>';
            $message .= '</div>';
        }
        
        $message .= '<p>We look forward to seeing you!</p>';
        $message .= '<p style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px;">
            If you have any questions, please contact us at ' . get_option('admin_email') . '
        </p>';
        
        $message .= '</div></body></html>';
        
        // Send email
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $sent = wp_mail($to, $subject, $message, $headers);
        
        if ($sent) {
            error_log('Confirmation email sent to: ' . $to);
        } else {
            error_log('ERROR: Failed to send confirmation email to: ' . $to);
        }
        
        // Clean up password meta
        if ($user_id && $password) {
            delete_user_meta($user_id, '_waza_initial_password');
        }
        
        return $sent;
    }
    
    /**
     * Update slot booked count
     */
    private function update_slot_capacity($slot_id, $quantity) {
        global $wpdb;
        
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}waza_slots 
             SET booked_count = booked_count + %d
             WHERE id = %d",
            $quantity,
            $slot_id
        ));
        
        error_log('Updated slot #' . $slot_id . ' booked count by +' . $quantity);
    }
}
