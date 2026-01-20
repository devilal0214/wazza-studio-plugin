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
        add_action('wp_ajax_waza_approve_workshop', [$this, 'ajax_approve_workshop']);
        add_action('wp_ajax_waza_reject_workshop', [$this, 'ajax_reject_workshop']);
        add_action('wp_ajax_waza_approve_cancellation', [$this, 'ajax_approve_cancellation']);
        add_action('wp_ajax_waza_reject_cancellation', [$this, 'ajax_reject_cancellation']);
        add_action('wp_ajax_waza_reassign_workshop', [$this, 'ajax_reassign_workshop']);
        add_action('wp_ajax_waza_get_available_instructors', [$this, 'ajax_get_available_instructors']);
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

        // Get pending approvals count
        global $wpdb;
        $pending_count = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}waza_slots 
            WHERE status = 'pending_approval'
        ");
        
        $pending_menu_title = $pending_count > 0 
            ? sprintf(__('Pending Approvals %s', 'waza-booking'), '<span class="awaiting-mod">' . $pending_count . '</span>')
            : __('Pending Approvals', 'waza-booking');

        add_submenu_page(
            'waza-booking',
            __('Pending Approvals', 'waza-booking'),
            $pending_menu_title,
            'manage_options',
            'waza-pending-approvals',
            [$this, 'pending_approvals_page']
        );

        add_submenu_page(
            'waza-booking',
            __('Bookings', 'waza-booking'),
            __('Bookings', 'waza-booking'),
            'manage_options',
            'waza-all-bookings',
            [$this, 'all_bookings_page']
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
        $total_instructors = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}posts WHERE post_type = 'waza_instructor' AND post_status = 'publish'");
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
                    <a href="<?php echo admin_url('admin.php?page=waza-all-bookings'); ?>" class="button">
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
                        <span class="dashicons dashicons-businessperson"></span>
                    </div>
                    <div class="waza-stat-content">
                        <div class="waza-stat-value"><?php echo number_format($total_instructors); ?></div>
                        <div class="waza-stat-label"><?php esc_html_e('Total Instructors', 'waza-booking'); ?></div>
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
            
            <div style="margin-top: 30px; padding: 20px; background: #fff; border-left: 4px solid #2196F3;">
                <h3><?php esc_html_e('Quick Setup Guide', 'waza-booking'); ?></h3>
                <ol style="line-height: 2;">
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
     * QR Scanner page - Redirect to enhanced scanner
     */
    public function scanner_page() {
        // Use JavaScript redirect to avoid headers already sent error
        $redirect_url = admin_url('admin.php?page=waza-attendance-scanner');
        ?>
        <script type="text/javascript">
            window.location.href = "<?php echo esc_url($redirect_url); ?>";
        </script>
        <noscript>
            <meta http-equiv="refresh" content="0;url=<?php echo esc_url($redirect_url); ?>">
        </noscript>
        <p><?php _e('Redirecting to attendance scanner...', 'waza-booking'); ?></p>
        <?php
        exit;
    }
    
    /**
     * All Bookings Admin Page
     */
    public function all_bookings_page() {
        global $wpdb;
        
        // Get filter values
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';
        $filter_activity = isset($_GET['filter_activity']) ? intval($_GET['filter_activity']) : 0;
        $filter_date_from = isset($_GET['filter_date_from']) ? sanitize_text_field($_GET['filter_date_from']) : '';
        $filter_date_to = isset($_GET['filter_date_to']) ? sanitize_text_field($_GET['filter_date_to']) : '';
        $filter_payment_status = isset($_GET['filter_payment_status']) ? sanitize_text_field($_GET['filter_payment_status']) : '';
        $filter_payment_method = isset($_GET['filter_payment_method']) ? sanitize_text_field($_GET['filter_payment_method']) : '';
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        
        // Pagination
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;
        
        // Build WHERE clause
        $where_clauses = ['1=1'];
        
        // Status filter
        if ($status_filter !== 'all') {
            if ($status_filter === 'pending') {
                $where_clauses[] = "b.booking_status = 'pending'";
            } elseif ($status_filter === 'confirmed') {
                $where_clauses[] = "b.booking_status = 'confirmed'";
            } elseif ($status_filter === 'pending_payment') {
                $where_clauses[] = "b.payment_status = 'pending'";
            }
        }
        
        // Activity filter
        if ($filter_activity) {
            $where_clauses[] = $wpdb->prepare("s.activity_id = %d", $filter_activity);
        }
        
        // Date range filter
        if ($filter_date_from) {
            $where_clauses[] = $wpdb->prepare("DATE(s.start_datetime) >= %s", $filter_date_from);
        }
        if ($filter_date_to) {
            $where_clauses[] = $wpdb->prepare("DATE(s.start_datetime) <= %s", $filter_date_to);
        }
        
        // Payment status filter
        if ($filter_payment_status) {
            $where_clauses[] = $wpdb->prepare("b.payment_status = %s", $filter_payment_status);
        }
        
        // Payment method filter
        if ($filter_payment_method) {
            $where_clauses[] = $wpdb->prepare("b.payment_method = %s", $filter_payment_method);
        }
        
        // Search filter (customer name, email, booking ID)
        if ($search) {
            $search_like = '%' . $wpdb->esc_like($search) . '%';
            $where_clauses[] = $wpdb->prepare(
                "(u.display_name LIKE %s OR b.user_email LIKE %s OR b.user_name LIKE %s OR b.id = %d)",
                $search_like, $search_like, $search_like, intval($search)
            );
        }
        
        $where = implode(' AND ', $where_clauses);
        
        // Get total count for pagination
        $total_items = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->prefix}waza_bookings b
            LEFT JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
            LEFT JOIN {$wpdb->users} u ON b.user_id = u.ID
            WHERE $where
        ");
        
        $total_pages = ceil($total_items / $per_page);
        
        // Get bookings from database with pagination
        $bookings = $wpdb->get_results("
            SELECT 
                b.*,
                s.start_datetime,
                s.end_datetime,
                p.post_title as activity_title,
                u.display_name as user_name
            FROM {$wpdb->prefix}waza_bookings b
            LEFT JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
            LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
            LEFT JOIN {$wpdb->users} u ON b.user_id = u.ID
            WHERE $where
            ORDER BY b.created_at DESC
            LIMIT $per_page OFFSET $offset
        ");
        
        // Count statuses
        $total_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}waza_bookings");
        $pending_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}waza_bookings WHERE booking_status = 'pending'");
        $confirmed_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}waza_bookings WHERE booking_status = 'confirmed'");
        $pending_payment_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}waza_bookings WHERE payment_status = 'pending'");
        
        // Get all activities for filter
        $activities = $wpdb->get_results("
            SELECT ID, post_title 
            FROM {$wpdb->posts} 
            WHERE post_type = 'waza_activity' AND post_status = 'publish' 
            ORDER BY post_title ASC
        ");
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">All Bookings</h1>
            <hr class="wp-header-end">
            
            <ul class="subsubsub">
                <li><a href="?page=waza-all-bookings&status=all" class="<?php echo $status_filter === 'all' ? 'current' : ''; ?>">All <span class="count">(<?php echo $total_count; ?>)</span></a> |</li>
                <li><a href="?page=waza-all-bookings&status=confirmed" class="<?php echo $status_filter === 'confirmed' ? 'current' : ''; ?>">Confirmed <span class="count">(<?php echo $confirmed_count; ?>)</span></a> |</li>
                <li><a href="?page=waza-all-bookings&status=pending" class="<?php echo $status_filter === 'pending' ? 'current' : ''; ?>">Pending <span class="count">(<?php echo $pending_count; ?>)</span></a> |</li>
                <li><a href="?page=waza-all-bookings&status=pending_payment" class="<?php echo $status_filter === 'pending_payment' ? 'current' : ''; ?>">Pending Payment <span class="count">(<?php echo $pending_payment_count; ?>)</span></a></li>
            </ul>
            
            <!-- Filters -->
            <div class="tablenav top" style="margin-top: 20px;">
                <form method="get" action="" style="display: inline-flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <input type="hidden" name="page" value="waza-all-bookings">
                    <input type="hidden" name="status" value="<?php echo esc_attr($status_filter); ?>">
                    
                    <input type="search" name="s" value="<?php echo esc_attr($search); ?>" 
                           placeholder="<?php _e('Search by name, email, or ID', 'waza-booking'); ?>" 
                           style="min-width: 250px;">
                    
                    <select name="filter_activity" style="min-width: 180px;">
                        <option value=""><?php _e('All Activities', 'waza-booking'); ?></option>
                        <?php foreach ($activities as $activity): ?>
                            <option value="<?php echo esc_attr($activity->ID); ?>" <?php selected($filter_activity, $activity->ID); ?>>
                                <?php echo esc_html($activity->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <input type="date" name="filter_date_from" value="<?php echo esc_attr($filter_date_from); ?>" 
                           placeholder="<?php _e('From Date', 'waza-booking'); ?>">
                    
                    <input type="date" name="filter_date_to" value="<?php echo esc_attr($filter_date_to); ?>" 
                           placeholder="<?php _e('To Date', 'waza-booking'); ?>">
                    
                    <select name="filter_payment_status">
                        <option value=""><?php _e('All Payment Status', 'waza-booking'); ?></option>
                        <option value="pending" <?php selected($filter_payment_status, 'pending'); ?>><?php _e('Pending', 'waza-booking'); ?></option>
                        <option value="completed" <?php selected($filter_payment_status, 'completed'); ?>><?php _e('Completed', 'waza-booking'); ?></option>
                        <option value="failed" <?php selected($filter_payment_status, 'failed'); ?>><?php _e('Failed', 'waza-booking'); ?></option>
                    </select>
                    
                    <select name="filter_payment_method">
                        <option value=""><?php _e('All Payment Methods', 'waza-booking'); ?></option>
                        <option value="razorpay" <?php selected($filter_payment_method, 'razorpay'); ?>><?php _e('Razorpay', 'waza-booking'); ?></option>
                        <option value="stripe" <?php selected($filter_payment_method, 'stripe'); ?>><?php _e('Stripe', 'waza-booking'); ?></option>
                        <option value="cash" <?php selected($filter_payment_method, 'cash'); ?>><?php _e('Cash', 'waza-booking'); ?></option>
                        <option value="admin" <?php selected($filter_payment_method, 'admin'); ?>><?php _e('Admin', 'waza-booking'); ?></option>
                    </select>
                    
                    <button type="submit" class="button"><?php _e('Filter', 'waza-booking'); ?></button>
                    
                    <?php if ($filter_activity || $filter_date_from || $filter_date_to || $filter_payment_status || $filter_payment_method || $search): ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=waza-all-bookings&status=' . $status_filter)); ?>" class="button">
                            <?php _e('Clear Filters', 'waza-booking'); ?>
                        </a>
                    <?php endif; ?>
                </form>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Activity</th>
                        <th>Slot Date/Time</th>
                        <th>Quantity</th>
                        <th>Amount</th>
                        <th>Booking Status</th>
                        <th>Payment Status</th>
                        <th>Payment Method</th>
                        <th>Transaction ID</th>
                        <th>Created By</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="13" style="text-align: center; padding: 2rem;">
                                No bookings found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $booking): ?>
                            <?php
                            $booking_status_class = $booking->booking_status === 'confirmed' ? 'confirmed' : 'pending';
                            $payment_status_class = $booking->payment_status === 'completed' ? 'completed' : 'pending';
                            $created_by = $booking->user_id ? ($booking->user_name ?: 'User #' . $booking->user_id) : 'Guest (' . $booking->user_email . ')';
                            ?>
                            <tr>
                                <td><strong>#<?php echo $booking->id; ?></strong></td>
                                <td>
                                    <strong><?php echo esc_html($booking->user_name ?: $booking->user_name); ?></strong><br>
                                    <small><?php echo esc_html($booking->user_email); ?></small><br>
                                    <small><?php echo esc_html($booking->user_phone); ?></small>
                                </td>
                                <td><?php echo esc_html($booking->activity_title); ?></td>
                                <td>
                                    <?php if ($booking->start_datetime): ?>
                                        <strong><?php echo date('M j, Y', strtotime($booking->start_datetime)); ?></strong><br>
                                        <small><?php echo date('g:i A', strtotime($booking->start_datetime)); ?> - <?php echo date('g:i A', strtotime($booking->end_datetime)); ?></small>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $booking->quantity; ?></td>
                                <td><strong>₹<?php echo number_format($booking->total_amount, 2); ?></strong></td>
                                <td>
                                    <span class="waza-status-badge status-<?php echo $booking_status_class; ?>">
                                        <?php echo ucfirst($booking->booking_status); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="waza-status-badge payment-<?php echo $payment_status_class; ?>">
                                        <?php echo $booking->payment_status === 'pending' ? 'Pending Payment' : 'Completed'; ?>
                                    </span>
                                </td>
                                <td><?php echo $booking->payment_method ? ucfirst($booking->payment_method) : '—'; ?></td>
                                <td>
                                    <?php if (!empty($booking->gateway_payment_id)): ?>
                                        <code style="font-size: 11px;"><?php echo esc_html($booking->gateway_payment_id); ?></code>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($created_by); ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($booking->created_at)); ?></td>
                                <td>
                                    <button class="button button-small waza-view-booking" 
                                            data-booking-id="<?php echo $booking->id; ?>"
                                            data-customer="<?php echo esc_attr($booking->user_name ?: $booking->user_name); ?>"
                                            data-email="<?php echo esc_attr($booking->user_email); ?>"
                                            data-phone="<?php echo esc_attr($booking->user_phone); ?>"
                                            data-activity="<?php echo esc_attr($booking->activity_title); ?>"
                                            data-date="<?php echo $booking->start_datetime ? date('M j, Y', strtotime($booking->start_datetime)) : '—'; ?>"
                                            data-time="<?php echo $booking->start_datetime ? date('g:i A', strtotime($booking->start_datetime)) . ' - ' . date('g:i A', strtotime($booking->end_datetime)) : '—'; ?>"
                                            data-quantity="<?php echo $booking->quantity; ?>"
                                            data-amount="<?php echo number_format($booking->total_amount, 2); ?>"
                                            data-booking-status="<?php echo ucfirst($booking->booking_status); ?>"
                                            data-payment-status="<?php echo ucfirst($booking->payment_status); ?>"
                                            data-payment-method="<?php echo $booking->payment_method ?: 'N/A'; ?>"
                                            data-created-at="<?php echo date('M j, Y g:i A', strtotime($booking->created_at)); ?>">View</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if ($total_pages > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="displaying-num"><?php echo $total_items; ?> items</span>
                    <span class="pagination-links">
                        <?php if ($current_page > 1): ?>
                            <a class="first-page button" href="?page=waza-all-bookings&status=<?php echo $status_filter; ?>&paged=1">&laquo;</a>
                            <a class="prev-page button" href="?page=waza-all-bookings&status=<?php echo $status_filter; ?>&paged=<?php echo $current_page - 1; ?>">&lsaquo;</a>
                        <?php else: ?>
                            <span class="tablenav-pages-navspan button disabled">&laquo;</span>
                            <span class="tablenav-pages-navspan button disabled">&lsaquo;</span>
                        <?php endif; ?>
                        
                        <span class="paging-input">
                            <span class="tablenav-paging-text"><?php echo $current_page; ?> of <span class="total-pages"><?php echo $total_pages; ?></span></span>
                        </span>
                        
                        <?php if ($current_page < $total_pages): ?>
                            <a class="next-page button" href="?page=waza-all-bookings&status=<?php echo $status_filter; ?>&paged=<?php echo $current_page + 1; ?>">&rsaquo;</a>
                            <a class="last-page button" href="?page=waza-all-bookings&status=<?php echo $status_filter; ?>&paged=<?php echo $total_pages; ?>">&raquo;</a>
                        <?php else: ?>
                            <span class="tablenav-pages-navspan button disabled">&rsaquo;</span>
                            <span class="tablenav-pages-navspan button disabled">&raquo;</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Modal for Booking Details -->
            <div id="waza-booking-details-modal" class="waza-admin-modal" style="display: none;">
                <div class="waza-admin-modal-overlay"></div>
                <div class="waza-admin-modal-content">
                    <div class="waza-admin-modal-header">
                        <h2>Booking Details</h2>
                        <button class="waza-admin-modal-close">&times;</button>
                    </div>
                    <div class="waza-admin-modal-body">
                        <div class="waza-admin-detail-section">
                            <h3>Customer Information</h3>
                            <table class="widefat">
                                <tr><th>Name:</th><td id="modal-customer"></td></tr>
                                <tr><th>Email:</th><td id="modal-email"></td></tr>
                                <tr><th>Phone:</th><td id="modal-phone"></td></tr>
                            </table>
                        </div>
                        <div class="waza-admin-detail-section">
                            <h3>Booking Information</h3>
                            <table class="widefat">
                                <tr><th>Booking ID:</th><td id="modal-booking-id"></td></tr>
                                <tr><th>Activity:</th><td id="modal-activity"></td></tr>
                                <tr><th>Date:</th><td id="modal-date"></td></tr>
                                <tr><th>Time:</th><td id="modal-time"></td></tr>
                                <tr><th>Quantity:</th><td id="modal-quantity"></td></tr>
                                <tr><th>Total Amount:</th><td id="modal-amount"></td></tr>
                            </table>
                        </div>
                        <div class="waza-admin-detail-section">
                            <h3>Status & Payment</h3>
                            <table class="widefat">
                                <tr><th>Booking Status:</th><td id="modal-booking-status"></td></tr>
                                <tr><th>Payment Status:</th><td id="modal-payment-status"></td></tr>
                                <tr><th>Payment Method:</th><td id="modal-payment-method"></td></tr>
                                <tr><th>Created At:</th><td id="modal-created-at"></td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="waza-admin-modal-footer">
                        <button class="button button-secondary waza-admin-modal-close">Close</button>
                    </div>
                </div>
            </div>
            
            <style>
                .waza-status-badge {
                    display: inline-block;
                    padding: 4px 10px;
                    border-radius: 12px;
                    font-size: 12px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                .status-confirmed {
                    background: #D1FAE5;
                    color: #065F46;
                }
                .status-pending {
                    background: #FEF3C7;
                    color: #92400E;
                }
                .payment-completed {
                    background: #DBEAFE;
                    color: #1E40AF;
                }
                .payment-pending {
                    background: #FEE2E2;
                    color: #991B1B;
                }
                .wp-list-table td {
                    vertical-align: middle;
                }
                
                /* Admin Modal Styles */
                .waza-admin-modal {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    z-index: 100000;
                }
                .waza-admin-modal-overlay {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.7);
                }
                .waza-admin-modal-content {
                    position: relative;
                    width: 90%;
                    max-width: 700px;
                    margin: 50px auto;
                    background: #fff;
                    border-radius: 8px;
                    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
                    max-height: calc(100vh - 100px);
                    overflow-y: auto;
                }
                .waza-admin-modal-header {
                    padding: 20px 25px;
                    border-bottom: 1px solid #ddd;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                .waza-admin-modal-header h2 {
                    margin: 0;
                    font-size: 20px;
                }
                .waza-admin-modal-close {
                    background: none;
                    border: none;
                    font-size: 28px;
                    cursor: pointer;
                    color: #666;
                    padding: 0;
                    width: 30px;
                    height: 30px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 4px;
                }
                .waza-admin-modal-close:hover {
                    background: #f0f0f0;
                    color: #000;
                }
                .waza-admin-modal-body {
                    padding: 25px;
                }
                .waza-admin-modal-footer {
                    padding: 15px 25px;
                    border-top: 1px solid #ddd;
                    text-align: right;
                }
                .waza-admin-detail-section {
                    margin-bottom: 25px;
                }
                .waza-admin-detail-section:last-child {
                    margin-bottom: 0;
                }
                .waza-admin-detail-section h3 {
                    margin: 0 0 15px 0;
                    padding-bottom: 10px;
                    border-bottom: 2px solid #0073aa;
                    font-size: 16px;
                    color: #0073aa;
                }
                .waza-admin-detail-section table {
                    margin: 0;
                }
                .waza-admin-detail-section table th {
                    width: 40%;
                    text-align: left;
                    padding: 8px 12px;
                    background: #f9f9f9;
                    font-weight: 600;
                }
                .waza-admin-detail-section table td {
                    padding: 8px 12px;
                }
            </style>
            
            <script>
            jQuery(document).ready(function($) {
                // View booking details
                $('.waza-view-booking').on('click', function(e) {
                    e.preventDefault();
                    var btn = $(this);
                    
                    $('#modal-booking-id').text('#' + btn.data('booking-id'));
                    $('#modal-customer').text(btn.data('customer'));
                    $('#modal-email').text(btn.data('email'));
                    $('#modal-phone').text(btn.data('phone'));
                    $('#modal-activity').text(btn.data('activity'));
                    $('#modal-date').text(btn.data('date'));
                    $('#modal-time').text(btn.data('time'));
                    $('#modal-quantity').text(btn.data('quantity'));
                    $('#modal-amount').html('<strong>₹' + btn.data('amount') + '</strong>');
                    $('#modal-booking-status').html('<span class="waza-status-badge status-' + btn.data('booking-status').toLowerCase() + '">' + btn.data('booking-status') + '</span>');
                    $('#modal-payment-status').html('<span class="waza-status-badge payment-' + btn.data('payment-status').toLowerCase() + '">' + btn.data('payment-status') + '</span>');
                    $('#modal-payment-method').text(btn.data('payment-method'));
                    $('#modal-created-at').text(btn.data('created-at'));
                    
                    $('#waza-booking-details-modal').fadeIn(200);
                });
                
                // Close modal
                $('.waza-admin-modal-close, .waza-admin-modal-overlay').on('click', function() {
                    $('#waza-booking-details-modal').fadeOut(200);
                });
            });
            </script>
        </div>
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
    }
    /**
     * Pending Approvals Page
     */
    public function pending_approvals_page() {
        global $wpdb;
        
        // Get pending approvals and cancellation requests
        $pending_slots = $wpdb->get_results("
            SELECT 
                s.*,
                p.post_title as activity_title,
                p.post_content as activity_description,
                pm.meta_value as instructor_id,
                pi.post_title as instructor_name
            FROM {$wpdb->prefix}waza_slots s
            INNER JOIN {$wpdb->posts} p ON s.activity_id = p.ID
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_waza_instructor'
            LEFT JOIN {$wpdb->posts} pi ON pm.meta_value = pi.ID
            WHERE s.status IN ('pending_approval', 'pending_cancellation')
            ORDER BY s.created_at DESC
        ");
        
        // Get cancelled slots (available for reassignment)
        $cancelled_slots = $wpdb->get_results("
            SELECT 
                s.*,
                p.post_title as activity_title,
                p.post_content as activity_description,
                pm.meta_value as instructor_id,
                pi.post_title as instructor_name
            FROM {$wpdb->prefix}waza_slots s
            INNER JOIN {$wpdb->posts} p ON s.activity_id = p.ID
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_waza_instructor'
            LEFT JOIN {$wpdb->posts} pi ON pm.meta_value = pi.ID
            WHERE s.status = 'cancelled'
            ORDER BY s.updated_at DESC
        ");
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Pending Approvals', 'waza-booking'); ?></h1>
            
            <?php if (empty($pending_slots)): ?>
                <div class="notice notice-info">
                    <p><?php esc_html_e('No pending approvals at the moment.', 'waza-booking'); ?></p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Activity', 'waza-booking'); ?></th>
                            <th><?php esc_html_e('Instructor', 'waza-booking'); ?></th>
                            <th><?php esc_html_e('Date & Time', 'waza-booking'); ?></th>
                            <th><?php esc_html_e('Capacity', 'waza-booking'); ?></th>
                            <th><?php esc_html_e('Price', 'waza-booking'); ?></th>
                            <th><?php esc_html_e('Status', 'waza-booking'); ?></th>
                            <th><?php esc_html_e('Actions', 'waza-booking'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_slots as $slot): 
                            $is_cancellation = ($slot->status === 'pending_cancellation');
                            $cancellation_reason = get_post_meta($slot->activity_id, '_waza_cancellation_reason_' . $slot->id, true);
                        ?>
                            <tr id="slot-<?php echo esc_attr($slot->id); ?>" class="<?php echo $is_cancellation ? 'cancellation-request' : ''; ?>">
                                <td>
                                    <strong><?php echo esc_html($slot->activity_title); ?></strong>
                                    <?php if ($slot->activity_description): ?>
                                        <br><small><?php echo esc_html(wp_trim_words($slot->activity_description, 15)); ?></small>
                                    <?php endif; ?>
                                    <?php if ($is_cancellation && $cancellation_reason): ?>
                                        <br><br><strong><?php esc_html_e('Cancellation Reason:', 'waza-booking'); ?></strong>
                                        <br><em><?php echo esc_html($cancellation_reason); ?></em>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($slot->instructor_name ?: 'Unknown'); ?></td>
                                <td>
                                    <?php echo date('M j, Y', strtotime($slot->start_datetime)); ?><br>
                                    <small><?php echo date('g:i A', strtotime($slot->start_datetime)) . ' - ' . date('g:i A', strtotime($slot->end_datetime)); ?></small>
                                </td>
                                <td><?php echo esc_html($slot->capacity); ?></td>
                                <td>₹<?php echo number_format($slot->price, 0); ?></td>
                                <td>
                                    <?php if ($is_cancellation): ?>
                                        <span class="status-badge cancellation"><?php esc_html_e('Cancellation Request', 'waza-booking'); ?></span>
                                    <?php else: ?>
                                        <span class="status-badge pending"><?php esc_html_e('Pending Approval', 'waza-booking'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($is_cancellation): ?>
                                        <button class="button button-primary approve-cancellation" data-slot-id="<?php echo esc_attr($slot->id); ?>" data-nonce="<?php echo wp_create_nonce('waza_approve_cancellation_' . $slot->id); ?>">
                                            <?php esc_html_e('Approve Cancel', 'waza-booking'); ?>
                                        </button>
                                        <button class="button button-secondary reject-cancellation" data-slot-id="<?php echo esc_attr($slot->id); ?>" data-nonce="<?php echo wp_create_nonce('waza_reject_cancellation_' . $slot->id); ?>">
                                            <?php esc_html_e('Reject Cancel', 'waza-booking'); ?>
                                        </button>
                                        <button class="button reassign-slot" data-slot-id="<?php echo esc_attr($slot->id); ?>" data-activity-id="<?php echo esc_attr($slot->activity_id); ?>">
                                            <?php esc_html_e('Reassign', 'waza-booking'); ?>
                                        </button>
                                    <?php else: ?>
                                        <button class="button button-primary approve-slot" data-slot-id="<?php echo esc_attr($slot->id); ?>" data-nonce="<?php echo wp_create_nonce('waza_approve_slot_' . $slot->id); ?>">
                                            <?php esc_html_e('Approve', 'waza-booking'); ?>
                                        </button>
                                        <button class="button button-secondary reject-slot" data-slot-id="<?php echo esc_attr($slot->id); ?>" data-nonce="<?php echo wp_create_nonce('waza_reject_slot_' . $slot->id); ?>">
                                            <?php esc_html_e('Reject', 'waza-booking'); ?>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <!-- Cancelled Slots Section -->
            <div style="margin-top: 40px;">
                <h2><?php esc_html_e('Cancelled Slots (Available for Reassignment)', 'waza-booking'); ?></h2>
                
                <?php if (empty($cancelled_slots)): ?>
                    <div class="notice notice-info">
                        <p><?php esc_html_e('No cancelled slots at the moment.', 'waza-booking'); ?></p>
                    </div>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Activity', 'waza-booking'); ?></th>
                                <th><?php esc_html_e('Original Instructor', 'waza-booking'); ?></th>
                                <th><?php esc_html_e('Date & Time', 'waza-booking'); ?></th>
                                <th><?php esc_html_e('Capacity', 'waza-booking'); ?></th>
                                <th><?php esc_html_e('Price', 'waza-booking'); ?></th>
                                <th><?php esc_html_e('Cancelled Date', 'waza-booking'); ?></th>
                                <th><?php esc_html_e('Actions', 'waza-booking'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cancelled_slots as $slot): 
                                $cancellation_reason = get_post_meta($slot->activity_id, '_waza_cancellation_reason_' . $slot->id, true);
                            ?>
                                <tr id="slot-<?php echo esc_attr($slot->id); ?>" style="background-color: #f9f9f9;">
                                    <td>
                                        <strong><?php echo esc_html($slot->activity_title); ?></strong>
                                        <?php if ($slot->activity_description): ?>
                                            <br><small><?php echo esc_html(wp_trim_words($slot->activity_description, 15)); ?></small>
                                        <?php endif; ?>
                                        <?php if ($cancellation_reason): ?>
                                            <br><br><strong><?php esc_html_e('Cancellation Reason:', 'waza-booking'); ?></strong>
                                            <br><em><?php echo esc_html($cancellation_reason); ?></em>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($slot->instructor_name ?: 'Unknown'); ?></td>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($slot->start_datetime)); ?><br>
                                        <small><?php echo date('g:i A', strtotime($slot->start_datetime)) . ' - ' . date('g:i A', strtotime($slot->end_datetime)); ?></small>
                                    </td>
                                    <td><?php echo esc_html($slot->capacity); ?></td>
                                    <td>₹<?php echo number_format($slot->price, 0); ?></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($slot->updated_at)); ?></td>
                                    <td>
                                        <button class="button button-primary reassign-slot" data-slot-id="<?php echo esc_attr($slot->id); ?>" data-activity-id="<?php echo esc_attr($slot->activity_id); ?>">
                                            <?php esc_html_e('Reassign', 'waza-booking'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.approve-slot').on('click', function() {
                const slotId = $(this).data('slot-id');
                const nonce = $(this).data('nonce');
                const row = $('#slot-' + slotId);
                
                if (!confirm('<?php esc_html_e('Are you sure you want to approve this slot?', 'waza-booking'); ?>')) {
                    return;
                }
                
                $(this).prop('disabled', true).text('<?php esc_html_e('Approving...', 'waza-booking'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'waza_approve_workshop',
                        slot_id: slotId,
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            row.fadeOut(400, function() {
                                $(this).remove();
                                if ($('tbody tr').length === 0) {
                                    location.reload();
                                }
                            });
                        } else {
                            alert(response.data.message || '<?php esc_html_e('Failed to approve slot', 'waza-booking'); ?>');
                            location.reload();
                        }
                    }
                });
            });
            
            $('.reject-slot').on('click', function() {
                const slotId = $(this).data('slot-id');
                const nonce = $(this).data('nonce');
                const row = $('#slot-' + slotId);
                
                const reason = prompt('<?php esc_html_e('Reason for rejection (optional):', 'waza-booking'); ?>');
                if (reason === null) return;
                
                $(this).prop('disabled', true).text('<?php esc_html_e('Rejecting...', 'waza-booking'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'waza_reject_workshop',
                        slot_id: slotId,
                        nonce: nonce,
                        reason: reason
                    },
                    success: function(response) {
                        if (response.success) {
                            row.fadeOut(400, function() {
                                $(this).remove();
                                if ($('tbody tr').length === 0) {
                                    location.reload();
                                }
                            });
                        } else {
                            alert(response.data.message || '<?php esc_html_e('Failed to reject slot', 'waza-booking'); ?>');
                            location.reload();
                        }
                    }
                });
            });
            
            // Approve cancellation
            $('.approve-cancellation').on('click', function() {
                const slotId = $(this).data('slot-id');
                const nonce = $(this).data('nonce');
                const row = $('#slot-' + slotId);
                
                if (!confirm('<?php esc_html_e('Approve this cancellation request? The slot will be deleted.', 'waza-booking'); ?>')) {
                    return;
                }
                
                $(this).prop('disabled', true).text('<?php esc_html_e('Processing...', 'waza-booking'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'waza_approve_cancellation',
                        slot_id: slotId,
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            row.fadeOut(400, function() {
                                $(this).remove();
                                if ($('tbody tr').length === 0) {
                                    location.reload();
                                }
                            });
                        } else {
                            alert(response.data.message);
                            location.reload();
                        }
                    }
                });
            });
            
            // Reject cancellation
            $('.reject-cancellation').on('click', function() {
                const slotId = $(this).data('slot-id');
                const nonce = $(this).data('nonce');
                const row = $('#slot-' + slotId);
                
                if (!confirm('<?php esc_html_e('Reject this cancellation? The slot will remain active.', 'waza-booking'); ?>')) {
                    return;
                }
                
                $(this).prop('disabled', true).text('<?php esc_html_e('Processing...', 'waza-booking'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'waza_reject_cancellation',
                        slot_id: slotId,
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            row.fadeOut(400, function() {
                                $(this).remove();
                                if ($('tbody tr').length === 0) {
                                    location.reload();
                                }
                            });
                        } else {
                            alert(response.data.message);
                            location.reload();
                        }
                    }
                });
            });
            
            // Reassign slot
            $('.reassign-slot').on('click', function() {
                const slotId = $(this).data('slot-id');
                const activityId = $(this).data('activity-id');
                const btn = $(this);
                
                btn.prop('disabled', true).text('<?php esc_html_e('Loading...', 'waza-booking'); ?>');
                
                // Get available instructors
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'waza_get_available_instructors',
                        slot_id: slotId,
                        activity_id: activityId
                    },
                    success: function(response) {
                        btn.prop('disabled', false).text('<?php esc_html_e('Reassign', 'waza-booking'); ?>');
                        
                        if (response.success && response.data.instructors.length > 0) {
                            // Create modal with dropdown
                            showReassignModal(slotId, activityId, response.data.instructors);
                        } else {
                            alert('<?php esc_html_e('No available instructors for this time slot', 'waza-booking'); ?>');
                        }
                    }
                });
            });
            
            function showReassignModal(slotId, activityId, instructors) {
                const modalHtml = `
                    <div id="reassign-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center;">
                        <div style="background: white; padding: 30px; border-radius: 8px; max-width: 500px; width: 90%;">
                            <h2 style="margin-top: 0;"><?php esc_html_e('Reassign Workshop', 'waza-booking'); ?></h2>
                            <p><?php esc_html_e('Select an available instructor for this workshop:', 'waza-booking'); ?></p>
                            <select id="reassign-instructor-select" class="widefat" style="padding: 8px; font-size: 14px; margin: 15px 0;">
                                <option value=""><?php esc_html_e('-- Select Instructor --', 'waza-booking'); ?></option>
                                ${instructors.map(i => `<option value="${i.id}">${i.name}</option>`).join('')}
                            </select>
                            <div style="margin-top: 20px; text-align: right;">
                                <button class="button" onclick="jQuery('#reassign-modal').remove()">
                                    <?php esc_html_e('Cancel', 'waza-booking'); ?>
                                </button>
                                <button class="button button-primary" id="confirm-reassign-btn" style="margin-left: 10px;">
                                    <?php esc_html_e('Reassign', 'waza-booking'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                
                $('body').append(modalHtml);
                
                $('#confirm-reassign-btn').on('click', function() {
                    const instructorId = $('#reassign-instructor-select').val();
                    
                    if (!instructorId) {
                        alert('<?php esc_html_e('Please select an instructor', 'waza-booking'); ?>');
                        return;
                    }
                    
                    $(this).prop('disabled', true).text('<?php esc_html_e('Processing...', 'waza-booking'); ?>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'waza_reassign_workshop',
                            slot_id: slotId,
                            activity_id: activityId,
                            instructor_id: instructorId
                        },
                        success: function(res) {
                            alert(res.data.message);
                            if (res.success) {
                                location.reload();
                            } else {
                                $('#reassign-modal').remove();
                            }
                        }
                    });
                });
            }
        });
        </script>
        
        <!-- Reassignment Modal Placeholder -->
        <div id="reassign-modal-container"></div>
        
        <?php
    }
    
    /**
     * AJAX: Approve Workshop
     */
    public function ajax_approve_workshop() {
        $slot_id = intval($_POST['slot_id'] ?? 0);
        $nonce = $_POST['nonce'] ?? '';
        
        if (!wp_verify_nonce($nonce, 'waza_approve_workshop_' . $slot_id)) {
            wp_send_json_error(['message' => __('Invalid security token', 'waza-booking')]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'waza-booking')]);
        }
        
        global $wpdb;
        
        // Update slot status
        $updated = $wpdb->update(
            $wpdb->prefix . 'waza_slots',
            ['status' => 'active', 'updated_at' => current_time('mysql')],
            ['id' => $slot_id],
            ['%s', '%s'],
            ['%d']
        );
        
        if (!$updated) {
            wp_send_json_error(['message' => __('Failed to update workshop status', 'waza-booking')]);
        }
        
        // Get workshop and instructor details
        $slot = $wpdb->get_row($wpdb->prepare("
            SELECT s.*, p.post_title as activity_title, pm.meta_value as instructor_id
            FROM {$wpdb->prefix}waza_slots s
            INNER JOIN {$wpdb->posts} p ON s.activity_id = p.ID
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_waza_instructor'
            WHERE s.id = %d
        ", $slot_id));
        
        if ($slot && $slot->instructor_id) {
            $this->notify_instructor_approval($slot, 'approved');
        }
        
        wp_send_json_success(['message' => __('Workshop approved successfully!', 'waza-booking')]);
    }
    
    /**
     * AJAX: Reject Workshop
     */
    public function ajax_reject_workshop() {
        $slot_id = intval($_POST['slot_id'] ?? 0);
        $nonce = $_POST['nonce'] ?? '';
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');
        
        if (!wp_verify_nonce($nonce, 'waza_reject_workshop_' . $slot_id)) {
            wp_send_json_error(['message' => __('Invalid security token', 'waza-booking')]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'waza-booking')]);
        }
        
        global $wpdb;
        
        // Get workshop details before deletion
        $slot = $wpdb->get_row($wpdb->prepare("
            SELECT s.*, p.post_title as activity_title, pm.meta_value as instructor_id
            FROM {$wpdb->prefix}waza_slots s
            INNER JOIN {$wpdb->posts} p ON s.activity_id = p.ID
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_waza_instructor'
            WHERE s.id = %d
        ", $slot_id));
        
        // Delete the slot
        $deleted = $wpdb->delete(
            $wpdb->prefix . 'waza_slots',
            ['id' => $slot_id],
            ['%d']
        );
        
        if (!$deleted) {
            wp_send_json_error(['message' => __('Failed to delete workshop', 'waza-booking')]);
        }
        
        // Notify instructor
        if ($slot && $slot->instructor_id) {
            $this->notify_instructor_approval($slot, 'rejected', $reason);
        }
        
        wp_send_json_success(['message' => __('Workshop rejected', 'waza-booking')]);
    }
    
    /**
     * Notify instructor about approval/rejection
     */
    private function notify_instructor_approval($slot, $status, $reason = '') {
        // Get instructor's user
        $instructor = get_post($slot->instructor_id);
        $user_id = get_post_meta($slot->instructor_id, '_waza_user_id', true);
        
        if (!$user_id) {
            return;
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }
        
        if ($status === 'approved') {
            $subject = sprintf(__('[Waza Booking] Workshop Approved - %s', 'waza-booking'), $slot->activity_title);
            $message = sprintf(
                __('Great news! Your workshop "%s" has been approved by the admin.', 'waza-booking'),
                $slot->activity_title
            ) . "\n\n";
            
            $message .= __('Workshop Details:', 'waza-booking') . "\n";
            $message .= sprintf(__('Date & Time: %s', 'waza-booking'), date('M j, Y g:i A', strtotime($slot->start_datetime))) . "\n";
            $message .= sprintf(__('Capacity: %d students', 'waza-booking'), $slot->capacity) . "\n";
            $message .= sprintf(__('Price: $%s', 'waza-booking'), $slot->price) . "\n\n";
            
            $message .= __('Your workshop is now available for booking!', 'waza-booking') . "\n";
            $message .= __('View it in your instructor dashboard.', 'waza-booking');
            
        } else {
            $subject = sprintf(__('[Waza Booking] Workshop Rejected - %s', 'waza-booking'), $slot->activity_title);
            $message = sprintf(
                __('Your workshop "%s" was not approved.', 'waza-booking'),
                $slot->activity_title
            ) . "\n\n";
            
            if ($reason) {
                $message .= __('Reason:', 'waza-booking') . " $reason\n\n";
            }
            
            $message .= __('Please contact the admin if you have questions.', 'waza-booking');
        }
        
        wp_mail($user->user_email, $subject, $message);
    }
    
    /**
     * AJAX: Approve cancellation request
     */
    public function ajax_approve_cancellation() {
        $slot_id = intval($_POST['slot_id'] ?? 0);
        $nonce = $_POST['nonce'] ?? '';
        
        if (!wp_verify_nonce($nonce, 'waza_approve_cancellation_' . $slot_id)) {
            wp_send_json_error(['message' => __('Invalid security token', 'waza-booking')]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'waza-booking')]);
        }
        
        global $wpdb;
        
        // Get slot details
        $slot = $wpdb->get_row($wpdb->prepare("
            SELECT s.*, p.post_title as activity_title, pm.meta_value as instructor_id
            FROM {$wpdb->prefix}waza_slots s
            INNER JOIN {$wpdb->posts} p ON s.activity_id = p.ID
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_waza_instructor'
            WHERE s.id = %d
        ", $slot_id));
        
        // Update slot status to 'cancelled' instead of deleting
        $updated = $wpdb->update(
            $wpdb->prefix . 'waza_slots',
            ['status' => 'cancelled', 'updated_at' => current_time('mysql')],
            ['id' => $slot_id],
            ['%s', '%s'],
            ['%d']
        );
        
        if (!$updated) {
            wp_send_json_error(['message' => __('Failed to cancel workshop', 'waza-booking')]);
        }
        
        // Notify instructor
        if ($slot && $slot->instructor_id) {
            $this->notify_instructor_cancellation($slot, 'approved');
        }
        
        wp_send_json_success(['message' => __('Workshop cancelled successfully. You can reassign it to another instructor.', 'waza-booking')]);
    }
    
    /**
     * AJAX: Reject cancellation request
     */
    public function ajax_reject_cancellation() {
        $slot_id = intval($_POST['slot_id'] ?? 0);
        $nonce = $_POST['nonce'] ?? '';
        
        if (!wp_verify_nonce($nonce, 'waza_reject_cancellation_' . $slot_id)) {
            wp_send_json_error(['message' => __('Invalid security token', 'waza-booking')]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'waza-booking')]);
        }
        
        global $wpdb;
        
        // Restore slot to active status
        $updated = $wpdb->update(
            $wpdb->prefix . 'waza_slots',
            ['status' => 'active', 'updated_at' => current_time('mysql')],
            ['id' => $slot_id],
            ['%s', '%s'],
            ['%d']
        );
        
        if (!$updated) {
            wp_send_json_error(['message' => __('Failed to reject cancellation', 'waza-booking')]);
        }
        
        // Get slot details for notification
        $slot = $wpdb->get_row($wpdb->prepare("
            SELECT s.*, p.post_title as activity_title, pm.meta_value as instructor_id
            FROM {$wpdb->prefix}waza_slots s
            INNER JOIN {$wpdb->posts} p ON s.activity_id = p.ID
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_waza_instructor'
            WHERE s.id = %d
        ", $slot_id));
        
        // Notify instructor
        if ($slot && $slot->instructor_id) {
            $this->notify_instructor_cancellation($slot, 'rejected');
        }
        
        wp_send_json_success(['message' => __('Cancellation request rejected', 'waza-booking')]);
    }
    
    /**
     * AJAX: Get available instructors for reassignment
     */
    public function ajax_get_available_instructors() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'waza-booking')]);
        }
        
        $slot_id = intval($_POST['slot_id'] ?? 0);
        $activity_id = intval($_POST['activity_id'] ?? 0);
        
        global $wpdb;
        
        // Get slot time and current instructor
        $slot = $wpdb->get_row($wpdb->prepare("
            SELECT s.*, pm.meta_value as current_instructor_id
            FROM {$wpdb->prefix}waza_slots s
            LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_waza_instructor'
            WHERE s.id = %d
        ", $slot_id));
        
        if (!$slot) {
            wp_send_json_error(['message' => __('Slot not found', 'waza-booking')]);
        }
        
        // Get all active instructors
        $instructors = get_posts([
            'post_type' => 'waza_instructor',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);
        
        $available = [];
        
        foreach ($instructors as $instructor) {
            // Skip the instructor who cancelled this workshop
            if ($slot->current_instructor_id && $instructor->ID == $slot->current_instructor_id) {
                continue;
            }
            
            // Check if instructor has conflicts
            $conflict = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) 
                FROM {$wpdb->prefix}waza_slots s
                INNER JOIN {$wpdb->posts} p ON s.activity_id = p.ID
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_waza_instructor'
                WHERE pm.meta_value = %d
                AND s.status = 'active'
                AND s.id != %d
                AND (
                    (s.start_datetime <= %s AND s.end_datetime > %s)
                    OR (s.start_datetime < %s AND s.end_datetime >= %s)
                    OR (s.start_datetime >= %s AND s.end_datetime <= %s)
                )
            ", $instructor->ID, $slot_id, $slot->start_datetime, $slot->start_datetime, 
               $slot->end_datetime, $slot->end_datetime, $slot->start_datetime, $slot->end_datetime));
            
            if ($conflict == 0) {
                $available[] = [
                    'id' => $instructor->ID,
                    'name' => $instructor->post_title
                ];
            }
        }
        
        wp_send_json_success(['instructors' => $available]);
    }
    
    /**
     * AJAX: Reassign workshop to another instructor
     */
    public function ajax_reassign_workshop() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'waza-booking')]);
        }
        
        $slot_id = intval($_POST['slot_id'] ?? 0);
        $activity_id = intval($_POST['activity_id'] ?? 0);
        $new_instructor_id = intval($_POST['instructor_id'] ?? 0);
        
        if (!$slot_id || !$activity_id || !$new_instructor_id) {
            wp_send_json_error(['message' => __('Missing required data', 'waza-booking')]);
        }
        
        // Update activity instructor
        update_post_meta($activity_id, '_waza_instructor', $new_instructor_id);
        
        // Update slot status to active AND update instructor_id in slots table
        global $wpdb;
        $updated = $wpdb->update(
            $wpdb->prefix . 'waza_slots',
            [
                'status' => 'active', 
                'instructor_id' => $new_instructor_id,
                'updated_at' => current_time('mysql')
            ],
            ['id' => $slot_id],
            ['%s', '%d', '%s'],
            ['%d']
        );
        
        if (!$updated) {
            wp_send_json_error(['message' => __('Failed to reassign workshop', 'waza-booking')]);
        }
        
        // Notify new instructor
        $slot = $wpdb->get_row($wpdb->prepare("
            SELECT s.*, p.post_title as activity_title
            FROM {$wpdb->prefix}waza_slots s
            INNER JOIN {$wpdb->posts} p ON s.activity_id = p.ID
            WHERE s.id = %d
        ", $slot_id));
        
        $this->notify_instructor_assignment($slot, $new_instructor_id);
        
        wp_send_json_success(['message' => __('Workshop reassigned successfully', 'waza-booking')]);
    }
    
    /**
     * Notify instructor about cancellation decision
     */
    private function notify_instructor_cancellation($slot, $status) {
        $user_id = get_post_meta($slot->instructor_id, '_waza_user_id', true);
        if (!$user_id) return;
        
        $user = get_userdata($user_id);
        if (!$user) return;
        
        if ($status === 'approved') {
            $subject = sprintf(__('[Waza Booking] Workshop Cancelled - %s', 'waza-booking'), $slot->activity_title);
            $message = sprintf(__('Your cancellation request for "%s" has been approved.', 'waza-booking'), $slot->activity_title) . "\n\n";
            $message .= __('The workshop has been cancelled.', 'waza-booking');
        } else {
            $subject = sprintf(__('[Waza Booking] Cancellation Rejected - %s', 'waza-booking'), $slot->activity_title);
            $message = sprintf(__('Your cancellation request for "%s" has been rejected.', 'waza-booking'), $slot->activity_title) . "\n\n";
            $message .= __('The workshop will proceed as scheduled.', 'waza-booking');
        }
        
        wp_mail($user->user_email, $subject, $message);
    }
    
    /**
     * Notify instructor about workshop assignment
     */
    private function notify_instructor_assignment($slot, $instructor_id) {
        $user_id = get_post_meta($instructor_id, '_waza_user_id', true);
        if (!$user_id) return;
        
        $user = get_userdata($user_id);
        if (!$user) return;
        
        $subject = sprintf(__('[Waza Booking] Workshop Assigned - %s', 'waza-booking'), $slot->activity_title);
        $message = sprintf(__('You have been assigned to lead the workshop "%s".', 'waza-booking'), $slot->activity_title) . "\n\n";
        $message .= __('Workshop Details:', 'waza-booking') . "\n";
        $message .= sprintf(__('Date & Time: %s', 'waza-booking'), date('M j, Y g:i A', strtotime($slot->start_datetime))) . "\n";
        $message .= sprintf(__('Capacity: %d students', 'waza-booking'), $slot->capacity) . "\n\n";
        $message .= __('Please check your instructor dashboard for more details.', 'waza-booking');
        
        wp_mail($user->user_email, $subject, $message);
    }
}
