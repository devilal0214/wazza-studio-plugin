<?php
/**
 * Rental Payment Handler
 * 
 * Handles payment processing for studio rentals
 * 
 * @package WazaBooking\Payment
 */

namespace WazaBooking\Payment;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class RentalPaymentHandler {
    
    public function __construct() {
        add_action('waza_payment_success', [$this, 'handle_rental_payment_success'], 10, 1);
        add_action('waza_payment_failed', [$this, 'handle_rental_payment_failed'], 10, 1);
    }
    
    /**
     * Handle successful rental payment
     */
    public function handle_rental_payment_success($payment_data) {
        if (!isset($payment_data['type']) || $payment_data['type'] !== 'rental') {
            return;
        }
        
        // Rental completion is now handled directly in PaymentManager
        // This hook is kept for backwards compatibility and future extensions
        error_log('RentalPaymentHandler: Payment success action triggered (rental already completed in PaymentManager)');
        
        // Note: The rental was already completed in PaymentManager->verify_payment()
        // before this action was triggered, so we don't need to complete it again
        
        return;
    }
    
    /**
     * Handle failed rental payment
     */
    public function handle_rental_payment_failed($payment_data) {
        if (!isset($payment_data['type']) || $payment_data['type'] !== 'rental') {
            return;
        }
        
        $rental_id = intval($payment_data['rental_id'] ?? 0);
        $error_message = $payment_data['error_message'] ?? 'Payment failed';
        
        if (!$rental_id) {
            return;
        }
        
        global $wpdb;
        
        // Update rental payment status
        $wpdb->update(
            $wpdb->prefix . 'waza_rentals',
            ['payment_status' => 'failed'],
            ['id' => $rental_id],
            ['%s'],
            ['%d']
        );
    }
    
    /**
     * Send rental confirmation email
     */
    private function send_rental_confirmation($rental_id) {
        global $wpdb;
        
        $rental = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}waza_rentals WHERE id = %d",
            $rental_id
        ));
        
        if (!$rental) {
            return;
        }
        
        $subject = sprintf(__('[%s] Studio Rental Confirmed', 'waza-booking'), get_bloginfo('name'));
        
        $message = sprintf(
            __('Dear %s,

Your studio rental booking has been confirmed!

Booking Details:
Rental Type: %s
Duration: %s
Date: %s
Time: %s - %s
Amount Paid: ₹%s

Please show your QR code at the studio entrance.

Studio Address: %s
Contact: %s

Thank you for booking with us!

Best regards,
%s Team', 'waza-booking'),
            $rental->customer_name,
            ucwords(str_replace('_', ' ', $rental->rental_type)),
            ucwords(str_replace('_', ' ', $rental->duration_type)),
            date('M j, Y', strtotime($rental->rental_date)),
            date('g:i A', strtotime($rental->start_time)),
            date('g:i A', strtotime($rental->end_time)),
            number_format($rental->total_amount, 2),
            get_option('waza_studio_address', 'Waza Studio'),
            get_option('waza_studio_phone', get_option('admin_email')),
            get_bloginfo('name')
        );
        
        wp_mail($rental->customer_email, $subject, $message);
    }
}
