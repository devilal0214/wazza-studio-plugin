<?php
/**
 * Post Type Manager
 * 
 * @package WazaBooking\PostTypes
 */

namespace WazaBooking\PostTypes;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Post Type Manager Class
 * Handles registration of custom post types and taxonomies
 */
class PostTypeManager {
    
    /**
     * Initialize post types and taxonomies
     */
    public function init() {
        add_action('init', [$this, 'register_post_types']);
        add_action('init', [$this, 'register_taxonomies']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_boxes']);
    }
    
    /**
     * Register custom post types
     */
    public function register_post_types() {
        $this->register_activity_post_type();
        $this->register_slot_post_type();
        $this->register_booking_post_type();
        $this->register_instructor_post_type();
    }
    
    /**
     * Register Activity post type
     */
    private function register_activity_post_type() {
        $labels = [
            'name'               => __('Activities', 'waza-booking'),
            'singular_name'      => __('Activity', 'waza-booking'),
            'menu_name'          => __('Activities', 'waza-booking'),
            'add_new'            => __('Add New Activity', 'waza-booking'),
            'add_new_item'       => __('Add New Activity', 'waza-booking'),
            'edit_item'          => __('Edit Activity', 'waza-booking'),
            'new_item'           => __('New Activity', 'waza-booking'),
            'view_item'          => __('View Activity', 'waza-booking'),
            'search_items'       => __('Search Activities', 'waza-booking'),
            'not_found'          => __('No activities found', 'waza-booking'),
            'not_found_in_trash' => __('No activities found in trash', 'waza-booking')
        ];
        
        $args = [
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'query_var'           => true,
            'rewrite'             => ['slug' => 'activities'],
            'capability_type'     => 'post',
            'capabilities'        => [
                'edit_post'          => 'edit_waza_slots',
                'read_post'          => 'read',
                'delete_post'        => 'edit_waza_slots',
                'edit_posts'         => 'edit_waza_slots',
                'edit_others_posts'  => 'manage_waza',
                'delete_posts'       => 'manage_waza',
                'publish_posts'      => 'edit_waza_slots',
                'read_private_posts' => 'manage_waza'
            ],
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => null,
            'menu_icon'           => 'dashicons-admin-generic',
            'supports'            => ['title', 'editor', 'thumbnail', 'excerpt'],
            'show_in_rest'        => true,
            'rest_base'           => 'waza-activities'
        ];
        
        register_post_type('waza_activity', $args);
    }
    
    /**
     * Register Slot post type
     */
    private function register_slot_post_type() {
        $labels = [
            'name'               => __('Slots', 'waza-booking'),
            'singular_name'      => __('Slot', 'waza-booking'),
            'menu_name'          => __('Slots', 'waza-booking'),
            'add_new'            => __('Add New Slot', 'waza-booking'),
            'add_new_item'       => __('Add New Slot', 'waza-booking'),
            'edit_item'          => __('Edit Slot', 'waza-booking'),
            'new_item'           => __('New Slot', 'waza-booking'),
            'view_item'          => __('View Slot', 'waza-booking'),
            'search_items'       => __('Search Slots', 'waza-booking'),
            'not_found'          => __('No slots found', 'waza-booking'),
            'not_found_in_trash' => __('No slots found in trash', 'waza-booking')
        ];
        
        $args = [
            'labels'              => $labels,
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'query_var'           => true,
            'rewrite'             => false,
            'capability_type'     => 'post',
            'capabilities'        => [
                'edit_post'          => 'edit_waza_slots',
                'read_post'          => 'read',
                'delete_post'        => 'edit_waza_slots',
                'edit_posts'         => 'edit_waza_slots',
                'edit_others_posts'  => 'manage_waza',
                'delete_posts'       => 'manage_waza',
                'publish_posts'      => 'edit_waza_slots',
                'read_private_posts' => 'manage_waza'
            ],
            'has_archive'         => false,
            'hierarchical'        => false,
            'supports'            => ['title', 'editor'],
            'show_in_rest'        => true,
            'rest_base'           => 'waza-slots'
        ];
        
        register_post_type('waza_slot', $args);
    }
    
    /**
     * Register Booking post type (for admin reference)
     */
    private function register_booking_post_type() {
        $labels = [
            'name'               => __('Bookings', 'waza-booking'),
            'singular_name'      => __('Booking', 'waza-booking'),
            'menu_name'          => __('Bookings', 'waza-booking'),
            'edit_item'          => __('View Booking', 'waza-booking'),
            'view_item'          => __('View Booking', 'waza-booking'),
            'search_items'       => __('Search Bookings', 'waza-booking'),
            'not_found'          => __('No bookings found', 'waza-booking'),
            'not_found_in_trash' => __('No bookings found in trash', 'waza-booking')
        ];
        
        $args = [
            'labels'              => $labels,
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'query_var'           => false,
            'rewrite'             => false,
            'capability_type'     => 'post',
            'capabilities'        => [
                'create_posts'       => 'do_not_allow',
                'edit_post'          => 'view_waza_bookings',
                'read_post'          => 'view_waza_bookings',
                'delete_post'        => 'manage_waza',
                'edit_posts'         => 'view_waza_bookings',
                'edit_others_posts'  => 'view_waza_bookings',
                'delete_posts'       => 'manage_waza',
                'publish_posts'      => 'do_not_allow',
                'read_private_posts' => 'view_waza_bookings'
            ],
            'has_archive'         => false,
            'hierarchical'        => false,
            'supports'            => ['title'],
            'show_in_rest'        => false
        ];
        
        register_post_type('waza_booking', $args);
    }
    
    /**
     * Register Instructor post type
     */
    private function register_instructor_post_type() {
        $labels = [
            'name'               => __('Instructors', 'waza-booking'),
            'singular_name'      => __('Instructor', 'waza-booking'),
            'menu_name'          => __('Instructors', 'waza-booking'),
            'add_new'            => __('Add New Instructor', 'waza-booking'),
            'add_new_item'       => __('Add New Instructor', 'waza-booking'),
            'edit_item'          => __('Edit Instructor', 'waza-booking'),
            'new_item'           => __('New Instructor', 'waza-booking'),
            'view_item'          => __('View Instructor', 'waza-booking'),
            'search_items'       => __('Search Instructors', 'waza-booking'),
            'not_found'          => __('No instructors found', 'waza-booking'),
            'not_found_in_trash' => __('No instructors found in trash', 'waza-booking')
        ];
        
        $args = [
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'query_var'           => true,
            'rewrite'             => ['slug' => 'instructors'],
            'capability_type'     => 'post',
            'capabilities'        => [
                'edit_post'          => 'manage_waza_instructors',
                'read_post'          => 'read',
                'delete_post'        => 'manage_waza_instructors',
                'edit_posts'         => 'manage_waza_instructors',
                'edit_others_posts'  => 'manage_waza',
                'delete_posts'       => 'manage_waza',
                'publish_posts'      => 'manage_waza_instructors',
                'read_private_posts' => 'manage_waza'
            ],
            'has_archive'         => true,
            'hierarchical'        => false,
            'supports'            => ['title', 'editor', 'thumbnail', 'excerpt'],
            'show_in_rest'        => true,
            'rest_base'           => 'waza-instructors'
        ];
        
        register_post_type('waza_instructor', $args);
    }
    
    /**
     * Register taxonomies
     */
    public function register_taxonomies() {
        // Activity Categories
        register_taxonomy('waza_activity_category', 'waza_activity', [
            'labels' => [
                'name'          => __('Activity Categories', 'waza-booking'),
                'singular_name' => __('Activity Category', 'waza-booking'),
            ],
            'public'            => true,
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_in_rest'      => true,
        ]);
        
        // Instructor Specialties
        register_taxonomy('waza_instructor_specialty', 'waza_instructor', [
            'labels' => [
                'name'          => __('Specialties', 'waza-booking'),
                'singular_name' => __('Specialty', 'waza-booking'),
            ],
            'public'            => true,
            'hierarchical'      => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_in_rest'      => true,
        ]);
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        // Slot meta boxes
        add_meta_box(
            'waza_slot_details',
            __('Slot Details', 'waza-booking'),
            [$this, 'slot_details_meta_box'],
            'waza_slot',
            'normal',
            'high'
        );
        
        // Activity meta boxes
        add_meta_box(
            'waza_activity_details',
            __('Activity Details', 'waza-booking'),
            [$this, 'activity_details_meta_box'],
            'waza_activity',
            'normal',
            'high'
        );
        
        // Instructor meta boxes
        add_meta_box(
            'waza_instructor_details',
            __('Instructor Details', 'waza-booking'),
            [$this, 'instructor_details_meta_box'],
            'waza_instructor',
            'normal',
            'high'
        );
        
        // Booking meta boxes
        add_meta_box(
            'waza_booking_details',
            __('Booking Details', 'waza-booking'),
            [$this, 'booking_details_meta_box'],
            'waza_booking',
            'normal',
            'high'
        );
    }
    
    /**
     * Slot details meta box
     */
    public function slot_details_meta_box($post) {
        wp_nonce_field('waza_slot_meta_box', 'waza_slot_meta_box_nonce');
        
        $activity_id = get_post_meta($post->ID, '_waza_activity_id', true);
        $instructor_id = get_post_meta($post->ID, '_waza_instructor_id', true);
        $start_date = get_post_meta($post->ID, '_waza_start_date', true);
        $start_time = get_post_meta($post->ID, '_waza_start_time', true);
        $end_time = get_post_meta($post->ID, '_waza_end_time', true);
        $capacity = get_post_meta($post->ID, '_waza_capacity', true);
        $price = get_post_meta($post->ID, '_waza_price', true);
        $room = get_post_meta($post->ID, '_waza_room', true);
        $recurring = get_post_meta($post->ID, '_waza_recurring', true);
        $recurring_type = get_post_meta($post->ID, '_waza_recurring_type', true);
        $recurring_end = get_post_meta($post->ID, '_waza_recurring_end', true);
        
        $activities = get_posts(['post_type' => 'waza_activity', 'numberposts' => -1]);
        $instructors = get_posts(['post_type' => 'waza_instructor', 'numberposts' => -1]);
        
        echo '<table class="form-table">';
        
        // Activity selection
        echo '<tr>';
        echo '<th><label for="waza_activity_id">' . esc_html__('Activity', 'waza-booking') . '</label></th>';
        echo '<td>';
        echo '<select id="waza_activity_id" name="waza_activity_id" required>';
        echo '<option value="">' . esc_html__('Select Activity', 'waza-booking') . '</option>';
        foreach ($activities as $activity) {
            $selected = selected($activity_id, $activity->ID, false);
            echo '<option value="' . esc_attr($activity->ID) . '" ' . $selected . '>' . esc_html($activity->post_title) . '</option>';
        }
        echo '</select>';
        echo '</td>';
        echo '</tr>';
        
        // Instructor selection
        echo '<tr>';
        echo '<th><label for="waza_instructor_id">' . esc_html__('Instructor', 'waza-booking') . '</label></th>';
        echo '<td>';
        echo '<select id="waza_instructor_id" name="waza_instructor_id">';
        echo '<option value="">' . esc_html__('Select Instructor', 'waza-booking') . '</option>';
        foreach ($instructors as $instructor) {
            $selected = selected($instructor_id, $instructor->ID, false);
            echo '<option value="' . esc_attr($instructor->ID) . '" ' . $selected . '>' . esc_html($instructor->post_title) . '</option>';
        }
        echo '</select>';
        echo '</td>';
        echo '</tr>';
        
        // Date and time
        echo '<tr>';
        echo '<th><label for="waza_start_date">' . esc_html__('Start Date', 'waza-booking') . '</label></th>';
        echo '<td><input type="date" id="waza_start_date" name="waza_start_date" value="' . esc_attr($start_date) . '" required /></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="waza_start_time">' . esc_html__('Start Time', 'waza-booking') . '</label></th>';
        echo '<td><input type="time" id="waza_start_time" name="waza_start_time" value="' . esc_attr($start_time) . '" required /></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="waza_end_time">' . esc_html__('End Time', 'waza-booking') . '</label></th>';
        echo '<td><input type="time" id="waza_end_time" name="waza_end_time" value="' . esc_attr($end_time) . '" required /></td>';
        echo '</tr>';
        
        // Capacity and price
        echo '<tr>';
        echo '<th><label for="waza_capacity">' . esc_html__('Capacity', 'waza-booking') . '</label></th>';
        echo '<td><input type="number" id="waza_capacity" name="waza_capacity" value="' . esc_attr($capacity) . '" min="1" required /></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="waza_price">' . esc_html__('Price (₹)', 'waza-booking') . '</label></th>';
        echo '<td><input type="number" id="waza_price" name="waza_price" value="' . esc_attr($price) . '" min="0" step="0.01" required /></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="waza_room">' . esc_html__('Room/Location', 'waza-booking') . '</label></th>';
        echo '<td><input type="text" id="waza_room" name="waza_room" value="' . esc_attr($room) . '" /></td>';
        echo '</tr>';
        
        // Recurring options
        echo '<tr>';
        echo '<th><label for="waza_recurring">' . esc_html__('Recurring', 'waza-booking') . '</label></th>';
        echo '<td><input type="checkbox" id="waza_recurring" name="waza_recurring" value="1" ' . checked($recurring, '1', false) . ' /></td>';
        echo '</tr>';
        
        echo '<tr class="waza-recurring-options" ' . ($recurring ? '' : 'style="display:none;"') . '>';
        echo '<th><label for="waza_recurring_type">' . esc_html__('Recurring Type', 'waza-booking') . '</label></th>';
        echo '<td>';
        echo '<select id="waza_recurring_type" name="waza_recurring_type">';
        echo '<option value="daily" ' . selected($recurring_type, 'daily', false) . '>' . esc_html__('Daily', 'waza-booking') . '</option>';
        echo '<option value="weekly" ' . selected($recurring_type, 'weekly', false) . '>' . esc_html__('Weekly', 'waza-booking') . '</option>';
        echo '<option value="monthly" ' . selected($recurring_type, 'monthly', false) . '>' . esc_html__('Monthly', 'waza-booking') . '</option>';
        echo '</select>';
        echo '</td>';
        echo '</tr>';
        
        echo '<tr class="waza-recurring-options" ' . ($recurring ? '' : 'style="display:none;"') . '>';
        echo '<th><label for="waza_recurring_end">' . esc_html__('Recurring End Date', 'waza-booking') . '</label></th>';
        echo '<td><input type="date" id="waza_recurring_end" name="waza_recurring_end" value="' . esc_attr($recurring_end) . '" /></td>';
        echo '</tr>';
        
        echo '</table>';
        
        // Add JavaScript for recurring toggle
        echo '<script>
        document.getElementById("waza_recurring").addEventListener("change", function() {
            var recurringOptions = document.querySelectorAll(".waza-recurring-options");
            if (this.checked) {
                recurringOptions.forEach(function(row) { row.style.display = ""; });
            } else {
                recurringOptions.forEach(function(row) { row.style.display = "none"; });
            }
        });
        </script>';
    }
    
    /**
     * Activity details meta box
     */
    public function activity_details_meta_box($post) {
        wp_nonce_field('waza_activity_meta_box', 'waza_activity_meta_box_nonce');
        
        $duration = get_post_meta($post->ID, '_waza_duration', true);
        $skill_level = get_post_meta($post->ID, '_waza_skill_level', true);
        $equipment_required = get_post_meta($post->ID, '_waza_equipment_required', true);
        $max_participants = get_post_meta($post->ID, '_waza_max_participants', true);
        
        echo '<table class="form-table">';
        
        echo '<tr>';
        echo '<th><label for="waza_duration">' . esc_html__('Duration (minutes)', 'waza-booking') . '</label></th>';
        echo '<td><input type="number" id="waza_duration" name="waza_duration" value="' . esc_attr($duration) . '" min="1" /></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="waza_skill_level">' . esc_html__('Skill Level', 'waza-booking') . '</label></th>';
        echo '<td>';
        echo '<select id="waza_skill_level" name="waza_skill_level">';
        echo '<option value="beginner" ' . selected($skill_level, 'beginner', false) . '>' . esc_html__('Beginner', 'waza-booking') . '</option>';
        echo '<option value="intermediate" ' . selected($skill_level, 'intermediate', false) . '>' . esc_html__('Intermediate', 'waza-booking') . '</option>';
        echo '<option value="advanced" ' . selected($skill_level, 'advanced', false) . '>' . esc_html__('Advanced', 'waza-booking') . '</option>';
        echo '<option value="all" ' . selected($skill_level, 'all', false) . '>' . esc_html__('All Levels', 'waza-booking') . '</option>';
        echo '</select>';
        echo '</td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="waza_equipment_required">' . esc_html__('Equipment Required', 'waza-booking') . '</label></th>';
        echo '<td><textarea id="waza_equipment_required" name="waza_equipment_required" rows="3">' . esc_textarea($equipment_required) . '</textarea></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="waza_max_participants">' . esc_html__('Default Max Participants', 'waza-booking') . '</label></th>';
        echo '<td><input type="number" id="waza_max_participants" name="waza_max_participants" value="' . esc_attr($max_participants) . '" min="1" /></td>';
        echo '</tr>';
        
        echo '</table>';
    }
    
    /**
     * Instructor details meta box
     */
    public function instructor_details_meta_box($post) {
        wp_nonce_field('waza_instructor_meta_box', 'waza_instructor_meta_box_nonce');
        
        $email = get_post_meta($post->ID, '_waza_email', true);
        $phone = get_post_meta($post->ID, '_waza_phone', true);
        $bio = get_post_meta($post->ID, '_waza_bio', true);
        $experience = get_post_meta($post->ID, '_waza_experience', true);
        $certifications = get_post_meta($post->ID, '_waza_certifications', true);
        $hourly_rate = get_post_meta($post->ID, '_waza_hourly_rate', true);
        
        echo '<table class="form-table">';
        
        echo '<tr>';
        echo '<th><label for="waza_email">' . esc_html__('Email', 'waza-booking') . '</label></th>';
        echo '<td><input type="email" id="waza_email" name="waza_email" value="' . esc_attr($email) . '" /></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="waza_phone">' . esc_html__('Phone', 'waza-booking') . '</label></th>';
        echo '<td><input type="tel" id="waza_phone" name="waza_phone" value="' . esc_attr($phone) . '" /></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="waza_bio">' . esc_html__('Bio', 'waza-booking') . '</label></th>';
        echo '<td><textarea id="waza_bio" name="waza_bio" rows="4">' . esc_textarea($bio) . '</textarea></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="waza_experience">' . esc_html__('Experience (years)', 'waza-booking') . '</label></th>';
        echo '<td><input type="number" id="waza_experience" name="waza_experience" value="' . esc_attr($experience) . '" min="0" /></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="waza_certifications">' . esc_html__('Certifications', 'waza-booking') . '</label></th>';
        echo '<td><textarea id="waza_certifications" name="waza_certifications" rows="3">' . esc_textarea($certifications) . '</textarea></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="waza_hourly_rate">' . esc_html__('Hourly Rate (₹)', 'waza-booking') . '</label></th>';
        echo '<td><input type="number" id="waza_hourly_rate" name="waza_hourly_rate" value="' . esc_attr($hourly_rate) . '" min="0" step="0.01" /></td>';
        echo '</tr>';
        
        echo '</table>';
    }
    
    /**
     * Booking details meta box
     */
    public function booking_details_meta_box($post) {
        // This will show booking details from custom table
        global $wpdb;
        
        $booking_id = get_post_meta($post->ID, '_waza_booking_id', true);
        
        if (!$booking_id) {
            echo '<p>' . esc_html__('No booking data found.', 'waza-booking') . '</p>';
            return;
        }
        
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_bookings WHERE id = %d
        ", $booking_id));
        
        if (!$booking) {
            echo '<p>' . esc_html__('Booking not found.', 'waza-booking') . '</p>';
            return;
        }
        
        echo '<table class="form-table">';
        echo '<tr><th>' . esc_html__('Booking ID', 'waza-booking') . '</th><td>' . esc_html($booking->id) . '</td></tr>';
        echo '<tr><th>' . esc_html__('User Email', 'waza-booking') . '</th><td>' . esc_html($booking->user_email) . '</td></tr>';
        echo '<tr><th>' . esc_html__('User Name', 'waza-booking') . '</th><td>' . esc_html($booking->user_name) . '</td></tr>';
        echo '<tr><th>' . esc_html__('Phone', 'waza-booking') . '</th><td>' . esc_html($booking->user_phone) . '</td></tr>';
        echo '<tr><th>' . esc_html__('Attendees', 'waza-booking') . '</th><td>' . esc_html($booking->attendees_count) . '</td></tr>';
        echo '<tr><th>' . esc_html__('Total Amount', 'waza-booking') . '</th><td>₹' . esc_html($booking->total_amount) . '</td></tr>';
        echo '<tr><th>' . esc_html__('Payment Status', 'waza-booking') . '</th><td>' . esc_html($booking->payment_status) . '</td></tr>';
        echo '<tr><th>' . esc_html__('Booking Status', 'waza-booking') . '</th><td>' . esc_html($booking->booking_status) . '</td></tr>';
        echo '<tr><th>' . esc_html__('Attended', 'waza-booking') . '</th><td>' . ($booking->attended ? esc_html__('Yes', 'waza-booking') : esc_html__('No', 'waza-booking')) . '</td></tr>';
        echo '<tr><th>' . esc_html__('Created', 'waza-booking') . '</th><td>' . esc_html($booking->created_at) . '</td></tr>';
        echo '</table>';
        
        // Add action buttons
        echo '<div class="waza-booking-actions" style="margin-top: 20px;">';
        echo '<button type="button" class="button button-primary" onclick="wazaCancelBooking(' . esc_attr($booking->id) . ')">' . esc_html__('Cancel Booking', 'waza-booking') . '</button>';
        echo ' ';
        echo '<button type="button" class="button" onclick="wazaRefundBooking(' . esc_attr($booking->id) . ')">' . esc_html__('Process Refund', 'waza-booking') . '</button>';
        echo '</div>';
    }
    
    /**
     * Save meta box data
     */
    public function save_meta_boxes($post_id) {
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check user permissions
        if (isset($_POST['post_type'])) {
            if ($_POST['post_type'] === 'waza_slot' && !current_user_can('edit_waza_slots', $post_id)) {
                return;
            }
            if ($_POST['post_type'] === 'waza_activity' && !current_user_can('edit_post', $post_id)) {
                return;
            }
            if ($_POST['post_type'] === 'waza_instructor' && !current_user_can('manage_waza_instructors', $post_id)) {
                return;
            }
        }
        
        // Save slot meta
        if (isset($_POST['waza_slot_meta_box_nonce']) && wp_verify_nonce($_POST['waza_slot_meta_box_nonce'], 'waza_slot_meta_box')) {
            $this->save_slot_meta($post_id);
        }
        
        // Save activity meta
        if (isset($_POST['waza_activity_meta_box_nonce']) && wp_verify_nonce($_POST['waza_activity_meta_box_nonce'], 'waza_activity_meta_box')) {
            $this->save_activity_meta($post_id);
        }
        
        // Save instructor meta
        if (isset($_POST['waza_instructor_meta_box_nonce']) && wp_verify_nonce($_POST['waza_instructor_meta_box_nonce'], 'waza_instructor_meta_box')) {
            $this->save_instructor_meta($post_id);
        }
    }
    
    /**
     * Save slot meta data
     */
    private function save_slot_meta($post_id) {
        $fields = [
            'waza_activity_id',
            'waza_instructor_id',
            'waza_start_date',
            'waza_start_time',
            'waza_end_time',
            'waza_capacity',
            'waza_price',
            'waza_room',
            'waza_recurring',
            'waza_recurring_type',
            'waza_recurring_end'
        ];
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        // Initialize booked seats to 0 if not set
        $booked_seats = get_post_meta($post_id, '_waza_booked_seats', true);
        if ($booked_seats === '') {
            update_post_meta($post_id, '_waza_booked_seats', 0);
        }
        
        // Handle recurring slot creation
        if (isset($_POST['waza_recurring']) && $_POST['waza_recurring'] === '1') {
            $this->create_recurring_slots($post_id);
        }
    }
    
    /**
     * Save activity meta data
     */
    private function save_activity_meta($post_id) {
        $fields = [
            'waza_duration',
            'waza_skill_level',
            'waza_equipment_required',
            'waza_max_participants'
        ];
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                if ($field === 'waza_equipment_required') {
                    update_post_meta($post_id, '_' . $field, sanitize_textarea_field($_POST[$field]));
                } else {
                    update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
                }
            }
        }
    }
    
    /**
     * Save instructor meta data
     */
    private function save_instructor_meta($post_id) {
        $fields = [
            'waza_email' => 'sanitize_email',
            'waza_phone' => 'sanitize_text_field',
            'waza_bio' => 'sanitize_textarea_field',
            'waza_experience' => 'sanitize_text_field',
            'waza_certifications' => 'sanitize_textarea_field',
            'waza_hourly_rate' => 'sanitize_text_field'
        ];
        
        foreach ($fields as $field => $sanitize_func) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, $sanitize_func($_POST[$field]));
            }
        }
    }
    
    /**
     * Create recurring slots
     */
    private function create_recurring_slots($parent_slot_id) {
        $recurring_type = get_post_meta($parent_slot_id, '_waza_recurring_type', true);
        $recurring_end = get_post_meta($parent_slot_id, '_waza_recurring_end', true);
        $start_date = get_post_meta($parent_slot_id, '_waza_start_date', true);
        
        if (!$recurring_type || !$recurring_end || !$start_date) {
            return;
        }
        
        $start = new \DateTime($start_date);
        $end = new \DateTime($recurring_end);
        $interval = null;
        
        switch ($recurring_type) {
            case 'daily':
                $interval = new \DateInterval('P1D');
                break;
            case 'weekly':
                $interval = new \DateInterval('P1W');
                break;
            case 'monthly':
                $interval = new \DateInterval('P1M');
                break;
        }
        
        if (!$interval) {
            return;
        }
        
        $current = clone $start;
        $current->add($interval); // Skip the parent slot date
        
        while ($current <= $end) {
            $this->create_recurring_slot($parent_slot_id, $current->format('Y-m-d'));
            $current->add($interval);
        }
    }
    
    /**
     * Create a recurring slot
     */
    private function create_recurring_slot($parent_slot_id, $new_date) {
        $parent_slot = get_post($parent_slot_id);
        
        $new_slot_data = [
            'post_title'   => $parent_slot->post_title . ' - ' . $new_date,
            'post_content' => $parent_slot->post_content,
            'post_status'  => 'publish',
            'post_type'    => 'waza_slot',
            'post_author'  => $parent_slot->post_author,
        ];
        
        $new_slot_id = wp_insert_post($new_slot_data);
        
        if (!is_wp_error($new_slot_id)) {
            // Copy all meta data from parent
            $meta_keys = [
                '_waza_activity_id',
                '_waza_instructor_id',
                '_waza_start_time',
                '_waza_end_time',
                '_waza_capacity',
                '_waza_price',
                '_waza_room'
            ];
            
            foreach ($meta_keys as $meta_key) {
                $meta_value = get_post_meta($parent_slot_id, $meta_key, true);
                if ($meta_value !== '') {
                    update_post_meta($new_slot_id, $meta_key, $meta_value);
                }
            }
            
            // Set the new date and initialize booked seats
            update_post_meta($new_slot_id, '_waza_start_date', $new_date);
            update_post_meta($new_slot_id, '_waza_booked_seats', 0);
            update_post_meta($new_slot_id, '_waza_parent_slot', $parent_slot_id);
        }
    }
}