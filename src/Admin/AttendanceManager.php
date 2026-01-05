<?php
/**
 * Attendance Manager
 * 
 * Handles student attendance tracking for both students and admins
 * 
 * @package WazaBooking\Admin
 */

namespace WazaBooking\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Attendance Manager Class
 */
class AttendanceManager {
    
    /**
     * Initialize attendance management
     */
    public function init() {
        add_action('admin_menu', [$this, 'add_admin_menu'], 26);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        
        // AJAX handlers
        add_action('wp_ajax_waza_mark_attendance', [$this, 'ajax_mark_attendance']);
        add_action('wp_ajax_waza_get_attendance_records', [$this, 'ajax_get_attendance_records']);
        add_action('wp_ajax_waza_get_student_attendance', [$this, 'ajax_get_student_attendance']);
        add_action('wp_ajax_waza_export_attendance_csv', [$this, 'ajax_export_attendance_csv']);
        
        // Shortcodes
        add_shortcode('waza_my_attendance', [$this, 'student_attendance_shortcode']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'waza-booking',
            __('Attendance', 'waza-booking'),
            __('Attendance', 'waza-booking'),
            'view_waza_bookings',
            'waza-attendance',
            [$this, 'admin_page']
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ('waza_page_waza-attendance' !== $hook) {
            return;
        }
        
        wp_localize_script('jquery', 'wazaAttendance', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('waza_admin_nonce')
        ]);
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        global $wpdb;
        
        // Get filter parameters
        $filter_date = isset($_GET['filter_date']) ? sanitize_text_field($_GET['filter_date']) : '';
        $filter_activity = isset($_GET['filter_activity']) ? intval($_GET['filter_activity']) : 0;
        $filter_instructor = isset($_GET['filter_instructor']) ? intval($_GET['filter_instructor']) : 0;
        
        // Build query
        $where = ['1=1'];
        $params = [];
        
        if ($filter_date) {
            $where[] = 'DATE(att.check_in_time) = %s';
            $params[] = $filter_date;
        }
        
        if ($filter_activity) {
            $where[] = 's.activity_id = %d';
            $params[] = $filter_activity;
        }
        
        if ($filter_instructor) {
            $where[] = 's.instructor_id = %d';
            $params[] = $filter_instructor;
        }
        
        $where_clause = implode(' AND ', $where);
        
        $query = "
            SELECT att.*, 
                   b.user_name, b.user_email,
                   s.start_datetime, s.end_datetime,
                   act.post_title as activity_name,
                   ins.post_title as instructor_name,
                   scanner.display_name as scanner_name
            FROM {$wpdb->prefix}waza_attendance att
            LEFT JOIN {$wpdb->prefix}waza_bookings b ON att.booking_id = b.id
            LEFT JOIN {$wpdb->prefix}waza_slots s ON att.slot_id = s.id
            LEFT JOIN {$wpdb->posts} act ON s.activity_id = act.ID
            LEFT JOIN {$wpdb->posts} ins ON s.instructor_id = ins.ID
            LEFT JOIN {$wpdb->users} scanner ON att.scanner_user_id = scanner.ID
            WHERE {$where_clause}
            ORDER BY att.check_in_time DESC
            LIMIT 100
        ";
        
        if (!empty($params)) {
            $attendance_records = $wpdb->get_results($wpdb->prepare($query, ...$params));
        } else {
            $attendance_records = $wpdb->get_results($query);
        }
        
        // Get activities for filter
        $activities = $wpdb->get_results("SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = 'waza_activity' AND post_status = 'publish' ORDER BY post_title");
        
        // Get instructors for filter
        $instructors = $wpdb->get_results("SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = 'waza_instructor' AND post_status = 'publish' ORDER BY post_title");
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Attendance Records', 'waza-booking'); ?></h1>
            <p><?php esc_html_e('Track and manage student attendance for all activities.', 'waza-booking'); ?></p>
            
            <!-- Filters -->
            <div class="waza-attendance-filters" style="background:#fff; padding:15px; margin:15px 0; border:1px solid #ccc; border-radius:4px;">
                <form method="get" action="">
                    <input type="hidden" name="page" value="waza-attendance">
                    
                    <label><?php esc_html_e('Date:', 'waza-booking'); ?>
                        <input type="date" name="filter_date" value="<?php echo esc_attr($filter_date); ?>">
                    </label>
                    
                    <label><?php esc_html_e('Activity:', 'waza-booking'); ?>
                        <select name="filter_activity">
                            <option value=""><?php esc_html_e('All Activities', 'waza-booking'); ?></option>
                            <?php foreach ($activities as $activity): ?>
                                <option value="<?php echo $activity->ID; ?>" <?php selected($filter_activity, $activity->ID); ?>>
                                    <?php echo esc_html($activity->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    
                    <label><?php esc_html_e('Instructor:', 'waza-booking'); ?>
                        <select name="filter_instructor">
                            <option value=""><?php esc_html_e('All Instructors', 'waza-booking'); ?></option>
                            <?php foreach ($instructors as $instructor): ?>
                                <option value="<?php echo $instructor->ID; ?>" <?php selected($filter_instructor, $instructor->ID); ?>>
                                    <?php echo esc_html($instructor->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    
                    <button type="submit" class="button"><?php esc_html_e('Filter', 'waza-booking'); ?></button>
                    <a href="<?php echo admin_url('admin.php?page=waza-attendance'); ?>" class="button"><?php esc_html_e('Clear', 'waza-booking'); ?></a>
                    <button type="button" id="waza-export-attendance-csv" class="button button-primary" style="float:right;">
                        <?php esc_html_e('Export to CSV', 'waza-booking'); ?>
                    </button>
                </form>
            </div>
            
            <!-- Stats Summary -->
            <div class="waza-attendance-stats" style="display:grid; grid-template-columns:repeat(4, 1fr); gap:15px; margin:15px 0;">
                <div style="background:#fff; padding:20px; border:1px solid #ccc; border-radius:4px; text-align:center;">
                    <h3 style="margin:0; font-size:32px; color:#4CAF50;">
                        <?php echo count($attendance_records); ?>
                    </h3>
                    <p style="margin:5px 0 0; color:#666;"><?php esc_html_e('Total Records', 'waza-booking'); ?></p>
                </div>
                <div style="background:#fff; padding:20px; border:1px solid #ccc; border-radius:4px; text-align:center;">
                    <h3 style="margin:0; font-size:32px; color:#2196F3;">
                        <?php 
                        $present_count = array_filter($attendance_records, function($r) { 
                            return $r->attendance_status === 'present'; 
                        });
                        echo count($present_count);
                        ?>
                    </h3>
                    <p style="margin:5px 0 0; color:#666;"><?php esc_html_e('Present', 'waza-booking'); ?></p>
                </div>
                <div style="background:#fff; padding:20px; border:1px solid #ccc; border-radius:4px; text-align:center;">
                    <h3 style="margin:0; font-size:32px; color:#FF9800;">
                        <?php 
                        $absent_count = array_filter($attendance_records, function($r) { 
                            return $r->attendance_status === 'absent'; 
                        });
                        echo count($absent_count);
                        ?>
                    </h3>
                    <p style="margin:5px 0 0; color:#666;"><?php esc_html_e('Absent', 'waza-booking'); ?></p>
                </div>
                <div style="background:#fff; padding:20px; border:1px solid #ccc; border-radius:4px; text-align:center;">
                    <h3 style="margin:0; font-size:32px; color:#9C27B0;">
                        <?php 
                        $late_count = array_filter($attendance_records, function($r) { 
                            return $r->attendance_status === 'late'; 
                        });
                        echo count($late_count);
                        ?>
                    </h3>
                    <p style="margin:5px 0 0; color:#666;"><?php esc_html_e('Late', 'waza-booking'); ?></p>
                </div>
            </div>
            
            <!-- Attendance Table -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Student', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Activity', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Instructor', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Slot Time', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Check-in Time', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Status', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Scanned By', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Notes', 'waza-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($attendance_records)): ?>
                        <tr>
                            <td colspan="8" style="text-align:center;">
                                <?php esc_html_e('No attendance records found.', 'waza-booking'); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($attendance_records as $record): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($record->user_name); ?></strong><br>
                                    <small><?php echo esc_html($record->user_email); ?></small>
                                </td>
                                <td><?php echo esc_html($record->activity_name ?? 'N/A'); ?></td>
                                <td><?php echo esc_html($record->instructor_name ?? 'N/A'); ?></td>
                                <td>
                                    <?php 
                                    if ($record->start_datetime) {
                                        echo date('M j, Y g:i A', strtotime($record->start_datetime));
                                    }
                                    ?>
                                </td>
                                <td><?php echo date('M j, Y g:i A', strtotime($record->check_in_time)); ?></td>
                                <td>
                                    <?php
                                    $status_colors = [
                                        'present' => '#4CAF50',
                                        'absent' => '#f44336',
                                        'late' => '#FF9800',
                                        'excused' => '#2196F3'
                                    ];
                                    $color = $status_colors[$record->attendance_status] ?? '#666';
                                    ?>
                                    <span style="color:<?php echo $color; ?>; font-weight:600;">
                                        <?php echo esc_html(ucfirst($record->attendance_status)); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($record->scanner_name ?? 'System'); ?></td>
                                <td><?php echo esc_html($record->notes ?? '—'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <p class="description" style="margin-top:20px;">
                <strong><?php esc_html_e('Student View:', 'waza-booking'); ?></strong>
                <?php esc_html_e('Students can view their own attendance using the shortcode [waza_my_attendance]', 'waza-booking'); ?>
            </p>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#waza-export-attendance-csv').on('click', function() {
                var filters = $('form').serialize();
                window.location.href = ajaxurl + '?action=waza_export_attendance_csv&' + filters + '&nonce=' + wazaAttendance.nonce;
            });
        });
        </script>
        <?php
    }
    
    /**
     * Mark attendance (AJAX)
     */
    public function ajax_mark_attendance() {
        check_ajax_referer('waza_admin_nonce', 'nonce');
        
        if (!current_user_can('view_waza_bookings')) {
            wp_send_json_error(__('Permission denied', 'waza-booking'));
        }
        
        $booking_id = intval($_POST['booking_id'] ?? 0);
        $slot_id = intval($_POST['slot_id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? 'present');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        
        if (!$booking_id || !$slot_id) {
            wp_send_json_error(__('Missing required parameters', 'waza-booking'));
        }
        
        global $wpdb;
        
        // Get booking details
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT user_id, user_email FROM {$wpdb->prefix}waza_bookings WHERE id = %d",
            $booking_id
        ));
        
        if (!$booking) {
            wp_send_json_error(__('Booking not found', 'waza-booking'));
        }
        
        $result = $wpdb->insert($wpdb->prefix . 'waza_attendance', [
            'booking_id' => $booking_id,
            'slot_id' => $slot_id,
            'user_id' => $booking->user_id,
            'attendance_status' => $status,
            'check_in_time' => current_time('mysql'),
            'scanner_user_id' => get_current_user_id(),
            'notes' => $notes,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        if ($result) {
            // Update booking attended status
            $wpdb->update(
                $wpdb->prefix . 'waza_bookings',
                ['attended' => 1, 'attended_at' => current_time('mysql')],
                ['id' => $booking_id]
            );
            
            // Log activity
            do_action('waza_log_activity', 'attendance_marked', 'booking', $booking_id, [
                'status' => $status,
                'user_email' => $booking->user_email
            ]);
            
            wp_send_json_success(['message' => __('Attendance marked successfully', 'waza-booking')]);
        } else {
            wp_send_json_error(__('Failed to mark attendance', 'waza-booking'));
        }
    }
    
    /**
     * Get student attendance (for student view)
     */
    public function ajax_get_student_attendance() {
        check_ajax_referer('waza_frontend_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Please log in to view attendance', 'waza-booking'));
        }
        
        $user_id = get_current_user_id();
        
        global $wpdb;
        
        $records = $wpdb->get_results($wpdb->prepare("
            SELECT att.*, 
                   b.user_name,
                   s.start_datetime, s.end_datetime,
                   act.post_title as activity_name,
                   ins.post_title as instructor_name
            FROM {$wpdb->prefix}waza_attendance att
            LEFT JOIN {$wpdb->prefix}waza_bookings b ON att.booking_id = b.id
            LEFT JOIN {$wpdb->prefix}waza_slots s ON att.slot_id = s.id
            LEFT JOIN {$wpdb->posts} act ON s.activity_id = act.ID
            LEFT JOIN {$wpdb->posts} ins ON s.instructor_id = ins.ID
            WHERE att.user_id = %d
            ORDER BY att.check_in_time DESC
            LIMIT 50
        ", $user_id));
        
        wp_send_json_success(['records' => $records]);
    }
    
    /**
     * Export attendance to CSV
     */
    public function ajax_export_attendance_csv() {
        check_admin_referer('waza_admin_nonce', 'nonce');
        
        if (!current_user_can('view_waza_bookings')) {
            wp_die(__('Permission denied', 'waza-booking'));
        }
        
        global $wpdb;
        
        // Get all attendance records with filters
        $filter_date = isset($_GET['filter_date']) ? sanitize_text_field($_GET['filter_date']) : '';
        $filter_activity = isset($_GET['filter_activity']) ? intval($_GET['filter_activity']) : 0;
        
        $where = ['1=1'];
        $params = [];
        
        if ($filter_date) {
            $where[] = 'DATE(att.check_in_time) = %s';
            $params[] = $filter_date;
        }
        
        if ($filter_activity) {
            $where[] = 's.activity_id = %d';
            $params[] = $filter_activity;
        }
        
        $where_clause = implode(' AND ', $where);
        
        $query = "
            SELECT att.*, 
                   b.user_name, b.user_email,
                   s.start_datetime,
                   act.post_title as activity_name,
                   ins.post_title as instructor_name
            FROM {$wpdb->prefix}waza_attendance att
            LEFT JOIN {$wpdb->prefix}waza_bookings b ON att.booking_id = b.id
            LEFT JOIN {$wpdb->prefix}waza_slots s ON att.slot_id = s.id
            LEFT JOIN {$wpdb->posts} act ON s.activity_id = act.ID
            LEFT JOIN {$wpdb->posts} ins ON s.instructor_id = ins.ID
            WHERE {$where_clause}
            ORDER BY att.check_in_time DESC
        ";
        
        if (!empty($params)) {
            $records = $wpdb->get_results($wpdb->prepare($query, ...$params));
        } else {
            $records = $wpdb->get_results($query);
        }
        
        // Generate CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="attendance-' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Header row
        fputcsv($output, [
            'Student Name',
            'Email',
            'Activity',
            'Instructor',
            'Slot Time',
            'Check-in Time',
            'Status',
            'Notes'
        ]);
        
        // Data rows
        foreach ($records as $record) {
            fputcsv($output, [
                $record->user_name,
                $record->user_email,
                $record->activity_name,
                $record->instructor_name,
                $record->start_datetime,
                $record->check_in_time,
                $record->attendance_status,
                $record->notes
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Student attendance shortcode
     */
    public function student_attendance_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your attendance.', 'waza-booking') . '</p>';
        }
        
        $user_id = get_current_user_id();
        
        global $wpdb;
        
        $records = $wpdb->get_results($wpdb->prepare("
            SELECT att.*, 
                   s.start_datetime,
                   act.post_title as activity_name,
                   ins.post_title as instructor_name
            FROM {$wpdb->prefix}waza_attendance att
            LEFT JOIN {$wpdb->prefix}waza_slots s ON att.slot_id = s.id
            LEFT JOIN {$wpdb->posts} act ON s.activity_id = act.ID
            LEFT JOIN {$wpdb->posts} ins ON s.instructor_id = ins.ID
            WHERE att.user_id = %d
            ORDER BY att.check_in_time DESC
            LIMIT 20
        ", $user_id));
        
        ob_start();
        ?>
        <div class="waza-student-attendance">
            <h3><?php esc_html_e('My Attendance History', 'waza-booking'); ?></h3>
            
            <table class="waza-attendance-table" style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f5f5f5;">
                        <th style="padding:10px; text-align:left; border:1px solid #ddd;"><?php esc_html_e('Activity', 'waza-booking'); ?></th>
                        <th style="padding:10px; text-align:left; border:1px solid #ddd;"><?php esc_html_e('Instructor', 'waza-booking'); ?></th>
                        <th style="padding:10px; text-align:left; border:1px solid #ddd;"><?php esc_html_e('Date', 'waza-booking'); ?></th>
                        <th style="padding:10px; text-align:left; border:1px solid #ddd;"><?php esc_html_e('Status', 'waza-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                        <tr>
                            <td colspan="4" style="padding:10px; text-align:center; border:1px solid #ddd;">
                                <?php esc_html_e('No attendance records found.', 'waza-booking'); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($records as $record): ?>
                            <tr>
                                <td style="padding:10px; border:1px solid #ddd;"><?php echo esc_html($record->activity_name ?? 'N/A'); ?></td>
                                <td style="padding:10px; border:1px solid #ddd;"><?php echo esc_html($record->instructor_name ?? 'N/A'); ?></td>
                                <td style="padding:10px; border:1px solid #ddd;"><?php echo date('M j, Y', strtotime($record->check_in_time)); ?></td>
                                <td style="padding:10px; border:1px solid #ddd;">
                                    <span style="color:<?php echo $record->attendance_status === 'present' ? '#4CAF50' : '#f44336'; ?>; font-weight:600;">
                                        <?php echo esc_html(ucfirst($record->attendance_status)); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }
}
