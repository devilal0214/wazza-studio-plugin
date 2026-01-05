<?php
/**
 * CSV Export Manager
 * 
 * Handles CSV exports for attendance lists and reports
 * 
 * @package WazaBooking\Export
 */

namespace WazaBooking\Export;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * CSV Export Manager Class
 */
class CSVExportManager {
    
    /**
     * Initialize export functionality
     */
    public function init() {
        add_action('admin_post_waza_export_attendance', [$this, 'export_attendance']);
        add_action('admin_post_waza_export_bookings', [$this, 'export_bookings']);
        add_action('admin_post_waza_export_slot_roster', [$this, 'export_slot_roster']);
        add_action('wp_ajax_waza_generate_export', [$this, 'ajax_generate_export']);
    }
    
    /**
     * Export attendance records
     */
    public function export_attendance() {
        if (!current_user_can('manage_waza')) {
            wp_die(__('Permission denied', 'waza-booking'));
        }
        
        check_admin_referer('waza_export_attendance');
        
        $slot_id = isset($_GET['slot_id']) ? intval($_GET['slot_id']) : 0;
        $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
        $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';
        
        global $wpdb;
        
        $query = "
            SELECT 
                a.id,
                a.check_in_time,
                b.user_name,
                b.user_email,
                b.user_phone,
                b.attendees_count,
                s.start_datetime,
                p.post_title as activity_name,
                a.scanner_device,
                u.display_name as scanner_name
            FROM {$wpdb->prefix}waza_attendance a
            JOIN {$wpdb->prefix}waza_bookings b ON a.booking_id = b.id
            JOIN {$wpdb->prefix}waza_slots s ON a.slot_id = s.id
            LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
            LEFT JOIN {$wpdb->users} u ON a.scanner_user_id = u.ID
            WHERE 1=1
        ";
        
        $params = [];
        
        if ($slot_id) {
            $query .= " AND a.slot_id = %d";
            $params[] = $slot_id;
        }
        
        if ($start_date) {
            $query .= " AND DATE(a.check_in_time) >= %s";
            $params[] = $start_date;
        }
        
        if ($end_date) {
            $query .= " AND DATE(a.check_in_time) <= %s";
            $params[] = $end_date;
        }
        
        $query .= " ORDER BY a.check_in_time DESC";
        
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        $filename = 'attendance-' . date('Y-m-d-H-i-s') . '.csv';
        
        $this->output_csv($results, $filename, [
            'Check-in Time',
            'User Name',
            'Email',
            'Phone',
            'Participants',
            'Slot Date/Time',
            'Activity',
            'Scanner Device',
            'Scanned By'
        ]);
    }
    
    /**
     * Export slot roster
     */
    public function export_slot_roster() {
        if (!current_user_can('edit_waza_slots')) {
            wp_die(__('Permission denied', 'waza-booking'));
        }
        
        check_admin_referer('waza_export_roster');
        
        $slot_id = isset($_GET['slot_id']) ? intval($_GET['slot_id']) : 0;
        
        if (!$slot_id) {
            wp_die(__('Slot ID required', 'waza-booking'));
        }
        
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                b.id as booking_id,
                CONCAT('WB-', LPAD(b.id, 5, '0')) as booking_number,
                b.user_name,
                b.user_email,
                b.user_phone,
                b.attendees_count,
                b.booking_type,
                b.payment_status,
                b.booking_status,
                b.attended,
                b.attended_at,
                b.created_at,
                b.qr_token
            FROM {$wpdb->prefix}waza_bookings b
            WHERE b.slot_id = %d AND b.booking_status != 'cancelled'
            ORDER BY b.created_at ASC
        ", $slot_id), ARRAY_A);
        
        $slot = $wpdb->get_row($wpdb->prepare("
            SELECT s.*, p.post_title as activity_name
            FROM {$wpdb->prefix}waza_slots s
            LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
            WHERE s.id = %d
        ", $slot_id));
        
        $filename = 'roster-' . sanitize_title($slot->activity_name) . '-' . date('Y-m-d', strtotime($slot->start_datetime)) . '.csv';
        
        $this->output_csv($results, $filename, [
            'Booking ID',
            'Booking Number',
            'Name',
            'Email',
            'Phone',
            'Participants',
            'Type',
            'Payment Status',
            'Booking Status',
            'Attended',
            'Check-in Time',
            'Booked At',
            'QR Token'
        ]);
    }
    
    /**
     * Export bookings
     */
    public function export_bookings() {
        if (!current_user_can('manage_waza')) {
            wp_die(__('Permission denied', 'waza-booking'));
        }
        
        check_admin_referer('waza_export_bookings');
        
        $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
        $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        
        global $wpdb;
        
        $query = "
            SELECT 
                b.id,
                CONCAT('WB-', LPAD(b.id, 5, '0')) as booking_number,
                b.user_name,
                b.user_email,
                b.user_phone,
                b.attendees_count,
                b.total_amount,
                b.discount_amount,
                b.coupon_code,
                b.payment_status,
                b.payment_method,
                b.booking_status,
                b.booking_type,
                b.attended,
                b.created_at,
                s.start_datetime,
                p.post_title as activity_name
            FROM {$wpdb->prefix}waza_bookings b
            JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
            LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
            WHERE 1=1
        ";
        
        $params = [];
        
        if ($start_date) {
            $query .= " AND DATE(b.created_at) >= %s";
            $params[] = $start_date;
        }
        
        if ($end_date) {
            $query .= " AND DATE(b.created_at) <= %s";
            $params[] = $end_date;
        }
        
        if ($status) {
            $query .= " AND b.booking_status = %s";
            $params[] = $status;
        }
        
        $query .= " ORDER BY b.created_at DESC";
        
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        $filename = 'bookings-' . date('Y-m-d-H-i-s') . '.csv';
        
        $this->output_csv($results, $filename, [
            'ID',
            'Booking Number',
            'Name',
            'Email',
            'Phone',
            'Participants',
            'Amount',
            'Discount',
            'Coupon',
            'Payment Status',
            'Payment Method',
            'Booking Status',
            'Type',
            'Attended',
            'Booked At',
            'Slot Date/Time',
            'Activity'
        ]);
    }
    
    /**
     * AJAX: Generate export
     */
    public function ajax_generate_export() {
        check_ajax_referer('waza_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_waza')) {
            wp_send_json_error(__('Permission denied', 'waza-booking'));
        }
        
        $export_type = sanitize_text_field($_POST['export_type'] ?? '');
        $filters = $_POST['filters'] ?? [];
        
        $export_url = admin_url('admin-post.php?action=waza_export_' . $export_type);
        $export_url = add_query_arg($filters, $export_url);
        $export_url = wp_nonce_url($export_url, 'waza_export_' . $export_type);
        
        wp_send_json_success(['export_url' => $export_url]);
    }
    
    /**
     * Output CSV file
     */
    private function output_csv($data, $filename, $headers = []) {
        // Set headers for download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel compatibility with UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write headers
        if (!empty($headers)) {
            fputcsv($output, $headers);
        } elseif (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
        }
        
        // Write data
        foreach ($data as $row) {
            fputcsv($output, array_values($row));
        }
        
        fclose($output);
        exit;
    }
}
