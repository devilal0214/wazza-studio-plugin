<?php
/**
 * Autoloader for Waza Booking Plugin
 * 
 * @package WazaBooking
 */

namespace WazaBooking;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Custom autoloader for plugin classes
 */
class Autoloader {
    
    /**
     * Register the autoloader
     */
    public static function register() {
        spl_autoload_register([self::class, 'autoload']);
    }
    
    /**
     * Autoload classes
     * 
     * @param string $class The fully-qualified class name.
     */
    public static function autoload($class) {
        // Check if the class belongs to our namespace
        if (strpos($class, 'WazaBooking\\') !== 0) {
            return;
        }
        
        // Remove the namespace prefix
        $relative_class = substr($class, strlen('WazaBooking\\'));
        
        // Convert namespace separators to directory separators
        $relative_path = str_replace('\\', DIRECTORY_SEPARATOR, $relative_class);
        
        // Build the full file path
        $file = WAZA_BOOKING_PLUGIN_DIR . 'src' . DIRECTORY_SEPARATOR . $relative_path . '.php';
        
        // Load the file if it exists
        if (file_exists($file)) {
            require_once $file;
        }
    }
}

// Register the autoloader
Autoloader::register();