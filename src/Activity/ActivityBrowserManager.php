<?php
/**
 * Activity Browser Manager
 * 
 * Handles activity browsing, filtering, and slot selection
 * 
 * @package WazaBooking\Activity
 */

namespace WazaBooking\Activity;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ActivityBrowserManager {
    
    public function __construct() {
        // Shortcodes
        add_shortcode('waza_activity_browser', [$this, 'activity_browser_shortcode']);
        add_shortcode('waza_activity_slots', [$this, 'activity_slots_shortcode']);
        
        // AJAX handlers
        add_action('wp_ajax_waza_get_activity_slots', [$this, 'get_activity_slots']);
        add_action('wp_ajax_nopriv_waza_get_activity_slots', [$this, 'get_activity_slots']);
        add_action('wp_ajax_waza_filter_activities', [$this, 'filter_activities']);
        add_action('wp_ajax_nopriv_waza_filter_activities', [$this, 'filter_activities']);
    }
    
    /**
     * Activity browser shortcode
     */
    public function activity_browser_shortcode($atts) {
        $atts = shortcode_atts([
            'per_page' => 12,
            'show_filters' => 'yes'
        ], $atts);
        
        ob_start();
        include WAZA_BOOKING_PLUGIN_DIR . 'templates/activity-browser.php';
        return ob_get_clean();
    }
    
    /**
     * Activity slots selection shortcode
     */
    public function activity_slots_shortcode($atts) {
        $atts = shortcode_atts([
            'activity_id' => get_query_var('activity_id', 0)
        ], $atts);
        
        ob_start();
        include WAZA_BOOKING_PLUGIN_DIR . 'templates/activity-slots.php';
        return ob_get_clean();
    }
    
    /**
     * Get slots for specific activity
     */
    public function get_activity_slots() {
        check_ajax_referer('waza_booking_nonce', 'nonce');
        
        $activity_id = intval($_POST['activity_id']);
        $selected_date = sanitize_text_field($_POST['selected_date'] ?? date('Y-m-d'));
        
        if (!$activity_id) {
            wp_send_json_error(['message' => __('Invalid activity', 'waza-booking')]);
        }
        
        global $wpdb;
        
        // Debug logging
        error_log("Waza: Loading slots for activity_id=$activity_id, date=$selected_date");
        
        // Get available slots for this activity
        $slots = $wpdb->get_results($wpdb->prepare("
            SELECT 
                s.id,
                s.instructor_id,
                s.start_datetime,
                s.end_datetime,
                s.capacity,
                s.price,
                s.booked_count,
                s.status,
                i.post_title as instructor_name
            FROM {$wpdb->prefix}waza_slots s
            LEFT JOIN {$wpdb->posts} i ON s.instructor_id = i.ID
            WHERE s.activity_id = %d
            AND DATE(s.start_datetime) = %s
            AND s.status IN ('active', 'available')
            AND s.booked_count < s.capacity
            ORDER BY s.start_datetime ASC
        ", $activity_id, $selected_date));
        
        // Debug logging
        error_log("Waza: Found " . count($slots) . " slots. Query: " . $wpdb->last_query);
        if ($wpdb->last_error) {
            error_log("Waza: DB Error: " . $wpdb->last_error);
        }
        
        if (empty($slots)) {
            wp_send_json_error(['message' => __('No available slots for this date', 'waza-booking'), 'debug' => ['activity_id' => $activity_id, 'date' => $selected_date, 'query' => $wpdb->last_query]]);
        }
        
        $formatted_slots = array_map(function($slot) {
            return [
                'id' => $slot->id,
                'instructor_name' => $slot->instructor_name ?: 'TBA',
                'start_time' => date('g:i A', strtotime($slot->start_datetime)),
                'end_time' => date('g:i A', strtotime($slot->end_datetime)),
                'available_spots' => $slot->capacity - $slot->booked_count,
                'max_participants' => $slot->capacity,
                'price' => $slot->price
            ];
        }, $slots);
        
        wp_send_json_success(['slots' => $formatted_slots]);
    }
    
    /**
     * Filter activities based on criteria
     */
    public function filter_activities() {
        // No nonce check - this is a public filter function
        
        $category = sanitize_text_field($_POST['category'] ?? '');
        $search = sanitize_text_field($_POST['search'] ?? '');
        $sort = sanitize_text_field($_POST['sort'] ?? 'popular');
        
        $args = [
            'post_type' => 'waza_activity',
            'post_status' => 'publish',
            'posts_per_page' => 12,
            'paged' => intval($_POST['paged'] ?? 1)
        ];
        
        if ($category) {
            $args['tax_query'] = [[
                'taxonomy' => 'waza_instructor_specialty',
                'field' => 'slug',
                'terms' => $category
            ]];
        }
        
        if ($search) {
            $args['s'] = $search;
        }
        
        // Sorting
        switch ($sort) {
            case 'price_low':
                $args['meta_key'] = '_waza_price';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'ASC';
                break;
            case 'price_high':
                $args['meta_key'] = '_waza_price';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
            case 'newest':
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;
            default: // popular
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
        }
        
        $query = new \WP_Query($args);
        
        $activities = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $activities[] = $this->format_activity_data(get_the_ID());
            }
            wp_reset_postdata();
        }
        
        wp_send_json_success([
            'activities' => $activities,
            'total_pages' => $query->max_num_pages,
            'current_page' => $args['paged']
        ]);
    }
    
    /**
     * Format activity data for response
     */
    private function format_activity_data($activity_id) {
        global $wpdb;
        // Get actual booking count from slots
        $booking_count = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(booked_count) FROM {$wpdb->prefix}waza_slots 
            WHERE activity_id = %d
        ", $activity_id)) ?: 0;
        
        return [
            'id' => $activity_id,
            'title' => get_the_title($activity_id),
            'description' => wp_trim_words(get_the_content(null, false, $activity_id), 20),
            'thumbnail' => get_the_post_thumbnail_url($activity_id, 'medium'),
            'price' => get_post_meta($activity_id, '_waza_price', true),
            'duration' => get_post_meta($activity_id, '_waza_duration', true),
            'category' => $this->get_activity_category($activity_id),
            'rating' => get_post_meta($activity_id, '_waza_rating', true) ?: '0',
            'booking_count' => $booking_count,
            'permalink' => add_query_arg('activity_id', $activity_id, home_url('/activity-booking/'))
        ];
    }
    
    /**
     * Get activity category
     */
    private function get_activity_category($activity_id) {
        $terms = get_the_terms($activity_id, 'waza_instructor_specialty');
        return !empty($terms) ? $terms[0]->name : 'General';
    }
}
