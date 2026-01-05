<?php
/**
 * Elementor Integration
 *
 * Provides Elementor widgets and page builder integration.
 *
 * @package WazaBooking
 */

namespace WazaBooking\Integrations;

if (!defined('ABSPATH')) {
    exit;
}

class ElementorIntegration {
    
    /**
     * Initialize Elementor integration
     */
    public function __construct() {
        // Only initialize if Elementor is available
        if (!class_exists('\Elementor\Plugin')) {
            return;
        }
        
        add_action('elementor/widgets/widgets_registered', [$this, 'register_widgets']);
        add_action('elementor/elements/categories_registered', [$this, 'add_elementor_widget_categories']);
        add_action('elementor/controls/controls_registered', [$this, 'register_controls']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_elementor_assets']);
    }
    
    /**
     * Check if Elementor is active
     */
    public static function is_elementor_active() {
        return did_action('elementor/loaded');
    }
    
    /**
     * Add widget categories
     */
    public function add_elementor_widget_categories($elements_manager) {
        $elements_manager->add_category(
            'waza-booking',
            [
                'title' => __('Waza Booking', 'waza-booking'),
                'icon' => 'fa fa-calendar',
            ]
        );
    }
    
    /**
     * Register Elementor widgets
     */
    public function register_widgets() {
        if (!self::is_elementor_active()) {
            return;
        }
        
        // Include widget files
        require_once WAZA_BOOKING_PLUGIN_DIR . 'src/Integrations/Elementor/ActivitiesGridWidget.php';
        require_once WAZA_BOOKING_PLUGIN_DIR . 'src/Integrations/Elementor/BookingCalendarWidget.php';
        require_once WAZA_BOOKING_PLUGIN_DIR . 'src/Integrations/Elementor/BookingFormWidget.php';
        require_once WAZA_BOOKING_PLUGIN_DIR . 'src/Integrations/Elementor/InstructorsListWidget.php';
        require_once WAZA_BOOKING_PLUGIN_DIR . 'src/Integrations/Elementor/UserDashboardWidget.php';
        
        // Register widgets
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new Elementor\ActivitiesGridWidget());
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new Elementor\BookingCalendarWidget());
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new Elementor\BookingFormWidget());
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new Elementor\InstructorsListWidget());
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new Elementor\UserDashboardWidget());
    }
    
    /**
     * Register custom controls
     */
    public function register_controls() {
        if (!self::is_elementor_active()) {
            return;
        }
        
        // Custom control for activity selection
        require_once WAZA_BOOKING_PLUGIN_DIR . 'src/Integrations/Elementor/Controls/ActivitySelectControl.php';
        
        $controls_manager = \Elementor\Plugin::$instance->controls_manager;
        $controls_manager->register_control('waza_activity_select', new Elementor\Controls\ActivitySelectControl());
    }
    
    /**
     * Enqueue Elementor-specific assets
     */
    public function enqueue_elementor_assets() {
        // Safety check - ensure Elementor is available
        if (!class_exists('\Elementor\Plugin')) {
            return;
        }
        
        // Check if this is an Elementor page using a more compatible method
        if (!$this->is_elementor_page()) {
            return;
        }
        
        wp_enqueue_style(
            'waza-elementor-widgets',
            WAZA_BOOKING_PLUGIN_URL . 'assets/elementor-widgets.css',
            ['waza-frontend'],
            WAZA_BOOKING_VERSION
        );
        
        wp_enqueue_script(
            'waza-elementor-widgets',
            WAZA_BOOKING_PLUGIN_URL . 'assets/elementor-widgets.js',
            ['jquery', 'waza-frontend'],
            WAZA_BOOKING_VERSION,
            true
        );
    }
    
    /**
     * Check if current page is built with Elementor (compatible method)
     * 
     * @return bool
     */
    private function is_elementor_page() {
        // Method 1: Check if Elementor frontend is available and has the method
        if (isset(\Elementor\Plugin::$instance->frontend) && 
            method_exists(\Elementor\Plugin::$instance->frontend, 'is_elementor_page')) {
            return \Elementor\Plugin::$instance->frontend->is_elementor_page();
        }
        
        // Method 2: Check post meta for Elementor data
        global $post;
        if ($post && get_post_meta($post->ID, '_elementor_edit_mode', true)) {
            return true;
        }
        
        // Method 3: Check if we're in Elementor editor
        if (isset($_GET['elementor-preview'])) {
            return true;
        }
        
        // Method 4: Check for Elementor body class
        if (function_exists('is_singular') && is_singular()) {
            $post_id = get_the_ID();
            if ($post_id && get_post_meta($post_id, '_elementor_data', true)) {
                return true;
            }
        }
        
        // Default: Not an Elementor page
        return false;
    }
}