<?php
/**
 * Scanner Manager - QR Code Scanner for Attendance
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
class AttendanceScanner {
    
    /**
     * Initialize scanner functionality
     */
    public function init() {
        // Shortcode for scanner page
        add_shortcode('waza_qr_scanner', [$this, 'scanner_shortcode']);
        
        // AJAX actions
        add_action('wp_ajax_waza_verify_qr', [$this, 'ajax_verify_qr']);
        add_action('wp_ajax_waza_mark_attendance', [$this, 'ajax_mark_attendance']);
        add_action('wp_ajax_waza_get_attendance_stats', [$this, 'ajax_get_attendance_stats']);
        
        // Enqueue scanner scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scanner_scripts']);
    }
    
    /**
     * Enqueue scanner scripts
     */
    public function enqueue_scanner_scripts() {
        if (is_page() && has_shortcode(get_post()->post_content, 'waza_qr_scanner')) {
            wp_enqueue_style('waza-scanner', WAZA_BOOKING_PLUGIN_URL . 'assets/admin/scanner.css', [], WAZA_BOOKING_VERSION);
            wp_enqueue_script('waza-scanner', WAZA_BOOKING_PLUGIN_URL . 'assets/admin/scanner.js', ['jquery'], WAZA_BOOKING_VERSION, true);
            
            // Add HTML5 QR Code Scanner library
            wp_enqueue_script('html5-qrcode', 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js', [], '2.3.8', true);
            
            wp_localize_script('waza-scanner', 'wazaScanner', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('waza_scanner_nonce')
            ]);
        }
    }
    
    /**
     * QR Scanner shortcode
     */
    public function scanner_shortcode($atts) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<div class="waza-error">Please log in to access the QR scanner.</div>';
        }
        
        // Check if user has admin capability (manage_options) OR is an instructor
        $has_access = current_user_can('manage_options');
        
        if (!$has_access) {
            $current_user = wp_get_current_user();
            $has_access = in_array('waza_instructor', (array)$current_user->roles);
        }
        
        if (!$has_access) {
            return '<div class="waza-error">Access denied. Administrator or instructor role required.</div>';
        }

        ob_start();
        ?>
        <div class="waza-scanner-container">
            <div class="waza-scanner-header">
                <h2>📱 Student Attendance Scanner</h2>
                <p>Scan student QR codes to mark attendance</p>
            </div>

            <div class="waza-scanner-tabs">
                <button class="waza-tab-btn active" data-tab="scanner">QR Scanner</button>
                <button class="waza-tab-btn" data-tab="manual">Manual Entry</button>
                <button class="waza-tab-btn" data-tab="stats">Today's Stats</button>
            </div>

            <!-- Scanner Tab -->
            <div class="waza-tab-content active" data-tab="scanner">
                <div class="waza-qr-reader-container">
                    <div id="qr-reader"></div>
                    <div id="qr-reader-status">
                        <p>Position the QR code within the frame</p>
                    </div>
                </div>
            </div>

            <!-- Manual Entry Tab -->
            <div class="waza-tab-content" data-tab="manual">
                <div class="waza-manual-entry">
                    <h3>Enter Booking Code</h3>
                    <div class="waza-input-group">
                        <input type="text" id="manual-token" placeholder="Enter booking code (e.g., WB-00044) or booking ID" />
                        <button id="verify-manual-btn" class="waza-btn waza-btn-primary">Verify</button>
                    </div>
                </div>
                
                <!-- Booking Details Display -->
                <div id="booking-details-container" style="display:none; margin-top: 20px;"></div>
            </div>

            <!-- Stats Tab -->
            <div class="waza-tab-content" data-tab="stats">
                <div id="attendance-stats">
                    <p class="loading">Loading statistics...</p>
                </div>
            </div>

            <!-- Student Details Modal -->
            <div id="student-details-modal" class="waza-modal" style="display:none;">
                <div class="waza-modal-overlay"></div>
                <div class="waza-modal-dialog waza-modal-lg">
                    <div class="waza-modal-content">
                        <div class="waza-modal-header">
                            <h3>Student Details</h3>
                            <button type="button" class="waza-modal-close">&times;</button>
                        </div>
                        <div class="waza-modal-body" id="student-info-container">
                            <!-- Populated by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX verify QR code
     */
    public function ajax_verify_qr() {
        // Log the AJAX request
        error_log('=== AJAX VERIFY QR CALLED ===');
        error_log('User ID: ' . get_current_user_id());
        error_log('Is user logged in: ' . (is_user_logged_in() ? 'YES' : 'NO'));
        error_log('POST data: ' . print_r($_POST, true));
        
        // Check nonce - don't die on failure, just log it
        $nonce_check = check_ajax_referer('waza_scanner_nonce', 'nonce', false);
        error_log('Nonce check result: ' . ($nonce_check ? 'PASSED' : 'FAILED'));
        
        if (!$nonce_check) {
            error_log('Nonce verification FAILED - sent nonce: ' . ($_POST['nonce'] ?? 'none'));
            wp_send_json_error('Security check failed. Please refresh the page and try again.', 403);
            return;
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            $current_user = wp_get_current_user();
            if (!in_array('waza_instructor', (array)$current_user->roles)) {
                error_log('Permission denied - not admin or instructor');
                wp_send_json_error('Permission denied', 403);
                return;
            }
        }
        
        $qr_data = sanitize_text_field($_POST['qr_data'] ?? '');
        error_log('QR Data: ' . $qr_data);
        
        if (empty($qr_data)) {
            error_log('Empty QR data');
            wp_send_json_error('Invalid QR code data');
            return;
        }
        
        // Try to parse as JSON (comprehensive QR with booking data)
        $parsed_data = json_decode($qr_data, true);
        if ($parsed_data && isset($parsed_data['booking_id'])) {
            $booking_id = intval($parsed_data['booking_id']);
            $this->handle_booking_scan($booking_id);
        } else {
            // Extract booking ID or rental ID from code like "WB-00044" or "WR-00044"
            if (preg_match('/WB-(\d+)/', $qr_data, $matches)) {
                $booking_id = intval($matches[1]);
                $this->handle_booking_scan($booking_id);
            } elseif (preg_match('/WR-(\d+)/', $qr_data, $matches)) {
                $rental_id = intval($matches[1]);
                $this->handle_rental_scan($rental_id);
            } else {
                $booking_id = intval($qr_data);
                $this->handle_booking_scan($booking_id);
            }
        }
    }
    
    /**
     * Handle booking scan
     */
    private function handle_booking_scan($booking_id) {
        error_log('Parsed booking ID: ' . $booking_id);
        
        global $wpdb;
        
        // Get booking details - search by ID only (booking_code is generated, not stored)
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT b.*, 
                   s.start_datetime, 
                   s.end_datetime,
                   s.capacity,
                   p.post_title as activity_title,
                   p.ID as activity_id
            FROM {$wpdb->prefix}waza_bookings b
            LEFT JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
            LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
            WHERE b.id = %d
            LIMIT 1
        ", $booking_id));
        
        if (!$booking) {
            wp_send_json_error('Booking not found. Invalid QR code.');
        }
        
        // Check if booking is confirmed
        if ($booking->booking_status !== 'confirmed') {
            wp_send_json_error('Booking is not confirmed. Status: ' . $booking->booking_status);
        }
        
        // Check slot date
        $slot_date = date('Y-m-d', strtotime($booking->start_datetime));
        $today = current_time('Y-m-d');
        
        // Check if it's within allowed time for attendance marking
        // Database times are already in Asia/Kolkata timezone
        $ist_timezone = new \DateTimeZone('Asia/Kolkata');
        
        $slot_start_datetime = new \DateTime($booking->start_datetime, $ist_timezone);
        $slot_start = $slot_start_datetime->getTimestamp();
        
        $slot_end_datetime = new \DateTime($booking->end_datetime, $ist_timezone);
        $slot_end = $slot_end_datetime->getTimestamp();
        
        $current_datetime = new \DateTime('now', $ist_timezone);
        $current_time = $current_datetime->getTimestamp();
        
        $time_until_slot = $slot_start - $current_time;
        
        // Determine if attendance can be marked
        $time_message = '';
        $can_mark_attendance = false;
        
        if ($slot_date !== $today) {
            // Not today - show appropriate message
            if ($slot_date > $today) {
                $time_message = 'This booking is for ' . date_i18n('F j, Y', strtotime($slot_date)) . ' (future date).';
            } else {
                $time_message = 'This booking was for ' . date_i18n('F j, Y', strtotime($slot_date)) . ' (past date).';
            }
        } elseif ($time_until_slot > 1800) {
            // Today but too early
            $minutes_until = round($time_until_slot / 60);
            $time_message = "Too early. Attendance can be marked 30 minutes before slot starts (in {$minutes_until} minutes).";
        } elseif ($current_time > ($slot_end + 3600)) {
            // Today but too late
            $time_message = 'Too late. Slot ended more than 1 hour ago.';
        } else {
            // Within the allowed time window
            $can_mark_attendance = true;
        }
        
        error_log('=== TIME CHECK (Asia/Kolkata) ===');
        error_log('DB Start datetime: ' . $booking->start_datetime);
        error_log('DB End datetime: ' . $booking->end_datetime);
        error_log('Slot date: ' . $slot_date . ' | Today: ' . $today);
        error_log('Slot start (IST): ' . $slot_start_datetime->format('Y-m-d H:i:s'));
        error_log('Slot end (IST): ' . $slot_end_datetime->format('Y-m-d H:i:s'));
        error_log('Current time (IST): ' . $current_datetime->format('Y-m-d H:i:s'));
        error_log('Time until slot: ' . $time_until_slot . ' seconds (' . round($time_until_slot/60) . ' minutes)');
        error_log('Can mark attendance: ' . ($can_mark_attendance ? 'YES' : 'NO'));
        error_log('Time message: ' . $time_message);
        
        // Get attendance record
        $attendance = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_attendance
            WHERE booking_id = %d AND DATE(check_in_time) = %s
            LIMIT 1
        ", $booking->id, $today));
        
        // Get user details
        $user = $booking->user_id ? get_userdata($booking->user_id) : null;
        
        // Generate booking code
        $booking->booking_code = 'WB-' . str_pad($booking->id, 5, '0', STR_PAD_LEFT);
        
        wp_send_json_success([
            'booking' => $booking,
            'user' => $user ? [
                'display_name' => $user->display_name,
                'email' => $user->user_email,
                'phone' => get_user_meta($user->ID, 'phone', true)
            ] : [
                'display_name' => $booking->user_name,
                'email' => $booking->user_email,
                'phone' => $booking->user_phone
            ],
            'attendance' => $attendance,
            'has_checked_in' => !empty($attendance->check_in_time),
            'check_in_time' => $attendance ? $attendance->check_in_time : null,
            'can_mark_attendance' => $can_mark_attendance,
            'time_message' => $time_message,
            'slot_start_time' => date('g:i A', $slot_start),
            'minutes_until_slot' => round($time_until_slot / 60),
            'type' => 'booking'
        ]);
    }
    
    /**
     * Handle rental scan
     */
    private function handle_rental_scan($rental_id) {
        error_log('Parsed rental ID: ' . $rental_id);
        
        global $wpdb;
        
        // Get rental details
        $rental = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_rentals
            WHERE id = %d
            LIMIT 1
        ", $rental_id));
        
        if (!$rental) {
            wp_send_json_error('Rental not found. Invalid QR code.');
        }
        
        // Check if rental is confirmed
        if ($rental->booking_status !== 'confirmed') {
            wp_send_json_error('Rental is not confirmed. Status: ' . $rental->booking_status);
        }
        
        // Check rental date and time (using Asia/Kolkata timezone)
        $rental_date = $rental->rental_date;
        $today = current_time('Y-m-d');
        
        $ist_timezone = new \DateTimeZone('Asia/Kolkata');
        
        $rental_start_datetime = new \DateTime($rental->rental_date . ' ' . $rental->start_time, $ist_timezone);
        $rental_start = $rental_start_datetime->getTimestamp();
        
        $rental_end_datetime = new \DateTime($rental->rental_date . ' ' . $rental->end_time, $ist_timezone);
        $rental_end = $rental_end_datetime->getTimestamp();
        
        $current_datetime = new \DateTime('now', $ist_timezone);
        $current_time = $current_datetime->getTimestamp();
        
        $time_until_rental = $rental_start - $current_time;
        
        // Determine if access can be granted
        $time_message = '';
        $can_mark_attendance = false;
        
        if ($rental_date !== $today) {
            if ($rental_date > $today) {
                $time_message = 'This rental is for ' . date_i18n('F j, Y', strtotime($rental_date)) . ' (future date).';
            } else {
                $time_message = 'This rental was for ' . date_i18n('F j, Y', strtotime($rental_date)) . ' (past date).';
            }
        } elseif ($time_until_rental > 1800) {
            $minutes_until = round($time_until_rental / 60);
            $time_message = "Too early. Access can be granted 30 minutes before rental starts (in {$minutes_until} minutes).";
        } elseif ($current_time > ($rental_end + 3600)) {
            $time_message = 'Too late. Rental ended more than 1 hour ago.';
        } else {
            $can_mark_attendance = true;
        }
        
        // Check attendance (rentals don't use waza_attendance table)
        // Attendance tracking is only for slot bookings, not rentals
        $attendance = null;
        
        // Generate rental code
        $rental->rental_code = 'WR-' . str_pad($rental->id, 5, '0', STR_PAD_LEFT);
        
        wp_send_json_success([
            'rental' => $rental,
            'booking' => (object)[
                'id' => $rental->id,
                'booking_code' => $rental->rental_code,
                'customer_name' => $rental->customer_name,
                'customer_email' => $rental->customer_email,
                'customer_phone' => $rental->customer_phone,
                'booking_status' => $rental->booking_status,
                'payment_status' => $rental->payment_status,
                'activity_title' => 'Studio Rental - ' . ucwords(str_replace('_', ' ', $rental->rental_type)),
                'start_datetime' => $rental->rental_date . ' ' . $rental->start_time,
                'end_datetime' => $rental->rental_date . ' ' . $rental->end_time
            ],
            'user' => [
                'display_name' => $rental->customer_name,
                'email' => $rental->customer_email,
                'phone' => $rental->customer_phone
            ],
            'attendance' => $attendance,
            'has_checked_in' => !empty($attendance->check_in_time ?? null),
            'check_in_time' => $attendance ? $attendance->check_in_time : null,
            'can_mark_attendance' => $can_mark_attendance,
            'time_message' => $time_message,
            'slot_start_time' => date('g:i A', $rental_start),
            'minutes_until_slot' => round($time_until_rental / 60),
            'type' => 'rental'
        ]);
    }
    
    /**
     * AJAX mark attendance (entry/exit)
     */
    public function ajax_mark_attendance() {
        // Log the request
        error_log('=== AJAX MARK ATTENDANCE CALLED ===');
        
        // Check nonce
        if (!check_ajax_referer('waza_scanner_nonce', 'nonce', false)) {
            error_log('Nonce verification failed');
            wp_send_json_error('Security check failed. Please refresh the page.', 403);
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            $current_user = wp_get_current_user();
            if (!in_array('waza_instructor', (array)$current_user->roles)) {
                error_log('Permission denied');
                wp_send_json_error('Permission denied', 403);
            }
        }
        
        $booking_id = intval($_POST['booking_id'] ?? 0);
        $rental_id = intval($_POST['rental_id'] ?? 0);
        $action_type = sanitize_text_field($_POST['action_type'] ?? ''); // 'entry' or 'exit'
        $method = sanitize_text_field($_POST['method'] ?? 'qr');
        
        if ((!$booking_id && !$rental_id) || !in_array($action_type, ['entry', 'exit'])) {
            wp_send_json_error('Invalid request parameters');
        }
        
        global $wpdb;
        
        // Handle rental or booking
        if ($rental_id) {
            $rental = $wpdb->get_row($wpdb->prepare("
                SELECT * FROM {$wpdb->prefix}waza_rentals WHERE id = %d
            ", $rental_id));
            
            if (!$rental) {
                wp_send_json_error('Rental not found');
            }
            
            $customer_name = $rental->customer_name;
            $record_id = $rental_id;
            $table_field = 'rental_id';
        } else {
            $booking = $wpdb->get_row($wpdb->prepare("
                SELECT * FROM {$wpdb->prefix}waza_bookings WHERE id = %d
            ", $booking_id));
            
            if (!$booking) {
                wp_send_json_error('Booking not found');
            }
            
            $customer_name = $booking->user_name ?? $booking->customer_name;
            $record_id = $booking_id;
            $table_field = 'booking_id';
        }
        
        $today = current_time('Y-m-d');
        
        // Get existing attendance
        $attendance = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_attendance
            WHERE {$table_field} = %d AND DATE(check_in_time) = %s
        ", $record_id, $today));
        
        if ($action_type === 'entry') {
            // Mark entry
            if ($attendance && $attendance->check_in_time) {
                wp_send_json_error('Already checked in at ' . 
                    date_i18n('g:i A', strtotime($attendance->check_in_time)));
            }
            
            $data = [
                $table_field => $record_id,
                'user_id' => $rental ? ($rental->user_id ?: 0) : ($booking->user_id ?: 0),
                'check_in_time' => current_time('mysql'),
                'scanner_user_id' => get_current_user_id()
            ];
            
            if ($booking_id) {
                $data['slot_id'] = $booking->slot_id;
            }
            
            $result = $wpdb->insert(
                $wpdb->prefix . 'waza_attendance',
                $data
            );
            
            if ($result) {
                // Update booking/rental attended status
                if ($rental_id) {
                    $wpdb->update(
                        $wpdb->prefix . 'waza_rentals',
                        ['booking_status' => 'active'],
                        ['id' => $rental_id]
                    );
                } else {
                    $wpdb->update(
                        $wpdb->prefix . 'waza_bookings',
                        ['attended' => 1, 'attended_at' => current_time('mysql')],
                        ['id' => $booking_id]
                    );
                }
                
                wp_send_json_success([
                    'message' => '✅ Entry marked successfully!',
                    'time' => current_time('g:i A'),
                    'student' => $customer_name
                ]);
            } else {
                wp_send_json_error('Failed to mark entry: ' . $wpdb->last_error);
            }
            
        } else if ($action_type === 'exit') {
            // Mark exit
            if (!$attendance || !$attendance->check_in_time) {
                wp_send_json_error('No entry found. Please mark entry first.');
            }
            
            if ($attendance->check_out_time) {
                wp_send_json_error('Already checked out at ' . 
                    date_i18n('g:i A', strtotime($attendance->check_out_time)));
            }
            
            $result = $wpdb->update(
                $wpdb->prefix . 'waza_attendance',
                [
                    'check_out_time' => current_time('mysql'),
                    'exit_method' => $method
                ],
                ['id' => $attendance->id]
            );
            
            if ($result !== false) {
                $duration = human_time_diff(
                    strtotime($attendance->check_in_time),
                    current_time('timestamp')
                );
                
                wp_send_json_success([
                    'message' => '✅ Exit marked successfully!',
                    'time' => current_time('g:i A'),
                    'duration' => $duration,
                    'student' => $booking->user_name
                ]);
            } else {
                wp_send_json_error('Failed to mark exit');
            }
        }
    }
    
    /**
     * AJAX get attendance statistics for today
     */
    public function ajax_get_attendance_stats() {
        check_ajax_referer('waza_scanner_nonce', 'nonce');
        
        global $wpdb;
        $today = current_time('Y-m-d');
        
        // Get today's slots
        $today_slots = $wpdb->get_results($wpdb->prepare("
            SELECT s.*, p.post_title as activity_title,
                   COUNT(b.id) as total_bookings,
                   SUM(b.attendees_count) as total_attendees,
                   COUNT(a.id) as checked_in
            FROM {$wpdb->prefix}waza_slots s
            LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
            LEFT JOIN {$wpdb->prefix}waza_bookings b ON s.id = b.slot_id AND b.booking_status = 'confirmed'
            LEFT JOIN {$wpdb->prefix}waza_attendance a ON b.id = a.booking_id AND DATE(a.check_in_time) = %s
            WHERE DATE(s.start_datetime) = %s
            GROUP BY s.id
            ORDER BY s.start_datetime ASC
        ", $today, $today));
        
        // Overall stats
        $total_checked_in = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}waza_attendance
            WHERE DATE(check_in_time) = %s
        ", $today));
        
        $total_checked_out = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}waza_attendance
            WHERE DATE(check_in_time) = %s AND check_out_time IS NOT NULL
        ", $today));
        
        wp_send_json_success([
            'slots' => $today_slots,
            'stats' => [
                'total_checked_in' => $total_checked_in,
                'total_checked_out' => $total_checked_out,
                'currently_active' => $total_checked_in - $total_checked_out
            ]
        ]);
    }
}
