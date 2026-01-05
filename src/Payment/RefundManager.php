<?php
/**
 * Refund Manager
 * 
 * Handles refund processing and policies
 * 
 * @package WazaBooking\Payment
 */

namespace WazaBooking\Payment;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Refund Manager Class
 */
class RefundManager {
    
    /**
     * Initialize refund functionality
     */
    public function init() {
        add_action('wp_ajax_waza_process_refund', [$this, 'ajax_process_refund']);
        add_action('wp_ajax_waza_calculate_refund', [$this, 'ajax_calculate_refund']);
        add_action('wp_ajax_waza_check_refund_eligibility', [$this, 'ajax_check_eligibility']);
    }
    
    /**
     * Process refund
     */
    public function ajax_process_refund() {
        check_ajax_referer('waza_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_waza')) {
            wp_send_json_error(__('Permission denied', 'waza-booking'));
        }
        
        $booking_id = intval($_POST['booking_id'] ?? 0);
        $refund_type = sanitize_text_field($_POST['refund_type'] ?? 'full');
        $refund_reason = sanitize_textarea_field($_POST['refund_reason'] ?? '');
        $custom_amount = isset($_POST['custom_amount']) ? floatval($_POST['custom_amount']) : 0;
        
        if (!$booking_id) {
            wp_send_json_error(__('Invalid booking ID', 'waza-booking'));
        }
        
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_bookings WHERE id = %d
        ", $booking_id));
        
        if (!$booking) {
            wp_send_json_error(__('Booking not found', 'waza-booking'));
        }
        
        if ($booking->payment_status !== 'completed') {
            wp_send_json_error(__('Payment not completed for this booking', 'waza-booking'));
        }
        
        // Get payment record
        $payment = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_payments 
            WHERE booking_id = %d AND status = 'completed'
            ORDER BY id DESC LIMIT 1
        ", $booking_id));
        
        if (!$payment) {
            wp_send_json_error(__('Payment record not found', 'waza-booking'));
        }
        
        // Calculate refund amount
        $refund_amount = $this->calculate_refund_amount($booking, $refund_type, $custom_amount);
        
        if ($refund_amount <= 0) {
            wp_send_json_error(__('Invalid refund amount', 'waza-booking'));
        }
        
        // Process refund through payment gateway
        $result = $this->process_gateway_refund($payment, $refund_amount);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        // Update payment record
        $wpdb->update(
            $wpdb->prefix . 'waza_payments',
            [
                'refund_amount' => $refund_amount,
                'refund_status' => 'completed',
                'refund_id' => $result['refund_id'],
                'refund_reason' => $refund_reason,
                'updated_at' => current_time('mysql')
            ],
            ['id' => $payment->id]
        );
        
        // Update booking status
        $wpdb->update(
            $wpdb->prefix . 'waza_bookings',
            [
                'booking_status' => 'refunded',
                'payment_status' => 'refunded',
                'updated_at' => current_time('mysql')
            ],
            ['id' => $booking_id]
        );
        
        // Log activity
        $this->log_refund_activity($booking_id, $refund_amount, $refund_reason);
        
        // Send refund notification
        do_action('waza_refund_processed', $booking_id, $refund_amount);
        
        wp_send_json_success([
            'message' => __('Refund processed successfully', 'waza-booking'),
            'refund_amount' => $refund_amount,
            'refund_id' => $result['refund_id']
        ]);
    }
    
    /**
     * Calculate refund amount based on policy
     */
    public function ajax_calculate_refund() {
        check_ajax_referer('waza_frontend_nonce', 'nonce');
        
        $booking_id = intval($_POST['booking_id'] ?? 0);
        
        if (!$booking_id) {
            wp_send_json_error(__('Invalid booking ID', 'waza-booking'));
        }
        
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_bookings WHERE id = %d
        ", $booking_id));
        
        if (!$booking) {
            wp_send_json_error(__('Booking not found', 'waza-booking'));
        }
        
        $slot = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_slots WHERE id = %d
        ", $booking->slot_id));
        
        $eligibility = $this->check_refund_eligibility($booking, $slot);
        
        wp_send_json_success([
            'eligible' => $eligibility['eligible'],
            'refund_percentage' => $eligibility['refund_percentage'],
            'refund_amount' => $booking->total_amount * ($eligibility['refund_percentage'] / 100),
            'reason' => $eligibility['reason']
        ]);
    }
    
    /**
     * Check refund eligibility
     */
    public function ajax_check_eligibility() {
        check_ajax_referer('waza_frontend_nonce', 'nonce');
        
        $booking_id = intval($_POST['booking_id'] ?? 0);
        
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_bookings WHERE id = %d
        ", $booking_id));
        
        $slot = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_slots WHERE id = %d
        ", $booking->slot_id));
        
        $eligibility = $this->check_refund_eligibility($booking, $slot);
        
        wp_send_json_success($eligibility);
    }
    
    /**
     * Check refund eligibility based on policy
     */
    private function check_refund_eligibility($booking, $slot) {
        $settings = get_option('waza_booking_settings', []);
        
        // Get refund policy settings
        $full_refund_hours = intval($settings['full_refund_hours'] ?? 48);
        $partial_refund_hours = intval($settings['partial_refund_hours'] ?? 24);
        $partial_refund_percentage = intval($settings['partial_refund_percentage'] ?? 50);
        
        // Calculate hours until slot
        $slot_time = strtotime($slot->start_datetime);
        $current_time = time();
        $hours_until_slot = ($slot_time - $current_time) / 3600;
        
        if ($hours_until_slot < 0) {
            return [
                'eligible' => false,
                'refund_percentage' => 0,
                'reason' => __('Activity has already occurred', 'waza-booking')
            ];
        }
        
        if ($hours_until_slot >= $full_refund_hours) {
            return [
                'eligible' => true,
                'refund_percentage' => 100,
                'reason' => sprintf(__('Full refund available (more than %d hours before activity)', 'waza-booking'), $full_refund_hours)
            ];
        }
        
        if ($hours_until_slot >= $partial_refund_hours) {
            return [
                'eligible' => true,
                'refund_percentage' => $partial_refund_percentage,
                'reason' => sprintf(__('%d%% refund available (%d-%d hours before activity)', 'waza-booking'), $partial_refund_percentage, $partial_refund_hours, $full_refund_hours)
            ];
        }
        
        return [
            'eligible' => false,
            'refund_percentage' => 0,
            'reason' => sprintf(__('No refund available (less than %d hours before activity)', 'waza-booking'), $partial_refund_hours)
        ];
    }
    
    /**
     * Calculate refund amount
     */
    private function calculate_refund_amount($booking, $refund_type, $custom_amount = 0) {
        switch ($refund_type) {
            case 'full':
                return $booking->total_amount;
                
            case 'partial':
                global $wpdb;
                $slot = $wpdb->get_row($wpdb->prepare("
                    SELECT * FROM {$wpdb->prefix}waza_slots WHERE id = %d
                ", $booking->slot_id));
                
                $eligibility = $this->check_refund_eligibility($booking, $slot);
                return $booking->total_amount * ($eligibility['refund_percentage'] / 100);
                
            case 'custom':
                return min($custom_amount, $booking->total_amount);
                
            default:
                return 0;
        }
    }
    
    /**
     * Process refund through payment gateway
     */
    private function process_gateway_refund($payment, $refund_amount) {
        $settings = get_option('waza_booking_settings', []);
        
        switch ($payment->payment_gateway) {
            case 'razorpay':
                return $this->process_razorpay_refund($payment, $refund_amount);
                
            case 'stripe':
                return $this->process_stripe_refund($payment, $refund_amount);
                
            default:
                return new \WP_Error('unsupported_gateway', __('Unsupported payment gateway', 'waza-booking'));
        }
    }
    
    /**
     * Process Razorpay refund
     */
    private function process_razorpay_refund($payment, $refund_amount) {
        $settings = get_option('waza_booking_settings', []);
        $key_id = $settings['razorpay_key_id'] ?? '';
        $key_secret = $settings['razorpay_key_secret'] ?? '';
        
        if (!class_exists('Razorpay\Api\Api')) {
            return new \WP_Error('sdk_missing', __('Razorpay SDK not available', 'waza-booking'));
        }
        
        try {
            $api = new \Razorpay\Api\Api($key_id, $key_secret);
            
            $refund = $api->payment->fetch($payment->gateway_payment_id)->refund([
                'amount' => $refund_amount * 100 // Convert to paise
            ]);
            
            return [
                'success' => true,
                'refund_id' => $refund->id
            ];
            
        } catch (\Exception $e) {
            return new \WP_Error('refund_failed', $e->getMessage());
        }
    }
    
    /**
     * Process Stripe refund
     */
    private function process_stripe_refund($payment, $refund_amount) {
        $settings = get_option('waza_booking_settings', []);
        $secret_key = $settings['stripe_secret_key'] ?? '';
        
        if (!class_exists('Stripe\Stripe')) {
            return new \WP_Error('sdk_missing', __('Stripe SDK not available', 'waza-booking'));
        }
        
        try {
            \Stripe\Stripe::setApiKey($secret_key);
            
            $refund = \Stripe\Refund::create([
                'payment_intent' => $payment->gateway_payment_id,
                'amount' => $refund_amount * 100 // Convert to cents
            ]);
            
            return [
                'success' => true,
                'refund_id' => $refund->id
            ];
            
        } catch (\Exception $e) {
            return new \WP_Error('refund_failed', $e->getMessage());
        }
    }
    
    /**
     * Log refund activity
     */
    private function log_refund_activity($booking_id, $refund_amount, $reason) {
        global $wpdb;
        
        $wpdb->insert($wpdb->prefix . 'waza_activity_logs', [
            'user_id' => get_current_user_id(),
            'action_type' => 'refund_processed',
            'object_type' => 'booking',
            'object_id' => $booking_id,
            'description' => sprintf(__('Refund of %s processed', 'waza-booking'), '$' . number_format($refund_amount, 2)),
            'metadata' => json_encode([
                'refund_amount' => $refund_amount,
                'refund_reason' => $reason
            ]),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'created_at' => current_time('mysql')
        ]);
    }
}
