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
        // NOTE: waza_instructor_dashboard and waza_instructor_register moved to InstructorFrontend class
        add_shortcode('waza_login_form', [$this, 'login_form_shortcode']);
        add_shortcode('waza_register_form', [$this, 'register_form_shortcode']);
        add_shortcode('waza_my_bookings', [$this, 'my_bookings_shortcode']);
        add_shortcode('waza_my_attendance', [$this, 'my_attendance_shortcode']);
        
        // AJAX actions
        add_action('wp_ajax_nopriv_waza_user_login', [$this, 'ajax_process_login']);
        add_action('wp_ajax_nopriv_waza_user_register', [$this, 'ajax_process_registration']);
        add_action('wp_ajax_waza_get_my_bookings', [$this, 'ajax_get_my_bookings']);
        add_action('wp_ajax_waza_update_profile', [$this, 'ajax_update_profile']);
        add_action('wp_ajax_nopriv_waza_instructor_register', [$this, 'ajax_instructor_register']);
        add_action('wp_ajax_waza_get_slot_roster', [$this, 'ajax_get_slot_roster']);
        add_action('wp_ajax_waza_get_booking_qr', [$this, 'ajax_get_booking_qr']);
        add_action('wp_ajax_waza_download_qr', [$this, 'ajax_download_qr']);
        add_action('wp_ajax_waza_get_my_attendance', [$this, 'ajax_get_my_attendance']);
        add_action('wp_ajax_waza_get_instructor_overview', [$this, 'ajax_get_instructor_overview']);
        add_action('wp_ajax_waza_get_instructor_activities', [$this, 'ajax_get_instructor_activities']);
        add_action('wp_ajax_waza_get_instructor_schedule', [$this, 'ajax_get_instructor_schedule']);
        add_action('wp_ajax_waza_get_instructor_students', [$this, 'ajax_get_instructor_students']);
        add_action('wp_ajax_waza_get_instructor_attendance', [$this, 'ajax_get_instructor_attendance']);
        add_action('wp_ajax_waza_get_booking_details', [$this, 'ajax_get_booking_details']);
        add_action('wp_ajax_waza_get_booking_payment_details', [$this, 'ajax_get_booking_payment_details']);
        
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
            
            // Enqueue dashboard styles and scripts
            wp_enqueue_style('waza-dashboard', WAZA_BOOKING_PLUGIN_URL . 'assets/dashboard.css', [], WAZA_BOOKING_VERSION);
            wp_enqueue_script('waza-dashboard', WAZA_BOOKING_PLUGIN_URL . 'assets/dashboard.js', ['jquery'], WAZA_BOOKING_VERSION, true);
            
            wp_localize_script('waza-account', 'waza_account', [
                'ajax_url' => admin_url('admin-ajax.php'),
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
                        <label for="inst_bio"><?php esc_html_e('Bio / About You', 'waza-booking'); ?></label>
                        <textarea id="inst_bio" name="bio" rows="4" placeholder="Tell us about yourself..."></textarea>
                    </div>
                    <div class="waza-form-group">
                        <label for="inst_skills"><?php esc_html_e('Skills (Comma separated)', 'waza-booking'); ?></label>
                        <input type="text" id="inst_skills" name="skills" placeholder="Yoga, Pilates, Zumba..." required>
                    </div>
                    <div class="waza-form-group">
                        <label for="inst_experience"><?php esc_html_e('Years of Experience', 'waza-booking'); ?></label>
                        <input type="number" id="inst_experience" name="experience" min="0" placeholder="0">
                    </div>
                    <div class="waza-form-group">
                        <label for="inst_certifications"><?php esc_html_e('Certifications', 'waza-booking'); ?></label>
                        <textarea id="inst_certifications" name="certifications" rows="3" placeholder="List your certifications..."></textarea>
                    </div>
                    <div class="waza-form-group">
                        <label><?php esc_html_e('Self Rating', 'waza-booking'); ?></label>
                        <div class="waza-rating-input">
                            <label><input type="radio" name="rating" value="2"> ⭐⭐ (2 Stars)</label>
                            <label><input type="radio" name="rating" value="3"> ⭐⭐⭐ (3 Stars)</label>
                            <label><input type="radio" name="rating" value="5"> ⭐⭐⭐⭐⭐ (5 Stars)</label>
                        </div>
                    </div>
                    <div class="waza-form-group">
                        <label><?php esc_html_e('Social Media Links', 'waza-booking'); ?></label>
                        <div id="social-links-container">
                            <div class="social-link-row">
                                <select name="social_platform[]">
                                    <option value="facebook">Facebook</option>
                                    <option value="instagram">Instagram</option>
                                    <option value="twitter">Twitter</option>
                                    <option value="linkedin">LinkedIn</option>
                                    <option value="youtube">YouTube</option>
                                    <option value="website">Website</option>
                                </select>
                                <input type="url" name="social_url[]" placeholder="https://..." style="flex: 1; margin: 0 10px;">
                                <button type="button" class="remove-social-link" style="background: #dc3232; color: white; border: none; padding: 8px 12px; cursor: pointer; border-radius: 4px;">−</button>
                            </div>
                        </div>
                        <button type="button" id="add-social-link" style="margin-top: 10px; background: #2271b1; color: white; border: none; padding: 8px 15px; cursor: pointer; border-radius: 4px;">+ <?php esc_html_e('Add Social Link', 'waza-booking'); ?></button>
                    </div>
                </div>

                <div class="waza-form-actions">
                    <button type="submit" class="waza-btn waza-btn-primary"><?php esc_html_e('Register as Instructor', 'waza-booking'); ?></button>
                </div>
            </form>
        </div>
        <style>
        .waza-instructor-register-container { max-width: 700px; margin: 0 auto; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .waza-rating-input label { display: block; margin: 10px 0; cursor: pointer; }
        .social-link-row { display: flex; align-items: center; margin-bottom: 10px; }
        .social-link-row select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .social-link-row input[type="url"] { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        #social-links-container { margin-bottom: 10px; }
        </style>
        <script>
        jQuery(document).ready(function($) {
            // Add social link
            $('#add-social-link').on('click', function() {
                var newRow = $('.social-link-row:first').clone();
                newRow.find('input').val('');
                $('#social-links-container').append(newRow);
            });
            
            // Remove social link
            $(document).on('click', '.remove-social-link', function() {
                if ($('.social-link-row').length > 1) {
                    $(this).closest('.social-link-row').remove();
                }
            });
        });
        </script>
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
        $bio = sanitize_textarea_field($_POST['bio'] ?? '');
        $skills = sanitize_text_field($_POST['skills']);
        $experience = intval($_POST['experience'] ?? 0);
        $certifications = sanitize_textarea_field($_POST['certifications'] ?? '');
        $rating = intval($_POST['rating']);
        
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
            update_post_meta($instructor_id, '_waza_bio', $bio);
            update_post_meta($instructor_id, '_waza_experience', $experience);
            update_post_meta($instructor_id, '_waza_certifications', $certifications);
            update_post_meta($instructor_id, '_waza_rating', $rating);
            update_post_meta($instructor_id, '_waza_social_links', $social_links);
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
     * Process user login via AJAX
     */
    public function ajax_process_login() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'waza_account_nonce')) {
            wp_send_json_error(__('Security verification failed', 'waza-booking'));
            return;
        }
        
        // Sanitize input
        $user_login = sanitize_text_field($_POST['user_login'] ?? '');
        $user_password = $_POST['user_password'] ?? '';
        $remember = isset($_POST['remember']);
        
        if (empty($user_login) || empty($user_password)) {
            wp_send_json_error(__('Please enter both email and password', 'waza-booking'));
            return;
        }
        
        // Prepare credentials
        $credentials = [
            'user_login' => $user_login,
            'user_password' => $user_password,
            'remember' => $remember
        ];
        
        // Attempt login
        $user = wp_signon($credentials, false);
        
        if (is_wp_error($user)) {
            wp_send_json_error($user->get_error_message());
            return;
        }
        
        // Check user role and determine redirect
        $redirect_url = home_url('/my-bookings'); // Default redirect to my-bookings
        
        if (in_array('waza_instructor', $user->roles)) {
            $redirect_url = home_url('/instructor-dashboard');
        } elseif (in_array('administrator', $user->roles)) {
            $redirect_url = admin_url();
        }
        // Students and all other users go to my-bookings
        
        // Log login activity
        do_action('waza_log_activity', 'user_login', 'user', $user->ID, [
            'description' => sprintf('User %s logged in', $user->display_name),
            'user_email' => $user->user_email,
            'user_role' => implode(', ', $user->roles)
        ]);
        
        wp_send_json_success([
            'message' => __('Login successful!', 'waza-booking'),
            'redirect' => $redirect_url
        ]);
    }
    
    /**
     * Process user registration via AJAX
     */
    public function ajax_process_registration() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'waza_account_nonce')) {
            wp_send_json_error(__('Security verification failed', 'waza-booking'));
            return;
        }
        
        // Sanitize input
        $user_name = sanitize_text_field($_POST['user_name'] ?? '');
        $user_email = sanitize_email($_POST['user_email'] ?? '');
        $user_password = $_POST['user_password'] ?? '';
        
        if (empty($user_name) || empty($user_email) || empty($user_password)) {
            wp_send_json_error(__('Please fill in all required fields', 'waza-booking'));
            return;
        }
        
        // Check if email already exists
        if (email_exists($user_email)) {
            wp_send_json_error(__('This email is already registered', 'waza-booking'));
            return;
        }
        
        // Create user
        $user_id = wp_create_user($user_email, $user_password, $user_email);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error($user_id->get_error_message());
            return;
        }
        
        // Update user data
        wp_update_user([
            'ID' => $user_id,
            'display_name' => $user_name,
            'first_name' => $user_name
        ]);
        
        // Set user role
        $user = new \WP_User($user_id);
        $user->set_role('waza_student');
        
        // Log registration activity
        do_action('waza_log_activity', 'user_registered', 'user', $user_id, [
            'description' => sprintf('New user registered: %s', $user_name),
            'user_email' => $user_email,
            'account_type' => 'student'
        ]);
        
        // Send welcome email
        wp_new_user_notification($user_id, null, 'user');
        
        wp_send_json_success([
            'message' => __('Account created successfully! You can now log in.', 'waza-booking')
        ]);
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
        
        // Use email template manager
        $email_template_manager = \WazaBooking\Core\Plugin::get_instance()->get_manager('email_template');
        
        if ($email_template_manager) {
            $email_template_manager->send_email('welcome_email', $email, [
                'user_name' => $name,
                'user_first_name' => $name,
                'verification_url' => $verification_url,
                'message' => __('Thank you for registering as an instructor! Please verify your email address.', 'waza-booking')
            ]);
        }
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
            <div class="waza-dashboard-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2><?php printf(__('Welcome, %s', 'waza-booking'), wp_get_current_user()->display_name); ?></h2>
                </div>
                <!-- Announcements Bell Widget -->
                <div class="waza-announcements-widget">
                    <button class="waza-announcements-bell" id="waza-student-announcements-btn" data-target="students">
                        <svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2zM8 1.918l-.797.161A4.002 4.002 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4.002 4.002 0 0 0-3.203-3.92L8 1.917zM14.22 12c.223.447.481.801.78 1H1c.299-.199.557-.553.78-1C2.68 10.2 3 6.88 3 6c0-2.42 1.72-4.44 4.005-4.901a1 1 0 1 1 1.99 0A5.002 5.002 0 0 1 13 6c0 .88.32 4.2 1.22 6z"/>
                        </svg>
                        <span class="waza-announcements-count" style="display: none;">0</span>
                    </button>
                </div>
            </div>
            
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
            <div class="waza-dashboard-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2><?php printf(__('Welcome, %s', 'waza-booking'), get_the_title($instructor->ID)); ?></h2>
                    <p class="waza-subtitle"><?php esc_html_e('Manage your activities, schedule, and students', 'waza-booking'); ?></p>
                </div>
                <!-- Announcements Bell Widget -->
                <div class="waza-announcements-widget">
                    <a href="<?php echo esc_url(home_url('/announcements/')); ?>" class="waza-announcements-bell" id="waza-instructor-announcements-btn" data-target="instructors" style="text-decoration: none; color: white;">
                        <svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2zM8 1.918l-.797.161A4.002 4.002 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4.002 4.002 0 0 0-3.203-3.92L8 1.917zM14.22 12c.223.447.481.801.78 1H1c.299-.199.557-.553.78-1C2.68 10.2 3 6.88 3 6c0-2.42 1.72-4.44 4.005-4.901a1 1 0 1 1 1.99 0A5.002 5.002 0 0 1 13 6c0 .88.32 4.2 1.22 6z"/>
                        </svg>
                        <span class="waza-announcements-count" style="display: none;">0</span>
                    </a>
                </div>
            </div>

            <!-- Dashboard Navigation Tabs -->
            <div class="waza-dashboard-tabs">
                <button class="waza-tab-btn active" data-tab="overview"><?php esc_html_e('Overview', 'waza-booking'); ?></button>
                <button class="waza-tab-btn" data-tab="activities"><?php esc_html_e('My Activities', 'waza-booking'); ?></button>
                <button class="waza-tab-btn" data-tab="schedule"><?php esc_html_e('My Schedule', 'waza-booking'); ?></button>
                <button class="waza-tab-btn" data-tab="students"><?php esc_html_e('Students', 'waza-booking'); ?></button>
                <button class="waza-tab-btn" data-tab="attendance"><?php esc_html_e('Attendance Tracker', 'waza-booking'); ?></button>
            </div>

            <!-- Overview Tab -->
            <div class="waza-tab-content active" data-tab-content="overview">
                
                <div class="waza-stats-grid">
                    <div class="waza-stat-card">
                        <div class="waza-stat-icon">🎯</div>
                        <div class="waza-stat-info">
                            <h3 id="instructor-total-activities">0</h3>
                            <p><?php esc_html_e('Active Activities', 'waza-booking'); ?></p>
                        </div>
                    </div>
                    <div class="waza-stat-card">
                        <div class="waza-stat-icon">📅</div>
                        <div class="waza-stat-info">
                            <h3 id="instructor-upcoming-slots">0</h3>
                            <p><?php esc_html_e('Upcoming Slots', 'waza-booking'); ?></p>
                        </div>
                    </div>
                    <div class="waza-stat-card">
                        <div class="waza-stat-icon">👥</div>
                        <div class="waza-stat-info">
                            <h3 id="instructor-total-students">0</h3>
                            <p><?php esc_html_e('Total Students', 'waza-booking'); ?></p>
                        </div>
                    </div>
                    <div class="waza-stat-card">
                        <div class="waza-stat-icon">✅</div>
                        <div class="waza-stat-info">
                            <h3 id="instructor-today-attendance">0</h3>
                            <p><?php esc_html_e('Today\'s Attendance', 'waza-booking'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="waza-quick-actions">
                    <h3><?php esc_html_e('Quick Actions', 'waza-booking'); ?></h3>
                    <div class="waza-actions-grid">
                        <a href="<?php echo admin_url('post-new.php?post_type=waza_activity'); ?>" class="waza-action-card">
                            <span class="waza-action-icon">➕</span>
                            <span><?php esc_html_e('Create Activity', 'waza-booking'); ?></span>
                        </a>
                        <button class="waza-action-card" onclick="document.querySelector('[data-tab=schedule]').click()">
                            <span class="waza-action-icon">📅</span>
                            <span><?php esc_html_e('View Schedule', 'waza-booking'); ?></span>
                        </button>
                        <button class="waza-action-card" onclick="document.querySelector('[data-tab=attendance]').click()">
                            <span class="waza-action-icon">📊</span>
                            <span><?php esc_html_e('Track Attendance', 'waza-booking'); ?></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Activities Tab -->
            <div class="waza-tab-content" data-tab-content="activities">
                <div id="instructor-activities-list">
                    <div class="waza-loading"><?php esc_html_e('Loading activities...', 'waza-booking'); ?></div>
                </div>
            </div>

            <!-- Schedule Tab -->
            <div class="waza-tab-content" data-tab-content="schedule">
                <div class="waza-schedule-filters">
                    <button class="waza-schedule-filter-btn active" data-filter="upcoming"><?php esc_html_e('Upcoming', 'waza-booking'); ?></button>
                    <button class="waza-schedule-filter-btn" data-filter="today"><?php esc_html_e('Today', 'waza-booking'); ?></button>
                    <button class="waza-schedule-filter-btn" data-filter="all"><?php esc_html_e('All', 'waza-booking'); ?></button>
                </div>
                <div id="instructor-schedule-list">
                    <div class="waza-loading"><?php esc_html_e('Loading schedule...', 'waza-booking'); ?></div>
                </div>
            </div>

            <!-- Students Tab -->
            <div class="waza-tab-content" data-tab-content="students">
                <div id="instructor-students-list">
                    <div class="waza-loading"><?php esc_html_e('Loading students...', 'waza-booking'); ?></div>
                </div>
            </div>

            <!-- Attendance Tab -->
            <div class="waza-tab-content" data-tab-content="attendance">
                <div class="waza-attendance-filters">
                    <button class="waza-attendance-filter-btn active" data-filter="today"><?php esc_html_e('Today', 'waza-booking'); ?></button>
                    <button class="waza-attendance-filter-btn" data-filter="this-week"><?php esc_html_e('This Week', 'waza-booking'); ?></button>
                    <button class="waza-attendance-filter-btn" data-filter="this-month"><?php esc_html_e('This Month', 'waza-booking'); ?></button>
                </div>
                <div id="instructor-attendance-list">
                    <div class="waza-loading"><?php esc_html_e('Loading attendance records...', 'waza-booking'); ?></div>
                </div>
            </div>
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
            <!-- Header with Announcements Bell -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="margin: 0;"><?php esc_html_e('My Bookings', 'waza-booking'); ?></h2>
                <div class="waza-announcements-widget">
                    <a href="<?php echo esc_url(home_url('/announcements/')); ?>" class="waza-announcements-bell" id="waza-student-announcements-btn" data-target="students" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; text-decoration: none; color: white;">
                        <svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2zM8 1.918l-.797.161A4.002 4.002 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4.002 4.002 0 0 0-3.203-3.92L8 1.917zM14.22 12c.223.447.481.801.78 1H1c.299-.199.557-.553.78-1C2.68 10.2 3 6.88 3 6c0-2.42 1.72-4.44 4.005-4.901a1 1 0 1 1 1.99 0A5.002 5.002 0 0 1 13 6c0 .88.32 4.2 1.22 6z"/>
                        </svg>
                        <span class="waza-announcements-count" style="display: none;">0</span>
                    </a>
                </div>
            </div>

            <div class="waza-dashboard-stats">
                <!-- Stats will be populated by JavaScript -->
            </div>
            
            <div class="waza-bookings-toolbar">
                <div class="waza-bookings-filter">
                    <button class="waza-filter-btn active" data-filter="all">📋 All Bookings</button>
                    <button class="waza-filter-btn" data-filter="upcoming">🔜 Upcoming</button>
                    <button class="waza-filter-btn" data-filter="past">✅ Past</button>
                </div>
                <div class="waza-search-box">
                    <span class="waza-search-icon">🔍</span>
                    <input type="text" class="waza-search-input" placeholder="Search bookings...">
                </div>
            </div>
            
            <div id="waza-bookings-list">
                <!-- Table will be rendered by JavaScript -->
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX get my bookings
     */
    public function ajax_get_my_bookings() {
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in', 'waza-booking'));
        }

        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        
        global $wpdb;
        
        // Get all bookings for this user
        $bookings = $wpdb->get_results($wpdb->prepare("
            SELECT b.*, 
                   s.start_datetime, 
                   s.end_datetime,
                   s.capacity,
                   p.post_title as activity_title,
                   p.ID as activity_id
            FROM {$wpdb->prefix}waza_bookings b
            LEFT JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
            LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
            WHERE b.user_id = %d OR b.user_email = %s
            ORDER BY b.created_at DESC
        ", $user_id, $user->user_email));
        
        // Format bookings with additional data
        $formatted_bookings = [];
        $current_time = current_time('mysql');
        
        foreach ($bookings as $booking) {
            $is_upcoming = strtotime($booking->start_datetime) > strtotime($current_time);
            
            // Get activity featured image
            $featured_image = get_the_post_thumbnail_url($booking->activity_id, 'medium');
            
            $formatted_bookings[] = [
                'id' => $booking->id,
                'activity_title' => $booking->activity_title,
                'activity_id' => $booking->activity_id,
                'activity_image' => $featured_image ?: WAZA_BOOKING_PLUGIN_URL . 'assets/images/placeholder.jpg',
                'start_datetime' => $booking->start_datetime,
                'end_datetime' => $booking->end_datetime,
                'formatted_date' => date_i18n('F j, Y', strtotime($booking->start_datetime)),
                'formatted_time' => date_i18n('g:i A', strtotime($booking->start_datetime)) . ' - ' . date_i18n('g:i A', strtotime($booking->end_datetime)),
                'quantity' => $booking->quantity,
                'total_amount' => number_format($booking->total_amount, 2),
                'payment_status' => $booking->payment_status,
                'booking_status' => $booking->booking_status,
                'qr_token' => $booking->qr_token,
                'qr_code_url' => $booking->qr_code_url ?? '',
                'is_upcoming' => $is_upcoming,
                'attended' => $booking->attended,
                'created_at' => date_i18n('F j, Y g:i A', strtotime($booking->created_at))
            ];
        }
        
        wp_send_json_success([
            'bookings' => $formatted_bookings,
            'total' => count($formatted_bookings)
        ]);
    }
    
    /**
     * AJAX download QR code
     */
    public function ajax_download_qr() {
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in', 'waza-booking'));
        }

        $booking_id = intval($_POST['booking_id'] ?? 0);
        $user_id = get_current_user_id();
        
        if (!$booking_id) {
            wp_send_json_error(__('Invalid booking ID', 'waza-booking'));
        }
        
        global $wpdb;
        
        // Verify booking belongs to user
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT b.*, s.activity_id, s.start_datetime 
            FROM {$wpdb->prefix}waza_bookings b
            LEFT JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
            WHERE b.id = %d AND b.user_id = %d
        ", $booking_id, $user_id));
        
        if (!$booking) {
            wp_send_json_error(__('Booking not found', 'waza-booking'));
        }
        
        // Get QR Manager
        $qr_manager = new \WazaBooking\QR\QRManager();
        
        // Generate master QR token if not exists
        if (empty($booking->qr_token)) {
            $booking->qr_token = $qr_manager->generate_qr_token($booking->id, $booking->slot_id, 'single');
        }
        
        // Check if this is a multi-seat booking
        $attendees = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_booking_attendees
            WHERE booking_id = %d
            ORDER BY seat_number ASC
        ", $booking_id));
        
        $qr_codes = [];
        
        if (!empty($attendees)) {
            // Multi-seat booking - generate individual QR codes
            foreach ($attendees as $attendee) {
                // Generate QR token for attendee if not exists
                if (empty($attendee->qr_token)) {
                    $attendee_token = 'WAZA-ATT-' . $booking_id . '-' . $attendee->seat_number . '-' . wp_generate_password(12, false);
                    $wpdb->update(
                        $wpdb->prefix . 'waza_booking_attendees',
                        ['qr_token' => $attendee_token],
                        ['id' => $attendee->id]
                    );
                    $attendee->qr_token = $attendee_token;
                }
                
                // Prepare QR data with all necessary info for scanning
                $qr_data = [
                    'type' => 'attendee',
                    'booking_id' => $booking->id,
                    'attendee_id' => $attendee->id,
                    'token' => $attendee->qr_token,
                    'seat_number' => $attendee->seat_number,
                    'name' => $attendee->attendee_name,
                    'email' => $attendee->attendee_email,
                    'slot_id' => $booking->slot_id,
                    'activity_id' => $booking->activity_id
                ];
                
                $qr_string = json_encode($qr_data);
                $qr_image = $qr_manager->generate_qr_image($qr_string, 400);
                
                $qr_codes[] = [
                    'seat_number' => $attendee->seat_number,
                    'name' => $attendee->attendee_name,
                    'qr_image' => $qr_image,
                    'token' => $attendee->qr_token
                ];
            }
        } else {
            // Single seat booking - use master booking QR
            $qr_data = [
                'type' => 'booking',
                'booking_id' => $booking->id,
                'token' => $booking->qr_token,
                'email' => $booking->user_email,
                'slot_id' => $booking->slot_id,
                'activity_id' => $booking->activity_id
            ];
            
            $qr_string = json_encode($qr_data);
            $qr_image = $qr_manager->generate_qr_image($qr_string, 400);
            
            $qr_codes[] = [
                'seat_number' => 1,
                'name' => $booking->user_name,
                'qr_image' => $qr_image,
                'token' => $booking->qr_token
            ];
        }
        
        wp_send_json_success([
            'qr_codes' => $qr_codes,
            'booking_id' => $booking_id,
            'is_multi_seat' => !empty($attendees)
        ]);
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
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Update user data
        $update_data = [
            'ID' => $user_id,
            'display_name' => $display_name,
            'user_email' => $user_email
        ];
        
        // Handle password change if provided
        if (!empty($new_password)) {
            // Verify current password
            $user = wp_get_current_user();
            if (!wp_check_password($current_password, $user->user_pass, $user_id)) {
                wp_send_json_error(['message' => __('Current password is incorrect', 'waza-booking')]);
            }
            
            // Check if passwords match
            if ($new_password !== $confirm_password) {
                wp_send_json_error(['message' => __('New passwords do not match', 'waza-booking')]);
            }
            
            // Validate password strength (minimum 6 characters)
            if (strlen($new_password) < 6) {
                wp_send_json_error(['message' => __('Password must be at least 6 characters long', 'waza-booking')]);
            }
            
            $update_data['user_pass'] = $new_password;
        }
        
        // Update user
        $result = wp_update_user($update_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        // Update meta
        update_user_meta($user_id, 'phone', $phone);
        
        // Log profile update activity
        do_action('waza_log_activity', 'profile_updated', 'user', $user_id, [
            'description' => sprintf('Profile updated by %s', $display_name),
            'password_changed' => !empty($new_password)
        ]);
        
        $message = !empty($new_password) 
            ? __('Profile and password updated successfully!', 'waza-booking')
            : __('Profile updated successfully!', 'waza-booking');
        
        wp_send_json_success(['message' => $message]);
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
    
    /**
     * My Attendance shortcode
     * Displays user's attendance history
     */
    public function my_attendance_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p class="waza-error">' . __('Please log in to view your attendance history.', 'waza-booking') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="waza-my-attendance-container">
            <div class="waza-attendance-header">
                <h2><?php esc_html_e('My Attendance History', 'waza-booking'); ?></h2>
                <div class="waza-attendance-filters">
                    <button class="waza-filter-btn active" data-filter="all"><?php esc_html_e('All', 'waza-booking'); ?></button>
                    <button class="waza-filter-btn" data-filter="this-month"><?php esc_html_e('This Month', 'waza-booking'); ?></button>
                    <button class="waza-filter-btn" data-filter="this-week"><?php esc_html_e('This Week', 'waza-booking'); ?></button>
                </div>
            </div>
            
            <div class="waza-attendance-stats">
                <div class="waza-stat-card">
                    <div class="waza-stat-icon">📊</div>
                    <div class="waza-stat-content">
                        <h3 id="waza-total-sessions">0</h3>
                        <p><?php esc_html_e('Total Sessions', 'waza-booking'); ?></p>
                    </div>
                </div>
                <div class="waza-stat-card">
                    <div class="waza-stat-icon">✅</div>
                    <div class="waza-stat-content">
                        <h3 id="waza-completed-sessions">0</h3>
                        <p><?php esc_html_e('Completed', 'waza-booking'); ?></p>
                    </div>
                </div>
                <div class="waza-stat-card">
                    <div class="waza-stat-icon">⏰</div>
                    <div class="waza-stat-content">
                        <h3 id="waza-total-hours">0h</h3>
                        <p><?php esc_html_e('Total Hours', 'waza-booking'); ?></p>
                    </div>
                </div>
            </div>
            
            <div id="waza-attendance-list" class="waza-attendance-list">
                <div class="waza-loading"><?php esc_html_e('Loading attendance records...', 'waza-booking'); ?></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX get my attendance
     * Returns user's attendance history
     */
    public function ajax_get_my_attendance() {
        check_ajax_referer('waza_account_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Not logged in', 'waza-booking')]);
        }
        
        $user_id = get_current_user_id();
        $filter = sanitize_text_field($_POST['filter'] ?? 'all');
        
        global $wpdb;
        
        // Base query
        $where = $wpdb->prepare("a.user_id = %d", $user_id);
        
        // Apply filters
        if ($filter === 'this-month') {
            $where .= " AND MONTH(s.start_datetime) = MONTH(CURRENT_DATE()) AND YEAR(s.start_datetime) = YEAR(CURRENT_DATE())";
        } elseif ($filter === 'this-week') {
            $where .= " AND YEARWEEK(s.start_datetime, 1) = YEARWEEK(CURRENT_DATE(), 1)";
        }
        
        // Get attendance records with slot and activity details
        $records = $wpdb->get_results("
            SELECT 
                a.*,
                s.start_datetime,
                s.end_datetime,
                s.activity_id,
                p.post_title as activity_name
            FROM {$wpdb->prefix}waza_attendance a
            INNER JOIN {$wpdb->prefix}waza_slots s ON a.slot_id = s.id
            LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
            WHERE $where
            ORDER BY s.start_datetime DESC
        ");
        
        // Calculate statistics
        $total_sessions = count($records);
        $completed_sessions = 0;
        $total_minutes = 0;
        
        foreach ($records as $record) {
            if ($record->check_out_time) {
                $completed_sessions++;
                $check_in = strtotime($record->check_in_time);
                $check_out = strtotime($record->check_out_time);
                $total_minutes += ($check_out - $check_in) / 60;
            }
        }
        
        $total_hours = round($total_minutes / 60, 1);
        
        // Format records for display
        $formatted_records = [];
        foreach ($records as $record) {
            $slot_date = date('Y-m-d', strtotime($record->start_datetime));
            $slot_time = date('h:i A', strtotime($record->start_datetime)) . ' - ' . date('h:i A', strtotime($record->end_datetime));
            
            $check_in_time = $record->check_in_time ? date('h:i A', strtotime($record->check_in_time)) : '—';
            $check_out_time = $record->check_out_time ? date('h:i A', strtotime($record->check_out_time)) : '—';
            
            $duration = '—';
            $status = 'checked-in';
            $status_label = __('Checked In', 'waza-booking');
            
            if ($record->check_out_time) {
                $check_in = strtotime($record->check_in_time);
                $check_out = strtotime($record->check_out_time);
                $minutes = ($check_out - $check_in) / 60;
                
                if ($minutes >= 60) {
                    $hours = floor($minutes / 60);
                    $mins = $minutes % 60;
                    $duration = sprintf('%dh %dm', $hours, $mins);
                } else {
                    $duration = sprintf('%dm', $minutes);
                }
                
                $status = 'completed';
                $status_label = __('Completed', 'waza-booking');
            }
            
            $formatted_records[] = [
                'id' => $record->id,
                'activity_name' => $record->activity_name,
                'date' => date('M j, Y', strtotime($record->start_datetime)),
                'day_of_week' => date('l', strtotime($record->start_datetime)),
                'slot_time' => $slot_time,
                'check_in_time' => $check_in_time,
                'check_out_time' => $check_out_time,
                'duration' => $duration,
                'entry_method' => $record->entry_method,
                'exit_method' => $record->exit_method,
                'status' => $status,
                'status_label' => $status_label
            ];
        }
        
        wp_send_json_success([
            'records' => $formatted_records,
            'stats' => [
                'total_sessions' => $total_sessions,
                'completed_sessions' => $completed_sessions,
                'total_hours' => $total_hours
            ]
        ]);
    }

    /**
     * AJAX get instructor overview stats
     */
    public function ajax_get_instructor_overview() {
        check_ajax_referer('waza_account_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Not logged in', 'waza-booking')]);
        }
        
        $user_id = get_current_user_id();
        global $wpdb;
        
        // Get instructor post
        $instructors = get_posts([
            'post_type' => 'waza_instructor',
            'meta_key' => '_waza_user_id',
            'meta_value' => $user_id,
            'posts_per_page' => 1
        ]);
        
        if (empty($instructors)) {
            wp_send_json_error(['message' => __('Not an instructor', 'waza-booking')]);
        }
        
        $instructor_id = $instructors[0]->ID;
        
        // Get active activities count
        $activities_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->posts}
            WHERE post_type = 'waza_activity'
            AND post_status = 'publish'
            AND ID IN (
                SELECT meta_value FROM {$wpdb->postmeta}
                WHERE meta_key = '_waza_instructor_id' AND meta_value = %d
            )
        ", $instructor_id));
        
        // Get upcoming slots count
        $upcoming_slots = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}waza_slots s
            INNER JOIN {$wpdb->postmeta} pm ON s.activity_id = pm.post_id
            WHERE pm.meta_key = '_waza_instructor_id'
            AND pm.meta_value = %d
            AND s.start_datetime >= NOW()
        ", $instructor_id));
        
        // Get total students (unique bookings)
        $total_students = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT b.user_id)
            FROM {$wpdb->prefix}waza_bookings b
            INNER JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
            INNER JOIN {$wpdb->postmeta} pm ON s.activity_id = pm.post_id
            WHERE pm.meta_key = '_waza_instructor_id'
            AND pm.meta_value = %d
            AND b.booking_status = 'confirmed'
        ", $instructor_id));
        
        // Get today's attendance count
        $today_attendance = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->prefix}waza_attendance a
            INNER JOIN {$wpdb->prefix}waza_slots s ON a.slot_id = s.id
            INNER JOIN {$wpdb->postmeta} pm ON s.activity_id = pm.post_id
            WHERE pm.meta_key = '_waza_instructor_id'
            AND pm.meta_value = %d
            AND DATE(s.start_datetime) = CURDATE()
        ", $instructor_id));
        
        wp_send_json_success([
            'total_activities' => intval($activities_count),
            'upcoming_slots' => intval($upcoming_slots),
            'total_students' => intval($total_students),
            'today_attendance' => intval($today_attendance)
        ]);
    }

    /**
     * AJAX get instructor activities
     */
    public function ajax_get_instructor_activities() {
        check_ajax_referer('waza_account_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Not logged in', 'waza-booking')]);
        }
        
        $user_id = get_current_user_id();
        
        // Get instructor post
        $instructors = get_posts([
            'post_type' => 'waza_instructor',
            'meta_key' => '_waza_user_id',
            'meta_value' => $user_id,
            'posts_per_page' => 1
        ]);
        
        if (empty($instructors)) {
            wp_send_json_error(['message' => __('Not an instructor', 'waza-booking')]);
        }
        
        $instructor_id = $instructors[0]->ID;
        
        // Get activities
        $activities = get_posts([
            'post_type' => 'waza_activity',
            'meta_key' => '_waza_instructor_id',
            'meta_value' => $instructor_id,
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
        
        $formatted_activities = [];
        global $wpdb;
        
        foreach ($activities as $activity) {
            $price = get_post_meta($activity->ID, '_waza_price', true);
            $duration = get_post_meta($activity->ID, '_waza_duration', true);
            $capacity = get_post_meta($activity->ID, '_waza_capacity', true);
            
            // Get total slots
            $total_slots = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM {$wpdb->prefix}waza_slots
                WHERE activity_id = %d
            ", $activity->ID));
            
            // Get total bookings
            $total_bookings = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM {$wpdb->prefix}waza_bookings b
                INNER JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
                WHERE s.activity_id = %d AND b.booking_status = 'confirmed'
            ", $activity->ID));
            
            $formatted_activities[] = [
                'id' => $activity->ID,
                'title' => $activity->post_title,
                'description' => wp_trim_words($activity->post_content, 20),
                'price' => $price ? '₹' . number_format($price) : 'Free',
                'duration' => $duration ? $duration . ' mins' : '—',
                'capacity' => $capacity ? $capacity . ' per slot' : '—',
                'total_slots' => intval($total_slots),
                'total_bookings' => intval($total_bookings),
                'edit_link' => admin_url('post.php?post=' . $activity->ID . '&action=edit')
            ];
        }
        
        wp_send_json_success(['activities' => $formatted_activities]);
    }

    /**
     * AJAX get instructor schedule
     */
    public function ajax_get_instructor_schedule() {
        check_ajax_referer('waza_account_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Not logged in', 'waza-booking')]);
        }
        
        $user_id = get_current_user_id();
        $filter = sanitize_text_field($_POST['filter'] ?? 'upcoming');
        
        // Get instructor post
        $instructors = get_posts([
            'post_type' => 'waza_instructor',
            'meta_key' => '_waza_user_id',
            'meta_value' => $user_id,
            'posts_per_page' => 1
        ]);
        
        if (empty($instructors)) {
            wp_send_json_error(['message' => __('Not an instructor', 'waza-booking')]);
        }
        
        $instructor_id = $instructors[0]->ID;
        global $wpdb;
        
        // Build where clause based on filter
        $where = "pm.meta_key = '_waza_instructor_id' AND pm.meta_value = " . intval($instructor_id);
        
        if ($filter === 'upcoming') {
            $where .= " AND s.start_datetime >= NOW()";
            $order = "s.start_datetime ASC";
        } elseif ($filter === 'today') {
            $where .= " AND DATE(s.start_datetime) = CURDATE()";
            $order = "s.start_datetime ASC";
        } else {
            $order = "s.start_datetime DESC";
        }
        
        // Get slots
        $slots = $wpdb->get_results("
            SELECT 
                s.*,
                p.post_title as activity_name
            FROM {$wpdb->prefix}waza_slots s
            INNER JOIN {$wpdb->postmeta} pm ON s.activity_id = pm.post_id
            INNER JOIN {$wpdb->posts} p ON s.activity_id = p.ID
            WHERE $where
            ORDER BY $order
            LIMIT 50
        ");
        
        $formatted_slots = [];
        foreach ($slots as $slot) {
            // Get bookings count
            $bookings_count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM {$wpdb->prefix}waza_bookings
                WHERE slot_id = %d AND booking_status = 'confirmed'
            ", $slot->id));
            
            $formatted_slots[] = [
                'id' => $slot->id,
                'activity_name' => $slot->activity_name,
                'date' => date('M j, Y', strtotime($slot->start_datetime)),
                'day' => date('l', strtotime($slot->start_datetime)),
                'time' => date('h:i A', strtotime($slot->start_datetime)) . ' - ' . date('h:i A', strtotime($slot->end_datetime)),
                'capacity' => $slot->capacity,
                'booked' => intval($bookings_count),
                'available' => $slot->capacity - intval($bookings_count),
                'status' => strtotime($slot->start_datetime) < time() ? 'past' : 'upcoming'
            ];
        }
        
        wp_send_json_success(['slots' => $formatted_slots]);
    }

    /**
     * AJAX get instructor students
     */
    public function ajax_get_instructor_students() {
        check_ajax_referer('waza_account_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Not logged in', 'waza-booking')]);
        }
        
        $user_id = get_current_user_id();
        
        // Get instructor post
        $instructors = get_posts([
            'post_type' => 'waza_instructor',
            'meta_key' => '_waza_user_id',
            'meta_value' => $user_id,
            'posts_per_page' => 1
        ]);
        
        if (empty($instructors)) {
            wp_send_json_error(['message' => __('Not an instructor', 'waza-booking')]);
        }
        
        $instructor_id = $instructors[0]->ID;
        global $wpdb;
        
        // Get unique students with their booking info
        $students = $wpdb->get_results($wpdb->prepare("
            SELECT 
                u.ID as user_id,
                u.display_name,
                u.user_email,
                COUNT(DISTINCT b.id) as total_bookings,
                COUNT(DISTINCT a.id) as total_attendance,
                MAX(s.start_datetime) as last_activity_date
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->prefix}waza_bookings b ON u.ID = b.user_id
            INNER JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
            INNER JOIN {$wpdb->postmeta} pm ON s.activity_id = pm.post_id
            LEFT JOIN {$wpdb->prefix}waza_attendance a ON b.id = a.booking_id
            WHERE pm.meta_key = '_waza_instructor_id'
            AND pm.meta_value = %d
            AND b.booking_status = 'confirmed'
            GROUP BY u.ID
            ORDER BY MAX(s.start_datetime) DESC
        ", $instructor_id));
        
        $formatted_students = [];
        foreach ($students as $student) {
            $formatted_students[] = [
                'user_id' => $student->user_id,
                'name' => $student->display_name,
                'email' => $student->user_email,
                'total_bookings' => intval($student->total_bookings),
                'total_attendance' => intval($student->total_attendance),
                'last_activity' => $student->last_activity_date ? date('M j, Y', strtotime($student->last_activity_date)) : '—'
            ];
        }
        
        wp_send_json_success(['students' => $formatted_students]);
    }

    /**
     * AJAX get instructor attendance records
     */
    public function ajax_get_instructor_attendance() {
        check_ajax_referer('waza_account_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Not logged in', 'waza-booking')]);
        }
        
        $user_id = get_current_user_id();
        $filter = sanitize_text_field($_POST['filter'] ?? 'today');
        
        // Get instructor post
        $instructors = get_posts([
            'post_type' => 'waza_instructor',
            'meta_key' => '_waza_user_id',
            'meta_value' => $user_id,
            'posts_per_page' => 1
        ]);
        
        if (empty($instructors)) {
            wp_send_json_error(['message' => __('Not an instructor', 'waza-booking')]);
        }
        
        $instructor_id = $instructors[0]->ID;
        global $wpdb;
        
        // Build where clause based on filter
        $where = "s.instructor_id = " . intval($instructor_id);
        
        if ($filter === 'today') {
            $where .= " AND DATE(s.start_datetime) = CURDATE()";
        } elseif ($filter === 'this-week') {
            $where .= " AND YEARWEEK(s.start_datetime, 1) = YEARWEEK(CURRENT_DATE(), 1)";
        } elseif ($filter === 'this-month') {
            $where .= " AND MONTH(s.start_datetime) = MONTH(CURRENT_DATE()) AND YEAR(s.start_datetime) = YEAR(CURRENT_DATE())";
        }
        
        // Get attendance records
        $records = $wpdb->get_results("
            SELECT 
                a.*,
                s.start_datetime,
                s.end_datetime,
                p.post_title as activity_name,
                u.display_name as student_name
            FROM {$wpdb->prefix}waza_attendance a
            INNER JOIN {$wpdb->prefix}waza_slots s ON a.slot_id = s.id
            INNER JOIN {$wpdb->posts} p ON s.activity_id = p.ID
            INNER JOIN {$wpdb->users} u ON a.user_id = u.ID
            WHERE $where
            ORDER BY s.start_datetime DESC
        ");
        
        $formatted_records = [];
        foreach ($records as $record) {
            $check_in_time = $record->check_in_time ? date('h:i A', strtotime($record->check_in_time)) : '—';
            $check_out_time = $record->check_out_time ? date('h:i A', strtotime($record->check_out_time)) : '—';
            
            $duration = '—';
            $status = 'checked-in';
            
            if ($record->check_out_time) {
                $check_in = strtotime($record->check_in_time);
                $check_out = strtotime($record->check_out_time);
                $minutes = ($check_out - $check_in) / 60;
                
                if ($minutes >= 60) {
                    $hours = floor($minutes / 60);
                    $mins = $minutes % 60;
                    $duration = sprintf('%dh %dm', $hours, $mins);
                } else {
                    $duration = sprintf('%dm', $minutes);
                }
                
                $status = 'completed';
            }
            
            $formatted_records[] = [
                'student_name' => $record->student_name,
                'activity_name' => $record->activity_name,
                'date' => date('M j, Y', strtotime($record->start_datetime)),
                'slot_time' => date('h:i A', strtotime($record->start_datetime)) . ' - ' . date('h:i A', strtotime($record->end_datetime)),
                'check_in' => $check_in_time,
                'check_out' => $check_out_time,
                'duration' => $duration,
                'entry_method' => $record->entry_method,
                'exit_method' => $record->exit_method,
                'status' => $status
            ];
        }
        
        wp_send_json_success(['records' => $formatted_records]);
    }
    
    /**
     * AJAX get booking details
     */
    public function ajax_get_booking_details() {
        check_ajax_referer('waza_account_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Not logged in', 'waza-booking')]);
        }
        
        $booking_id = intval($_POST['booking_id'] ?? 0);
        
        if (!$booking_id) {
            wp_send_json_error(['message' => __('Invalid booking ID', 'waza-booking')]);
        }
        
        global $wpdb;
        
        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        
        // Get booking details - match by user_id OR user_email (for pending bookings)
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT 
                b.*,
                s.start_datetime,
                s.end_datetime,
                p.post_title as activity_title
            FROM {$wpdb->prefix}waza_bookings b
            LEFT JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
            LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
            WHERE b.id = %d AND (b.user_id = %d OR b.user_email = %s)
        ", $booking_id, $user_id, $user->user_email));
        
        if (!$booking) {
            wp_send_json_error(['message' => __('Booking not found', 'waza-booking')]);
        }
        
        // Get attendees for this booking (for multi-seat bookings)
        // Check if table exists first to avoid errors
        $table_name = $wpdb->prefix . 'waza_booking_attendees';
        $attendees = [];
        $booking_attended = $booking->attended ? true : false;
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name) {
            $attendees = $wpdb->get_results($wpdb->prepare("
                SELECT 
                    attendee_name as name,
                    attendee_email as email,
                    attendee_phone as phone,
                    seat_number
                FROM {$wpdb->prefix}waza_booking_attendees
                WHERE booking_id = %d
                ORDER BY seat_number ASC
            ", $booking_id));
            
            // Add attended status to all attendees (same for all since it's tracked at booking level)
            foreach ($attendees as $attendee) {
                $attendee->attended = $booking_attended;
            }
        }
        
        // Count attended
        $attended_count = $booking_attended ? count($attendees) : 0;
        
        // Format the booking data with proper status display
        $formatted_booking = [
            'id' => $booking->id,
            'activity_title' => $booking->activity_title,
            'formatted_date' => date('l, F j, Y', strtotime($booking->start_datetime)),
            'formatted_time' => date('g:i A', strtotime($booking->start_datetime)) . ' - ' . date('g:i A', strtotime($booking->end_datetime)),
            'quantity' => $booking->quantity,
            'total_amount' => number_format($booking->total_amount, 2),
            'booking_status' => $booking->booking_status, // Keep original for comparison
            'payment_status' => $booking->payment_status, // Keep original for comparison
            'booking_status_display' => ucfirst($booking->booking_status),
            'payment_status_display' => $booking->payment_status === 'completed' ? 'Completed' : 'Pending Payment',
            'user_name' => $booking->user_name,
            'user_email' => $booking->user_email,
            'user_phone' => $booking->user_phone,
            'created_at' => date('M j, Y g:i A', strtotime($booking->created_at)),
            'qr_code_url' => $booking->qr_code_url ?? '',
            'attendees' => $attendees,
            'attended_count' => $attended_count
        ];
        
        wp_send_json_success($formatted_booking);
    }
    
    /**
     * AJAX get booking payment details
     */
    public function ajax_get_booking_payment_details() {
        check_ajax_referer('waza_account_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Not logged in', 'waza-booking')]);
        }
        
        $booking_id = intval($_POST['booking_id'] ?? 0);
        
        if (!$booking_id) {
            wp_send_json_error(['message' => __('Invalid booking ID', 'waza-booking')]);
        }
        
        global $wpdb;
        
        // Get booking details
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT 
                b.*,
                s.start_datetime,
                s.end_datetime,
                p.post_title as activity_title
            FROM {$wpdb->prefix}waza_bookings b
            LEFT JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
            LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
            WHERE b.id = %d AND b.user_id = %d
        ", $booking_id, get_current_user_id()));
        
        if (!$booking) {
            wp_send_json_error(['message' => __('Booking not found', 'waza-booking')]);
        }
        
        if ($booking->payment_status === 'completed') {
            wp_send_json_error(['message' => __('Payment already completed', 'waza-booking')]);
        }
        
        // Format payment data
        $payment_data = [
            'booking_id' => $booking->id,
            'activity_title' => $booking->activity_title,
            'datetime' => date('l, F j, Y g:i A', strtotime($booking->start_datetime)),
            'quantity' => $booking->quantity,
            'total_amount' => number_format($booking->total_amount, 2)
        ];
        
        wp_send_json_success($payment_data);
    }
}

