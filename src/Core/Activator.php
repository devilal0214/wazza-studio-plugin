<?php
/**
 * Plugin Activator
 * 
 * @package WazaBooking\Core
 */

namespace WazaBooking\Core;

use WazaBooking\Database\DatabaseManager;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin Activator Class
 */
class Activator {
    
    /**
     * Activate the plugin
     */
    public static function activate() {
        // Create custom database tables
        $database_manager = new DatabaseManager();
        $database_manager->create_tables();
        
        // Create default capabilities
        self::add_capabilities();
        
        // Create default pages if needed
        self::create_default_pages();
        
        // Schedule default cron jobs
        self::schedule_cron_jobs();
        
        // Flush rewrite rules to ensure custom post types work
        flush_rewrite_rules();
        
        // Set activation flag
        update_option('waza_booking_activated', true);
        update_option('waza_booking_version', WAZA_BOOKING_VERSION);
    }
    
    /**
     * Add plugin capabilities to admin role
     */
    private static function add_capabilities() {
        $admin_role = get_role('administrator');
        
        if ($admin_role) {
            $capabilities = [
                'manage_waza',
                'edit_waza_slots',
                'view_waza_bookings',
                'scan_waza_qr',
                'manage_waza_instructors',
                'export_waza_data'
            ];
            
            foreach ($capabilities as $cap) {
                $admin_role->add_cap($cap);
            }
        }
    }
    
    /**
     * Create default pages for the plugin
     */
    private static function create_default_pages() {
        // Check if booking page exists
        $booking_page_id = get_option('waza_booking_page_id');
        
        if (!$booking_page_id || !get_post($booking_page_id)) {
            $page_id = wp_insert_post([
                'post_title'   => __('Book Activity', 'waza-booking'),
                'post_content' => '[waza_calendar]',
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_name'    => 'book-activity'
            ]);
            
            if (!is_wp_error($page_id)) {
                update_option('waza_booking_page_id', $page_id);
            }
        }
    }
    
    /**
     * Schedule cron jobs
     */
    private static function schedule_cron_jobs() {
        // Schedule reminder notifications
        if (!wp_next_scheduled('waza_send_reminders')) {
            wp_schedule_event(time(), 'hourly', 'waza_send_reminders');
        }
        
        // Schedule cleanup old QR tokens
        if (!wp_next_scheduled('waza_cleanup_qr_tokens')) {
            wp_schedule_event(time(), 'daily', 'waza_cleanup_qr_tokens');
        }
    }
}