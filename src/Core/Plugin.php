<?php
/**
 * Main Plugin Class
 * 
 * @package WazaBooking\Core
 */

namespace WazaBooking\Core;

use WazaBooking\Database\DatabaseManager;
use WazaBooking\PostTypes\PostTypeManager;
use WazaBooking\API\RestApiManager;
use WazaBooking\Admin\AdminManager;
use WazaBooking\Admin\CustomizationManager;
use WazaBooking\Admin\SettingsManager;
use WazaBooking\Admin\SlotManager;
use WazaBooking\Admin\AttendanceManager;
use WazaBooking\Admin\ActivityLogsManager;
use WazaBooking\Email\EmailTemplateManager;
use WazaBooking\Frontend\FrontendManager;
use WazaBooking\Frontend\ShortcodeManager;
use WazaBooking\Frontend\AjaxHandler;
use WazaBooking\Payment\PaymentManager;
use WazaBooking\QR\QRManager;
use WazaBooking\Notifications\NotificationManager;
use WazaBooking\Security\SecurityManager;
use WazaBooking\User\UserAccountManager;
use WazaBooking\User\AutoLogoutManager;
use WazaBooking\Integrations\ElementorIntegration;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Plugin Class
 */
final class Plugin {
    
    /**
     * Plugin instance
     * 
     * @var Plugin|null
     */
    private static $instance = null;
    
    /**
     * Database manager
     * 
     * @var DatabaseManager
     */
    private $database_manager;
    
    /**
     * Post type manager
     * 
     * @var PostTypeManager
     */
    private $post_type_manager;
    
    /**
     * REST API manager
     * 
     * @var RestApiManager
     */
    private $rest_api_manager;
    
    /**
     * Admin manager
     * 
     * @var AdminManager
     */
    private $admin_manager;
    
    /**
     * Frontend manager
     * 
     * @var FrontendManager
     */
    private $frontend_manager;
    
    /**
     * Shortcode manager
     * 
     * @var ShortcodeManager
     */
    private $shortcode_manager;
    
    /**
     * AJAX handler
     * 
     * @var AjaxHandler
     */
    private $ajax_handler;
    
    /**
     * Payment manager
     * 
     * @var PaymentManager
     */
    private $payment_manager;
    
    /**
     * QR manager
     * 
     * @var QRManager
     */
    private $qr_manager;
    
    /**
     * Notification manager
     * 
     * @var NotificationManager
     */
    private $notification_manager;
    
    /**
     * Security manager
     * 
     * @var SecurityManager
     */
    private $security_manager;
    
    /**
     * Settings manager
     * 
     * @var SettingsManager
     */
    private $settings_manager;
    
    /**
     * Slot manager
     * 
     * @var SlotManager
     */
    private $slot_manager;
    
    /**
     * User account manager
     * 
     * @var UserAccountManager
     */
    private $user_account_manager;
    
    /**
     * Auto logout manager
     * 
     * @var AutoLogoutManager
     */
    private $auto_logout_manager;
    
    /**
     * Customization manager
     * 
     * @var CustomizationManager
     */
    private $customization_manager;
    
    /**
     * Instructor manager
     * 
     * @var InstructorManager
     */
    private $instructor_manager;
    
    /**
     * Scanner manager
     * 
     * @var ScannerManager
     */
    private $scanner_manager;
    
    /**
     * Email template manager
     * 
     * @var EmailTemplateManager
     */
    private $email_template_manager;
    
    /**
     * Elementor integration
     * 
     * @var ElementorIntegration
     */
    private $elementor_integration;
    
    /**
     * Workshop manager
     * 
     * @var \WazaBooking\Workshop\WorkshopManager
     */
    private $workshop_manager;
    
    /**
     * iCalendar manager
     * 
     * @var \WazaBooking\Calendar\ICalendarManager
     */
    private $icalendar_manager;
    
    /**
     * Booking confirmation manager
     * 
     * @var \WazaBooking\Booking\BookingConfirmationManager
     */
    private $booking_confirmation_manager;
    
    /**
     * SMS manager
     * 
     * @var \WazaBooking\Notifications\SMSManager
     */
    private $sms_manager;
    
    /**
     * Refund manager
     * 
     * @var \WazaBooking\Payment\RefundManager
     */
    private $refund_manager;
    
    /**
     * CSV export manager
     * 
     * @var \WazaBooking\Export\CSVExportManager
     */
    private $csv_export_manager;
    
    /**
     * Reschedule manager
     * 
     * @var \WazaBooking\Booking\RescheduleManager
     */
    private $reschedule_manager;
    
    /**
     * Activity logger
     * 
     * @var \WazaBooking\Logs\ActivityLogger
     */
    private $activity_logger;
    
    /**
     * Announcements manager
     * 
     * @var \WazaBooking\Admin\AnnouncementsManager
     */
    private $announcements_manager;
    
    /**     * Attendance manager
     * 
     * @var AttendanceManager
     */
    private $attendance_manager;
    
    /**
     * Activity logs manager
     * 
     * @var ActivityLogsManager
     */
    private $activity_logs_manager;
    
    /**     * Interactive Calendar manager
     * 
     * @var \WazaBooking\Frontend\InteractiveCalendarManager
     */
    private $interactive_calendar_manager;
    
    /**
     * Slot Details manager
     * 
     * @var \WazaBooking\Frontend\SlotDetailsManager
     */
    private $slot_details_manager;
    
    /**
     * Private constructor to prevent direct instantiation
     * CRITICAL FIX: init_managers MUST be called before init_hooks
     */
    private function __construct() {
        $this->init_managers();
        $this->init_hooks();
    }
    
    /**
     * Get plugin instance
     * 
     * @return Plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }

    /**
     * Alias for get_instance()
     * 
     * @return Plugin
     */
    public static function instance() {
        return self::get_instance();
    }

    /**
     * Get a manager instance by name
     * 
     * @param string $name
     * @return mixed
     */
    public function get_manager($name) {
        $method = 'get_' . $name . '_manager';
        if (method_exists($this, $method)) {
            return $this->$method();
        }
        
        $property = $name . '_manager';
        if (property_exists($this, $property)) {
            return $this->$property;
        }
        
        return null;
    }
    
    /**
     * Initialize WordPress hooks
     * CRITICAL FIX: Hook into 'init' to allow post types and shortcodes to register
     * Priority 1 = translation, 5 = post types, 10 = admin/frontend
     */
    private function init_hooks() {
        add_action('init', [$this, 'load_textdomain'], 1);
        add_action('init', [$this, 'init_post_types'], 5);
        add_action('init', [$this, 'init_admin'], 10);
        add_action('init', [$this, 'init_frontend'], 10);
        add_action('rest_api_init', [$this, 'init_rest_api']);
    }
    
    /**
     * Initialize managers
     */
    private function init_managers() {
        $this->database_manager = new DatabaseManager();
        $this->post_type_manager = new PostTypeManager();
        $this->rest_api_manager = new RestApiManager();
        $this->admin_manager = new AdminManager();
        $this->frontend_manager = new FrontendManager();
        $this->shortcode_manager = new ShortcodeManager();
        $this->ajax_handler = new AjaxHandler();
        $this->payment_manager = new PaymentManager();
        $this->qr_manager = new QRManager();
        $this->security_manager = new SecurityManager();
        $this->user_account_manager = new UserAccountManager();
        $this->auto_logout_manager = new AutoLogoutManager();
        $this->customization_manager = new CustomizationManager();
        $this->settings_manager = new SettingsManager();
        $this->slot_manager = new SlotManager();
        $this->email_template_manager = new EmailTemplateManager();
        $this->notification_manager = new NotificationManager();
        $this->instructor_manager = new \WazaBooking\Admin\InstructorManager();
        $this->scanner_manager = new \WazaBooking\Admin\ScannerManager();
        
        // Initialize new managers
        $this->workshop_manager = new \WazaBooking\Workshop\WorkshopManager();
        $this->icalendar_manager = new \WazaBooking\Calendar\ICalendarManager();
        $this->booking_confirmation_manager = new \WazaBooking\Booking\BookingConfirmationManager();
        $this->sms_manager = new \WazaBooking\Notifications\SMSManager();
        $this->refund_manager = new \WazaBooking\Payment\RefundManager();
        $this->csv_export_manager = new \WazaBooking\Export\CSVExportManager();
        $this->reschedule_manager = new \WazaBooking\Booking\RescheduleManager();
        $this->activity_logger = new \WazaBooking\Logs\ActivityLogger();
        $this->announcements_manager = new \WazaBooking\Admin\AnnouncementsManager();
        $this->attendance_manager = new AttendanceManager();
        $this->activity_logs_manager = new ActivityLogsManager();
        $this->interactive_calendar_manager = new \WazaBooking\Frontend\InteractiveCalendarManager();
        $this->slot_details_manager = new \WazaBooking\Frontend\SlotDetailsManager();
        
        // Inject dependencies
        $this->notification_manager->set_email_template_manager($this->email_template_manager);
        
        // Initialize Elementor integration if Elementor is active
        if (class_exists('\Elementor\Plugin')) {
            try {
                $this->elementor_integration = new ElementorIntegration();
            } catch (\Exception $e) {
                error_log('Waza Booking: Failed to initialize Elementor integration - ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Load plugin textdomain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'waza-booking',
            false,
            dirname(WAZA_BOOKING_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Initialize custom post types
     */
    public function init_post_types() {
        $this->post_type_manager->init();
    }
    
    /**
     * Initialize REST API
     */
    public function init_rest_api() {
        $this->rest_api_manager->init();
    }
    
    /**
     * Initialize admin functionality
     * NOTE: Only init() methods that exist are called
     */
    public function init_admin() {
        if (is_admin()) {
            $this->admin_manager->init();
            $this->settings_manager->init();
            // CustomizationManager, EmailTemplateManager, SlotManager use constructors - no init() method
        }
    }
    
    /**
     * Initialize frontend functionality
     * NOTE: Only init() methods that exist are called
     */
    public function init_frontend() {
        if (!is_admin()) {
            $this->frontend_manager->init();
            // ShortcodeManager uses constructor - no init() method
        }
        
        // Initialize managers that work on both frontend and backend
        $this->user_account_manager->init();
        $this->auto_logout_manager->init();
        $this->payment_manager->init();
        $this->security_manager->init();
        $this->notification_manager->init();
        
        // Initialize new feature managers
        $this->workshop_manager->init();
        $this->icalendar_manager->init();
        $this->booking_confirmation_manager->init();
        $this->sms_manager->init();
        $this->refund_manager->init();
        $this->csv_export_manager->init();
        $this->reschedule_manager->init();
        $this->activity_logger->init();
        $this->announcements_manager->init();
        $this->attendance_manager->init();
        $this->activity_logs_manager->init();
        $this->interactive_calendar_manager->init();
        $this->slot_details_manager->init();
        
        // AjaxHandler uses constructor - no init() method
    }
    
    /**
     * Get database manager
     * 
     * @return DatabaseManager
     */
    public function get_database_manager() {
        return $this->database_manager;
    }
    
    /**
     * Get admin manager
     * 
     * @return AdminManager
     */
    public function get_admin_manager() {
        return $this->admin_manager;
    }
    
    /**
     * Get post type manager
     * 
     * @return PostTypeManager
     */
    public function get_post_type_manager() {
        return $this->post_type_manager;
    }
    
    /**
     * Get frontend manager
     * 
     * @return FrontendManager
     */
    public function get_frontend_manager() {
        return $this->frontend_manager;
    }
    
    /**
     * Get REST API manager
     * 
     * @return RestApiManager
     */
    public function get_rest_api_manager() {
        return $this->rest_api_manager;
    }
    
    /**
     * Get AJAX handler
     * 
     * @return AjaxHandler
     */
    public function get_ajax_handler() {
        return $this->ajax_handler;
    }
    
    /**
     * Get payment manager
     * 
     * @return PaymentManager
     */
    public function get_payment_manager() {
        return $this->payment_manager;
    }
    
    /**
     * Get QR manager
     * 
     * @return QRManager
     */
    public function get_qr_manager() {
        return $this->qr_manager;
    }
    
    /**
     * Get notification manager
     * 
     * @return NotificationManager
     */
    public function get_notification_manager() {
        return $this->notification_manager;
    }
    
    /**
     * Get security manager
     * 
     * @return SecurityManager
     */
    public function get_security_manager() {
        return $this->security_manager;
    }
    
    /**
     * Get shortcode manager
     * 
     * @return ShortcodeManager
     */
    public function get_shortcode_manager() {
        return $this->shortcode_manager;
    }
    
    /**
     * Get user account manager
     * 
     * @return UserAccountManager
     */
    public function get_user_account_manager() {
        return $this->user_account_manager;
    }
    
    /**
     * Get customization manager
     * 
     * @return CustomizationManager
     */
    public function get_customization_manager() {
        return $this->customization_manager;
    }
    
    /**
     * Get settings manager
     * 
     * @return SettingsManager
     */
    public function get_settings_manager() {
        return $this->settings_manager;
    }
    
    /**
     * Get slot manager
     * 
     * @return SlotManager
     */
    public function get_slot_manager() {
        return $this->slot_manager;
    }
    
    /**
     * Get email template manager
     * 
     * @return EmailTemplateManager
     */
    public function get_email_template_manager() {
        return $this->email_template_manager;
    }
    
    /**
     * Get Elementor integration
     * 
     * @return ElementorIntegration|null
     */
    public function get_elementor_integration() {
        return $this->elementor_integration;
    }
    
    /**
     * Check if Elementor integration is available
     * 
     * @return bool
     */
    public function has_elementor_integration() {
        return $this->elementor_integration !== null;
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new \Exception('Cannot unserialize singleton');
    }
}