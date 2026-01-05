<?php
/**
 * Debug script to test plugin activation
 * 
 * Run this to identify any fatal errors during plugin loading
 */

// Include WordPress
require_once '../../../wp-config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Waza Booking Plugin Debug Test</h2>\n";

// Define constants as they would be in the plugin
define('WAZA_BOOKING_VERSION', '1.0.0');
define('WAZA_BOOKING_PLUGIN_DIR', __DIR__ . '/');
define('WAZA_BOOKING_PLUGIN_URL', plugins_url('', __FILE__) . '/');
define('WAZA_BOOKING_PLUGIN_BASENAME', plugin_basename(__FILE__));

echo "✓ Constants defined\n";

try {
    // Test autoloader
    require_once __DIR__ . '/includes/class-autoloader.php';
    echo "✓ Autoloader loaded\n";
    
    echo "✓ Plugin class namespace loaded\n";
    
    // Try to get plugin instance
    $plugin = \WazaBooking\Core\Plugin::get_instance();
    echo "✓ Plugin instance created successfully\n";
    
    // Test individual managers
    $database_manager = $plugin->get_database_manager();
    echo "✓ Database manager accessible\n";
    
    $admin_manager = $plugin->get_admin_manager();
    echo "✓ Admin manager accessible\n";
    
    $email_manager = $plugin->get_email_template_manager();
    echo "✓ Email template manager accessible\n";
    
    // Test database table creation
    $database_manager->create_tables();
    echo "✓ Database tables created successfully\n";
    
    echo "\n<strong>SUCCESS: Plugin loaded without fatal errors!</strong>\n";
    
} catch (Throwable $e) {
    echo "\n<strong>ERROR FOUND:</strong>\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack Trace:\n" . $e->getTraceAsString() . "\n";
}

// If running in browser, add styling
if (isset($_SERVER['HTTP_HOST'])) {
    echo "<style>";
    echo "body { font-family: monospace; white-space: pre-wrap; line-height: 1.4; }";
    echo "strong { color: #c62d2d; }";
    echo "</style>";
}
?>