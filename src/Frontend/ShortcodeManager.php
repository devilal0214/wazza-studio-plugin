<?php
/**
 * Shortcode Manager
 *
 * Handles registration and rendering of plugin shortcodes.
 *
 * @package WazaBooking
 */

namespace WazaBooking\Frontend;

if (!defined('ABSPATH')) {
    exit;
}

class ShortcodeManager {
    
    /**
     * Initialize shortcodes
     */
    public function __construct() {
        add_action('init', [$this, 'register_shortcodes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
    }
    
    /**
     * Register all shortcodes
     */
    public function register_shortcodes() {
        // Activity shortcodes
        add_shortcode('waza_activities_list', [$this, 'activities_list_shortcode']);
        add_shortcode('waza_activity_grid', [$this, 'activity_grid_shortcode']);
        add_shortcode('waza_featured_activities', [$this, 'featured_activities_shortcode']);
        
        // Calendar and booking shortcodes
        add_shortcode('waza_booking_calendar', [$this, 'booking_calendar_shortcode']);
        add_shortcode('waza_booking_form', [$this, 'booking_form_shortcode']);
        add_shortcode('waza_booking_confirmation', [$this, 'booking_confirmation_shortcode']);
        
        // Instructor shortcodes
        add_shortcode('waza_instructors_list', [$this, 'instructors_list_shortcode']);
        add_shortcode('waza_instructor_profile', [$this, 'instructor_profile_shortcode']);
        
        // Search and filter shortcodes
        add_shortcode('waza_activity_search', [$this, 'activity_search_shortcode']);
        add_shortcode('waza_activity_filters', [$this, 'activity_filters_shortcode']);
        
        // My Bookings
        add_shortcode('waza_my_bookings', [$this, 'my_bookings_shortcode']);
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        global $post;
        
        // Check if any Waza shortcodes are present
        if (is_a($post, 'WP_Post') && $this->has_waza_shortcodes($post->post_content)) {
            $this->enqueue_assets();
        }
    }
    
    /**
     * Check if content has Waza shortcodes
     */
    private function has_waza_shortcodes($content) {
        $shortcodes = [
            'waza_activities_list',
            'waza_activity_grid',
            'waza_featured_activities',
            'waza_booking_calendar',
            'waza_booking_form',
            'waza_booking_confirmation',
            'waza_instructors_list',
            'waza_instructor_profile',
            'waza_activity_search',
            'waza_activity_filters',
            'waza_my_bookings'
        ];
        
        foreach ($shortcodes as $shortcode) {
            if (has_shortcode($content, $shortcode)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Enqueue CSS and JS assets
     */
    private function enqueue_assets() {
        // Styles
        wp_enqueue_style(
            'waza-frontend',
            WAZA_BOOKING_PLUGIN_URL . 'assets/frontend.css',
            [],
            WAZA_BOOKING_VERSION
        );
        
        // Scripts
        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'waza-frontend',
            WAZA_BOOKING_PLUGIN_URL . 'assets/frontend.js',
            ['jquery'],
            WAZA_BOOKING_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('waza-frontend', 'waza_frontend', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'api_url' => rest_url('waza/v1/'),
            'nonce' => wp_create_nonce('waza_frontend_nonce'),
            'current_user_id' => get_current_user_id(),
            'currency_symbol' => get_option('waza_booking_currency_symbol', '₹'),
            'date_format' => get_option('waza_booking_date_format', 'd/m/Y'),
            'time_format' => get_option('waza_booking_time_format', 'H:i'),
            'strings' => [
                'loading' => __('Loading...', 'waza-booking'),
                'select_slot' => __('Please select a time slot', 'waza-booking'),
                'booking_success' => __('Booking confirmed successfully!', 'waza-booking'),
                'booking_error' => __('Failed to process booking', 'waza-booking'),
                'login_required' => __('Please login to make a booking', 'waza-booking'),
                'confirm_cancel' => __('Are you sure you want to cancel this booking?', 'waza-booking')
            ]
        ]);
    }
    
    /**
     * Activities list shortcode
     */
    public function activities_list_shortcode($atts) {
        $atts = shortcode_atts([
            'limit' => 12,
            'category' => '',
            'instructor' => '',
            'featured' => false,
            'columns' => 3,
            'show_filters' => true,
            'show_search' => true
        ], $atts, 'waza_activities_list');
        
        ob_start();
        
        // Get activities
        $activities = $this->get_activities($atts);
        
        ?>
        <div class="waza-booking-frontend waza-activities-container" data-activity-id="">
            <?php if ($atts['show_search']): ?>
            <div class="waza-activities-header">
                <div class="waza-search-container">
                    <input type="text" class="waza-search-activities" placeholder="<?php esc_attr_e('Search activities...', 'waza-booking'); ?>">
                </div>
                <?php if ($atts['show_filters']): ?>
                <div class="waza-filters-container">
                    <select id="waza-activity-filter">
                        <option value="all"><?php esc_html_e('All Categories', 'waza-booking'); ?></option>
                        <option value="dance"><?php esc_html_e('Dance', 'waza-booking'); ?></option>
                        <option value="yoga"><?php esc_html_e('Yoga', 'waza-booking'); ?></option>
                        <option value="zumba"><?php esc_html_e('Zumba', 'waza-booking'); ?></option>
                        <option value="photography"><?php esc_html_e('Photography', 'waza-booking'); ?></option>
                        <option value="influencer"><?php esc_html_e('Influencer Showcase', 'waza-booking'); ?></option>
                    </select>
                    
                    <select id="waza-instructor-filter">
                        <option value="all"><?php esc_html_e('All Instructors', 'waza-booking'); ?></option>
                        <?php
                        $instructors = get_users(['role' => 'waza_instructor']);
                        foreach ($instructors as $instructor):
                        ?>
                        <option value="<?php echo esc_attr($instructor->ID); ?>">
                            <?php echo esc_html($instructor->display_name); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="waza-activities-grid" style="grid-template-columns: repeat(<?php echo esc_attr($atts['columns']); ?>, 1fr);">
                <?php if (!empty($activities)): ?>
                    <?php foreach ($activities as $activity): ?>
                        <?php $this->render_activity_card($activity); ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="waza-no-activities">
                        <h3><?php esc_html_e('No Activities Found', 'waza-booking'); ?></h3>
                        <p><?php esc_html_e('No activities are currently available. Please check back later.', 'waza-booking'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Activity grid shortcode
     */
    public function activity_grid_shortcode($atts) {
        return $this->activities_list_shortcode($atts);
    }
    
    /**
     * Featured activities shortcode
     */
    public function featured_activities_shortcode($atts) {
        $atts['featured'] = true;
        return $this->activities_list_shortcode($atts);
    }
    
    /**
     * Booking calendar shortcode
     */
    public function booking_calendar_shortcode($atts) {
        $atts = shortcode_atts([
            'activity_id' => get_query_var('activity_id', ''),
            'view' => 'month', // month, week, day
            'height' => '600px'
        ], $atts, 'waza_booking_calendar');
        
        ob_start();
        ?>
        <div class="waza-booking-frontend waza-calendar-container" data-activity-id="<?php echo esc_attr($atts['activity_id']); ?>">
            <div class="waza-calendar-header">
                <div class="waza-month-navigation">
                    <button class="waza-nav-button prev" aria-label="<?php esc_attr_e('Previous month', 'waza-booking'); ?>">‹</button>
                    <div class="waza-current-month"></div>
                    <button class="waza-nav-button next" aria-label="<?php esc_attr_e('Next month', 'waza-booking'); ?>">›</button>
                </div>
                <div class="waza-calendar-controls">
                    <select class="waza-calendar-view">
                        <option value="month" <?php selected($atts['view'], 'month'); ?>><?php esc_html_e('Month', 'waza-booking'); ?></option>
                        <option value="week" <?php selected($atts['view'], 'week'); ?>><?php esc_html_e('Week', 'waza-booking'); ?></option>
                        <option value="day" <?php selected($atts['view'], 'day'); ?>><?php esc_html_e('Day', 'waza-booking'); ?></option>
                    </select>
                </div>
            </div>
            
            <div class="waza-calendar-grid" style="min-height: <?php echo esc_attr($atts['height']); ?>;">
                <!-- Calendar will be loaded via JavaScript -->
            </div>
            
            <div class="waza-time-slots">
                <!-- Time slots will be loaded when a day is selected -->
            </div>
            
            <div id="waza-booking-form-container" style="display: none;">
                <!-- Booking form will be loaded when a slot is selected -->
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Booking form shortcode
     */
    public function booking_form_shortcode($atts) {
        $atts = shortcode_atts([
            'slot_id' => get_query_var('slot_id', ''),
            'activity_id' => get_query_var('activity_id', ''),
            'show_calendar' => true
        ], $atts, 'waza_booking_form');
        
        ob_start();
        ?>
        <div class="waza-booking-frontend waza-booking-container">
            <?php if ($atts['show_calendar']): ?>
            <div class="waza-booking-steps">
                <div class="waza-progress">
                    <div class="waza-progress-step active">1</div>
                    <div class="waza-progress-step">2</div>
                    <div class="waza-progress-step">3</div>
                </div>
            </div>
            
            <?php echo $this->booking_calendar_shortcode(['activity_id' => $atts['activity_id']]); ?>
            <?php endif; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Booking confirmation shortcode
     */
    public function booking_confirmation_shortcode($atts) {
        $booking_id = get_query_var('booking_id', '');
        
        if (!$booking_id) {
            return '<div class="waza-alert waza-alert-error">' . 
                   esc_html__('Invalid booking ID.', 'waza-booking') . 
                   '</div>';
        }
        
        ob_start();
        ?>
        <div class="waza-booking-frontend waza-confirmation-container">
            <div class="waza-confirmation-header">
                <h2><?php esc_html_e('Booking Confirmation', 'waza-booking'); ?></h2>
            </div>
            
            <div id="waza-booking-details" data-booking-id="<?php echo esc_attr($booking_id); ?>">
                <!-- Booking details will be loaded via JavaScript -->
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Instructors list shortcode
     */
    public function instructors_list_shortcode($atts) {
        $atts = shortcode_atts([
            'limit' => 12,
            'columns' => 3,
            'show_bio' => true,
            'show_activities' => true
        ], $atts, 'waza_instructors_list');
        
        ob_start();
        
        $instructors = get_users(['role' => 'waza_instructor', 'number' => $atts['limit']]);
        
        ?>
        <div class="waza-booking-frontend waza-instructors-container">
            <div class="waza-instructors-grid" style="grid-template-columns: repeat(<?php echo esc_attr($atts['columns']); ?>, 1fr);">
                <?php if (!empty($instructors)): ?>
                    <?php foreach ($instructors as $instructor): ?>
                        <?php $this->render_instructor_card($instructor, $atts); ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="waza-no-instructors">
                        <h3><?php esc_html_e('No Instructors Found', 'waza-booking'); ?></h3>
                        <p><?php esc_html_e('No instructors are currently available.', 'waza-booking'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Instructor profile shortcode
     */
    public function instructor_profile_shortcode($atts) {
        $atts = shortcode_atts([
            'instructor_id' => get_query_var('instructor_id', ''),
            'show_activities' => true,
            'show_schedule' => true
        ], $atts, 'waza_instructor_profile');
        
        if (!$atts['instructor_id']) {
            return '<div class="waza-alert waza-alert-error">' . 
                   esc_html__('Instructor ID is required.', 'waza-booking') . 
                   '</div>';
        }
        
        $instructor = get_user_by('id', $atts['instructor_id']);
        
        if (!$instructor || !in_array('waza_instructor', $instructor->roles)) {
            return '<div class="waza-alert waza-alert-error">' . 
                   esc_html__('Instructor not found.', 'waza-booking') . 
                   '</div>';
        }
        
        ob_start();
        ?>
        <div class="waza-booking-frontend waza-instructor-profile">
            <div class="waza-instructor-header">
                <div class="waza-instructor-avatar">
                    <?php echo get_avatar($instructor->ID, 150); ?>
                </div>
                <div class="waza-instructor-info">
                    <h1><?php echo esc_html($instructor->display_name); ?></h1>
                    <div class="waza-instructor-meta">
                        <?php 
                        $specializations = get_user_meta($instructor->ID, 'waza_specializations', true);
                        if ($specializations): 
                        ?>
                        <div class="waza-specializations">
                            <strong><?php esc_html_e('Specializations:', 'waza-booking'); ?></strong>
                            <?php echo esc_html($specializations); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php 
            $bio = get_user_meta($instructor->ID, 'description', true);
            if ($bio):
            ?>
            <div class="waza-instructor-bio">
                <h3><?php esc_html_e('About', 'waza-booking'); ?></h3>
                <p><?php echo wp_kses_post($bio); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($atts['show_activities']): ?>
            <div class="waza-instructor-activities">
                <h3><?php esc_html_e('Classes & Activities', 'waza-booking'); ?></h3>
                <?php echo $this->activities_list_shortcode(['instructor' => $instructor->ID, 'show_filters' => false]); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Activity search shortcode
     */
    public function activity_search_shortcode($atts) {
        ob_start();
        ?>
        <div class="waza-booking-frontend waza-search-container">
            <form class="waza-activity-search-form" method="get">
                <div class="waza-search-fields">
                    <input type="text" name="search" placeholder="<?php esc_attr_e('Search activities...', 'waza-booking'); ?>" 
                           value="<?php echo esc_attr(get_query_var('search', '')); ?>">
                    
                    <select name="category">
                        <option value=""><?php esc_html_e('All Categories', 'waza-booking'); ?></option>
                        <option value="dance" <?php selected(get_query_var('category'), 'dance'); ?>><?php esc_html_e('Dance', 'waza-booking'); ?></option>
                        <option value="yoga" <?php selected(get_query_var('category'), 'yoga'); ?>><?php esc_html_e('Yoga', 'waza-booking'); ?></option>
                        <option value="zumba" <?php selected(get_query_var('category'), 'zumba'); ?>><?php esc_html_e('Zumba', 'waza-booking'); ?></option>
                        <option value="photography" <?php selected(get_query_var('category'), 'photography'); ?>><?php esc_html_e('Photography', 'waza-booking'); ?></option>
                    </select>
                    
                    <button type="submit" class="waza-btn waza-btn-primary"><?php esc_html_e('Search', 'waza-booking'); ?></button>
                </div>
            </form>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Activity filters shortcode
     */
    public function activity_filters_shortcode($atts) {
        ob_start();
        ?>
        <div class="waza-booking-frontend waza-filters-widget">
            <h4><?php esc_html_e('Filter Activities', 'waza-booking'); ?></h4>
            
            <div class="waza-filter-group">
                <label><?php esc_html_e('Category', 'waza-booking'); ?></label>
                <select class="waza-filter" data-filter="category">
                    <option value="all"><?php esc_html_e('All Categories', 'waza-booking'); ?></option>
                    <option value="dance"><?php esc_html_e('Dance', 'waza-booking'); ?></option>
                    <option value="yoga"><?php esc_html_e('Yoga', 'waza-booking'); ?></option>
                    <option value="zumba"><?php esc_html_e('Zumba', 'waza-booking'); ?></option>
                    <option value="photography"><?php esc_html_e('Photography', 'waza-booking'); ?></option>
                </select>
            </div>
            
            <div class="waza-filter-group">
                <label><?php esc_html_e('Price Range', 'waza-booking'); ?></label>
                <select class="waza-filter" data-filter="price">
                    <option value="all"><?php esc_html_e('Any Price', 'waza-booking'); ?></option>
                    <option value="0-500"><?php esc_html_e('₹0 - ₹500', 'waza-booking'); ?></option>
                    <option value="500-1000"><?php esc_html_e('₹500 - ₹1000', 'waza-booking'); ?></option>
                    <option value="1000+"><?php esc_html_e('₹1000+', 'waza-booking'); ?></option>
                </select>
            </div>
            
            <div class="waza-filter-group">
                <label><?php esc_html_e('Duration', 'waza-booking'); ?></label>
                <select class="waza-filter" data-filter="duration">
                    <option value="all"><?php esc_html_e('Any Duration', 'waza-booking'); ?></option>
                    <option value="30"><?php esc_html_e('30 minutes', 'waza-booking'); ?></option>
                    <option value="60"><?php esc_html_e('1 hour', 'waza-booking'); ?></option>
                    <option value="90"><?php esc_html_e('1.5 hours', 'waza-booking'); ?></option>
                    <option value="120+"><?php esc_html_e('2+ hours', 'waza-booking'); ?></option>
                </select>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Get activities based on attributes
     */
    private function get_activities($atts) {
        $args = [
            'post_type' => 'waza_activity',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['limit']),
            'meta_query' => []
        ];
        
        if (!empty($atts['category'])) {
            $args['meta_query'][] = [
                'key' => '_waza_category',
                'value' => $atts['category'],
                'compare' => '='
            ];
        }
        
        if (!empty($atts['instructor'])) {
            $args['meta_query'][] = [
                'key' => '_waza_instructor_id',
                'value' => $atts['instructor'],
                'compare' => '='
            ];
        }
        
        if ($atts['featured']) {
            $args['meta_query'][] = [
                'key' => '_waza_featured',
                'value' => '1',
                'compare' => '='
            ];
        }
        
        return get_posts($args);
    }
    
    /**
     * Render activity card
     */
    private function render_activity_card($activity) {
        $category = get_post_meta($activity->ID, '_waza_category', true);
        $instructor_id = get_post_meta($activity->ID, '_waza_instructor_id', true);
        $instructor = get_user_by('id', $instructor_id);
        $price = get_post_meta($activity->ID, '_waza_price', true);
        $duration = get_post_meta($activity->ID, '_waza_duration', true);
        $featured_image = get_the_post_thumbnail_url($activity->ID, 'medium');
        
        ?>
        <div class="waza-activity-card" data-category="<?php echo esc_attr($category); ?>" 
             data-instructor="<?php echo esc_attr($instructor_id); ?>" data-activity-id="<?php echo esc_attr($activity->ID); ?>">
            <div class="waza-activity-image">
                <?php if ($featured_image): ?>
                    <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr($activity->post_title); ?>">
                <?php else: ?>
                    <span><?php echo esc_html(ucfirst($category)); ?></span>
                <?php endif; ?>
            </div>
            
            <div class="waza-activity-content">
                <h3 class="waza-activity-title"><?php echo esc_html($activity->post_title); ?></h3>
                <div class="waza-activity-description">
                    <?php echo wp_trim_words($activity->post_content, 20); ?>
                </div>
                
                <div class="waza-activity-meta">
                    <div class="waza-activity-price">
                        <?php echo get_option('waza_booking_currency_symbol', '₹') . number_format($price, 2); ?>
                    </div>
                    <div class="waza-activity-duration">
                        <?php echo esc_html($duration); ?> <?php esc_html_e('minutes', 'waza-booking'); ?>
                    </div>
                </div>
                
                <?php if ($instructor): ?>
                <div class="waza-activity-instructor">
                    <?php esc_html_e('with', 'waza-booking'); ?> <?php echo esc_html($instructor->display_name); ?>
                </div>
                <?php endif; ?>
                
                <div class="waza-activity-actions">
                    <a href="<?php echo esc_url(add_query_arg('activity_id', $activity->ID, get_permalink(get_option('waza_booking_calendar_page_id')))); ?>" 
                       class="waza-btn waza-btn-primary waza-btn-full">
                        <?php esc_html_e('Book Now', 'waza-booking'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render instructor card
     */
    private function render_instructor_card($instructor, $atts) {
        $specializations = get_user_meta($instructor->ID, 'waza_specializations', true);
        $bio = get_user_meta($instructor->ID, 'description', true);
        
        ?>
        <div class="waza-instructor-card">
            <div class="waza-instructor-avatar">
                <?php echo get_avatar($instructor->ID, 120); ?>
            </div>
            
            <div class="waza-instructor-content">
                <h4 class="waza-instructor-name"><?php echo esc_html($instructor->display_name); ?></h4>
                
                <?php if ($specializations): ?>
                <div class="waza-instructor-specializations">
                    <?php echo esc_html($specializations); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($atts['show_bio'] && $bio): ?>
                <div class="waza-instructor-bio">
                    <?php echo wp_trim_words($bio, 15); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($atts['show_activities']): ?>
                <div class="waza-instructor-activities-count">
                    <?php
                    $activity_count = $this->get_instructor_activity_count($instructor->ID);
                    printf(
                        _n('%d Activity', '%d Activities', $activity_count, 'waza-booking'),
                        $activity_count
                    );
                    ?>
                </div>
                <?php endif; ?>
                
                <div class="waza-instructor-actions">
                    <a href="<?php echo esc_url(add_query_arg('instructor_id', $instructor->ID, get_permalink())); ?>" 
                       class="waza-btn waza-btn-outline">
                        <?php esc_html_e('View Profile', 'waza-booking'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get instructor activity count
     */
    private function get_instructor_activity_count($instructor_id) {
        $args = [
            'post_type' => 'waza_activity',
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => '_waza_instructor_id',
                    'value' => $instructor_id,
                    'compare' => '='
                ]
            ]
        ];
        
        $query = new \WP_Query($args);
        return $query->found_posts;
    }

    /**
     * My Bookings shortcode
     */
    public function my_bookings_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="waza-alert waza-alert-info">' . 
                   sprintf(__('Please <a href="%s">login</a> to view your bookings.', 'waza-booking'), wp_login_url(get_permalink())) . 
                   '</div>';
        }
        
        ob_start();
        ?>
        <div class="waza-booking-frontend waza-my-bookings-container">
            <h3><?php esc_html_e('My Bookings', 'waza-booking'); ?></h3>
            
            <div class="waza-bookings-tabs">
                <button class="waza-tab-btn active" data-status="confirmed"><?php esc_html_e('Upcoming', 'waza-booking'); ?></button>
                <button class="waza-tab-btn" data-status="past"><?php esc_html_e('Past', 'waza-booking'); ?></button>
                <button class="waza-tab-btn" data-status="cancelled"><?php esc_html_e('Cancelled', 'waza-booking'); ?></button>
            </div>
            
            <div id="waza-user-bookings-list">
                <!-- Bookings loaded via JS -->
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
}