<?php
/**
 * Debug Admin Menu - Check if Waza menu should appear
 * 
 * Run this in WordPress admin to diagnose menu visibility issues
 */

// Only run in WordPress admin
if (!defined('ABSPATH')) {
    require_once '../../../wp-config.php';
}

if (!is_admin() && !defined('WP_CLI')) {
    wp_die('This script should only be run in WordPress admin context.');
}

echo "<h2>Waza Admin Menu Debug</h2>\n";

// Check current user capabilities
$current_user = wp_get_current_user();
echo "<h3>Current User Info</h3>\n";
echo "User ID: " . $current_user->ID . "\n";
echo "Username: " . $current_user->user_login . "\n";
echo "Email: " . $current_user->user_email . "\n";
echo "Roles: " . implode(', ', $current_user->roles) . "\n";

echo "\n<h3>Required Capabilities Check</h3>\n";
$required_caps = [
    'manage_waza',
    'edit_waza_slots', 
    'view_waza_bookings',
    'manage_waza_instructors',
    'scan_waza_qr',
    'export_waza_data'
];

foreach ($required_caps as $cap) {
    $has_cap = current_user_can($cap) ? '✓' : '✗';
    echo "{$has_cap} {$cap}\n";
}

echo "\n<h3>WordPress Admin Capabilities</h3>\n";
$wp_caps = [
    'manage_options',
    'edit_posts',
    'edit_pages',
    'edit_users',
    'install_plugins'
];

foreach ($wp_caps as $cap) {
    $has_cap = current_user_can($cap) ? '✓' : '✗';
    echo "{$has_cap} {$cap}\n";
}

echo "\n<h3>Plugin Status</h3>\n";

// Check if plugin is loaded
if (class_exists('WazaBooking\Core\Plugin')) {
    echo "✓ Waza Booking plugin class loaded\n";
    
    try {
        $plugin = \WazaBooking\Core\Plugin::get_instance();
        echo "✓ Plugin instance accessible\n";
        
        $admin_manager = $plugin->get_admin_manager();
        if ($admin_manager) {
            echo "✓ Admin manager accessible\n";
        } else {
            echo "✗ Admin manager not accessible\n";
        }
        
    } catch (Exception $e) {
        echo "✗ Error accessing plugin: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ Waza Booking plugin class not loaded\n";
}

// Check if admin menu hook is registered
echo "\n<h3>Admin Menu Hooks</h3>\n";

// List all admin_menu actions to see if ours is there
global $wp_filter;
if (isset($wp_filter['admin_menu'])) {
    $admin_menu_hooks = $wp_filter['admin_menu']->callbacks;
    $found_waza = false;
    
    foreach ($admin_menu_hooks as $priority => $callbacks) {
        foreach ($callbacks as $callback) {
            if (is_array($callback['function'])) {
                $class = is_object($callback['function'][0]) ? get_class($callback['function'][0]) : $callback['function'][0];
                if (strpos($class, 'AdminManager') !== false) {
                    echo "✓ Found Waza AdminManager hook at priority {$priority}\n";
                    $found_waza = true;
                }
            }
        }
    }
    
    if (!$found_waza) {
        echo "✗ No Waza AdminManager admin_menu hook found\n";
    }
} else {
    echo "✗ No admin_menu hooks registered\n";
}

echo "\n<h3>Recommendations</h3>\n";

if (!current_user_can('manage_waza')) {
    echo "❌ Main issue: Current user lacks 'manage_waza' capability\n";
    echo "🔧 Solution: Deactivate and reactivate the plugin to add capabilities\n";
    echo "🔧 Or run: wp user add-cap " . $current_user->user_login . " manage_waza\n";
}

if (!current_user_can('manage_options')) {
    echo "❌ User is not an administrator - Waza menu requires admin privileges\n";
    echo "🔧 Solution: Login as administrator or add manage_options capability\n";
}

echo "\n<h3>Quick Fix Commands</h3>\n";
if (defined('WP_CLI') && WP_CLI) {
    echo "Run these WP-CLI commands to fix capabilities:\n";
    echo "wp user add-cap admin manage_waza\n";
    echo "wp user add-cap admin edit_waza_slots\n"; 
    echo "wp user add-cap admin view_waza_bookings\n";
} else {
    echo "You can also add capabilities programmatically:\n";
    echo "\$admin = get_role('administrator');\n";
    echo "\$admin->add_cap('manage_waza');\n";
}

// Add some styling if running in browser
if (isset($_SERVER['HTTP_HOST'])) {
    echo "<style>";
    echo "body { font-family: monospace; white-space: pre-wrap; line-height: 1.4; margin: 20px; }";
    echo "h2, h3 { color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 5px; }";
    echo "h3 { margin-top: 25px; }";
    echo "</style>";
}
?>