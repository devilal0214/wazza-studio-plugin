<?php
/**
 * Plugin Installer
 *
 * Handles plugin activation, deactivation, and installation processes.
 *
 * @package WazaBooking
 */

namespace WazaBooking\Core;

use WazaBooking\Database\DatabaseManager;

if (!defined('ABSPATH')) {
    exit;
}

class Installer {
    
    /**
     * Default pages to create during installation
     */
    private static $default_pages = [
        'activities' => [
            'title' => 'Activities',
            'content' => '[waza_activities_list]',
            'slug' => 'activities',
            'description' => 'Browse and book available activities'
        ],
        'calendar' => [
            'title' => 'Calendar',
            'content' => '[waza_booking_calendar]',
            'slug' => 'calendar',
            'description' => 'View activity calendar and available slots'
        ],
        'my-account' => [
            'title' => 'My Account',
            'content' => '[waza_user_dashboard]',
            'slug' => 'my-account',
            'description' => 'User dashboard for managing bookings and profile'
        ],
        'my-bookings' => [
            'title' => 'My Bookings',
            'content' => '[waza_my_bookings]',
            'slug' => 'my-bookings',
            'description' => 'View and manage your bookings'
        ],
        'login' => [
            'title' => 'Login',
            'content' => '[waza_user_login]',
            'slug' => 'login',
            'description' => 'User login page'
        ],
        'register' => [
            'title' => 'Register',
            'content' => '[waza_user_register]',
            'slug' => 'register',
            'description' => 'User registration page'
        ],
        'booking' => [
            'title' => 'Book Activity',
            'content' => '[waza_booking_form]',
            'slug' => 'book',
            'description' => 'Activity booking form'
        ],
        'booking-confirmation' => [
            'title' => 'Booking Confirmation',
            'content' => '[waza_booking_confirmation]',
            'slug' => 'booking-confirmation',
            'description' => 'Booking confirmation and payment page'
        ]
    ];

    /**
     * Default user roles and capabilities
     */
    private static $user_roles = [
        'waza_instructor' => [
            'display_name' => 'Waza Instructor',
            'capabilities' => [
                'read' => true,
                'waza_manage_activities' => true,
                'waza_view_bookings' => true,
                'waza_mark_attendance' => true,
                'waza_view_reports' => true
            ]
        ],
        'waza_student' => [
            'display_name' => 'Waza Student',
            'capabilities' => [
                'read' => true,
                'waza_book_activities' => true,
                'waza_view_own_bookings' => true,
                'waza_cancel_bookings' => true
            ]
        ]
    ];

    /**
     * Run plugin activation
     */
    public static function activate() {
        // Create database tables
        self::create_database_tables();
        
        // Create default pages
        self::create_default_pages();
        
        // Create user roles
        self::create_user_roles();
        
        // Set default options
        self::set_default_options();
        
        // Create upload directories
        self::create_upload_directories();
        
        // Schedule cron events
        self::schedule_cron_events();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Create sample data
        self::create_sample_data();
        
        // Set activation flag
        update_option('waza_booking_activated', current_time('mysql'));
        update_option('waza_booking_version', WAZA_BOOKING_VERSION);
        
        error_log("Waza Booking: Plugin activation completed successfully");
        
        // Force capability refresh for current user if in admin
        if (is_admin() && function_exists('wp_get_current_user')) {
            $current_user = wp_get_current_user();
            if ($current_user && in_array('administrator', $current_user->roles)) {
                // Refresh user capabilities
                $current_user->get_role_caps();
                error_log("Waza Booking: Refreshed capabilities for user " . $current_user->user_login);
            }
        }
        
        // Log installation
        error_log('Waza Booking Plugin activated successfully');
    }

    /**
     * Run plugin deactivation
     */
    public static function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('waza_booking_cleanup');
        wp_clear_scheduled_hook('waza_booking_notifications');
        wp_clear_scheduled_hook('waza_booking_reminders');
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Log deactivation
        error_log('Waza Booking Plugin deactivated');
    }

    /**
     * Run plugin uninstallation
     */
    public static function uninstall() {
        // Check if user wants to keep data
        $keep_data = get_option('waza_booking_keep_data_on_uninstall', false);
        
        if (!$keep_data) {
            // Drop database tables
            self::drop_database_tables();
            
            // Remove pages
            self::remove_default_pages();
            
            // Remove user roles
            self::remove_user_roles();
            
            // Remove options
            self::remove_options();
            
            // Remove upload directories
            self::remove_upload_directories();
        }
        
        // Clear scheduled events
        wp_clear_scheduled_hook('waza_booking_cleanup');
        wp_clear_scheduled_hook('waza_booking_notifications');
        wp_clear_scheduled_hook('waza_booking_reminders');
        
        // Log uninstallation
        error_log('Waza Booking Plugin uninstalled');
    }

    /**
     * Create database tables
     */
    private static function create_database_tables() {
        $database_manager = new DatabaseManager();
        $database_manager->create_tables();
        
        error_log('Waza Booking: Database tables created');
    }

    /**
     * Create default pages
     */
    private static function create_default_pages() {
        $created_pages = [];
        
        foreach (self::$default_pages as $page_key => $page_data) {
            // Check if page already exists
            $existing_page = get_page_by_path($page_data['slug']);
            
            if (!$existing_page) {
                $page_id = wp_insert_post([
                    'post_title' => $page_data['title'],
                    'post_content' => $page_data['content'],
                    'post_name' => $page_data['slug'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_author' => 1,
                    'meta_input' => [
                        '_waza_page_type' => $page_key,
                        '_waza_page_description' => $page_data['description']
                    ]
                ]);
                
                if (!is_wp_error($page_id)) {
                    $created_pages[$page_key] = $page_id;
                    
                    // Store page ID in options for reference
                    update_option("waza_booking_{$page_key}_page_id", $page_id);
                    
                    error_log("Waza Booking: Created page '{$page_data['title']}' (ID: {$page_id})");
                } else {
                    error_log("Waza Booking: Failed to create page '{$page_data['title']}': " . $page_id->get_error_message());
                }
            } else {
                // Page exists, store its ID
                update_option("waza_booking_{$page_key}_page_id", $existing_page->ID);
                error_log("Waza Booking: Page '{$page_data['title']}' already exists (ID: {$existing_page->ID})");
            }
        }
        
        // Store created pages info
        update_option('waza_booking_created_pages', $created_pages);
        
        return $created_pages;
    }

    /**
     * Create user roles
     */
    private static function create_user_roles() {
        foreach (self::$user_roles as $role_name => $role_data) {
            // Remove role if it exists
            remove_role($role_name);
            
            // Add role
            add_role(
                $role_name,
                $role_data['display_name'],
                $role_data['capabilities']
            );
            
            error_log("Waza Booking: Created user role '{$role_data['display_name']}'");
        }
        
        // Add capabilities to administrator
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_capabilities = [
                'manage_waza',
                'edit_waza_slots',
                'view_waza_bookings',
                'manage_waza_instructors',
                'scan_waza_qr',
                'export_waza_data'
            ];
            
            foreach ($admin_capabilities as $cap) {
                $admin_role->add_cap($cap);
                error_log("Waza Booking: Added capability '{$cap}' to administrator role");
            }
            
            error_log("Waza Booking: All admin capabilities added successfully");
        } else {
            error_log("Waza Booking: Warning - Administrator role not found");
        }
        
        // Also add capabilities to editor role for some features
        $editor_role = get_role('editor');
        if ($editor_role) {
            $editor_capabilities = [
                'edit_waza_slots',
                'view_waza_bookings',
                'scan_waza_qr'
            ];
            
            foreach ($editor_capabilities as $cap) {
                $editor_role->add_cap($cap);
            }
        }
    }

    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        $default_options = [
            'waza_booking_currency' => 'INR',
            'waza_booking_currency_symbol' => '₹',
            'waza_booking_timezone' => 'Asia/Kolkata',
            'waza_booking_date_format' => 'd/m/Y',
            'waza_booking_time_format' => 'H:i',
            'waza_booking_booking_window' => 30, // days
            'waza_booking_cancellation_window' => 24, // hours
            'waza_booking_max_bookings_per_user' => 10,
            'waza_booking_enable_waitlist' => true,
            'waza_booking_enable_qr_codes' => true,
            'waza_booking_qr_expiry' => 3600, // 1 hour
            'waza_booking_enable_notifications' => true,
            'waza_booking_notification_from_email' => get_option('admin_email'),
            'waza_booking_notification_from_name' => get_option('blogname'),
            'waza_booking_enable_reminders' => true,
            'waza_booking_reminder_hours' => [24, 2], // 24 hours and 2 hours before
            'waza_booking_enable_payments' => false,
            'waza_booking_payment_methods' => ['razorpay', 'stripe'],
            'waza_booking_test_mode' => true,
            'waza_booking_keep_data_on_uninstall' => false,
            'waza_booking_enable_guest_booking' => true,
            'waza_booking_require_approval' => false,
            'waza_booking_auto_confirm' => true,
            'waza_booking_enable_reviews' => true,
            'waza_booking_enable_certificates' => false
        ];
        
        foreach ($default_options as $option_name => $option_value) {
            if (get_option($option_name) === false) {
                add_option($option_name, $option_value);
            }
        }
        
        error_log('Waza Booking: Default options set');
    }

    /**
     * Create upload directories
     */
    private static function create_upload_directories() {
        $upload_dir = wp_upload_dir();
        $waza_upload_dir = $upload_dir['basedir'] . '/waza-booking';
        
        $directories = [
            $waza_upload_dir,
            $waza_upload_dir . '/qr-codes',
            $waza_upload_dir . '/certificates',
            $waza_upload_dir . '/activity-images',
            $waza_upload_dir . '/temp'
        ];
        
        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
                
                // Create .htaccess file for security
                $htaccess_content = "Options -Indexes\nDeny from all\n";
                if (in_array(basename($dir), ['qr-codes', 'certificates'])) {
                    $htaccess_content = "Options -Indexes\n";
                }
                
                file_put_contents($dir . '/.htaccess', $htaccess_content);
                
                // Create index.html to prevent directory browsing
                file_put_contents($dir . '/index.html', '');
                
                error_log("Waza Booking: Created directory {$dir}");
            }
        }
    }

    /**
     * Schedule cron events
     */
    private static function schedule_cron_events() {
        // Daily cleanup
        if (!wp_next_scheduled('waza_booking_cleanup')) {
            wp_schedule_event(time(), 'daily', 'waza_booking_cleanup');
        }
        
        // Hourly notifications
        if (!wp_next_scheduled('waza_booking_notifications')) {
            wp_schedule_event(time(), 'hourly', 'waza_booking_notifications');
        }
        
        // Reminder notifications
        if (!wp_next_scheduled('waza_booking_reminders')) {
            wp_schedule_event(time(), 'twicedaily', 'waza_booking_reminders');
        }
        
        error_log('Waza Booking: Scheduled cron events');
    }

    /**
     * Drop database tables
     */
    private static function drop_database_tables() {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'waza_bookings',
            $wpdb->prefix . 'waza_qr_tokens',
            $wpdb->prefix . 'waza_attendance',
            $wpdb->prefix . 'waza_payments',
            $wpdb->prefix . 'waza_waitlist'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
        
        error_log('Waza Booking: Database tables dropped');
    }

    /**
     * Remove default pages
     */
    private static function remove_default_pages() {
        $created_pages = get_option('waza_booking_created_pages', []);
        
        foreach ($created_pages as $page_key => $page_id) {
            wp_delete_post($page_id, true);
            delete_option("waza_booking_{$page_key}_page_id");
        }
        
        delete_option('waza_booking_created_pages');
        
        error_log('Waza Booking: Default pages removed');
    }

    /**
     * Remove user roles
     */
    private static function remove_user_roles() {
        foreach (array_keys(self::$user_roles) as $role_name) {
            remove_role($role_name);
        }
        
        // Remove capabilities from administrator
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_capabilities = [
                'waza_manage_activities',
                'waza_manage_bookings',
                'waza_view_bookings',
                'waza_mark_attendance',
                'waza_view_reports',
                'waza_manage_instructors',
                'waza_manage_settings',
                'waza_export_data'
            ];
            
            foreach ($admin_capabilities as $cap) {
                $admin_role->remove_cap($cap);
            }
        }
        
        error_log('Waza Booking: User roles removed');
    }

    /**
     * Remove plugin options
     */
    private static function remove_options() {
        global $wpdb;
        
        // Remove all plugin options
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'waza_booking_%'");
        
        error_log('Waza Booking: Plugin options removed');
    }

    /**
     * Remove upload directories
     */
    private static function remove_upload_directories() {
        $upload_dir = wp_upload_dir();
        $waza_upload_dir = $upload_dir['basedir'] . '/waza-booking';
        
        if (file_exists($waza_upload_dir)) {
            self::delete_directory($waza_upload_dir);
            error_log('Waza Booking: Upload directories removed');
        }
    }

    /**
     * Recursively delete directory
     */
    private static function delete_directory($dir) {
        if (!file_exists($dir)) {
            return true;
        }
        
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            
            if (!self::delete_directory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        
        return rmdir($dir);
    }

    /**
     * Get page ID by page key
     */
    public static function get_page_id($page_key) {
        return get_option("waza_booking_{$page_key}_page_id", 0);
    }

    /**
     * Get page URL by page key
     */
    public static function get_page_url($page_key) {
        $page_id = self::get_page_id($page_key);
        return $page_id ? get_permalink($page_id) : home_url();
    }

    /**
     * Check if plugin is properly installed
     */
    public static function is_installed() {
        return (bool) get_option('waza_booking_activated', false);
    }

    /**
     * Get installation status
     */
    public static function get_installation_status() {
        $status = [
            'activated' => get_option('waza_booking_activated', false),
            'version' => get_option('waza_booking_version', '0.0.0'),
            'database_created' => self::check_database_tables(),
            'pages_created' => self::check_default_pages(),
            'roles_created' => self::check_user_roles(),
            'directories_created' => self::check_upload_directories()
        ];
        
        $status['fully_installed'] = $status['activated'] && 
                                   $status['database_created'] && 
                                   $status['pages_created'] && 
                                   $status['roles_created'] && 
                                   $status['directories_created'];
        
        return $status;
    }

    /**
     * Check if database tables exist
     */
    private static function check_database_tables() {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'waza_bookings',
            $wpdb->prefix . 'waza_qr_tokens',
            $wpdb->prefix . 'waza_attendance',
            $wpdb->prefix . 'waza_payments',
            $wpdb->prefix . 'waza_waitlist'
        ];
        
        foreach ($tables as $table) {
            $result = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
            if ($result !== $table) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Check if default pages exist
     */
    private static function check_default_pages() {
        foreach (array_keys(self::$default_pages) as $page_key) {
            $page_id = self::get_page_id($page_key);
            if (!$page_id || !get_post($page_id)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Check if user roles exist
     */
    private static function check_user_roles() {
        foreach (array_keys(self::$user_roles) as $role_name) {
            if (!get_role($role_name)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Check if upload directories exist
     */
    private static function check_upload_directories() {
        $upload_dir = wp_upload_dir();
        $waza_upload_dir = $upload_dir['basedir'] . '/waza-booking';
        
        $directories = [
            $waza_upload_dir,
            $waza_upload_dir . '/qr-codes',
            $waza_upload_dir . '/certificates',
            $waza_upload_dir . '/activity-images',
            $waza_upload_dir . '/temp'
        ];
        
        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Create sample data for demonstration
     */
    private static function create_sample_data() {
        // Create sample instructor user
        $instructor_id = wp_create_user('instructor', wp_generate_password(), 'instructor@waza.studio');
        
        if (!is_wp_error($instructor_id)) {
            $instructor = new \WP_User($instructor_id);
            $instructor->set_role('waza_instructor');
            
            // Update instructor meta
            update_user_meta($instructor_id, 'first_name', 'Sarah');
            update_user_meta($instructor_id, 'last_name', 'Johnson');
            update_user_meta($instructor_id, 'display_name', 'Sarah Johnson');
            update_user_meta($instructor_id, 'description', 'Professional dance instructor with 10+ years of experience in various dance forms.');
            update_user_meta($instructor_id, 'waza_specializations', 'Hip Hop, Contemporary, Salsa');
            
            // Create sample activities
            $sample_activities = [
                [
                    'title' => 'Hip Hop Basics',
                    'content' => 'Learn the fundamentals of Hip Hop dance with high-energy moves and urban choreography. Perfect for beginners!',
                    'category' => 'dance',
                    'instructor_id' => $instructor_id,
                    'price' => 800.00,
                    'duration' => 60,
                    'capacity' => 15,
                    'featured' => true
                ],
                [
                    'title' => 'Yoga Flow Session',
                    'content' => 'A relaxing yoga session focusing on breath work and gentle movements to improve flexibility and mindfulness.',
                    'category' => 'yoga',
                    'instructor_id' => $instructor_id,
                    'price' => 600.00,
                    'duration' => 90,
                    'capacity' => 20,
                    'featured' => false
                ],
                [
                    'title' => 'Zumba Fitness',
                    'content' => 'High-energy Zumba class combining Latin rhythms with easy-to-follow dance moves for a fun workout.',
                    'category' => 'zumba',
                    'instructor_id' => $instructor_id,
                    'price' => 500.00,
                    'duration' => 45,
                    'capacity' => 25,
                    'featured' => true
                ],
                [
                    'title' => 'Photography Workshop',
                    'content' => 'Learn the basics of photography including composition, lighting, and camera settings in this hands-on workshop.',
                    'category' => 'photography',
                    'instructor_id' => $instructor_id,
                    'price' => 1200.00,
                    'duration' => 120,
                    'capacity' => 10,
                    'featured' => false
                ]
            ];
            
            foreach ($sample_activities as $activity_data) {
                $activity_id = wp_insert_post([
                    'post_title' => $activity_data['title'],
                    'post_content' => $activity_data['content'],
                    'post_status' => 'publish',
                    'post_type' => 'waza_activity',
                    'post_author' => 1,
                    'meta_input' => [
                        '_waza_category' => $activity_data['category'],
                        '_waza_instructor_id' => $activity_data['instructor_id'],
                        '_waza_price' => $activity_data['price'],
                        '_waza_duration' => $activity_data['duration'],
                        '_waza_capacity' => $activity_data['capacity'],
                        '_waza_featured' => $activity_data['featured'] ? '1' : '0',
                        '_waza_status' => 'active'
                    ]
                ]);
                
                if (!is_wp_error($activity_id)) {
                    // Create some sample time slots for each activity
                    self::create_sample_slots($activity_id, $activity_data);
                    error_log("Waza Booking: Created sample activity '{$activity_data['title']}' (ID: {$activity_id})");
                }
            }
            
            error_log('Waza Booking: Sample data created successfully');
        }
    }
    
    /**
     * Create sample time slots for an activity
     */
    private static function create_sample_slots($activity_id, $activity_data) {
        global $wpdb;
        
        $start_date = strtotime('next Monday');
        
        // Create slots for the next 4 weeks
        for ($week = 0; $week < 4; $week++) {
            for ($day = 0; $day < 7; $day++) {
                $slot_date = $start_date + ($week * 7 * 24 * 3600) + ($day * 24 * 3600);
                
                // Skip Sundays for most activities
                if (date('w', $slot_date) == 0 && $activity_data['category'] !== 'yoga') {
                    continue;
                }
                
                // Different time slots based on activity category
                $time_slots = [];
                
                switch ($activity_data['category']) {
                    case 'dance':
                        $time_slots = ['10:00:00', '14:00:00', '18:00:00'];
                        break;
                    case 'yoga':
                        $time_slots = ['07:00:00', '09:00:00', '17:00:00', '19:00:00'];
                        break;
                    case 'zumba':
                        $time_slots = ['08:00:00', '17:30:00'];
                        break;
                    case 'photography':
                        $time_slots = ['10:00:00', '15:00:00'];
                        break;
                }
                
                foreach ($time_slots as $time) {
                    $start_datetime = date('Y-m-d H:i:s', strtotime(date('Y-m-d', $slot_date) . ' ' . $time));
                    $end_datetime = date('Y-m-d H:i:s', strtotime($start_datetime) + ($activity_data['duration'] * 60));
                    
                    $slot_data = [
                        'activity_id' => $activity_id,
                        'start_datetime' => $start_datetime,
                        'end_datetime' => $end_datetime,
                        'capacity' => $activity_data['capacity'],
                        'status' => 'available',
                        'created_at' => current_time('mysql')
                    ];
                    
                    $wpdb->insert(
                        $wpdb->prefix . 'waza_slots',
                        $slot_data,
                        ['%d', '%s', '%s', '%d', '%s', '%s']
                    );
                }
            }
        }
    }
}