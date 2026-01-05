<?php
/**
 * Plugin Name: Waza Booking
 * Plugin URI: https://waza.studio
 * Description: A modern, secure WordPress plugin for managing activities, bookings, payments, and instructor workflows with QR verification.
 * Version: 1.0.0
 * Author: Waza Studio
 * Author URI: https://waza.studio
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: waza-booking
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Network: false
 *
 * @package WazaBooking
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WAZA_BOOKING_VERSION', '1.0.0');
define('WAZA_BOOKING_FILE', __FILE__);
define('WAZA_BOOKING_PLUGIN_FILE', __FILE__);
define('WAZA_BOOKING_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WAZA_BOOKING_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WAZA_BOOKING_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Require Composer autoloader if it exists
if (file_exists(WAZA_BOOKING_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once WAZA_BOOKING_PLUGIN_DIR . 'vendor/autoload.php';
}

// Require custom autoloader for plugin classes
require_once WAZA_BOOKING_PLUGIN_DIR . 'includes/class-autoloader.php';

// Include sample data creation (for development/demo)
require_once WAZA_BOOKING_PLUGIN_DIR . 'includes/sample-data.php';

// Include quick setup functionality
require_once WAZA_BOOKING_PLUGIN_DIR . 'includes/quick-setup.php';

use WazaBooking\Core\Plugin;

/**
 * Initialize the plugin
 */
function waza_booking_init() {
    // Check minimum PHP version
    if (version_compare(PHP_VERSION, '8.0', '<')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('Waza Booking requires PHP 8.0 or higher. Your current version is ', 'waza-booking') . PHP_VERSION;
            echo '</p></div>';
        });
        return;
    }

    // Check WordPress version
    global $wp_version;
    if (version_compare($wp_version, '6.0', '<')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('Waza Booking requires WordPress 6.0 or higher.', 'waza-booking');
            echo '</p></div>';
        });
        return;
    }

    // Initialize the plugin
    Plugin::get_instance();
}

// Hook into WordPress initialization
add_action('plugins_loaded', 'waza_booking_init');

// Grant capabilities to admin on init (Fix for missing menu items)
add_action('init', function() {
    $role = get_role('administrator');
    if ($role) {
        $caps = [
            'manage_waza',
            'edit_waza_slots',
            'view_waza_bookings',
            'scan_waza_qr',
            'manage_waza_instructors',
            'export_waza_data'
        ];
        
        foreach ($caps as $cap) {
            if (!$role->has_cap($cap)) {
                $role->add_cap($cap);
            }
        }
    }
}, 999);

// Emergency admin menu registration (fallback)
add_action('admin_menu', 'waza_booking_emergency_menu', 99);

/**
 * Emergency admin menu registration
 * This ensures the menu appears even if the main plugin has issues
 */
function waza_booking_emergency_menu() {
    // Only register if user has capability and main menu doesn't exist
    global $admin_page_hooks;
    
    if (current_user_can('manage_waza') && !isset($admin_page_hooks['waza-booking'])) {
        add_menu_page(
            __('Waza Booking', 'waza-booking'),
            __('Waza', 'waza-booking'),
            'manage_waza',
            'waza-booking',
            'waza_booking_dashboard_fallback',
            'dashicons-calendar-alt',
            30
        );
        
        add_submenu_page('waza-booking', __('Dashboard', 'waza-booking'), __('Dashboard', 'waza-booking'), 'manage_options', 'waza-booking', 'waza_booking_dashboard_fallback');
        add_submenu_page('waza-booking', __('Email Templates', 'waza-booking'), __('Email Templates', 'waza-booking'), 'manage_options', 'waza-email-templates', 'waza_booking_email_templates_fallback');
        add_submenu_page('waza-booking', __('Customization', 'waza-booking'), __('Customization', 'waza-booking'), 'manage_options', 'waza-customization', 'waza_booking_customization_fallback');
        add_submenu_page('waza-booking', __('Settings', 'waza-booking'), __('Settings', 'waza-booking'), 'manage_options', 'waza-settings', 'waza_booking_settings_fallback');
        
        error_log('Waza Booking: Emergency admin menu registered');
    }
}

/**
 * Fallback dashboard page
 */
function waza_booking_dashboard_fallback() {
    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Waza Booking Dashboard', 'waza-booking') . '</h1>';
    echo '<p>' . esc_html__('Welcome to Waza Booking management dashboard.', 'waza-booking') . '</p>';
    
    // Try to get the real plugin instance
    if (class_exists('WazaBooking\Core\Plugin')) {
        try {
            $plugin = WazaBooking\Core\Plugin::get_instance();
            if ($plugin && method_exists($plugin, 'get_admin_manager')) {
                $admin_manager = $plugin->get_admin_manager();
                if ($admin_manager && method_exists($admin_manager, 'dashboard_page')) {
                    echo '<div style="border: 1px solid #ddd; padding: 15px; margin-top: 15px;">';
                    $admin_manager->dashboard_page();
                    echo '</div>';
                }
            }
        } catch (Exception $e) {
            echo '<div class="notice notice-error"><p>Plugin initialization error: ' . esc_html($e->getMessage()) . '</p></div>';
        }
    }
    
    echo '</div>';
}

/**
 * Fallback email templates page
 */
function waza_booking_email_templates_fallback() {
    if (class_exists('WazaBooking\Core\Plugin')) {
        try {
            $plugin = WazaBooking\Core\Plugin::get_instance();
            if (method_exists($plugin, 'get_email_template_manager')) {
                $email_manager = $plugin->get_email_template_manager();
                if ($email_manager && method_exists($email_manager, 'admin_page')) {
                    $email_manager->admin_page();
                    return;
                }
            }
        } catch (Exception $e) {
            error_log('Waza Booking: Email templates page error - ' . $e->getMessage());
            // Fall through to default page
        } catch (Error $e) {
            error_log('Waza Booking: Email templates page fatal error - ' . $e->getMessage());
            // Fall through to default page
        }
    }
    
    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Email Templates', 'waza-booking') . '</h1>';
    echo '<p>' . esc_html__('Email template management will be available here.', 'waza-booking') . '</p>';
    echo '<div class="notice notice-info"><p>' . esc_html__('Full email template features will be available once the plugin is fully loaded.', 'waza-booking') . '</p></div>';
    echo '</div>';
}

/**
 * Fallback customization page
 */
function waza_booking_customization_fallback() {
    if (class_exists('WazaBooking\Core\Plugin')) {
        try {
            $plugin = WazaBooking\Core\Plugin::get_instance();
            if (method_exists($plugin, 'get_customization_manager')) {
                $customization_manager = $plugin->get_customization_manager();
                if ($customization_manager) {
                    if (method_exists($customization_manager, 'admin_page')) {
                        $customization_manager->admin_page();
                        return;
                    } elseif (method_exists($customization_manager, 'render_customization_page')) {
                        $customization_manager->render_customization_page();
                        return;
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Waza Booking: Customization page error - ' . $e->getMessage());
            // Fall through to default page
        } catch (Error $e) {
            error_log('Waza Booking: Customization page fatal error - ' . $e->getMessage());
            // Fall through to default page
        }
    }
    
    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Customization', 'waza-booking') . '</h1>';
    echo '<p>' . esc_html__('Theme and styling customization will be available here.', 'waza-booking') . '</p>';
    echo '<div class="notice notice-info"><p>' . esc_html__('Full customization features will be available once the plugin is fully loaded.', 'waza-booking') . '</p></div>';
    echo '</div>';
}

/**
 * Fallback settings page
 */
function waza_booking_settings_fallback() {
    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Waza Booking Settings', 'waza-booking') . '</h1>';
    echo '<p>' . esc_html__('Configure payment gateways, notifications, and other settings.', 'waza-booking') . '</p>';
    echo '</div>';
}

/**
 * Plugin activation hook callback
 */
function waza_booking_activate() {
    // Check requirements during activation
    if (version_compare(PHP_VERSION, '8.0', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            esc_html__('Waza Booking requires PHP 8.0 or higher.', 'waza-booking'),
            esc_html__('Plugin Activation Error', 'waza-booking'),
            array('back_link' => true)
        );
    }

    global $wp_version;
    if (version_compare($wp_version, '6.0', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            esc_html__('Waza Booking requires WordPress 6.0 or higher.', 'waza-booking'),
            esc_html__('Plugin Activation Error', 'waza-booking'),
            array('back_link' => true)
        );
    }

    // Load autoloader if not already loaded
    if (!class_exists('WazaBooking\Core\Installer')) {
        require_once WAZA_BOOKING_PLUGIN_DIR . 'includes/class-autoloader.php';
    }

    // Run installation tasks
    if (class_exists('WazaBooking\Core\Installer')) {
        WazaBooking\Core\Installer::activate();
    }
}

/**
 * Plugin deactivation hook callback
 */
function waza_booking_deactivate() {
    // Load autoloader if not already loaded
    if (!class_exists('WazaBooking\Core\Installer')) {
        require_once WAZA_BOOKING_PLUGIN_DIR . 'includes/class-autoloader.php';
    }

    if (class_exists('WazaBooking\Core\Installer')) {
        WazaBooking\Core\Installer::deactivate();
    }
}

/**
 * Plugin uninstall hook callback
 */
function waza_booking_uninstall() {
    // Define plugin constants for uninstall
    if (!defined('WAZA_BOOKING_PLUGIN_DIR')) {
        define('WAZA_BOOKING_PLUGIN_DIR', plugin_dir_path(__FILE__));
    }

    // Load autoloader if not already loaded
    if (!class_exists('WazaBooking\Core\Installer')) {
        require_once WAZA_BOOKING_PLUGIN_DIR . 'includes/class-autoloader.php';
    }

    if (class_exists('WazaBooking\Core\Installer')) {
        WazaBooking\Core\Installer::uninstall();
    }
}

/**
 * Register plugin hooks
 */
register_activation_hook(__FILE__, 'waza_booking_activate');
register_deactivation_hook(__FILE__, 'waza_booking_deactivate');
register_uninstall_hook(__FILE__, 'waza_booking_uninstall');