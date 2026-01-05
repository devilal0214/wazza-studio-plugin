<?php
/**
 * Plugin Uninstaller
 * 
 * @package WazaBooking\Core
 */

namespace WazaBooking\Core;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin Uninstaller Class
 */
class Uninstaller {
    
    /**
     * Uninstall the plugin (only when user chooses to delete plugin data)
     */
    public static function uninstall() {
        // Check if user wants to keep data
        $keep_data = get_option('waza_booking_keep_data_on_uninstall', false);
        
        if (!$keep_data) {
            // Remove custom database tables
            self::remove_custom_tables();
            
            // Remove custom post types and their data
            self::remove_post_types_data();
            
            // Remove plugin options
            self::remove_options();
            
            // Remove capabilities
            self::remove_capabilities();
        }
        
        // Always clear cron jobs
        wp_clear_scheduled_hook('waza_send_reminders');
        wp_clear_scheduled_hook('waza_cleanup_qr_tokens');
    }
    
    /**
     * Remove custom database tables
     */
    private static function remove_custom_tables() {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'waza_bookings',
            $wpdb->prefix . 'waza_qr_tokens',
            $wpdb->prefix . 'waza_attendance',
            $wpdb->prefix . 'waza_payments'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
    }
    
    /**
     * Remove custom post types data
     */
    private static function remove_post_types_data() {
        $post_types = ['waza_activity', 'waza_slot', 'waza_booking'];
        
        foreach ($post_types as $post_type) {
            $posts = get_posts([
                'post_type'   => $post_type,
                'numberposts' => -1,
                'post_status' => 'any'
            ]);
            
            foreach ($posts as $post) {
                wp_delete_post($post->ID, true);
            }
        }
    }
    
    /**
     * Remove plugin options
     */
    private static function remove_options() {
        $options = [
            'waza_booking_activated',
            'waza_booking_deactivated',
            'waza_booking_version',
            'waza_booking_page_id',
            'waza_booking_settings',
            'waza_razorpay_settings',
            'waza_stripe_settings'
        ];
        
        foreach ($options as $option) {
            delete_option($option);
        }
    }
    
    /**
     * Remove plugin capabilities
     */
    private static function remove_capabilities() {
        $capabilities = [
            'manage_waza',
            'edit_waza_slots',
            'view_waza_bookings',
            'scan_waza_qr',
            'manage_waza_instructors',
            'export_waza_data'
        ];
        
        $roles = ['administrator', 'editor'];
        
        foreach ($roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                foreach ($capabilities as $cap) {
                    $role->remove_cap($cap);
                }
            }
        }
    }
}