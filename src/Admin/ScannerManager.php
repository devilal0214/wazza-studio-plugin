<?php
/**
 * Scanner Manager
 * 
 * Handles QR code verification and attendance marking
 * 
 * @package WazaBooking\Admin
 */

namespace WazaBooking\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Scanner Manager Class
 */
class ScannerManager {
    
    /**
     * Initialize scanner functionality
     */
    public function __construct() {
        add_action('wp_ajax_waza_verify_scanner_token', [$this, 'ajax_verify_token']);
    }
    
    /**
     * AJAX handler for token verification
     */
    public function ajax_verify_token() {
        check_ajax_referer('waza_scanner_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'waza-booking')]);
        }
        
        $token = sanitize_text_field($_POST['token'] ?? '');
        
        if (empty($token)) {
            wp_send_json_error(['message' => __('No token provided', 'waza-booking')]);
        }
        
        $plugin = \WazaBooking\Core\Plugin::get_instance();
        $qr_manager = $plugin->get_manager('qr');
        
        if (!$qr_manager) {
            wp_send_json_error(['message' => __('QR Manager not found', 'waza-booking')]);
        }
        
        $result = $qr_manager->verify_token($token, 'admin_scanner');
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        // Add more details to the result for the scanner UI
        $result['message'] = __('Check-in successful!', 'waza-booking');
        
        wp_send_json_success($result);
    }
}
