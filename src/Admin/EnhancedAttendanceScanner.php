<?php
/**
 * Enhanced Admin Attendance Scanner
 * 
 * Features:
 * - QR code scanning with validation
 * - Manual booking ID search
 * - Payment status verification
 * - Slot timing validation (past/current/future)
 * - Group booking support (X of Y attended)
 * - Detailed error messages
 * 
 * @package WazaBooking\Admin
 */

namespace WazaBooking\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enhanced Attendance Scanner Class
 */
class EnhancedAttendanceScanner {
    
    /**
     * Initialize scanner functionality
     */
    public function init() {
        // Admin menu page
        add_action('admin_menu', [$this, 'add_scanner_page'], 25);
        
        // AJAX actions
        add_action('wp_ajax_waza_scan_qr_code', [$this, 'ajax_scan_qr_code']);
        add_action('wp_ajax_waza_search_booking', [$this, 'ajax_search_booking']);
        add_action('wp_ajax_waza_mark_single_attendance', [$this, 'ajax_mark_single_attendance']);
        add_action('wp_ajax_waza_get_today_stats', [$this, 'ajax_get_today_stats']);
        
        // Enqueue scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scanner_assets']);
    }
    
    /**
     * Add scanner page to admin menu
     */
    public function add_scanner_page() {
        add_submenu_page(
            'waza-booking',
            __('Attendance Scanner', 'waza-booking'),
            __('📱 Scanner', 'waza-booking'),
            'manage_options',
            'waza-attendance-scanner',
            [$this, 'render_scanner_page']
        );
    }
    
    /**
     * Enqueue scanner assets
     */
    public function enqueue_scanner_assets($hook) {
        // Check if we're on the attendance scanner page
        // Hook format: {parent-slug}_page_{page-slug}
        // Parent slug is 'waza-booking', page slug is 'waza-attendance-scanner'
        if ($hook !== 'waza-booking_page_waza-attendance-scanner') {
            return;
        }
        
        wp_enqueue_style('waza-scanner-enhanced', WAZA_BOOKING_PLUGIN_URL . 'assets/admin/scanner-enhanced.css', [], WAZA_BOOKING_VERSION);
        wp_enqueue_script('waza-scanner-enhanced', WAZA_BOOKING_PLUGIN_URL . 'assets/admin/scanner-enhanced.js', ['jquery'], WAZA_BOOKING_VERSION, true);
        
        // HTML5 QR Code Scanner library
        wp_enqueue_script('html5-qrcode', 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js', [], '2.3.8', false);
        
        wp_localize_script('waza-scanner-enhanced', 'wazaScannerData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('waza_scanner_nonce'),
            'current_user' => get_current_user_id(),
            'scanner_name' => wp_get_current_user()->display_name
        ]);
    }
    
    /**
     * Render scanner page
     */
    public function render_scanner_page() {
        ?>
        <div class="wrap waza-scanner-wrap">
            <h1 class="wp-heading-inline">
                <span class="dashicons dashicons-smartphone" style="font-size:28px;"></span>
                <?php esc_html_e('Attendance Scanner', 'waza-booking'); ?>
            </h1>
            <p class="description"><?php esc_html_e('Scan student QR codes or search bookings to mark attendance', 'waza-booking'); ?></p>
            
            <hr class="wp-header-end">
            
            <!-- Today's Stats -->
            <div class="waza-scanner-stats">
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <h3 id="total-checked-in">0</h3>
                        <p><?php esc_html_e('Checked In Today', 'waza-booking'); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <h3 id="total-expected">0</h3>
                        <p><?php esc_html_e('Expected Today', 'waza-booking'); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-info">
                        <h3 id="attendance-rate">0%</h3>
                        <p><?php esc_html_e('Attendance Rate', 'waza-booking'); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🎯</div>
                    <div class="stat-info">
                        <h3 id="active-slots">0</h3>
                        <p><?php esc_html_e('Active Slots', 'waza-booking'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Scanner Tabs -->
            <div class="waza-scanner-tabs">
                <button class="waza-tab-btn active" data-tab="qr-scanner">
                    <span class="dashicons dashicons-camera"></span>
                    <?php esc_html_e('QR Scanner', 'waza-booking'); ?>
                </button>
                <button class="waza-tab-btn" data-tab="manual-search">
                    <span class="dashicons dashicons-search"></span>
                    <?php esc_html_e('Manual Search', 'waza-booking'); ?>
                </button>
                <button class="waza-tab-btn" data-tab="recent-scans">
                    <span class="dashicons dashicons-list-view"></span>
                    <?php esc_html_e('Recent Scans', 'waza-booking'); ?>
                </button>
            </div>
            
            <!-- Tab Content -->
            <div class="waza-scanner-content">
                
                <!-- QR Scanner Tab -->
                <div class="waza-tab-content active" data-tab="qr-scanner">
                    <div class="scanner-container">
                        <div class="scanner-viewport">
                            <div id="qr-reader"></div>
                            <div id="qr-reader-status">
                                <p><?php esc_html_e('Position the QR code within the frame', 'waza-booking'); ?></p>
                            </div>
                        </div>
                        <div class="scanner-controls">
                            <button id="start-scanner" class="button button-primary button-large">
                                <span class="dashicons dashicons-camera"></span>
                                <?php esc_html_e('Start Scanner', 'waza-booking'); ?>
                            </button>
                            <button id="stop-scanner" class="button button-large" style="display:none;">
                                <span class="dashicons dashicons-no"></span>
                                <?php esc_html_e('Stop Scanner', 'waza-booking'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Manual Search Tab -->
                <div class="waza-tab-content" data-tab="manual-search">
                    <div class="manual-search-container">
                        <h2><?php esc_html_e('Search by Booking ID or QR Token', 'waza-booking'); ?></h2>
                        <p><?php esc_html_e('If the QR code is not working, you can manually enter the booking ID', 'waza-booking'); ?></p>
                        
                        <div class="search-form">
                            <input 
                                type="text" 
                                id="booking-search-input" 
                                class="large-text" 
                                placeholder="<?php esc_attr_e('Enter Booking ID or QR Token...', 'waza-booking'); ?>"
                            />
                            <button id="search-booking-btn" class="button button-primary button-large">
                                <span class="dashicons dashicons-search"></span>
                                <?php esc_html_e('Search', 'waza-booking'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Scans Tab -->
                <div class="waza-tab-content" data-tab="recent-scans">
                    <div id="recent-scans-list">
                        <p class="loading"><?php esc_html_e('Loading recent scans...', 'waza-booking'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Result Display Area -->
            <div id="scan-result-area" style="display:none;"></div>
        </div>
        <?php
    }
    
    /**
     * AJAX: Scan QR code
     */
    public function ajax_scan_qr_code() {
        check_ajax_referer('waza_scanner_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => '❌ Permission denied',
                'type' => 'permission_denied'
            ]);
        }
        
        $qr_data = sanitize_text_field($_POST['qr_data'] ?? '');
        
        if (empty($qr_data)) {
            wp_send_json_error([
                'message' => '❌ Invalid QR code data',
                'type' => 'invalid_qr'
            ]);
        }
        
        // Validate and get booking details
        $result = $this->validate_booking($qr_data);
        
        wp_send_json($result);
    }
    
    /**
     * AJAX: Search booking manually
     */
    public function ajax_search_booking() {
        check_ajax_referer('waza_scanner_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => '❌ Permission denied',
                'type' => 'permission_denied'
            ]);
        }
        
        $search_term = sanitize_text_field($_POST['search_term'] ?? '');
        
        if (empty($search_term)) {
            wp_send_json_error([
                'message' => '❌ Please enter a booking ID or QR token',
                'type' => 'empty_search'
            ]);
        }
        
        // Validate and get booking details
        $result = $this->validate_booking($search_term);
        
        wp_send_json($result);
    }
    
    /**
     * Validate booking and check all conditions
     * 
     * @param string $qr_data QR code or booking ID
     * @return array Success or error response
     */
    private function validate_booking($qr_data) {
        global $wpdb;
        
        // Try to parse as JSON first
        $parsed_data = json_decode($qr_data, true);
        if ($parsed_data && isset($parsed_data['booking_id'])) {
            $booking_id = intval($parsed_data['booking_id']);
            $qr_token = sanitize_text_field($parsed_data['token'] ?? '');
        } else {
            // Use as booking ID or QR token
            $booking_id = intval($qr_data);
            $qr_token = $qr_data;
        }
        
        // Get booking with all details
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT b.*, 
                   s.start_datetime, 
                   s.end_datetime,
                   s.capacity,
                   s.id as slot_id,
                   p.post_title as activity_title,
                   p.ID as activity_id
            FROM {$wpdb->prefix}waza_bookings b
            LEFT JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
            LEFT JOIN {$wpdb->prefix}posts p ON s.activity_id = p.ID
            WHERE b.id = %d OR b.qr_token = %s
            LIMIT 1
        ", $booking_id, $qr_token));
        
        // VALIDATION 1: Booking exists
        if (!$booking) {
            return [
                'success' => false,
                'data' => [
                    'message' => '❌ Booking Not Found',
                    'type' => 'not_found',
                    'details' => 'This QR code is invalid or the booking does not exist in our system.',
                    'help_text' => 'Please verify the booking ID or try scanning again.'
                ]
            ];
        }
        
        // VALIDATION 2: Payment status
        if ($booking->payment_status !== 'completed' && $booking->payment_status !== 'paid') {
            return [
                'success' => false,
                'data' => [
                    'message' => '💳 Payment Not Completed',
                    'type' => 'payment_pending',
                    'details' => sprintf(
                        'Payment status: %s. Amount due: %s',
                        ucfirst($booking->payment_status),
                        wc_price($booking->total_amount)
                    ),
                    'booking_id' => $booking->id,
                    'payment_status' => $booking->payment_status,
                    'amount' => $booking->total_amount,
                    'help_text' => 'Please complete payment before attending the activity.'
                ]
            ];
        }
        
        // VALIDATION 3: Booking confirmation status
        if ($booking->booking_status !== 'confirmed') {
            return [
                'success' => false,
                'data' => [
                    'message' => '⚠️ Booking Not Confirmed',
                    'type' => 'not_confirmed',
                    'details' => sprintf('Booking status: %s', ucfirst($booking->booking_status)),
                    'booking_id' => $booking->id,
                    'status' => $booking->booking_status,
                    'help_text' => 'Please contact admin to confirm this booking.'
                ]
            ];
        }
        
        // VALIDATION 4: Slot timing (using Asia/Kolkata timezone)
        // Convert slot datetime to site timezone for comparison
        $slot_start_obj = new \DateTime($booking->start_datetime, new \DateTimeZone('UTC'));
        $slot_start_obj->setTimezone(new \DateTimeZone(wp_timezone_string()));
        $slot_end_obj = new \DateTime($booking->end_datetime, new \DateTimeZone('UTC'));
        $slot_end_obj->setTimezone(new \DateTimeZone(wp_timezone_string()));
        
        $slot_date = $slot_start_obj->format('Y-m-d');
        $today = current_time('Y-m-d');
        $slot_start_timestamp = $slot_start_obj->getTimestamp();
        $slot_end_timestamp = $slot_end_obj->getTimestamp();
        $current_datetime = current_time('timestamp');
        
        // Check if slot date is in the past (different day)
        if ($slot_date < $today) {
            // Past slot (different day)
            return [
                'success' => false,
                'data' => [
                    'message' => '⏰ This is a Past Slot',
                    'type' => 'past_slot',
                    'details' => sprintf(
                        'This booking was for %s at %s - %s',
                        date_i18n('F j, Y', $slot_start_timestamp),
                        date_i18n('g:i A', $slot_start_timestamp),
                        date_i18n('g:i A', $slot_end_timestamp)
                    ),
                    'slot_date' => date_i18n('F j, Y', $slot_start_timestamp),
                    'slot_time' => date_i18n('g:i A', $slot_start_timestamp) . ' - ' . date_i18n('g:i A', $slot_end_timestamp),
                    'activity' => $booking->activity_title,
                    'help_text' => 'Attendance can only be marked on the day of the activity.'
                ]
            ];
        }
        
        // Check if slot date is in the future (different day)
        if ($slot_date > $today) {
            // Future slot (different day)
            $days_remaining = ceil(($slot_start_timestamp - $current_datetime) / 86400);
            return [
                'success' => false,
                'data' => [
                    'message' => '📅 This is an Upcoming Slot',
                    'type' => 'future_slot',
                    'details' => sprintf(
                        'This booking is for %s at %s - %s (%d days from now)',
                        date_i18n('F j, Y', $slot_start_timestamp),
                        date_i18n('g:i A', $slot_start_timestamp),
                        date_i18n('g:i A', $slot_end_timestamp),
                        $days_remaining
                    ),
                    'slot_date' => date_i18n('F j, Y', $slot_start_timestamp),
                    'slot_time' => date_i18n('g:i A', $slot_start_timestamp) . ' - ' . date_i18n('g:i A', $slot_end_timestamp),
                    'days_remaining' => $days_remaining,
                    'activity' => $booking->activity_title,
                    'help_text' => 'Please come back on the scheduled day to mark attendance.'
                ]
            ];
        }
        
        // Slot is today - now check if the slot time has already ended
        if ($current_datetime > $slot_end_timestamp) {
            // Slot ended today (time has passed)
            $hours_ago = ceil(($current_datetime - $slot_end_timestamp) / 3600);
            return [
                'success' => false,
                'data' => [
                    'message' => '⏰ Slot Time Has Ended',
                    'type' => 'slot_ended',
                    'details' => sprintf(
                        'This slot ended at %s (about %d %s ago). Current time: %s',
                        date_i18n('g:i A', $slot_end_timestamp),
                        $hours_ago,
                        $hours_ago == 1 ? 'hour' : 'hours',
                        current_time('g:i A')
                    ),
                    'slot_date' => date_i18n('F j, Y', $slot_start_timestamp),
                    'slot_time' => date_i18n('g:i A', $slot_start_timestamp) . ' - ' . date_i18n('g:i A', $slot_end_timestamp),
                    'current_time' => current_time('g:i A'),
                    'activity' => $booking->activity_title,
                    'help_text' => 'Attendance can only be marked during or shortly after the slot time.'
                ]
            ];
        }
        
        // Check if slot hasn't started yet (today but future time)
        if ($current_datetime < $slot_start_timestamp) {
            $minutes_remaining = ceil(($slot_start_timestamp - $current_datetime) / 60);
            return [
                'success' => false,
                'data' => [
                    'message' => '⏰ Slot Hasn\'t Started Yet',
                    'type' => 'slot_not_started',
                    'details' => sprintf(
                        'This slot starts at %s (in %d minutes). Current time: %s',
                        date_i18n('g:i A', $slot_start_timestamp),
                        $minutes_remaining,
                        current_time('g:i A')
                    ),
                    'slot_date' => date_i18n('F j, Y', $slot_start_timestamp),
                    'slot_time' => date_i18n('g:i A', $slot_start_timestamp) . ' - ' . date_i18n('g:i A', $slot_end_timestamp),
                    'current_time' => current_time('g:i A'),
                    'minutes_remaining' => $minutes_remaining,
                    'activity' => $booking->activity_title,
                    'help_text' => 'Please wait until the slot starts to mark attendance.'
                ]
            ];
        }
        
        // VALIDATION 5: Group booking - check attendance count
        $attendance_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}waza_attendance
            WHERE booking_id = %d AND attendance_status = 'present'
        ", $booking->id));
        
        $total_attendees = max(1, intval($booking->attendees_count));
        $remaining_attendees = $total_attendees - $attendance_count;
        
        if ($remaining_attendees <= 0) {
            // All attendees already marked
            $first_check_in = $wpdb->get_var($wpdb->prepare("
                SELECT check_in_time FROM {$wpdb->prefix}waza_attendance
                WHERE booking_id = %d
                ORDER BY check_in_time ASC LIMIT 1
            ", $booking->id));
            
            return [
                'success' => false,
                'data' => [
                    'message' => '✅ Attendance Already Completed',
                    'type' => 'already_attended',
                    'details' => sprintf(
                        'All %d attendee(s) for this booking have been marked present.',
                        $total_attendees
                    ),
                    'booking_id' => $booking->id,
                    'attended_count' => $attendance_count,
                    'total_count' => $total_attendees,
                    'first_check_in' => date_i18n('g:i A', strtotime($first_check_in)),
                    'help_text' => 'This booking has reached its maximum attendance count.'
                ]
            ];
        }
        
        // Get user details
        $user = $booking->user_id ? get_userdata($booking->user_id) : null;
        
        // SUCCESS - All validations passed
        return [
            'success' => true,
            'data' => [
                'message' => '✅ Valid Booking - Ready to Mark Attendance',
                'type' => 'valid',
                'booking' => [
                    'id' => $booking->id,
                    'activity' => $booking->activity_title,
                    'slot_date' => date_i18n('F j, Y', strtotime($booking->start_datetime)),
                    'slot_time' => date_i18n('g:i A', strtotime($booking->start_datetime)) . ' - ' . date_i18n('g:i A', strtotime($booking->end_datetime)),
                    'payment_status' => $booking->payment_status,
                    'total_amount' => $booking->total_amount,
                    'booking_status' => $booking->booking_status
                ],
                'user' => $user ? [
                    'display_name' => $user->display_name,
                    'email' => $user->user_email,
                    'phone' => get_user_meta($user->ID, 'phone', true)
                ] : [
                    'display_name' => $booking->user_name,
                    'email' => $booking->user_email,
                    'phone' => $booking->user_phone
                ],
                'attendance_info' => [
                    'total_attendees' => $total_attendees,
                    'already_marked' => $attendance_count,
                    'remaining' => $remaining_attendees,
                    'can_mark' => true,
                    'progress_message' => $total_attendees > 1 ? 
                        sprintf('%d of %d attendees checked in', $attendance_count, $total_attendees) : 
                        'Single attendee booking',
                    'is_group_booking' => $total_attendees > 1
                ],
                'slot_id' => $booking->slot_id
            ]
        ];
    }
    
    /**
     * AJAX: Mark single attendance
     */
    public function ajax_mark_single_attendance() {
        check_ajax_referer('waza_scanner_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => '❌ Permission denied']);
        }
        
        $booking_id = intval($_POST['booking_id'] ?? 0);
        $slot_id = intval($_POST['slot_id'] ?? 0);
        
        if (!$booking_id || !$slot_id) {
            wp_send_json_error(['message' => '❌ Missing required parameters']);
        }
        
        global $wpdb;
        
        // Get booking details
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_bookings WHERE id = %d
        ", $booking_id));
        
        if (!$booking) {
            wp_send_json_error(['message' => '❌ Booking not found']);
        }
        
        // Check if already at max capacity
        $attendance_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}waza_attendance
            WHERE booking_id = %d AND attendance_status = 'present'
        ", $booking_id));
        
        $total_attendees = max(1, intval($booking->attendees_count));
        
        if ($attendance_count >= $total_attendees) {
            wp_send_json_error([
                'message' => '❌ All attendees already marked',
                'attended' => $attendance_count,
                'total' => $total_attendees
            ]);
        }
        
        // Insert attendance record
        $result = $wpdb->insert(
            "{$wpdb->prefix}waza_attendance",
            [
                'booking_id' => $booking_id,
                'slot_id' => $slot_id,
                'user_id' => $booking->user_id ?: 0,
                'attendance_status' => 'present',
                'check_in_time' => current_time('mysql'),
                'scanner_user_id' => get_current_user_id(),
                'entry_method' => 'admin_scanner',
                'scanner_device' => 'admin_dashboard',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
            ],
            ['%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s']
        );
        
        if ($result) {
            // Update booking attended status
            $wpdb->update(
                "{$wpdb->prefix}waza_bookings",
                [
                    'attended' => 1,
                    'attended_at' => current_time('mysql')
                ],
                ['id' => $booking_id],
                ['%d', '%s'],
                ['%d']
            );
            
            $new_attendance_count = $attendance_count + 1;
            $remaining = $total_attendees - $new_attendance_count;
            
            wp_send_json_success([
                'message' => '✅ Attendance marked successfully!',
                'student_name' => $booking->user_name,
                'check_in_time' => current_time('g:i A'),
                'attendance_progress' => [
                    'marked' => $new_attendance_count,
                    'total' => $total_attendees,
                    'remaining' => $remaining,
                    'progress_message' => $total_attendees > 1 ? 
                        sprintf('%d user checked in, %d remaining for this booking', $new_attendance_count, $remaining) : 
                        'Attendance completed'
                ]
            ]);
        } else {
            wp_send_json_error([
                'message' => '❌ Failed to mark attendance',
                'error' => $wpdb->last_error
            ]);
        }
    }
    
    /**
     * AJAX: Get today's statistics
     */
    public function ajax_get_today_stats() {
        check_ajax_referer('waza_scanner_nonce', 'nonce');
        
        global $wpdb;
        $today = current_time('Y-m-d');
        
        // Total checked in today
        $total_checked_in = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}waza_attendance
            WHERE DATE(check_in_time) = %s
        ", $today));
        
        // Expected today (total bookings for today's slots)
        $total_expected = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(b.attendees_count)
            FROM {$wpdb->prefix}waza_bookings b
            INNER JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
            WHERE DATE(s.start_datetime) = %s
            AND b.booking_status = 'confirmed'
            AND b.payment_status IN ('completed', 'paid')
        ", $today));
        
        // Active slots today
        $active_slots = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}waza_slots
            WHERE DATE(start_datetime) = %s
            AND status = 'active'
        ", $today));
        
        // Recent scans (last 10)
        $recent_scans = $wpdb->get_results($wpdb->prepare("
            SELECT a.*, 
                   b.user_name,
                   p.post_title as activity_title
            FROM {$wpdb->prefix}waza_attendance a
            LEFT JOIN {$wpdb->prefix}waza_bookings b ON a.booking_id = b.id
            LEFT JOIN {$wpdb->prefix}waza_slots s ON a.slot_id = s.id
            LEFT JOIN {$wpdb->prefix}posts p ON s.activity_id = p.ID
            WHERE DATE(a.check_in_time) = %s
            ORDER BY a.check_in_time DESC
            LIMIT 10
        ", $today));
        
        $attendance_rate = $total_expected > 0 ? round(($total_checked_in / $total_expected) * 100) : 0;
        
        wp_send_json_success([
            'stats' => [
                'total_checked_in' => intval($total_checked_in),
                'total_expected' => intval($total_expected),
                'attendance_rate' => $attendance_rate,
                'active_slots' => intval($active_slots)
            ],
            'recent_scans' => array_map(function($scan) {
                return [
                    'student_name' => $scan->user_name,
                    'activity' => $scan->activity_title,
                    'check_in_time' => date_i18n('g:i A', strtotime($scan->check_in_time)),
                    'time_ago' => human_time_diff(strtotime($scan->check_in_time), current_time('timestamp')) . ' ago'
                ];
            }, $recent_scans)
        ]);
    }
}
