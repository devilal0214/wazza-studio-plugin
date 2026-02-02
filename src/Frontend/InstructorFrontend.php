<?php
/**
 * Instructor Frontend Manager
 * 
 * Handles instructor registration, dashboard, and workshop management
 * 
 * @package WazaBooking\Frontend
 */

namespace WazaBooking\Frontend;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class InstructorFrontend {
    
    public function __construct() {
        // Shortcodes
        add_shortcode('waza_instructor_register', [$this, 'registration_form']);
        add_shortcode('waza_instructor_dashboard', [$this, 'instructor_dashboard']);
        
        // AJAX handlers
        add_action('wp_ajax_nopriv_waza_submit_instructor_application', [$this, 'submit_application']);
        add_action('wp_ajax_waza_submit_instructor_application', [$this, 'submit_application']);
        add_action('wp_ajax_waza_get_instructor_workshops', [$this, 'get_workshops']);
        add_action('wp_ajax_waza_create_workshop', [$this, 'create_workshop']);
        add_action('wp_ajax_waza_get_workshop_qr', [$this, 'get_workshop_qr']);
        add_action('wp_ajax_waza_get_workshop_students', [$this, 'get_workshop_students']);
        add_action('wp_ajax_waza_get_instructor_overview', [$this, 'get_instructor_overview']);
        add_action('wp_ajax_waza_get_instructor_schedule', [$this, 'get_instructor_schedule']);
        add_action('wp_ajax_waza_get_instructor_students', [$this, 'get_instructor_students']);
        add_action('wp_ajax_waza_get_workshop_students', [$this, 'get_workshop_students']);
        add_action('wp_ajax_waza_request_workshop_cancellation', [$this, 'request_workshop_cancellation']);

        // Enqueue scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_scripts() {
        if (is_page() && (has_shortcode(get_post()->post_content, 'waza_instructor_registration') || 
                         has_shortcode(get_post()->post_content, 'waza_instructor_dashboard'))) {
            
            wp_enqueue_style('waza-instructor', WAZA_BOOKING_PLUGIN_URL . 'assets/instructor.css', [], '2.5.0');
            wp_enqueue_script('waza-instructor', WAZA_BOOKING_PLUGIN_URL . 'assets/instructor.js', ['jquery'], '2.5.0', true);
            
            wp_localize_script('waza-instructor', 'wazaInstructor', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('waza_instructor_nonce')
            ]);
        }
    }
    
    /**
     * Instructor registration form shortcode
     */
    public function registration_form() {
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            
            // Check if already registered as instructor
            $existing = get_posts([
                'post_type' => 'waza_instructor',
                'meta_key' => '_waza_user_id',
                'meta_value' => $user_id,
                'post_status' => ['publish', 'pending'],
                'posts_per_page' => 1
            ]);
            
            if (!empty($existing)) {
                return '<div class="waza-notice info"><p>' . __('You have already submitted an instructor application. Please wait for admin approval.', 'waza-booking') . '</p></div>';
            }
        }
        
        ob_start();
        include WAZA_BOOKING_PLUGIN_DIR . 'templates/instructor-registration.php';
        return ob_get_clean();
    }
    
    /**
     * Submit instructor application
     */
    public function submit_application() {
        check_ajax_referer('waza_instructor_nonce', 'nonce');
        
        // Validate required fields
        $required = ['instructor_name', 'instructor_email', 'instructor_phone', 'activity_type', 'experience_years', 'instructor_bio', 'instructor_rating'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(['message' => __('Please fill in all required fields', 'waza-booking')]);
            }
        }
        
        $email = sanitize_email($_POST['instructor_email']);
        
        // Check if email already exists
        if (email_exists($email)) {
            wp_send_json_error(['message' => __('An account with this email already exists', 'waza-booking')]);
        }
        
        // Create WordPress user
        $user_id = wp_create_user(
            $email,
            wp_generate_password(12, true),
            $email
        );
        
        if (is_wp_error($user_id)) {
            wp_send_json_error(['message' => $user_id->get_error_message()]);
        }
        
        $instructor_name = sanitize_text_field($_POST['instructor_name']);
        
        // Update user data
        wp_update_user([
            'ID' => $user_id,
            'display_name' => $instructor_name,
            'first_name' => $instructor_name,
            'last_name' => '',
            'nickname' => $instructor_name
        ]);
        
        update_user_meta($user_id, 'phone', sanitize_text_field($_POST['instructor_phone']));
        
        // Create instructor post
        $instructor_id = wp_insert_post([
            'post_title' => $instructor_name,
            'post_content' => sanitize_textarea_field($_POST['instructor_bio']),
            'post_type' => 'waza_instructor',
            'post_status' => 'pending', // Requires admin approval
            'post_author' => $user_id
        ]);
        
        if (is_wp_error($instructor_id)) {
            wp_send_json_error(['message' => __('Failed to create instructor profile', 'waza-booking')]);
        }
        
        // Save instructor meta
        update_post_meta($instructor_id, '_waza_user_id', $user_id);
        update_post_meta($instructor_id, '_waza_email', $email);
        update_post_meta($instructor_id, '_waza_phone', sanitize_text_field($_POST['instructor_phone']));
        update_post_meta($instructor_id, '_waza_bio', sanitize_textarea_field($_POST['instructor_bio']));
        update_post_meta($instructor_id, '_waza_activity_type', sanitize_text_field($_POST['activity_type']));
        update_post_meta($instructor_id, '_waza_experience', intval($_POST['experience_years']));
        update_post_meta($instructor_id, '_waza_certifications', sanitize_textarea_field($_POST['certifications'] ?? ''));
        update_post_meta($instructor_id, '_waza_rating', intval($_POST['instructor_rating']));
        
        // Process social links
        $social_links = [];
        if (!empty($_POST['social_platform']) && !empty($_POST['social_url'])) {
            $platforms = $_POST['social_platform'];
            $urls = $_POST['social_url'];
            for ($i = 0; $i < count($platforms); $i++) {
                if (!empty($urls[$i])) {
                    $social_links[sanitize_text_field($platforms[$i])] = esc_url_raw($urls[$i]);
                }
            }
        }
        update_post_meta($instructor_id, '_waza_social_links', $social_links);
        
        // Set activity as specialty taxonomy
        $activity_type = sanitize_text_field($_POST['activity_type']);
        wp_set_object_terms($instructor_id, [$activity_type], 'waza_instructor_specialty');
        
        // Send notification to admin
        $this->notify_admin_new_application($instructor_id, $email);
        
        // Send confirmation email to instructor
        $this->send_application_confirmation($email, sanitize_text_field($_POST['instructor_name']));
        
        wp_send_json_success([
            'message' => __('Application submitted successfully! You will receive an email once your application is reviewed.', 'waza-booking')
        ]);
    }
    
    /**
     * Notify admin about new instructor application
     */
    private function notify_admin_new_application($instructor_id, $email) {
        $admin_email = get_option('admin_email');
        $instructor_name = get_the_title($instructor_id);
        $review_url = admin_url('post.php?post=' . $instructor_id . '&action=edit');
        
        $subject = sprintf(__('[%s] New Instructor Application', 'waza-booking'), get_bloginfo('name'));
        $message = sprintf(
            __('A new instructor application has been submitted.

Instructor Name: %s
Email: %s

Review and approve the application here:
%s

You can approve or reject this application from the WordPress admin panel.', 'waza-booking'),
            $instructor_name,
            $email,
            $review_url
        );
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Send confirmation email to instructor applicant
     */
    private function send_application_confirmation($email, $name) {
        $subject = sprintf(__('[%s] Application Received', 'waza-booking'), get_bloginfo('name'));
        $message = sprintf(
            __('Dear %s,

Thank you for applying to become an instructor at %s.

Your application has been received and is currently under review. Our team will review your application and get back to you within 2-3 business days.

You will receive an email notification once your application is approved.

If you have any questions, please contact us at %s.

Best regards,
%s Team', 'waza-booking'),
            $name,
            get_bloginfo('name'),
            get_option('admin_email'),
            get_bloginfo('name')
        );
        
        wp_mail($email, $subject, $message);
    }
    
    /**
     * Instructor dashboard shortcode
     */
    public function instructor_dashboard() {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to access the instructor dashboard.', 'waza-booking') . '</p>';
        }
        
        $user_id = get_current_user_id();
        
        // Check if user is an approved instructor
        $instructors = get_posts([
            'post_type' => 'waza_instructor',
            'meta_key' => '_waza_user_id',
            'meta_value' => $user_id,
            'post_status' => 'publish',
            'posts_per_page' => 1
        ]);
        
        if (empty($instructors)) {
            // Check if pending
            $pending = get_posts([
                'post_type' => 'waza_instructor',
                'meta_key' => '_waza_user_id',
                'meta_value' => $user_id,
                'post_status' => 'pending',
                'posts_per_page' => 1
            ]);
            
            if (!empty($pending)) {
                return '<div class="waza-notice warning"><p>' . __('Your instructor application is pending admin approval. You will be notified via email once approved.', 'waza-booking') . '</p></div>';
            }
            
            return '<div class="waza-notice error"><p>' . __('You are not registered as an instructor. <a href="' . home_url('/instructor-registration') . '">Apply here</a>', 'waza-booking') . '</p></div>';
        }
        
        $instructor = $instructors[0];
        $instructor_id = $instructor->ID;
        
        ob_start();
        include WAZA_BOOKING_PLUGIN_DIR . 'templates/instructor-dashboard.php';
        return ob_get_clean();
    }
    
    /**
     * Get instructor workshops via AJAX
     */
    public function get_workshops() {
        if (!check_ajax_referer('waza_instructor_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Invalid security token. Please refresh the page.', 'waza-booking')]);
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Not authorized', 'waza-booking')]);
        }
        
        $user_id = get_current_user_id();
        
        // Get instructor post
        $instructors = get_posts([
            'post_type' => 'waza_instructor',
            'meta_key' => '_waza_user_id',
            'meta_value' => $user_id,
            'post_status' => 'publish',
            'posts_per_page' => 1
        ]);
        
        if (empty($instructors)) {
            wp_send_json_error(['message' => __('Not an approved instructor', 'waza-booking')]);
        }
        
        $instructor_id = $instructors[0]->ID;
        global $wpdb;
        
        // Get filter parameter
        $filter = isset($_POST['filter']) ? sanitize_text_field($_POST['filter']) : 'upcoming';
        
        // Build date condition based on filter
        $date_condition = '';
        switch ($filter) {
            case 'today':
                $date_condition = "AND DATE(s.start_datetime) = CURDATE()";
                break;
            case 'past':
                $date_condition = "AND s.start_datetime < NOW()";
                break;
            case 'upcoming':
            default:
                $date_condition = "AND s.start_datetime >= NOW()";
                break;
        }
        
        // Get workshops (slots) for this instructor
        $workshops = $wpdb->get_results($wpdb->prepare("
            SELECT 
                s.*,
                p.post_title as activity_title,
                p.ID as activity_id,
                COALESCE((SELECT SUM(attendees_count) FROM {$wpdb->prefix}waza_bookings WHERE slot_id = s.id AND booking_status = 'confirmed'), 0) as booked_count
            FROM {$wpdb->prefix}waza_slots s
            LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_waza_instructor'
            WHERE pm.meta_value = %d
            AND s.status NOT IN ('cancelled', 'pending_cancellation')
            {$date_condition}
            ORDER BY s.start_datetime " . ($filter === 'past' ? 'DESC' : 'ASC') . "
        ", $instructor_id));
        
        $formatted_workshops = [];
        foreach ($workshops as $workshop) {
            $is_pending = $workshop->status === 'pending_approval';
            $is_past = strtotime($workshop->start_datetime) < time();
            
            $formatted_workshops[] = [
                'id' => $workshop->id,
                'activity_title' => $workshop->activity_title,
                'date' => date('M j, Y', strtotime($workshop->start_datetime)),
                'day' => date('l', strtotime($workshop->start_datetime)),
                'time' => date('g:i A', strtotime($workshop->start_datetime)) . ' - ' . date('g:i A', strtotime($workshop->end_datetime)),
                'capacity' => $workshop->capacity,
                'booked' => intval($workshop->booked_count),
                'available' => $workshop->capacity - intval($workshop->booked_count),
                'status' => $is_pending ? 'pending' : ($is_past ? 'past' : 'upcoming'),
                'approval_status' => $workshop->status
            ];
        }
        
        wp_send_json_success(['workshops' => $formatted_workshops]);
    }
    
    /**
     * Get workshop QR code
     */
    public function get_workshop_qr() {
        if (!check_ajax_referer('waza_instructor_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Invalid security token. Please refresh the page.', 'waza-booking')]);
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Not authorized', 'waza-booking')]);
        }
        
        $slot_id = intval($_POST['slot_id'] ?? 0);
        
        if (!$slot_id) {
            wp_send_json_error(['message' => __('Invalid slot ID', 'waza-booking')]);
        }
        
        // Generate instructor workshop QR
        $qr_manager = new \WazaBooking\QR\QRManager();
        
        // Create QR data
        $qr_data = [
            'type' => 'instructor_workshop',
            'slot_id' => $slot_id,
            'user_id' => get_current_user_id(),
            'timestamp' => time()
        ];
        
        $qr_string = json_encode($qr_data);
        $qr_image = $qr_manager->generate_qr_image($qr_string, 400);
        
        wp_send_json_success([
            'qr_image' => $qr_image,
            'slot_id' => $slot_id
        ]);
    }
    
    /**
     * Create new workshop (activity)
     */
    public function create_workshop() {
        check_ajax_referer('waza_instructor_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Not authorized', 'waza-booking'));
        }
        
        // Get instructor post for current user
        $user_id = get_current_user_id();
        $instructors = get_posts([
            'post_type' => 'waza_instructor',
            'meta_key' => '_waza_user_id',
            'meta_value' => $user_id,
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ]);
        
        if (empty($instructors)) {
            wp_send_json_error(__('You are not registered as an instructor', 'waza-booking'));
        }
        
        $instructor_id = $instructors[0]->ID;
        
        // Validate required fields
        $activity_id = intval($_POST['activity_id'] ?? 0);
        $capacity = intval($_POST['capacity'] ?? 20);
        $price = floatval($_POST['price'] ?? 0);
        $date = sanitize_text_field($_POST['date'] ?? '');
        $time = sanitize_text_field($_POST['time'] ?? '');
        $end_time = sanitize_text_field($_POST['end_time'] ?? '');
        
        if (empty($activity_id)) {
            wp_send_json_error(__('Please select an activity', 'waza-booking'));
        }
        
        // Verify activity exists and get its details
        $activity = get_post($activity_id);
        if (!$activity || $activity->post_type !== 'waza_activity') {
            wp_send_json_error(__('Invalid activity selected', 'waza-booking'));
        }
        
        if (empty($date) || empty($time) || empty($end_time)) {
            wp_send_json_error(__('Date, start time, and end time are required', 'waza-booking'));
        }
        
        // Validate end time is after start time
        $start_timestamp = strtotime($date . ' ' . $time);
        $end_timestamp = strtotime($date . ' ' . $end_time);
        
        if ($end_timestamp <= $start_timestamp) {
            wp_send_json_error(__('End time must be after start time', 'waza-booking'));
        }
        
        // Calculate duration in minutes
        $duration = round(($end_timestamp - $start_timestamp) / 60);
        
        // Check for time conflicts with existing workshops
        $start_datetime = $date . ' ' . $time . ':00';
        $end_datetime = $date . ' ' . $end_time . ':00';
        
        global $wpdb;
        $conflict = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}waza_slots s
            INNER JOIN {$wpdb->posts} p ON s.activity_id = p.ID
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_waza_instructor'
            WHERE pm.meta_value = %d
            AND s.status IN ('active', 'pending_approval')
            AND (
                (s.start_datetime <= %s AND s.end_datetime > %s)
                OR (s.start_datetime < %s AND s.end_datetime >= %s)
                OR (s.start_datetime >= %s AND s.end_datetime <= %s)
            )
        ", $instructor_id, $start_datetime, $start_datetime, $end_datetime, $end_datetime, $start_datetime, $end_datetime));
        
        if ($conflict > 0) {
            wp_send_json_error(__('You already have a workshop scheduled during this time. Please choose a different time slot.', 'waza-booking'));
        }
        
        if (empty($price)) {
            $price = floatval(get_post_meta($activity_id, '_waza_price', true));
        }
        
        // Ensure the instructor is assigned to this activity
        $activity_instructor_id = get_post_meta($activity_id, '_waza_instructor', true);
        if (empty($activity_instructor_id) || $activity_instructor_id != $instructor_id) {
            update_post_meta($activity_id, '_waza_instructor', $instructor_id);
        }
        
        // Update activity duration based on this workshop
        update_post_meta($activity_id, '_waza_duration', $duration);
        
        if (!empty($_POST['location'])) {
            update_post_meta($activity_id, '_waza_location', sanitize_text_field($_POST['location']));
        }
        
        // Handle image upload
        $image_url = '';
        if (!empty($_FILES['workshop_image']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            
            $uploaded_file = wp_handle_upload($_FILES['workshop_image'], ['test_form' => false]);
            
            if (!isset($uploaded_file['error'])) {
                $image_url = $uploaded_file['url'];
            }
        }
        
        // Create slot with pending approval status
        
        $original_price = !empty($_POST['original_price']) ? floatval($_POST['original_price']) : null;
        $sale_price = !empty($_POST['sale_price']) ? floatval($_POST['sale_price']) : null;
        
        global $wpdb;
        $slot_inserted = $wpdb->insert(
            $wpdb->prefix . 'waza_slots',
            [
                'activity_id' => $activity_id,
                'instructor_id' => $instructor_id,
                'start_datetime' => $start_datetime,
                'end_datetime' => $end_datetime,
                'capacity' => $capacity,
                'price' => $price,
                'original_price' => $original_price,
                'sale_price' => $sale_price,
                'image_url' => $image_url,
                'status' => 'pending_approval',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ],
            ['%d', '%d', '%s', '%s', '%d', '%f', '%f', '%f', '%s', '%s', '%s', '%s']
        );
        
        if (!$slot_inserted) {
            wp_delete_post($activity_id, true);
            wp_send_json_error(__('Failed to create workshop slot', 'waza-booking'));
        }
        
        $slot_id = $wpdb->insert_id;
        
        // Send notification to admin
        $this->notify_admin_new_workshop($activity_id, $slot_id, $instructor_id);
        
        wp_send_json_success([
            'message' => __('Workshop submitted for approval! You will be notified once admin approves it.', 'waza-booking'),
            'activity_id' => $activity_id,
            'slot_id' => $slot_id
        ]);
    }
    
    /**
     * Notify admin about new workshop
     */
    private function notify_admin_new_workshop($activity_id, $slot_id, $instructor_id) {
        $admin_email = get_option('admin_email');
        $instructor_name = get_the_title($instructor_id);
        $workshop_title = get_the_title($activity_id);
        
        global $wpdb;
        $slot = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}waza_slots WHERE id = %d",
            $slot_id
        ));
        
        $subject = sprintf(__('[Waza Booking] New Workshop Pending Approval - %s', 'waza-booking'), $workshop_title);
        
        $message = sprintf(
            __('A new workshop has been submitted by %s and is pending your approval.', 'waza-booking'),
            $instructor_name
        ) . "\n\n";
        
        $message .= __('Workshop Details:', 'waza-booking') . "\n";
        $message .= sprintf(__('Title: %s', 'waza-booking'), $workshop_title) . "\n";
        $message .= sprintf(__('Date & Time: %s', 'waza-booking'), date('M j, Y g:i A', strtotime($slot->start_datetime))) . "\n";
        $message .= sprintf(__('Duration: %d minutes', 'waza-booking'), get_post_meta($activity_id, '_waza_duration', true)) . "\n";
        $message .= sprintf(__('Capacity: %d', 'waza-booking'), $slot->capacity) . "\n";
        $message .= sprintf(__('Price: $%s', 'waza-booking'), $slot->price) . "\n\n";
        
        $message .= __('Please login to your admin dashboard to review and approve this workshop.', 'waza-booking') . "\n";
        $message .= admin_url('admin.php?page=waza-workshops&status=pending');
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Get instructor overview stats
     */
    public function get_instructor_overview() {
        // Verify nonce
        if (!check_ajax_referer('waza_instructor_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Invalid security token. Please refresh the page.', 'waza-booking')]);
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Not authorized', 'waza-booking')]);
        }
        
        $user_id = get_current_user_id();
        
        // Get instructor post
        $instructors = get_posts([
            'post_type' => 'waza_instructor',
            'meta_key' => '_waza_user_id',
            'meta_value' => $user_id,
            'post_status' => 'publish',
            'posts_per_page' => 1
        ]);
        
        if (empty($instructors)) {
            wp_send_json_error(['message' => __('Not an approved instructor', 'waza-booking')]);
        }
        
        $instructor_id = $instructors[0]->ID;
        global $wpdb;
        
        // Get workshops today
        $workshops_today = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->prefix}waza_slots s
            INNER JOIN {$wpdb->postmeta} pm ON s.activity_id = pm.post_id
            WHERE pm.meta_key = '_waza_instructor'
            AND pm.meta_value = %d
            AND DATE(s.start_datetime) = CURDATE()
        ", $instructor_id));
        
        // Get upcoming workshops
        $upcoming_workshops = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->prefix}waza_slots s
            INNER JOIN {$wpdb->postmeta} pm ON s.activity_id = pm.post_id
            WHERE pm.meta_key = '_waza_instructor'
            AND pm.meta_value = %d
            AND s.start_datetime >= NOW()
        ", $instructor_id));
        
        // Get total unique students
        $total_students = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT b.user_id)
            FROM {$wpdb->prefix}waza_bookings b
            INNER JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
            INNER JOIN {$wpdb->postmeta} pm ON s.activity_id = pm.post_id
            WHERE pm.meta_key = '_waza_instructor'
            AND pm.meta_value = %d
            AND b.booking_status = 'confirmed'
        ", $instructor_id));
        
        wp_send_json_success([
            'workshops_today' => intval($workshops_today),
            'upcoming_workshops' => intval($upcoming_workshops),
            'total_students' => intval($total_students)
        ]);
    }
    
    /**
     * Get instructor schedule
     */
    public function get_instructor_schedule() {
        if (!check_ajax_referer('waza_instructor_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Invalid security token. Please refresh the page.', 'waza-booking')]);
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Not authorized', 'waza-booking')]);
        }
        
        $user_id = get_current_user_id();
        
        // Get instructor post
        $instructors = get_posts([
            'post_type' => 'waza_instructor',
            'meta_key' => '_waza_user_id',
            'meta_value' => $user_id,
            'post_status' => 'publish',
            'posts_per_page' => 1
        ]);
        
        if (empty($instructors)) {
            wp_send_json_error(['message' => __('Not an approved instructor', 'waza-booking')]);
        }
        
        $instructor_id = $instructors[0]->ID;
        global $wpdb;
        
        // Get upcoming schedule
        $schedule = $wpdb->get_results($wpdb->prepare("
            SELECT 
                s.*,
                p.post_title as activity_title,
                COALESCE((SELECT SUM(attendees_count) FROM {$wpdb->prefix}waza_bookings WHERE slot_id = s.id AND booking_status = 'confirmed'), 0) as booked_count
            FROM {$wpdb->prefix}waza_slots s
            INNER JOIN {$wpdb->posts} p ON s.activity_id = p.ID
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE pm.meta_key = '_waza_instructor'
            AND pm.meta_value = %d
            AND s.start_datetime >= NOW()
            ORDER BY s.start_datetime ASC
            LIMIT 20
        ", $instructor_id));
        
        $formatted_schedule = [];
        foreach ($schedule as $item) {
            $formatted_schedule[] = [
                'id' => $item->id,
                'activity_title' => $item->activity_title,
                'date' => date('M j, Y', strtotime($item->start_datetime)),
                'day' => date('l', strtotime($item->start_datetime)),
                'time' => date('g:i A', strtotime($item->start_datetime)) . ' - ' . date('g:i A', strtotime($item->end_datetime)),
                'capacity' => $item->capacity,
                'booked' => intval($item->booked_count)
            ];
        }
        
        wp_send_json_success(['schedule' => $formatted_schedule]);
    }
    
    /**
     * Get instructor students
     */
    public function get_instructor_students() {
        if (!check_ajax_referer('waza_instructor_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Invalid security token. Please refresh the page.', 'waza-booking')]);
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Not authorized', 'waza-booking')]);
        }
        
        $user_id = get_current_user_id();
        
        // Get instructor post
        $instructors = get_posts([
            'post_type' => 'waza_instructor',
            'meta_key' => '_waza_user_id',
            'meta_value' => $user_id,
            'post_status' => 'publish',
            'posts_per_page' => 1
        ]);
        
        if (empty($instructors)) {
            wp_send_json_error(['message' => __('Not an approved instructor', 'waza-booking')]);
        }
        
        $instructor_id = $instructors[0]->ID;
        global $wpdb;
        
        // Get students
        $students = $wpdb->get_results($wpdb->prepare("
            SELECT 
                u.ID as user_id,
                u.display_name,
                u.user_email,
                COUNT(DISTINCT b.id) as total_bookings,
                COUNT(DISTINCT a.id) as total_attendance
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->prefix}waza_bookings b ON u.ID = b.user_id
            INNER JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
            INNER JOIN {$wpdb->postmeta} pm ON s.activity_id = pm.post_id
            LEFT JOIN {$wpdb->prefix}waza_attendance a ON b.id = a.booking_id
            WHERE pm.meta_key = '_waza_instructor'
            AND pm.meta_value = %d
            AND b.booking_status = 'confirmed'
            GROUP BY u.ID
            ORDER BY total_bookings DESC
        ", $instructor_id));
        
        $formatted_students = [];
        foreach ($students as $student) {
            $formatted_students[] = [
                'user_id' => $student->user_id,
                'name' => $student->display_name,
                'email' => $student->user_email,
                'total_bookings' => intval($student->total_bookings),
                'total_attendance' => intval($student->total_attendance)
            ];
        }
        
        wp_send_json_success(['students' => $formatted_students]);
    }

    /**
     * Get students for a specific workshop/slot
     */
    public function get_workshop_students() {
        if (!check_ajax_referer('waza_instructor_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Invalid security token. Please refresh the page.', 'waza-booking')]);
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Not authorized', 'waza-booking')]);
        }
        
        $slot_id = intval($_POST['slot_id'] ?? 0);
        
        if (!$slot_id) {
            wp_send_json_error(['message' => __('Invalid slot ID', 'waza-booking')]);
        }
        
        global $wpdb;
        
        // Get slot details
        $slot = $wpdb->get_row($wpdb->prepare("
            SELECT s.*, p.post_title as activity_title
            FROM {$wpdb->prefix}waza_slots s
            INNER JOIN {$wpdb->posts} p ON s.activity_id = p.ID
            WHERE s.id = %d
        ", $slot_id));
        
        if (!$slot) {
            wp_send_json_error(['message' => __('Slot not found', 'waza-booking')]);
        }
        
        // Get bookings for this slot
        $bookings = $wpdb->get_results($wpdb->prepare("
            SELECT 
                b.id as booking_id,
                b.user_id,
                b.user_name,
                b.user_email,
                b.user_phone,
                b.attendees_count,
                b.booking_status,
                b.created_at,
                u.display_name,
                a.check_in_time as attended_at,
                a.scanner_user_id as scanned_by
            FROM {$wpdb->prefix}waza_bookings b
            LEFT JOIN {$wpdb->users} u ON b.user_id = u.ID
            LEFT JOIN {$wpdb->prefix}waza_attendance a ON b.id = a.booking_id
            WHERE b.slot_id = %d
            ORDER BY b.created_at DESC
        ", $slot_id));
        
        $students = [];
        foreach ($bookings as $booking) {
            $students[] = [
                'booking_id' => $booking->booking_id,
                'name' => $booking->user_name ?: $booking->display_name ?: 'Guest',
                'email' => $booking->user_email,
                'phone' => $booking->user_phone,
                'attendees' => intval($booking->attendees_count),
                'status' => $booking->booking_status,
                'attended' => !empty($booking->attended_at),
                'attended_at' => $booking->attended_at,
                'booked_at' => $booking->created_at
            ];
        }
        
        wp_send_json_success([
            'slot_id' => $slot_id,
            'activity_title' => $slot->activity_title,
            'slot_time' => date('M j, Y g:i A', strtotime($slot->start_datetime)),
            'capacity' => intval($slot->capacity),
            'booked' => array_sum(array_column($bookings, 'attendees')),
            'students' => $students
        ]);
    }
    
    /**
     * Request workshop cancellation (requires admin approval)
     */
    public function request_workshop_cancellation() {
        if (!check_ajax_referer('waza_instructor_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Invalid security token. Please refresh the page.', 'waza-booking')]);
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in', 'waza-booking')]);
        }
        
        $user_id = get_current_user_id();
        $slot_id = intval($_POST['slot_id'] ?? 0);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');
        
        if (!$slot_id) {
            wp_send_json_error(['message' => __('Invalid workshop', 'waza-booking')]);
        }
        
        // Verify instructor owns this workshop
        $instructors = get_posts([
            'post_type' => 'waza_instructor',
            'meta_query' => [
                ['key' => '_waza_user_id', 'value' => $user_id]
            ],
            'posts_per_page' => 1
        ]);
        
        if (empty($instructors)) {
            wp_send_json_error(['message' => __('Instructor profile not found', 'waza-booking')]);
        }
        
        $instructor_id = $instructors[0]->ID;
        
        global $wpdb;
        
        // Verify the slot belongs to this instructor
        $slot = $wpdb->get_row($wpdb->prepare("
            SELECT s.*, p.post_title as activity_title, pm.meta_value as instructor_id
            FROM {$wpdb->prefix}waza_slots s
            INNER JOIN {$wpdb->posts} p ON s.activity_id = p.ID
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_waza_instructor'
            WHERE s.id = %d
        ", $slot_id));
        
        if (!$slot || $slot->instructor_id != $instructor_id) {
            wp_send_json_error(['message' => __('You do not have permission to cancel this workshop', 'waza-booking')]);
        }
        
        if ($slot->status === 'pending_cancellation') {
            wp_send_json_error(['message' => __('This workshop already has a pending cancellation request', 'waza-booking')]);
        }
        
        // Update status to pending_cancellation
        $updated = $wpdb->update(
            $wpdb->prefix . 'waza_slots',
            [
                'status' => 'pending_cancellation',
                'updated_at' => current_time('mysql')
            ],
            ['id' => $slot_id],
            ['%s', '%s'],
            ['%d']
        );
        
        if (!$updated) {
            wp_send_json_error(['message' => __('Failed to submit cancellation request', 'waza-booking')]);
        }
        
        // Store cancellation reason
        update_post_meta($slot->activity_id, '_waza_cancellation_reason_' . $slot_id, $reason);
        
        // Notify admin
        $this->notify_admin_cancellation_request($slot, $instructor_id, $reason);
        
        wp_send_json_success(['message' => __('Cancellation request submitted. Waiting for admin approval.', 'waza-booking')]);
    }
    
    /**
     * Notify admin about cancellation request
     */
    private function notify_admin_cancellation_request($slot, $instructor_id, $reason) {
        $admin_email = get_option('admin_email');
        $instructor_name = get_the_title($instructor_id);
        
        $subject = sprintf(__('[Waza Booking] Workshop Cancellation Request - %s', 'waza-booking'), $slot->activity_title);
        
        $message = sprintf(
            __('Instructor %s has requested to cancel a workshop.', 'waza-booking'),
            $instructor_name
        ) . "\n\n";
        
        $message .= __('Workshop Details:', 'waza-booking') . "\n";
        $message .= sprintf(__('Title: %s', 'waza-booking'), $slot->activity_title) . "\n";
        $message .= sprintf(__('Date & Time: %s', 'waza-booking'), date('M j, Y g:i A', strtotime($slot->start_datetime))) . "\n\n";
        
        if ($reason) {
            $message .= __('Reason:', 'waza-booking') . "\n" . $reason . "\n\n";
        }
        
        $message .= __('Please review this request in your admin panel.', 'waza-booking') . "\n";
        $message .= admin_url('admin.php?page=waza-pending-workshops');
        
        wp_mail($admin_email, $subject, $message);
    }
}
