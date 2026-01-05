<?php
/**
 * Activity Logger
 * 
 * Handles logging of all important activities in the system
 * 
 * @package WazaBooking\Logs
 */

namespace WazaBooking\Logs;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Activity Logger Class
 */
class ActivityLogger {
    
    /**
     * Initialize activity logging
     */
    public function init() {
        // Hook into various actions to log activity
        add_action('waza_booking_created', [$this, 'log_booking_created'], 10, 1);
        add_action('waza_booking_cancelled', [$this, 'log_booking_cancelled'], 10, 2);
        add_action('waza_booking_rescheduled', [$this, 'log_booking_rescheduled'], 10, 3);
        add_action('waza_refund_processed', [$this, 'log_refund_processed'], 10, 2);
        add_action('waza_qr_scanned', [$this, 'log_qr_scan'], 10, 2);
        add_action('wp_login', [$this, 'log_user_login'], 10, 2);
    }
    
    /**
     * Log booking creation
     */
    public function log_booking_created($booking_id) {
        $this->log_activity('booking_created', 'booking', $booking_id, __('New booking created', 'waza-booking'));
    }
    
    /**
     * Log booking cancellation
     */
    public function log_booking_cancelled($booking_id, $reason) {
        $this->log_activity(
            'booking_cancelled', 
            'booking', 
            $booking_id, 
            __('Booking cancelled', 'waza-booking'),
            ['reason' => $reason]
        );
    }
    
    /**
     * Log booking rescheduled
     */
    public function log_booking_rescheduled($booking_id, $old_slot_id, $new_slot_id) {
        $this->log_activity(
            'booking_rescheduled', 
            'booking', 
            $booking_id, 
            __('Booking rescheduled', 'waza-booking'),
            [
                'old_slot_id' => $old_slot_id,
                'new_slot_id' => $new_slot_id
            ]
        );
    }
    
    /**
     * Log refund processed
     */
    public function log_refund_processed($booking_id, $refund_amount) {
        $this->log_activity(
            'refund_processed', 
            'booking', 
            $booking_id, 
            sprintf(__('Refund of %s processed', 'waza-booking'), '$' . number_format($refund_amount, 2)),
            ['refund_amount' => $refund_amount]
        );
    }
    
    /**
     * Log QR code scan
     */
    public function log_qr_scan($booking_id, $result) {
        $this->log_activity(
            'qr_scanned', 
            'booking', 
            $booking_id, 
            __('QR code scanned', 'waza-booking'),
            ['scan_result' => $result]
        );
    }
    
    /**
     * Log user login
     */
    public function log_user_login($user_login, $user) {
        if (user_can($user, 'edit_waza_slots') || user_can($user, 'manage_waza')) {
            $this->log_activity(
                'user_login', 
                'user', 
                $user->ID, 
                sprintf(__('%s logged in', 'waza-booking'), $user->user_login)
            );
        }
    }
    
    /**
     * Core logging function
     */
    private function log_activity($action_type, $object_type, $object_id, $description, $metadata = []) {
        global $wpdb;
        
        $wpdb->insert($wpdb->prefix . 'waza_activity_logs', [
            'user_id' => get_current_user_id() ?: null,
            'action_type' => sanitize_text_field($action_type),
            'object_type' => sanitize_text_field($object_type),
            'object_id' => intval($object_id),
            'description' => sanitize_text_field($description),
            'metadata' => !empty($metadata) ? json_encode($metadata) : null,
            'ip_address' => $this->get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
            'created_at' => current_time('mysql')
        ]);
    }
    
    /**
     * Get activity logs
     */
    public function get_logs($filters = []) {
        global $wpdb;
        
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['action_type'])) {
            $where[] = 'action_type = %s';
            $params[] = $filters['action_type'];
        }
        
        if (!empty($filters['object_type'])) {
            $where[] = 'object_type = %s';
            $params[] = $filters['object_type'];
        }
        
        if (!empty($filters['object_id'])) {
            $where[] = 'object_id = %d';
            $params[] = intval($filters['object_id']);
        }
        
        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = %d';
            $params[] = intval($filters['user_id']);
        }
        
        if (!empty($filters['start_date'])) {
            $where[] = 'DATE(created_at) >= %s';
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $where[] = 'DATE(created_at) <= %s';
            $params[] = $filters['end_date'];
        }
        
        $limit = isset($filters['limit']) ? intval($filters['limit']) : 100;
        $offset = isset($filters['offset']) ? intval($filters['offset']) : 0;
        
        $where_clause = implode(' AND ', $where);
        
        $query = "
            SELECT l.*, u.display_name as user_name
            FROM {$wpdb->prefix}waza_activity_logs l
            LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
            WHERE {$where_clause}
            ORDER BY l.created_at DESC
            LIMIT %d OFFSET %d
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        
        return $wpdb->get_results($query);
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
    
    /**
     * AJAX: Get activity logs
     */
    public function ajax_get_logs() {
        check_ajax_referer('waza_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_waza')) {
            wp_send_json_error(__('Permission denied', 'waza-booking'));
        }
        
        $filters = [
            'action_type' => isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '',
            'object_type' => isset($_POST['object_type']) ? sanitize_text_field($_POST['object_type']) : '',
            'start_date' => isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '',
            'end_date' => isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '',
            'limit' => isset($_POST['limit']) ? intval($_POST['limit']) : 100,
            'offset' => isset($_POST['offset']) ? intval($_POST['offset']) : 0
        ];
        
        $logs = $this->get_logs($filters);
        
        wp_send_json_success(['logs' => $logs]);
    }
}
