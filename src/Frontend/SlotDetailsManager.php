<?php
/**
 * Slot Details Manager
 * 
 * Handles dedicated slot detail pages with booking integration
 * 
 * @package WazaBooking\Frontend
 */

namespace WazaBooking\Frontend;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Slot Details Manager Class
 */
class SlotDetailsManager {
    
    /**
     * Initialize slot details functionality
     */
    public function init() {
        add_action('init', [$this, 'add_rewrite_rules']);
        add_action('template_redirect', [$this, 'handle_slot_detail_page']);
        add_shortcode('waza_slot_details', [$this, 'slot_details_shortcode']);
        add_action('wp_ajax_waza_get_slot_details', [$this, 'ajax_get_slot_details']);
        add_action('wp_ajax_nopriv_waza_get_slot_details', [$this, 'ajax_get_slot_details']);
    }
    
    /**
     * Add rewrite rules for slot detail pages
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^slot/([0-9]+)/?$',
            'index.php?waza_slot_id=$matches[1]',
            'top'
        );
        
        add_rewrite_tag('%waza_slot_id%', '([0-9]+)');
    }
    
    /**
     * Handle slot detail page display
     */
    public function handle_slot_detail_page() {
        $slot_id = get_query_var('waza_slot_id');
        
        if (!empty($slot_id)) {
            $this->render_slot_detail_page($slot_id);
            exit;
        }
    }
    
    /**
     * Render slot detail page
     */
    private function render_slot_detail_page($slot_id) {
        global $wpdb;
        
        $slot = $wpdb->get_row($wpdb->prepare("
            SELECT s.*, 
                   p.post_title as activity_name,
                   p.post_content as activity_description,
                   u.display_name as instructor_name,
                   u.ID as instructor_id,
                   (SELECT COUNT(*) FROM {$wpdb->prefix}waza_bookings 
                    WHERE slot_id = s.id AND booking_status != 'cancelled') as booked_count
            FROM {$wpdb->prefix}waza_slots s
            LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
            LEFT JOIN {$wpdb->users} u ON s.instructor_id = u.ID
            WHERE s.id = %d
        ", $slot_id));
        
        if (!$slot) {
            wp_die(__('Slot not found.', 'waza-booking'), __('Not Found', 'waza-booking'), 404);
        }
        
        $available_seats = $slot->capacity - $slot->booked_count;
        $is_past = strtotime($slot->start_datetime) < time();
        $is_full = $available_seats <= 0;
        
        // Get activity image
        $activity_image = get_the_post_thumbnail_url($slot->activity_id, 'large');
        
        // Get instructor bio
        $instructor_bio = get_user_meta($slot->instructor_id, 'description', true);
        
        get_header();
        ?>
        
        <div class="waza-slot-detail-page">
            <div class="waza-slot-container">
                
                <!-- Hero Section -->
                <div class="waza-slot-hero" style="<?php if ($activity_image): ?>background-image: url(<?php echo esc_url($activity_image); ?>);<?php endif; ?>">
                    <div class="waza-slot-hero-overlay">
                        <div class="waza-slot-header">
                            <h1 class="waza-slot-title"><?php echo esc_html($slot->activity_name); ?></h1>
                            <div class="waza-slot-meta">
                                <span class="waza-slot-date">
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                    <?php echo wp_date('F j, Y', strtotime($slot->start_datetime)); ?>
                                </span>
                                <span class="waza-slot-time">
                                    <span class="dashicons dashicons-clock"></span>
                                    <?php echo wp_date('g:i A', strtotime($slot->start_datetime)); ?> - 
                                    <?php echo wp_date('g:i A', strtotime($slot->end_datetime)); ?>
                                </span>
                                <?php if ($slot->location): ?>
                                <span class="waza-slot-location">
                                    <span class="dashicons dashicons-location"></span>
                                    <?php echo esc_html($slot->location); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Main Content -->
                <div class="waza-slot-content">
                    
                    <!-- Left Column -->
                    <div class="waza-slot-main">
                        
                        <!-- Activity Description -->
                        <div class="waza-slot-section">
                            <h2><?php _e('About This Activity', 'waza-booking'); ?></h2>
                            <div class="waza-slot-description">
                                <?php echo wpautop(wp_kses_post($slot->activity_description)); ?>
                            </div>
                        </div>
                        
                        <!-- Slot Details -->
                        <div class="waza-slot-section">
                            <h2><?php _e('Session Details', 'waza-booking'); ?></h2>
                            <div class="waza-slot-details-grid">
                                <div class="waza-detail-item">
                                    <span class="waza-detail-label"><?php _e('Duration:', 'waza-booking'); ?></span>
                                    <span class="waza-detail-value">
                                        <?php 
                                        $duration = (strtotime($slot->end_datetime) - strtotime($slot->start_datetime)) / 60;
                                        echo intval($duration) . ' ' . __('minutes', 'waza-booking');
                                        ?>
                                    </span>
                                </div>
                                <div class="waza-detail-item">
                                    <span class="waza-detail-label"><?php _e('Capacity:', 'waza-booking'); ?></span>
                                    <span class="waza-detail-value"><?php echo intval($slot->capacity); ?> <?php _e('participants', 'waza-booking'); ?></span>
                                </div>
                                <div class="waza-detail-item">
                                    <span class="waza-detail-label"><?php _e('Available Seats:', 'waza-booking'); ?></span>
                                    <span class="waza-detail-value <?php echo $is_full ? 'waza-full' : 'waza-available'; ?>">
                                        <?php echo max(0, $available_seats); ?>
                                    </span>
                                </div>
                                <?php if ($slot->price > 0): ?>
                                <div class="waza-detail-item">
                                    <span class="waza-detail-label"><?php _e('Price:', 'waza-booking'); ?></span>
                                    <span class="waza-detail-value waza-price">
                                        ₹<?php echo number_format($slot->price, 2); ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Instructor Info -->
                        <?php if ($slot->instructor_name): ?>
                        <div class="waza-slot-section">
                            <h2><?php _e('Your Instructor', 'waza-booking'); ?></h2>
                            <div class="waza-instructor-card">
                                <div class="waza-instructor-avatar">
                                    <?php echo get_avatar($slot->instructor_id, 80); ?>
                                </div>
                                <div class="waza-instructor-info">
                                    <h3 class="waza-instructor-name"><?php echo esc_html($slot->instructor_name); ?></h3>
                                    <?php if ($instructor_bio): ?>
                                        <p class="waza-instructor-bio"><?php echo esc_html($instructor_bio); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Additional Notes -->
                        <?php if ($slot->notes): ?>
                        <div class="waza-slot-section">
                            <h2><?php _e('Important Information', 'waza-booking'); ?></h2>
                            <div class="waza-slot-notes">
                                <?php echo wpautop(esc_html($slot->notes)); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                    </div>
                    
                    <!-- Right Column - Booking Card -->
                    <div class="waza-slot-sidebar">
                        <div class="waza-booking-card">
                            <div class="waza-booking-card-header">
                                <?php if ($slot->price > 0): ?>
                                    <div class="waza-booking-price">
                                        <span class="waza-currency">₹</span>
                                        <span class="waza-amount"><?php echo number_format($slot->price, 0); ?></span>
                                        <span class="waza-per-person"><?php _e('per person', 'waza-booking'); ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="waza-booking-free">
                                        <?php _e('Free Session', 'waza-booking'); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="waza-booking-card-body">
                                <?php if ($is_past): ?>
                                    <div class="waza-slot-status waza-status-past">
                                        <?php _e('This session has already passed', 'waza-booking'); ?>
                                    </div>
                                <?php elseif ($is_full): ?>
                                    <div class="waza-slot-status waza-status-full">
                                        <?php _e('This session is fully booked', 'waza-booking'); ?>
                                    </div>
                                    <button class="waza-btn waza-btn-secondary waza-btn-block" id="waza-join-waitlist">
                                        <?php _e('Join Waitlist', 'waza-booking'); ?>
                                    </button>
                                <?php else: ?>
                                    <div class="waza-availability">
                                        <span class="waza-available-count"><?php echo $available_seats; ?></span>
                                        <?php _e('seats available', 'waza-booking'); ?>
                                    </div>
                                    
                                    <form id="waza-quick-booking-form">
                                        <input type="hidden" name="slot_id" value="<?php echo esc_attr($slot_id); ?>">
                                        
                                        <div class="waza-form-group">
                                            <label for="attendees"><?php _e('Number of People:', 'waza-booking'); ?></label>
                                            <select id="attendees" name="attendees" class="waza-form-control">
                                                <?php for ($i = 1; $i <= min(10, $available_seats); $i++): ?>
                                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        
                                        <?php if (!is_user_logged_in()): ?>
                                        <div class="waza-form-group">
                                            <label for="user_name"><?php _e('Your Name:', 'waza-booking'); ?></label>
                                            <input type="text" id="user_name" name="user_name" class="waza-form-control" required>
                                        </div>
                                        
                                        <div class="waza-form-group">
                                            <label for="user_email"><?php _e('Email:', 'waza-booking'); ?></label>
                                            <input type="email" id="user_email" name="user_email" class="waza-form-control" required>
                                        </div>
                                        
                                        <div class="waza-form-group">
                                            <label for="user_phone"><?php _e('Phone:', 'waza-booking'); ?></label>
                                            <input type="tel" id="user_phone" name="user_phone" class="waza-form-control" required>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <button type="submit" class="waza-btn waza-btn-primary waza-btn-block">
                                            <?php _e('Book Now', 'waza-booking'); ?>
                                        </button>
                                    </form>
                                    
                                    <div class="waza-booking-features">
                                        <div class="waza-feature">
                                            <span class="dashicons dashicons-yes"></span>
                                            <?php _e('Instant Confirmation', 'waza-booking'); ?>
                                        </div>
                                        <div class="waza-feature">
                                            <span class="dashicons dashicons-yes"></span>
                                            <?php _e('QR Code Entry', 'waza-booking'); ?>
                                        </div>
                                        <div class="waza-feature">
                                            <span class="dashicons dashicons-yes"></span>
                                            <?php _e('Email & SMS Reminders', 'waza-booking'); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
        
        <style>
        .waza-slot-detail-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .waza-slot-hero {
            background-size: cover;
            background-position: center;
            min-height: 300px;
            margin: -20px -20px 40px;
            position: relative;
        }
        
        .waza-slot-hero-overlay {
            background: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.7));
            min-height: 300px;
            display: flex;
            align-items: flex-end;
            padding: 40px;
        }
        
        .waza-slot-header {
            color: #fff;
        }
        
        .waza-slot-title {
            font-size: 36px;
            margin: 0 0 15px;
            font-weight: 700;
        }
        
        .waza-slot-meta {
            display: flex;
            gap: 25px;
            flex-wrap: wrap;
            font-size: 16px;
        }
        
        .waza-slot-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .waza-slot-content {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 40px;
            margin-bottom: 60px;
        }
        
        .waza-slot-section {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .waza-slot-section h2 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
        }
        
        .waza-slot-details-grid {
            display: grid;
            gap: 15px;
        }
        
        .waza-detail-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        
        .waza-detail-label {
            font-weight: 600;
            color: #666;
        }
        
        .waza-detail-value {
            color: #333;
            font-weight: 500;
        }
        
        .waza-detail-value.waza-price {
            font-size: 20px;
            color: #28a745;
            font-weight: 700;
        }
        
        .waza-instructor-card {
            display: flex;
            gap: 20px;
            align-items: start;
        }
        
        .waza-instructor-avatar img {
            border-radius: 50%;
        }
        
        .waza-instructor-name {
            margin: 0 0 10px;
            font-size: 20px;
        }
        
        .waza-instructor-bio {
            color: #666;
            line-height: 1.6;
        }
        
        .waza-booking-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            position: sticky;
            top: 20px;
        }
        
        .waza-booking-card-header {
            padding: 25px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }
        
        .waza-booking-price {
            font-size: 14px;
            color: #666;
        }
        
        .waza-amount {
            font-size: 42px;
            font-weight: 700;
            color: #333;
            display: block;
        }
        
        .waza-booking-free {
            font-size: 32px;
            font-weight: 700;
            color: #28a745;
        }
        
        .waza-booking-card-body {
            padding: 25px;
        }
        
        .waza-availability {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            background: #e8f5e9;
            border-radius: 6px;
        }
        
        .waza-available-count {
            font-size: 24px;
            font-weight: 700;
            color: #28a745;
        }
        
        .waza-form-group {
            margin-bottom: 20px;
        }
        
        .waza-form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .waza-form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
        }
        
        .waza-btn {
            padding: 14px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .waza-btn-primary {
            background: #007bff;
            color: #fff;
        }
        
        .waza-btn-primary:hover {
            background: #0056b3;
        }
        
        .waza-btn-block {
            width: 100%;
        }
        
        .waza-booking-features {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #eee;
        }
        
        .waza-feature {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            color: #666;
        }
        
        .waza-feature .dashicons {
            color: #28a745;
        }
        
        .waza-slot-status {
            padding: 20px;
            text-align: center;
            border-radius: 6px;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .waza-status-full {
            background: #fff3cd;
            color: #856404;
        }
        
        .waza-status-past {
            background: #f8d7da;
            color: #721c24;
        }
        
        @media (max-width: 992px) {
            .waza-slot-content {
                grid-template-columns: 1fr;
            }
            
            .waza-booking-card {
                position: static;
            }
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#waza-quick-booking-form').on('submit', function(e) {
                e.preventDefault();
                // Redirect to main booking flow
                var formData = $(this).serialize();
                window.location.href = '<?php echo home_url('/booking'); ?>?' + formData;
            });
        });
        </script>
        
        <?php
        get_footer();
    }
    
    /**
     * Slot details shortcode
     */
    public function slot_details_shortcode($atts) {
        $atts = shortcode_atts(['id' => ''], $atts);
        
        if (empty($atts['id'])) {
            return '<p>' . __('Please provide a valid slot ID.', 'waza-booking') . '</p>';
        }
        
        ob_start();
        $this->render_slot_detail_page($atts['id']);
        return ob_get_clean();
    }
    
    /**
     * Get slot details via AJAX
     */
    public function ajax_get_slot_details() {
        $slot_id = intval($_POST['slot_id'] ?? 0);
        
        if (!$slot_id) {
            wp_send_json_error(__('Invalid slot ID', 'waza-booking'));
        }
        
        global $wpdb;
        
        $slot = $wpdb->get_row($wpdb->prepare("
            SELECT s.*, 
                   p.post_title as activity_name,
                   u.display_name as instructor_name,
                   (SELECT COUNT(*) FROM {$wpdb->prefix}waza_bookings 
                    WHERE slot_id = s.id AND booking_status != 'cancelled') as booked_count
            FROM {$wpdb->prefix}waza_slots s
            LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
            LEFT JOIN {$wpdb->users} u ON s.instructor_id = u.ID
            WHERE s.id = %d
        ", $slot_id));
        
        if (!$slot) {
            wp_send_json_error(__('Slot not found', 'waza-booking'));
        }
        
        $slot->available_seats = $slot->capacity - $slot->booked_count;
        $slot->formatted_date = wp_date('F j, Y', strtotime($slot->start_datetime));
        $slot->formatted_time = wp_date('g:i A', strtotime($slot->start_datetime));
        
        wp_send_json_success($slot);
    }
}
