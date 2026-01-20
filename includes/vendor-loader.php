<?php
/**
 * Vendor Loader with Error Handling
 * 
 * @package WazaBooking
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load Composer autoloader with error handling
$vendor_autoload = dirname(__FILE__, 2) . '/vendor/autoload.php';

if (file_exists($vendor_autoload)) {
    try {
        // Suppress warnings from autoloader conflicts
        $old_error_level = error_reporting(E_ERROR | E_PARSE);
        require_once $vendor_autoload;
        error_reporting($old_error_level);
    } catch (\Throwable $e) {
        // Log error but don't break the plugin
        error_log('Waza Booking: Vendor autoload failed - ' . $e->getMessage());
        
        // Add admin notice
        add_action('admin_notices', function() use ($e) {
            if (current_user_can('manage_options')) {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p><strong>Waza Booking Plugin:</strong> Vendor autoloader error.</p>';
                echo '<p><code>' . esc_html($e->getMessage()) . '</code></p>';
                echo '<p><a href="' . plugins_url('regenerate-vendor.php?confirm=yes', dirname(__FILE__) . '/waza-booking.php') . '" class="button button-primary">Fix Autoloader</a></p>';
                echo '</div>';
            }
        });
    }
} else {
    // Vendor folder missing - show admin notice
    add_action('admin_notices', function() {
        if (current_user_can('manage_options')) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>Waza Booking Plugin:</strong> Vendor folder not found. Some features may not work.</p>';
            echo '<p>Please upload the vendor folder or contact support.</p>';
            echo '</div>';
        }
    });
}
