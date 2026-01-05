<?php
/**
 * Interactive Calendar Manager
 * 
 * Provides interactive calendar view for slot browsing and booking
 * 
 * @package WazaBooking\Frontend
 */

namespace WazaBooking\Frontend;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interactive Calendar Manager Class
 */
class InteractiveCalendarManager {
    
    /**
     * Initialize calendar functionality
     */
    public function init() {
        add_shortcode('waza_calendar', [$this, 'calendar_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_calendar_assets']);
        add_action('wp_ajax_waza_get_calendar_slots', [$this, 'ajax_get_calendar_slots']);
        add_action('wp_ajax_nopriv_waza_get_calendar_slots', [$this, 'ajax_get_calendar_slots']);
    }
    
    /**
     * Enqueue calendar assets
     */
    public function enqueue_calendar_assets() {
        if (is_singular() || is_page()) {
            global $post;
            if ($post && has_shortcode($post->post_content, 'waza_calendar')) {
                wp_enqueue_style('waza-calendar', WAZA_BOOKING_PLUGIN_URL . 'assets/calendar.css', [], WAZA_BOOKING_VERSION);
                wp_enqueue_script('waza-calendar', WAZA_BOOKING_PLUGIN_URL . 'assets/calendar.js', ['jquery'], WAZA_BOOKING_VERSION, true);
                
                wp_localize_script('waza-calendar', 'wazaCalendar', [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('waza_calendar_nonce'),
                    'settings' => $this->get_calendar_settings()
                ]);
            }
        }
    }
    
    /**
     * Calendar shortcode
     */
    public function calendar_shortcode($atts) {
        $atts = shortcode_atts([
            'view' => 'month', // month, week, day
            'activity_id' => '',
            'instructor_id' => '',
            'show_filters' => 'yes'
        ], $atts);
        
        $settings = $this->get_calendar_settings();
        
        ob_start();
        ?>
        <div class="waza-calendar-wrapper" data-view="<?php echo esc_attr($atts['view']); ?>">
            
            <?php if ($atts['show_filters'] === 'yes') : ?>
            <!-- Calendar Filters -->
            <div class="waza-calendar-filters">
                <div class="waza-filter-group">
                    <label><?php _e('Activity:', 'waza-booking'); ?></label>
                    <select id="waza-filter-activity" class="waza-filter">
                        <option value=""><?php _e('All Activities', 'waza-booking'); ?></option>
                        <?php 
                        $activities = get_posts(['post_type' => 'waza_activity', 'posts_per_page' => -1]);
                        foreach ($activities as $activity) {
                            $selected = $atts['activity_id'] == $activity->ID ? 'selected' : '';
                            echo '<option value="' . $activity->ID . '" ' . $selected . '>' . esc_html($activity->post_title) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="waza-filter-group">
                    <label><?php _e('Instructor:', 'waza-booking'); ?></label>
                    <select id="waza-filter-instructor" class="waza-filter">
                        <option value=""><?php _e('All Instructors', 'waza-booking'); ?></option>
                        <?php 
                        $instructors = get_users(['role' => 'waza_instructor']);
                        foreach ($instructors as $instructor) {
                            $selected = $atts['instructor_id'] == $instructor->ID ? 'selected' : '';
                            echo '<option value="' . $instructor->ID . '" ' . $selected . '>' . esc_html($instructor->display_name) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="waza-filter-group">
                    <label><?php _e('View:', 'waza-booking'); ?></label>
                    <select id="waza-calendar-view" class="waza-filter">
                        <option value="month" <?php selected($atts['view'], 'month'); ?>><?php _e('Month', 'waza-booking'); ?></option>
                        <option value="week" <?php selected($atts['view'], 'week'); ?>><?php _e('Week', 'waza-booking'); ?></option>
                        <option value="day" <?php selected($atts['view'], 'day'); ?>><?php _e('Day', 'waza-booking'); ?></option>
                    </select>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Calendar Header -->
            <div class="waza-calendar-header">
                <button class="waza-calendar-nav" data-nav="prev">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                    <?php _e('Previous', 'waza-booking'); ?>
                </button>
                
                <h2 class="waza-calendar-title">
                    <span id="waza-current-month"></span>
                    <span id="waza-current-year"></span>
                </h2>
                
                <button class="waza-calendar-nav" data-nav="next">
                    <?php _e('Next', 'waza-booking'); ?>
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </button>
                
                <button class="waza-calendar-today">
                    <?php _e('Today', 'waza-booking'); ?>
                </button>
            </div>
            
            <!-- Calendar Grid -->
            <div class="waza-calendar-grid">
                <div class="waza-calendar-weekdays">
                    <?php
                    $weekdays = $settings['start_of_week'] === 'sunday' 
                        ? ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
                        : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                    
                    foreach ($weekdays as $day) {
                        echo '<div class="waza-weekday">' . __($day, 'waza-booking') . '</div>';
                    }
                    ?>
                </div>
                
                <div id="waza-calendar-days" class="waza-calendar-days">
                    <!-- Days will be populated by JavaScript -->
                </div>
            </div>
            
            <!-- Loading Indicator -->
            <div class="waza-calendar-loading" style="display: none;">
                <span class="spinner is-active"></span>
                <?php _e('Loading slots...', 'waza-booking'); ?>
            </div>
            
            <!-- Legend -->
            <div class="waza-calendar-legend">
                <div class="waza-legend-item">
                    <span class="waza-legend-color waza-slot-available"></span>
                    <?php _e('Available', 'waza-booking'); ?>
                </div>
                <div class="waza-legend-item">
                    <span class="waza-legend-color waza-slot-limited"></span>
                    <?php _e('Limited Seats', 'waza-booking'); ?>
                </div>
                <div class="waza-legend-item">
                    <span class="waza-legend-color waza-slot-full"></span>
                    <?php _e('Full', 'waza-booking'); ?>
                </div>
                <div class="waza-legend-item">
                    <span class="waza-legend-color waza-slot-past"></span>
                    <?php _e('Past', 'waza-booking'); ?>
                </div>
            </div>
        </div>
        
        <!-- Slot Details Modal -->
        <div id="waza-slot-modal" class="waza-modal" style="display: none;">
            <div class="waza-modal-content">
                <span class="waza-modal-close">&times;</span>
                <div id="waza-slot-details">
                    <!-- Slot details will be loaded here -->
                </div>
            </div>
        </div>
        
        <style>
        .waza-calendar-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .waza-calendar-filters {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .waza-filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .waza-filter-group label {
            font-weight: 600;
            font-size: 14px;
            color: #333;
        }
        
        .waza-filter {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-width: 150px;
        }
        
        .waza-calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .waza-calendar-title {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
            color: <?php echo esc_attr($settings['primary_color']); ?>;
        }
        
        .waza-calendar-nav,
        .waza-calendar-today {
            background: <?php echo esc_attr($settings['primary_color']); ?>;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: 600;
            transition: opacity 0.3s;
        }
        
        .waza-calendar-nav:hover,
        .waza-calendar-today:hover {
            opacity: 0.8;
        }
        
        .waza-calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
            margin-bottom: 2px;
        }
        
        .waza-weekday {
            padding: 15px;
            text-align: center;
            font-weight: 700;
            background: #f5f5f5;
            color: #666;
            font-size: 14px;
        }
        
        .waza-calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
        }
        
        .waza-calendar-day {
            min-height: 120px;
            padding: 10px;
            background: #fafafa;
            border: 1px solid #e0e0e0;
            cursor: pointer;
            position: relative;
            transition: background-color 0.3s;
        }
        
        .waza-calendar-day:hover {
            background: #f0f0f0;
        }
        
        .waza-calendar-day.other-month {
            opacity: 0.4;
        }
        
        .waza-calendar-day.today {
            background: #fff3cd;
            border: 2px solid <?php echo esc_attr($settings['primary_color']); ?>;
        }
        
        .waza-day-number {
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 8px;
            color: #333;
        }
        
        .waza-day-slots {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .waza-slot-item {
            padding: 6px 8px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .waza-slot-item:hover {
            transform: translateX(5px);
        }
        
        .waza-slot-available {
            background: #d4edda;
            color: #155724;
            border-left: 3px solid #28a745;
        }
        
        .waza-slot-limited {
            background: #fff3cd;
            color: #856404;
            border-left: 3px solid #ffc107;
        }
        
        .waza-slot-full {
            background: #f8d7da;
            color: #721c24;
            border-left: 3px solid #dc3545;
        }
        
        .waza-slot-past {
            background: #e2e3e5;
            color: #6c757d;
            border-left: 3px solid #6c757d;
            opacity: 0.6;
        }
        
        .waza-calendar-legend {
            display: flex;
            gap: 20px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            flex-wrap: wrap;
        }
        
        .waza-legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .waza-legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }
        
        .waza-calendar-loading {
            text-align: center;
            padding: 40px;
            font-size: 16px;
            color: #666;
        }
        
        .waza-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.6);
        }
        
        .waza-modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            position: relative;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        
        .waza-modal-close {
            position: absolute;
            right: 15px;
            top: 15px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
        }
        
        .waza-modal-close:hover {
            color: #000;
        }
        
        @media (max-width: 768px) {
            .waza-calendar-day {
                min-height: 80px;
                padding: 5px;
            }
            
            .waza-day-number {
                font-size: 14px;
            }
            
            .waza-slot-item {
                font-size: 10px;
                padding: 4px 6px;
            }
            
            .waza-calendar-filters {
                flex-direction: column;
            }
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get calendar slots via AJAX
     */
    public function ajax_get_calendar_slots() {
        check_ajax_referer('waza_calendar_nonce', 'nonce');
        
        $month = intval($_POST['month'] ?? date('n'));
        $year = intval($_POST['year'] ?? date('Y'));
        $activity_id = intval($_POST['activity_id'] ?? 0);
        $instructor_id = intval($_POST['instructor_id'] ?? 0);
        
        global $wpdb;
        
        // Get first and last day of month
        $first_day = date('Y-m-01', strtotime("$year-$month-01"));
        $last_day = date('Y-m-t', strtotime("$year-$month-01"));
        
        // Build query
        $where = "WHERE s.start_datetime >= %s AND s.start_datetime <= %s AND s.status = 'active'";
        $params = [$first_day . ' 00:00:00', $last_day . ' 23:59:59'];
        
        if ($activity_id > 0) {
            $where .= " AND s.activity_id = %d";
            $params[] = $activity_id;
        }
        
        if ($instructor_id > 0) {
            $where .= " AND s.instructor_id = %d";
            $params[] = $instructor_id;
        }
        
        $slots = $wpdb->get_results($wpdb->prepare("
            SELECT s.*, 
                   p.post_title as activity_name,
                   u.display_name as instructor_name,
                   (SELECT COUNT(*) FROM {$wpdb->prefix}waza_bookings 
                    WHERE slot_id = s.id AND booking_status != 'cancelled') as booked_count
            FROM {$wpdb->prefix}waza_slots s
            LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
            LEFT JOIN {$wpdb->users} u ON s.instructor_id = u.ID
            $where
            ORDER BY s.start_datetime ASC
        ", $params));
        
        // Group slots by date
        $slots_by_date = [];
        foreach ($slots as $slot) {
            $date = date('Y-m-d', strtotime($slot->start_datetime));
            if (!isset($slots_by_date[$date])) {
                $slots_by_date[$date] = [];
            }
            
            $available_seats = $slot->capacity - $slot->booked_count;
            $availability_class = 'available';
            
            if (strtotime($slot->start_datetime) < time()) {
                $availability_class = 'past';
            } elseif ($available_seats <= 0) {
                $availability_class = 'full';
            } elseif ($available_seats <= $slot->capacity * 0.3) {
                $availability_class = 'limited';
            }
            
            $slots_by_date[$date][] = [
                'id' => $slot->id,
                'time' => date('g:i A', strtotime($slot->start_datetime)),
                'activity' => $slot->activity_name,
                'instructor' => $slot->instructor_name,
                'price' => $slot->price,
                'available_seats' => $available_seats,
                'total_seats' => $slot->capacity,
                'availability_class' => $availability_class
            ];
        }
        
        wp_send_json_success([
            'slots_by_date' => $slots_by_date,
            'month' => $month,
            'year' => $year
        ]);
    }
    
    /**
     * Get calendar settings
     */
    private function get_calendar_settings() {
        $settings_manager = \WazaBooking\Core\Plugin::get_instance()->get_manager('settings');
        
        return [
            'primary_color' => get_option('waza_calendar_primary_color', '#007bff'),
            'start_of_week' => get_option('waza_calendar_start_of_week', 'monday'),
            'time_format' => get_option('waza_calendar_time_format', '12h'),
            'show_instructor' => get_option('waza_calendar_show_instructor', 'yes'),
            'show_price' => get_option('waza_calendar_show_price', 'yes'),
            'slots_per_day' => get_option('waza_calendar_slots_per_day', '5')
        ];
    }
}
