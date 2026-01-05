<?php
/**
 * Plugin Deactivator
 * 
 * @package WazaBooking\Core
 */

namespace WazaBooking\Core;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin Deactivator Class
 */
class Deactivator {
    
    /**
     * Deactivate the plugin
     */
    public static function deactivate() {
        // Clear scheduled cron jobs
        wp_clear_scheduled_hook('waza_send_reminders');
        wp_clear_scheduled_hook('waza_cleanup_qr_tokens');
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set deactivation flag
        update_option('waza_booking_deactivated', true);
    }
}