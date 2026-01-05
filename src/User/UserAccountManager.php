<?php
/**
 * User Account Manager
 * 
 * @package WazaBooking\User
 */

namespace WazaBooking\User;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * User Account Manager Class
 * Handles user registration, login, and account management
 */
class UserAccountManager {
    
    /**
     * Initialize user account functionality
     */
    public function init() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_account_scripts']);
        
        // Shortcodes
        add_shortcode('waza_user_dashboard', [$this, 'user_dashboard_shortcode']);
        add_shortcode('waza_instructor_dashboard', [$this, 'instructor_dashboard_shortcode']);
        add_shortcode('waza_login_form', [$this, 'login_form_shortcode']);
        add_shortcode('waza_register_form', [$this, 'register_form_shortcode']);
        add_shortcode('waza_instructor_register', [$this, 'instructor_register_shortcode']);
        add_shortcode('waza_my_bookings', [$this, 'my_bookings_shortcode']);
        
        // AJAX actions
        add_action('wp_ajax_waza_update_profile', [$this, 'ajax_update_profile']);
        add_action('wp_ajax_nopriv_waza_instructor_register', [$this, 'ajax_instructor_register']);
        add_action('wp_ajax_waza_get_slot_roster', [$this, 'ajax_get_slot_roster']);
        add_action('wp_ajax_waza_get_booking_qr', [$this, 'ajax_get_booking_qr']);
        
        // Email verification
        add_action('template_redirect', [$this, 'handle_instructor_email_verification']);
    }
    
    /**
     * Enqueue account scripts
     */
    public function enqueue_account_scripts() {
        if (is_page()) {
            wp_enqueue_style('waza-account', WAZA_BOOKING_PLUGIN_URL . 'assets/account.css', [], WAZA_BOOKING_VERSION);
            wp_enqueue_script('waza-account', WAZA_BOOKING_PLUGIN_URL . 'assets/account.js', ['jquery'], WAZA_BOOKING_VERSION, true);
            
            wp_localize_script('waza-account', 'wazaAccount', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('waza_account_nonce')
            ]);
        }
    }
    
    /**
     * Login form shortcode
     */
    public function login_form_shortcode($atts) {
        if (is_user_logged_in()) {
            return '<p>' . __('You are already logged in.', 'waza-booking') . '</p>';
        }

        ob_start();
        ?>
        <div class="waza-login-form-container">
            <form id="waza-login-form" class="waza-form" method="post">
                <div class="waza-form-group">
                    <label for="user_login"><?php esc_html_e('Email or Username', 'waza-booking'); ?></label>
                    <input type="text" id="user_login" name="user_login" required>
                </div>
                <div class="waza-form-group">
                    <label for="user_password"><?php esc_html_e('Password', 'waza-booking'); ?></label>
                    <input type="password" id="user_password" name="user_password" required>
                </div>
                <div class="waza-form-group">
                    <label>
                        <input type="checkbox" name="remember" value="1">
                        <?php esc_html_e('Remember Me', 'waza-booking'); ?>
                    </label>
                </div>
                <button type="submit" class="waza-btn waza-btn-primary"><?php esc_html_e('Login', 'waza-booking'); ?></button>
                <p><a href="<?php echo wp_lostpassword_url(); ?>"><?php esc_html_e('Forgot Password?', 'waza-booking'); ?></a></p>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Register form shortcode
     */
    public function register_form_shortcode($atts) {
        if (is_user_logged_in()) {
            return '<p>' . __('You are already logged in.', 'waza-booking') . '</p>';
        }

        ob_start();
        ?>
        <div class="waza-register-form-container">
            <form id="waza-register-form" class="waza-form" method="post">
                <div class="waza-form-group">
                    <label for="user_name"><?php esc_html_e('Full Name', 'waza-booking'); ?></label>
                    <input type="text" id="user_name" name="user_name" required>
                </div>
                <div class="waza-form-group">
                    <label for="user_email"><?php esc_html_e('Email Address', 'waza-booking'); ?></label>
                    <input type="email" id="user_email" name="user_email" required>
                </div>
                <div class="waza-form-group">
                    <label for="user_password"><?php esc_html_e('Password', 'waza-booking'); ?></label>
                    <input type="password" id="user_password" name="user_password" required>
                </div>
                <button type="submit" class="waza-btn waza-btn-primary"><?php esc_html_e('Register', 'waza-booking'); ?></button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Instructor registration form shortcode
     */
    public function instructor_register_shortcode($atts) {
        if (is_user_logged_in()) {
            return '<p>' . __('You are already logged in.', 'waza-booking') . '</p>';
        }

        ob_start();
        ?>
        <div class="waza-instructor-register-container">
            <form id="waza-instructor-register-form" class="waza-form">
                <?php wp_nonce_field('waza_instructor_register', 'waza_instructor_nonce'); ?>
                
                <div class="waza-form-step active" data-step="1">
                    <h3><?php esc_html_e('Basic Information', 'waza-booking'); ?></h3>
                    <div class="waza-form-group">
                        <label for="inst_name"><?php esc_html_e('Full Name', 'waza-booking'); ?></label>
                        <input type="text" id="inst_name" name="name" required>
                    </div>
                    <div class="waza-form-group">
                        <label for="inst_email"><?php esc_html_e('Email Address', 'waza-booking'); ?></label>
                        <input type="email" id="inst_email" name="email" required>
                    </div>
                    <div class="waza-form-group">
                        <label for="inst_phone"><?php esc_html_e('Mobile Number', 'waza-booking'); ?></label>
                        <input type="tel" id="inst_phone" name="phone" required>
                    </div>
                    <div class="waza-form-group">
                        <label for="inst_pass"><?php esc_html_e('Password', 'waza-booking'); ?></label>
                        <input type="password" id="inst_pass" name="password" required>
                    </div>
                </div>

                <div class="waza-form-step" data-step="2">
                    <h3><?php esc_html_e('Skills & Expertise', 'waza-booking'); ?></h3>
                    <div class="waza-form-group">
                        <label for="inst_skills"><?php esc_html_e('Skills (Comma separated)', 'waza-booking'); ?></label>
                        <input type="text" id="inst_skills" name="skills" placeholder="Yoga, Pilates, Zumba..." required>
                    </div>
                    <div class="waza-form-group">
                        <label><?php esc_html_e('Self Rating', 'waza-booking'); ?></label>
                        <div class="waza-rating-input">
                            <label><input type="radio" name="rating" value="2"> ⭐⭐ (2 Stars)</label>
                            <label><input type="radio" name="rating" value="3"> ⭐⭐⭐ (3 Stars)</label>
                            <label><input type="radio" name="rating" value="5"> ⭐⭐⭐⭐⭐ (5 Stars)</label>
                        </div>
                    </div>
                </div>

                <div class="waza-form-actions">
                    <button type="submit" class="waza-btn waza-btn-primary"><?php esc_html_e('Register as Instructor', 'waza-booking'); ?></button>
                </div>
            </form>
        </div>
        <style>
        .waza-instructor-register-container { max-width: 600px; margin: 0 auto; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .waza-rating-input label { display: block; margin: 10px 0; cursor: pointer; }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX Instructor Registration
     */
    public function ajax_instructor_register() {
        check_ajax_referer('waza_instructor_register', 'nonce');

        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);
        $password = $_POST['password'];
        $skills = sanitize_text_field($_POST['skills']);
        $rating = intval($_POST['rating']);

        if (email_exists($email)) {
            wp_send_json_error(['message' => __('Email already exists', 'waza-booking')]);
        }

        // Create User
        $user_id = wp_create_user($email, $password, $email);
        if (is_wp_error($user_id)) {
            wp_send_json_error(['message' => $user_id->get_error_message()]);
        }

        // Update User info
        wp_update_user([
            'ID' => $user_id,
            'display_name' => $name,
            'role' => 'subscriber' // Use default role until verified
        ]);
        update_user_meta($user_id, 'phone', $phone);
        
        // Generate verification token
        $verification_token = wp_generate_password(32, false);
        update_user_meta($user_id, 'waza_email_verification_token', $verification_token);
        update_user_meta($user_id, 'waza_email_verified', '0');

        // Create Instructor Post (Pending)
        $instructor_id = wp_insert_post([
            'post_title' => $name,
            'post_type' => 'waza_instructor',
            'post_status' => 'pending',
            'post_author' => $user_id
        ]);

        if (!is_wp_error($instructor_id)) {
            update_post_meta($instructor_id, '_waza_email', $email);
            update_post_meta($instructor_id, '_waza_phone', $phone);
            update_post_meta($instructor_id, '_waza_rating', $rating);
            update_post_meta($instructor_id, '_waza_user_id', $user_id);
            update_post_meta($instructor_id, '_waza_email_verified', '0');
            
            // Handle skills as terms
            $skills_array = array_map('trim', explode(',', $skills));
            wp_set_object_terms($instructor_id, $skills_array, 'waza_instructor_specialty');
            
            // Send verification email
            $this->send_instructor_verification_email($email, $name, $verification_token);

            wp_send_json_success(['message' => __('Registration successful! Please check your email to verify your account. Once verified, your profile will be sent for admin approval.', 'waza-booking')]);
        }

        wp_send_json_error(['message' => __('Error creating instructor profile', 'waza-booking')]);
    }
    
    /**
     * Send instructor verification email
     */
    private function send_instructor_verification_email($email, $name, $token) {
        $verification_url = add_query_arg([
            'action' => 'verify_instructor_email',
            'token' => $token,
            'email' => urlencode($email)
        ], home_url());
        
        $subject = __('Verify Your Instructor Account - Waza Booking', 'waza-booking');
        
        $message = sprintf(
            __('Hi %s,

Thank you for registering as an instructor with Waza Booking!

Please click the link below to verify your email address:
%s

After verification, your profile will be reviewed by our admin team.

If you did not create this account, please ignore this email.

Best regards,
Waza Booking Team', 'waza-booking'),
            $name,
            $verification_url
        );
        
        wp_mail($email, $subject, $message);
    }
    
    /**
     * User dashboard shortcode
     */
    public function user_dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your dashboard.', 'waza-booking') . '</p>';
        }

        ob_start();
        ?>
        <div class="waza-user-dashboard">
            <h2><?php printf(__('Welcome, %s', 'waza-booking'), wp_get_current_user()->display_name); ?></h2>
            <div class="waza-dashboard-sections">
                <div class="waza-dashboard-section">
                    <h3><?php esc_html_e('My Bookings', 'waza-booking'); ?></h3>
                    <?php echo do_shortcode('[waza_my_bookings]'); ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Instructor dashboard shortcode
     */
    public function instructor_dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your dashboard.', 'waza-booking') . '</p>';
        }

        $user_id = get_current_user_id();
        
        // Find instructor post linked to this user
        $instructors = get_posts([
            'post_type' => 'waza_instructor',
            'meta_key' => '_waza_user_id',
            'meta_value' => $user_id,
            'posts_per_page' => 1,
            'post_status' => ['publish', 'pending']
        ]);

        if (empty($instructors)) {
            return '<p>' . __('You are not registered as an instructor.', 'waza-booking') . '</p>';
        }

        $instructor = $instructors[0];
        if ($instructor->post_status === 'pending') {
            return '<div class="waza-notice warning"><p>' . __('Your instructor account is pending admin approval.', 'waza-booking') . '</p></div>';
        }

        ob_start();
        ?>
        <div class="waza-instructor-dashboard">
            <h2><?php printf(__('Welcome, %s', 'waza-booking'), get_the_title($instructor->ID)); ?></h2>
            <p><?php esc_html_e('Instructor dashboard content goes here.', 'waza-booking'); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * My bookings shortcode
     */
    public function my_bookings_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your bookings.', 'waza-booking') . '</p>';
        }

        ob_start();
        ?>
        <div class="waza-my-bookings">
            <h3><?php esc_html_e('My Bookings', 'waza-booking'); ?></h3>
            <div id="waza-bookings-list">
                <p><?php esc_html_e('Loading bookings...', 'waza-booking'); ?></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX update profile
     */
    public function ajax_update_profile() {
        check_ajax_referer('waza_account_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in', 'waza-booking')]);
        }
        
        $user_id = get_current_user_id();
        $display_name = sanitize_text_field($_POST['display_name']);
        $user_email = sanitize_email($_POST['user_email']);
        $phone = sanitize_text_field($_POST['user_phone']);
        
        // Update user
        $result = wp_update_user([
            'ID' => $user_id,
            'display_name' => $display_name,
            'user_email' => $user_email
        ]);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        // Update meta
        update_user_meta($user_id, 'phone', $phone);
        
        wp_send_json_success(['message' => __('Profile updated successfully!', 'waza-booking')]);
    }
    
    /**
     * AJAX get slot roster
     */
    public function ajax_get_slot_roster() {
        check_ajax_referer('waza_admin_nonce', 'nonce');
        
        $slot_id = intval($_POST['slot_id'] ?? 0);
        
        if (!$slot_id) {
            wp_send_json_error(__('Invalid slot ID', 'waza-booking'));
        }
        
        global $wpdb;
        
        $bookings = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_bookings 
            WHERE slot_id = %d AND booking_status = 'confirmed'
            ORDER BY id DESC
        ", $slot_id));
        
        wp_send_json_success(['bookings' => $bookings]);
    }
    
    /**
     * AJAX get booking QR
     */
    public function ajax_get_booking_qr() {
        $booking_id = intval($_POST['booking_id'] ?? 0);
        
        if (!$booking_id) {
            wp_send_json_error(__('Invalid booking ID', 'waza-booking'));
        }
        
        $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode("WAZA-BOOKING-" . $booking_id);
        
        wp_send_json_success(['qr_url' => $qr_url]);
    }
    
    /**
     * Handle instructor email verification
     */
    public function handle_instructor_email_verification() {
        if (!isset($_GET['action']) || $_GET['action'] !== 'verify_instructor_email') {
            return;
        }
        
        $token = sanitize_text_field($_GET['token'] ?? '');
        $email = sanitize_email($_GET['email'] ?? '');
        
        if (empty($token) || empty($email)) {
            wp_die(__('Invalid verification link.', 'waza-booking'));
        }
        
        // Find user by email
        $user = get_user_by('email', $email);
        if (!$user) {
            wp_die(__('User not found.', 'waza-booking'));
        }
        
        // Verify token
        $stored_token = get_user_meta($user->ID, 'waza_email_verification_token', true);
        if ($stored_token !== $token) {
            wp_die(__('Invalid or expired verification token.', 'waza-booking'));
        }
        
        // Check if already verified
        $already_verified = get_user_meta($user->ID, 'waza_email_verified', true);
        if ($already_verified === '1') {
            wp_die(__('Email already verified.', 'waza-booking'));
        }
        
        // Mark as verified
        update_user_meta($user->ID, 'waza_email_verified', '1');
        delete_user_meta($user->ID, 'waza_email_verification_token');
        
        // Find and update instructor post
        $instructors = get_posts([
            'post_type' => 'waza_instructor',
            'meta_key' => '_waza_user_id',
            'meta_value' => $user->ID,
            'posts_per_page' => 1
        ]);
        
        if (!empty($instructors)) {
            update_post_meta($instructors[0]->ID, '_waza_email_verified', '1');
            
            // Notify admin about new instructor pending approval
            $this->notify_admin_new_instructor($instructors[0]->ID);
        }
        
        // Show success message
        wp_die(__('Email verified successfully! Your instructor profile is now pending admin approval. You will be notified once approved.', 'waza-booking'), __('Email Verified', 'waza-booking'), ['response' => 200]);
    }
    
    /**
     * Notify admin about new instructor pending approval
     */
    private function notify_admin_new_instructor($instructor_id) {
        $admin_email = get_option('admin_email');
        $instructor_name = get_the_title($instructor_id);
        $review_url = admin_url('post.php?post=' . $instructor_id . '&action=edit');
        
        $subject = __('New Instructor Pending Approval', 'waza-booking');
        $message = sprintf(
            __('A new instructor has verified their email and is pending approval.

Instructor Name: %s
Review Link: %s

Please review and approve or reject the instructor application.', 'waza-booking'),
            $instructor_name,
            $review_url
        );
        
        wp_mail($admin_email, $subject, $message);
    }
}
