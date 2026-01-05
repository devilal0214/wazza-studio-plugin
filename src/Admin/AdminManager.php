<?php
/**
 * Admin Manager
 * 
 * Handles admin menu and dashboard functionality
 * 
 * @package WazaBooking\Admin
 */

namespace WazaBooking\Admin;

class AdminManager {
    
    /**
     * Initialize admin functionality
     */
    public function init() {
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_ajax_waza_fix_database', [$this, 'ajax_fix_database']);
    }
    
    /**
     * Register admin menu and submenus
     */
    public function register_admin_menu() {
        add_menu_page(
            __('Waza Booking', 'waza-booking'),
            __('Waza Booking', 'waza-booking'),
            'manage_options',
            'waza-booking',
            [$this, 'dashboard_page'],
            'dashicons-calendar-alt',
            25
        );
        
        add_submenu_page(
            'waza-booking',
            __('Dashboard', 'waza-booking'),
            __('Dashboard', 'waza-booking'),
            'manage_options',
            'waza-booking',
            [$this, 'dashboard_page']
        );

        add_submenu_page(
            'waza-booking',
            __('Time Slots', 'waza-booking'),
            __('Time Slots', 'waza-booking'),
            'manage_options',
            'waza-slots',
            function() {
                $plugin = \WazaBooking\Core\Plugin::get_instance();
                $slot_manager = $plugin->get_manager('slot');
                if ($slot_manager) {
                    $slot_manager->admin_page();
                }
            }
        );

        add_submenu_page(
            'waza-booking',
            __('Instructors', 'waza-booking'),
            __('Instructors', 'waza-booking'),
            'manage_options',
            'edit.php?post_type=waza_instructor'
        );

        add_submenu_page(
            'waza-booking',
            __('Activities', 'waza-booking'),
            __('Activities', 'waza-booking'),
            'manage_options',
            'edit.php?post_type=waza_activity'
        );

        add_submenu_page(
            'waza-booking',
            __('Bookings', 'waza-booking'),
            __('Bookings', 'waza-booking'),
            'manage_options',
            'edit.php?post_type=waza_booking'
        );
        
        add_submenu_page(
            'waza-booking',
            __('Email Templates', 'waza-booking'),
            __('Email Templates', 'waza-booking'),
            'manage_options',
            'waza-email-templates',
            [$this, 'email_templates_page']
        );
        
        add_submenu_page(
            'waza-booking',
            __('Customization', 'waza-booking'),
            __('Customization', 'waza-booking'),
            'manage_options',
            'waza-customization',
            [$this, 'customization_page']
        );
        
        add_submenu_page(
            'waza-booking',
            __('Settings', 'waza-booking'),
            __('Settings', 'waza-booking'),
            'manage_options',
            'waza-settings',
            [$this, 'settings_page']
        );
        
        add_submenu_page(
            'waza-booking',
            __('QR Scanner', 'waza-booking'),
            __('QR Scanner', 'waza-booking'),
            'manage_options',
            'waza-scanner',
            [$this, 'scanner_page']
        );
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        global $wpdb;
        
        // Get quick stats
        $total_bookings = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}waza_bookings");
        $total_slots = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}waza_slots");
        $total_workshops = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}waza_workshops");
        $total_announcements = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}waza_announcements WHERE is_active = 1");
        
        ?>
        <div class="wrap waza-dashboard">
            <div class="waza-dashboard-header">
                <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
                <div class="waza-quick-actions">
                    <button id="waza-fix-database-btn" class="button button-secondary" style="margin-right: 10px;">
                        <span class="dashicons dashicons-admin-tools"></span>
                        <?php esc_html_e('Fix Database Issues', 'waza-booking'); ?>
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=waza-slots&tab=add'); ?>" class="button button-primary">
                        <span class="dashicons dashicons-plus"></span>
                        <?php esc_html_e('Add Slot', 'waza-booking'); ?>
                    </a>
                    <a href="<?php echo admin_url('edit.php?post_type=waza_booking'); ?>" class="button">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php esc_html_e('View Bookings', 'waza-booking'); ?>
                    </a>
                </div>
            </div>
            
            <div id="waza-db-fix-message" style="display:none; margin: 15px 0;"></div>
            
            <!-- Stats Overview -->
            <div class="waza-stats-grid">
                <div class="waza-stat-card">
                    <div class="waza-stat-icon" style="background-color: #4CAF50;">
                        <span class="dashicons dashicons-tickets-alt"></span>
                    </div>
                    <div class="waza-stat-content">
                        <div class="waza-stat-value"><?php echo number_format($total_bookings); ?></div>
                        <div class="waza-stat-label"><?php esc_html_e('Total Bookings', 'waza-booking'); ?></div>
                    </div>
                </div>
                
                <div class="waza-stat-card">
                    <div class="waza-stat-icon" style="background-color: #2196F3;">
                        <span class="dashicons dashicons-clock"></span>
                    </div>
                    <div class="waza-stat-content">
                        <div class="waza-stat-value"><?php echo number_format($total_slots); ?></div>
                        <div class="waza-stat-label"><?php esc_html_e('Total Slots', 'waza-booking'); ?></div>
                    </div>
                </div>
                
                <div class="waza-stat-card">
                    <div class="waza-stat-icon" style="background-color: #FF9800;">
                        <span class="dashicons dashicons-groups"></span>
                    </div>
                    <div class="waza-stat-content">
                        <div class="waza-stat-value"><?php echo number_format($total_workshops); ?></div>
                        <div class="waza-stat-label"><?php esc_html_e('Workshops', 'waza-booking'); ?></div>
                    </div>
                </div>
                
                <div class="waza-stat-card">
                    <div class="waza-stat-icon" style="background-color: #9C27B0;">
                        <span class="dashicons dashicons-megaphone"></span>
                    </div>
                    <div class="waza-stat-content">
                        <div class="waza-stat-value"><?php echo number_format($total_announcements); ?></div>
                        <div class="waza-stat-label"><?php esc_html_e('Active Announcements', 'waza-booking'); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Features Grid -->
            <h2><?php esc_html_e('Feature Management', 'waza-booking'); ?></h2>
            <div class="waza-features-grid">
                <!-- Slots -->
                <div class="waza-feature-card">
                    <span class="dashicons dashicons-clock"></span>
                    <h3><?php esc_html_e('Time Slots', 'waza-booking'); ?></h3>
                    <p><?php esc_html_e('Create and manage activity time slots with instructor assignment and pricing.', 'waza-booking'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=waza-slots'); ?>" class="button"><?php esc_html_e('Manage Slots', 'waza-booking'); ?></a>
                </div>
                
                <!-- Workshops -->
                <div class="waza-feature-card">
                    <span class="dashicons dashicons-groups"></span>
                    <h3><?php esc_html_e('Workshops', 'waza-booking'); ?></h3>
                    <p><?php esc_html_e('Instructor-led workshops with invite links and student rosters.', 'waza-booking'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=waza-workshops'); ?>" class="button"><?php esc_html_e('View Workshops', 'waza-booking'); ?></a>
                </div>
                
                <!-- Announcements -->
                <div class="waza-feature-card">
                    <span class="dashicons dashicons-megaphone"></span>
                    <h3><?php esc_html_e('Announcements', 'waza-booking'); ?></h3>
                    <p><?php esc_html_e('Create studio announcements for instructors and students.', 'waza-booking'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=waza-announcements'); ?>" class="button"><?php esc_html_e('Manage Announcements', 'waza-booking'); ?></a>
                </div>
                
                <!-- Payment Gateways -->
                <div class="waza-feature-card">
                    <span class="dashicons dashicons-money-alt"></span>
                    <h3><?php esc_html_e('Payment Gateways', 'waza-booking'); ?></h3>
                    <p><?php esc_html_e('Razorpay, Stripe, and PhonePe payment gateway integrations.', 'waza-booking'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=waza-settings#payment'); ?>" class="button"><?php esc_html_e('Configure Payments', 'waza-booking'); ?></a>
                </div>
                
                <!-- SMS Notifications -->
                <div class="waza-feature-card">
                    <span class="dashicons dashicons-smartphone"></span>
                    <h3><?php esc_html_e('SMS Notifications', 'waza-booking'); ?></h3>
                    <p><?php esc_html_e('Send booking confirmations and reminders via SMS (Twilio/TextLocal).', 'waza-booking'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=waza-settings#notifications'); ?>" class="button"><?php esc_html_e('Setup SMS', 'waza-booking'); ?></a>
                </div>
                
                <!-- Refunds -->
                <div class="waza-feature-card">
                    <span class="dashicons dashicons-undo"></span>
                    <h3><?php esc_html_e('Refund Management', 'waza-booking'); ?></h3>
                    <p><?php esc_html_e('Process full and partial refunds with configurable policies.', 'waza-booking'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=waza-settings#booking'); ?>" class="button"><?php esc_html_e('Refund Settings', 'waza-booking'); ?></a>
                </div>
                
                <!-- QR Scanner -->
                <div class="waza-feature-card">
                    <span class="dashicons dashicons-visibility"></span>
                    <h3><?php esc_html_e('QR Scanner', 'waza-booking'); ?></h3>
                    <p><?php esc_html_e('Scan QR codes for attendance, including group and master QR codes.', 'waza-booking'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=waza-scanner'); ?>" class="button"><?php esc_html_e('Open Scanner', 'waza-booking'); ?></a>
                </div>
                
                <!-- CSV Exports -->
                <div class="waza-feature-card">
                    <span class="dashicons dashicons-download"></span>
                    <h3><?php esc_html_e('Data Export', 'waza-booking'); ?></h3>
                    <p><?php esc_html_e('Export attendance, rosters, and bookings to CSV format.', 'waza-booking'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=waza-settings'); ?>" class="button"><?php esc_html_e('Export Data', 'waza-booking'); ?></a>
                </div>
                
                <!-- Calendar View -->
                <div class="waza-feature-card">
                    <span class="dashicons dashicons-calendar"></span>
                    <h3><?php esc_html_e('Interactive Calendar', 'waza-booking'); ?></h3>
                    <p><?php esc_html_e('Frontend calendar view with customizable settings and filters.', 'waza-booking'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=waza-settings#calendar'); ?>" class="button"><?php esc_html_e('Calendar Settings', 'waza-booking'); ?></a>
                </div>
            </div>
            
            <div style=\"margin-top: 30px; padding: 20px; background: #fff; border-left: 4px solid #2196F3;\">
                <h3><?php esc_html_e('Quick Setup Guide', 'waza-booking'); ?></h3>
                <ol style=\"line-height: 2;\">
                    <li><?php esc_html_e('Configure payment gateways in Settings > Payment Settings', 'waza-booking'); ?></li>
                    <li><?php esc_html_e('Set up SMS notifications in Settings > Notifications', 'waza-booking'); ?></li>
                    <li><?php esc_html_e('Create activities under Activities menu', 'waza-booking'); ?></li>
                    <li><?php esc_html_e('Add time slots with instructor and pricing', 'waza-booking'); ?></li>
                    <li><?php esc_html_e('Use shortcode [waza_calendar] to display interactive calendar on frontend', 'waza-booking'); ?></li>
                    <li><?php esc_html_e('Use shortcode [waza_announcements] to display announcements', 'waza-booking'); ?></li>
                </ol>
            </div>
        </div>
        
        <style>
        .waza-dashboard {
            max-width: 1400px;
        }
        
        .waza-dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .waza-quick-actions {
            display: flex;
            gap: 10px;
        }
        
        .waza-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .waza-stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .waza-stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }
        
        .waza-stat-icon .dashicons {
            font-size: 30px;
            width: 30px;
            height: 30px;
        }
        
        .waza-stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }
        
        .waza-stat-label {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        
        .waza-features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .waza-feature-card {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .waza-feature-card .dashicons {
            font-size: 48px;
            width: 48px;
            height: 48px;
            color: #2196F3;
            margin-bottom: 15px;
        }
        
        .waza-feature-card h3 {
            margin: 10px 0;
            font-size: 18px;
        }
        
        .waza-feature-card p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin: 15px 0;
            min-height: 60px;
        }
        
        .waza-feature-card .button {
            margin-top: 10px;
        }
        </style>
        <?php
    }
    
    /**
     * Get dashboard widgets
     * 
     * @return array Array of DashboardWidget instances
     */
    private function get_dashboard_widgets(): array {
        $widgets = [
            new \WazaBooking\Admin\Widgets\StatsOverviewWidget(),
            new \WazaBooking\Admin\Widgets\UpcomingSlotsWidget(),
            new \WazaBooking\Admin\Widgets\PaymentSummaryWidget(),
            new \WazaBooking\Admin\Widgets\QRActivityWidget(),
            new \WazaBooking\Admin\Widgets\RecentBookingsWidget(),
        ];
        
        // Sort by order
        usort($widgets, function($a, $b) {
            return $a->get_order() - $b->get_order();
        });
        
        return $widgets;
    }
    
    /**
     * Render dashboard styles
     */
    private function render_dashboard_styles() {
        ?>
        <style>
        .waza-dashboard {
            margin-top: 20px;
        }
        
        .waza-dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .waza-dashboard-header h1 {
            margin: 0;
        }
        
        .waza-quick-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .waza-quick-actions .button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .waza-quick-actions .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }
        
        .waza-dashboard-widgets {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        
        .waza-widget {
            background: white;
            border: 1px solid #c3c4c7;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .waza-widget-span-1 { grid-column: span 1; }
        .waza-widget-span-2 { grid-column: span 2; }
        .waza-widget-span-3 { grid-column: span 3; }
        .waza-widget-span-4 { grid-column: span 4; }
        
        .waza-widget-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 16px 20px;
            border-bottom: 1px solid #f0f0f1;
            background: #f6f7f7;
        }
        
        .waza-widget-header .dashicons {
            color: #2271b1;
            font-size: 20px;
            width: 20px;
            height: 20px;
        }
        
        .waza-widget-header h3 {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: #1d2327;
        }
        
        .waza-widget-content {
            padding: 20px;
        }
        
        /* Responsive */
        @media (max-width: 1400px) {
            .waza-dashboard-widgets {
                grid-template-columns: repeat(2, 1fr);
            }
            .waza-widget-span-3,
            .waza-widget-span-4 {
                grid-column: span 2;
            }
        }
        
        @media (max-width: 782px) {
            .waza-dashboard-widgets {
                grid-template-columns: 1fr;
            }
            .waza-widget-span-1,
            .waza-widget-span-2,
            .waza-widget-span-3,
            .waza-widget-span-4 {
                grid-column: span 1;
            }
            .waza-dashboard-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
        </style>
        <?php
    }
    
    /**
     * Email templates page
     */
    public function email_templates_page() {
        $plugin = \WazaBooking\Core\Plugin::get_instance();
        $email_manager = $plugin->get_manager('email_template');
        if ($email_manager) {
            $email_manager->admin_page();
        }
    }
    
    /**
     * Customization page
     */
    public function customization_page() {
        $plugin = \WazaBooking\Core\Plugin::get_instance();
        $customization_manager = $plugin->get_manager('customization');
        if ($customization_manager) {
            $customization_manager->admin_page();
        }
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        $plugin = \WazaBooking\Core\Plugin::get_instance();
        $settings_manager = $plugin->get_manager('settings');
        if ($settings_manager) {
            $settings_manager->render_settings_page();
        }
    }
    
    /**
     * QR Scanner page
     */
    public function scanner_page() {
        ?>
        <div class="wrap waza-scanner-page">
            <h1><?php esc_html_e('QR Scanner', 'waza-booking'); ?></h1>
            <div id="waza-scanner-container">
                <div class="waza-scanner-header">
                    <p><?php esc_html_e('Scan user tags or tickets to verify check-in.', 'waza-booking'); ?></p>
                </div>
                <div class="waza-scanner-viewport">
                    <div id="waza-reader"></div>
                </div>
                <div id="waza-scanner-result"></div>
            </div>
        </div>
        <style>
        .waza-scanner-page { max-width: 800px; margin: 20px auto; }
        #waza-reader { width: 100%; border-radius: 8px; overflow: hidden; background: #000; }
        #waza-scanner-result { margin-top: 20px; }
        </style>
        <?php
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'waza') !== false) {
            wp_enqueue_style('waza-admin', WAZA_BOOKING_PLUGIN_URL . 'assets/admin.css', [], WAZA_BOOKING_VERSION . '.1');
            wp_enqueue_script('waza-admin', WAZA_BOOKING_PLUGIN_URL . 'assets/admin.js', ['jquery'], WAZA_BOOKING_VERSION . '.1', true);
            
            wp_localize_script('waza-admin', 'wazaAdmin', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('waza_admin_nonce'),
            ]);
            
            // Specific assets for Email Templates page
            if (strpos($hook, 'waza-email-templates') !== false) {
                wp_enqueue_style('waza-email-templates', WAZA_BOOKING_PLUGIN_URL . 'assets/admin/email-templates.css', [], WAZA_BOOKING_VERSION);
                wp_enqueue_script('waza-email-templates', WAZA_BOOKING_PLUGIN_URL . 'assets/admin/email-templates.js', ['jquery'], WAZA_BOOKING_VERSION, true);
                
                wp_localize_script('waza-email-templates', 'waza_email_templates', [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce'    => wp_create_nonce('waza_email_templates_nonce'),
                    'strings'  => [
                        'save_success'     => __('Template saved successfully!', 'waza-booking'),
                        'save_error'       => __('Error saving template.', 'waza-booking'),
                        'confirm_reset'    => __('Are you sure you want to reset this template to default?', 'waza-booking'),
                        'test_email_sent'  => __('Test email sent successfully!', 'waza-booking'),
                        'test_email_error' => __('Error sending test email.', 'waza-booking'),
                    ]
                ]);
            }
            
            // Specific assets for Customization page
            if (strpos($hook, 'waza-customization') !== false) {
                wp_enqueue_media();
                wp_enqueue_style('wp-color-picker');
                wp_enqueue_script('wp-color-picker');
                
                wp_enqueue_style('waza-customization', WAZA_BOOKING_PLUGIN_URL . 'assets/admin/admin-customization.css', [], WAZA_BOOKING_VERSION);
                wp_enqueue_script('waza-customization', WAZA_BOOKING_PLUGIN_URL . 'assets/admin/admin-customization.js', ['jquery', 'wp-color-picker'], WAZA_BOOKING_VERSION, true);
                
                // Get presets from CustomizationManager
                $customization_manager = \WazaBooking\Core\Plugin::instance()->get_manager('customization');
                $presets = $customization_manager ? $customization_manager->get_theme_presets() : [];
                
                wp_localize_script('waza-customization', 'waza_customization', [
                    'ajax_url'     => admin_url('admin-ajax.php'),
                    'nonce'        => wp_create_nonce('waza_customization_nonce'),
                    'presets'      => $presets,
                    'google_fonts' => ['Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Oswald', 'Raleway', 'Poppins'],
                    'strings'      => [
                        'confirm_reset' => __('Are you sure you want to reset all customization settings?', 'waza-booking'),
                    ]
                ]);
            }
            
            // Specific assets for QR Scanner page
            if (strpos($hook, 'waza-scanner') !== false) {
                // Enqueue Html5Qrcode from CDN
                wp_enqueue_script('html5-qrcode', 'https://unpkg.com/html5-qrcode', [], '2.3.8', true);
                
                wp_enqueue_style('waza-scanner', WAZA_BOOKING_PLUGIN_URL . 'assets/admin/scanner.css', [], WAZA_BOOKING_VERSION);
                wp_enqueue_script('waza-scanner', WAZA_BOOKING_PLUGIN_URL . 'assets/admin/scanner.js', ['jquery', 'html5-qrcode'], WAZA_BOOKING_VERSION, true);
                
                wp_localize_script('waza-scanner', 'wazaScanner', [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce'    => wp_create_nonce('waza_scanner_nonce'),
                    'strings'  => [
                        'verifying'     => __('Verifying QR code...', 'waza-booking'),
                        'error'         => __('Invalid Token', 'waza-booking'),
                        'network_error' => __('Network error. Please try again.', 'waza-booking'),
                        'user'          => __('User', 'waza-booking'),
                        'activity'      => __('Activity', 'waza-booking'),
                        'attendees'     => __('Attendees', 'waza-booking'),
                        'next'          => __('Scan Next', 'waza-booking'),
                        'retry'         => __('Try Again', 'waza-booking'),
                    ]
                ]);
            }
        }
    }    
    /**
     * AJAX handler to fix database issues
     */
    public function ajax_fix_database() {
        check_ajax_referer('waza_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'waza-booking')]);
        }
        
        global $wpdb;
        $results = [];
        $errors = [];
        
        try {
            // FIRST: Create all missing database tables
            $db_manager = new \WazaBooking\Database\DatabaseManager();
            $db_manager->create_tables();
            $results[] = __('All database tables created/verified', 'waza-booking');
            
            // Check which tables now exist
            $required_tables = [
                'waza_bookings',
                'waza_slots',
                'waza_qr_tokens',
                'waza_attendance',
                'waza_payments',
                'waza_waitlist',
                'waza_email_templates',
                'waza_workshops',
                'waza_workshop_students',
                'waza_activity_logs',
                'waza_announcements',
                'waza_qr_groups',
                'waza_qr_group_members'
            ];
            
            $tables_status = [];
            foreach ($required_tables as $table) {
                $full_table = $wpdb->prefix . $table;
                $exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table}'") === $full_table;
                $tables_status[$table] = $exists ? '✅' : '❌';
                if ($exists) {
                    $results[] = sprintf(__('Table %s: OK', 'waza-booking'), $table);
                }
            }
            
            // Check and add missing columns in waza_slots table
            $slots_table = $wpdb->prefix . 'waza_slots';
            
            // Check if price column exists
            $price_exists = $wpdb->get_results("SHOW COLUMNS FROM {$slots_table} LIKE 'price'");
            if (empty($price_exists)) {
                $wpdb->query("ALTER TABLE {$slots_table} ADD COLUMN price DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER capacity");
                $results[] = __('Added price column to slots table', 'waza-booking');
            } else {
                $results[] = __('Price column already exists in slots table', 'waza-booking');
            }
            
            // Check if instructor_id column exists
            $instructor_exists = $wpdb->get_results("SHOW COLUMNS FROM {$slots_table} LIKE 'instructor_id'");
            if (empty($instructor_exists)) {
                $wpdb->query("ALTER TABLE {$slots_table} ADD COLUMN instructor_id BIGINT(20) DEFAULT NULL AFTER activity_id");
                $wpdb->query("ALTER TABLE {$slots_table} ADD KEY instructor_id (instructor_id)");
                $results[] = __('Added instructor_id column to slots table', 'waza-booking');
            } else {
                $results[] = __('Instructor_id column already exists in slots table', 'waza-booking');
            }
            
            // Update database version
            update_option('waza_booking_db_version', '1.1.0');
            $results[] = __('Database version updated to 1.1.0', 'waza-booking');
            
            wp_send_json_success([
                'message' => __('Database fixed successfully! All tables created.', 'waza-booking'),
                'details' => $results,
                'tables' => $tables_status
            ]);
            
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => __('Database fix failed', 'waza-booking'),
                'error' => $e->getMessage()
            ]);
        }
    }}