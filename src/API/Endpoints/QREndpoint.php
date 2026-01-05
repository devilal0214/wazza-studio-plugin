<?php
/**
 * QR Endpoint
 * 
 * @package WazaBooking\API\Endpoints
 */

namespace WazaBooking\API\Endpoints;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WazaBooking\Core\Plugin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * QR Endpoint Class
 */
class QREndpoint {
    
    /**
     * Register QR routes
     */
    public function register_routes() {
        // Verify QR token
        register_rest_route('waza/v1', '/qr/verify', [
            'methods' => 'POST',
            'callback' => [$this, 'verify_qr_token'],
            'permission_callback' => [$this, 'check_scanner_permission'],
            'args' => [
                'token' => [
                    'required' => true,
                    'type' => 'string',
                    'description' => 'QR token to verify'
                ],
                'scanner_device' => [
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Scanner device identifier'
                ]
            ]
        ]);
        
        // Get QR token details (for admin)
        register_rest_route('waza/v1', '/qr/(?P<token>[a-zA-Z0-9]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_qr_details'],
            'permission_callback' => 'current_user_can',
            'permission_callback_args' => ['scan_waza_qr']
        ]);
    }
    
    /**
     * Verify QR token and mark attendance
     */
    public function verify_qr_token(WP_REST_Request $request) {
        $token = sanitize_text_field($request->get_param('token'));
        $scanner_device = sanitize_text_field($request->get_param('scanner_device'));
        
        if (!$token) {
            return new WP_Error(
                'missing_token',
                __('QR token is required', 'waza-booking'),
                ['status' => 400]
            );
        }
        
        $qr_manager = Plugin::get_instance()->get_qr_manager();
        $verification_result = $qr_manager->verify_token($token, $scanner_device);
        
        if (is_wp_error($verification_result)) {
            // Log failed scan attempt
            $this->log_scan_attempt($token, $scanner_device, false, $verification_result->get_error_message());
            return $verification_result;
        }
        
        // Log successful scan
        $this->log_scan_attempt($token, $scanner_device, true, 'Successful verification');
        
        return new WP_REST_Response([
            'success' => true,
            'data' => $verification_result,
            'message' => __('QR code verified successfully', 'waza-booking')
        ]);
    }
    
    /**
     * Get QR token details
     */
    public function get_qr_details(WP_REST_Request $request) {
        $token = sanitize_text_field($request->get_param('token'));
        
        global $wpdb;
        
        $qr_data = $wpdb->get_row($wpdb->prepare("
            SELECT qt.*, b.user_name, b.user_email, b.attendees_count, 
                   p.post_title as slot_title
            FROM {$wpdb->prefix}waza_qr_tokens qt
            LEFT JOIN {$wpdb->prefix}waza_bookings b ON qt.booking_id = b.id
            LEFT JOIN {$wpdb->posts} p ON qt.slot_id = p.ID
            WHERE qt.token = %s
        ", $token));
        
        if (!$qr_data) {
            return new WP_Error(
                'token_not_found',
                __('QR token not found', 'waza-booking'),
                ['status' => 404]
            );
        }
        
        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'token' => $qr_data->token,
                'booking_id' => $qr_data->booking_id,
                'slot_title' => $qr_data->slot_title,
                'user_name' => $qr_data->user_name,
                'user_email' => $qr_data->user_email,
                'attendees_count' => $qr_data->attendees_count,
                'token_type' => $qr_data->token_type,
                'max_uses' => $qr_data->max_uses,
                'used_count' => $qr_data->used_count,
                'expires_at' => $qr_data->expires_at,
                'is_active' => (bool) $qr_data->is_active,
                'can_use' => $qr_data->is_active && $qr_data->used_count < $qr_data->max_uses && strtotime($qr_data->expires_at) > time()
            ]
        ]);
    }
    
    /**
     * Check scanner permission
     */
    public function check_scanner_permission() {
        // Check for API key authentication
        $api_key = $this->get_api_key();
        
        if ($api_key && $this->validate_scanner_api_key($api_key)) {
            return true;
        }
        
        // Check for user capability
        return current_user_can('scan_waza_qr');
    }
    
    /**
     * Get API key from request
     */
    private function get_api_key() {
        $headers = getallheaders();
        
        // Check Authorization header
        if (isset($headers['Authorization'])) {
            $auth_header = $headers['Authorization'];
            if (strpos($auth_header, 'Bearer ') === 0) {
                return substr($auth_header, 7);
            }
        }
        
        // Check X-API-Key header
        if (isset($headers['X-API-Key'])) {
            return $headers['X-API-Key'];
        }
        
        // Check query parameter
        if (isset($_GET['api_key'])) {
            return sanitize_text_field($_GET['api_key']);
        }
        
        return null;
    }
    
    /**
     * Validate scanner API key
     */
    private function validate_scanner_api_key($api_key) {
        $valid_keys = get_option('waza_scanner_api_keys', []);
        
        foreach ($valid_keys as $key_data) {
            if (hash_equals($key_data['key'], $api_key)) {
                // Update last used timestamp
                $key_data['last_used'] = time();
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Log scan attempt for audit trail
     */
    private function log_scan_attempt($token, $scanner_device, $success, $message) {
        global $wpdb;
        
        $log_data = [
            'token' => $token,
            'scanner_device' => $scanner_device,
            'success' => $success ? 1 : 0,
            'message' => $message,
            'ip_address' => $this->get_client_ip(),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'scanned_at' => current_time('mysql')
        ];
        
        // Create logs table if it doesn't exist
        $logs_table = $wpdb->prefix . 'waza_scan_logs';
        
        $wpdb->query("CREATE TABLE IF NOT EXISTS {$logs_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            token varchar(255) NOT NULL,
            scanner_device varchar(100) DEFAULT NULL,
            success tinyint(1) NOT NULL DEFAULT 0,
            message text DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            scanned_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY token (token),
            KEY scanned_at (scanned_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $wpdb->insert($logs_table, $log_data);
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                return trim($ips[0]);
            }
        }
        
        return '';
    }
}