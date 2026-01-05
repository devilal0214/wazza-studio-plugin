<?php
/**
 * Security Manager
 * 
 * @package WazaBooking\Security
 */

namespace WazaBooking\Security;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Security Manager Class
 * Handles input sanitization, rate limiting, and security measures
 */
class SecurityManager {
    
    /**
     * Initialize security measures
     */
    public function init() {
        add_filter('rest_pre_dispatch', [$this, 'rate_limit_api_requests'], 10, 3);
        add_action('wp_login_failed', [$this, 'log_failed_login']);
        add_action('init', [$this, 'setup_security_headers']);
    }
    
    /**
     * Rate limit API requests
     * 
     * @param mixed $result
     * @param WP_REST_Server $server
     * @param WP_REST_Request $request
     * @return mixed
     */
    public function rate_limit_api_requests($result, $server, $request) {
        $route = $request->get_route();
        
        // Apply rate limiting to QR verification endpoint
        if (strpos($route, '/waza/v1/qr/verify') === 0) {
            $ip = $this->get_client_ip();
            $limit_key = 'waza_qr_rate_limit_' . md5($ip);
            
            $attempts = get_transient($limit_key) ?: 0;
            
            if ($attempts >= 60) { // 60 requests per minute
                return new \WP_Error(
                    'rate_limit_exceeded',
                    __('Rate limit exceeded. Please try again later.', 'waza-booking'),
                    ['status' => 429]
                );
            }
            
            set_transient($limit_key, $attempts + 1, 60); // 1 minute window
        }
        
        return $result;
    }
    
    /**
     * Log failed login attempts
     * 
     * @param string $username
     */
    public function log_failed_login($username) {
        $ip = $this->get_client_ip();
        error_log(sprintf('Failed login attempt for user %s from IP %s', $username, $ip));
    }
    
    /**
     * Setup security headers
     */
    public function setup_security_headers() {
        if (!headers_sent()) {
            header('X-Frame-Options: SAMEORIGIN');
            header('X-Content-Type-Options: nosniff');
            header('X-XSS-Protection: 1; mode=block');
        }
    }
    
    /**
     * Sanitize booking data
     * 
     * @param array $data
     * @return array
     */
    public function sanitize_booking_data($data) {
        $sanitized = [];
        
        $sanitized['user_name'] = sanitize_text_field($data['user_name'] ?? '');
        $sanitized['user_email'] = sanitize_email($data['user_email'] ?? '');
        $sanitized['user_phone'] = sanitize_text_field($data['user_phone'] ?? '');
        $sanitized['attendees_count'] = (int) ($data['attendees_count'] ?? 1);
        $sanitized['coupon_code'] = sanitize_text_field($data['coupon_code'] ?? '');
        $sanitized['special_requests'] = sanitize_textarea_field($data['special_requests'] ?? '');
        
        return $sanitized;
    }
    
    /**
     * Validate booking permissions
     * 
     * @param int $booking_id
     * @param int $user_id
     * @return bool
     */
    public function validate_booking_permission($booking_id, $user_id = null) {
        if (current_user_can('manage_waza')) {
            return true;
        }
        
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT user_id, user_email FROM {$wpdb->prefix}waza_bookings 
            WHERE id = %d
        ", $booking_id));
        
        if (!$booking) {
            return false;
        }
        
        // Check if user owns the booking
        if ($booking->user_id && $booking->user_id == $user_id) {
            return true;
        }
        
        // Check by email if logged in
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            return $booking->user_email === $user->user_email;
        }
        
        return false;
    }
    
    /**
     * Get client IP address
     * 
     * @return string
     */
    private function get_client_ip() {
        $ip_keys = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP', 
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                return trim($ips[0]);
            }
        }
        
        return '';
    }
    
    /**
     * Generate secure API key
     * 
     * @return string
     */
    public function generate_api_key() {
        return 'waza_' . bin2hex(random_bytes(16));
    }
    
    /**
     * Hash sensitive data
     * 
     * @param string $data
     * @return string
     */
    public function hash_data($data) {
        return hash('sha256', $data . wp_salt());
    }
}