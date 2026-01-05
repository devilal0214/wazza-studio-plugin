<?php
/**
 * Simple Admin Menu Test
 * 
 * Quick test to verify admin menu functionality
 */

// Only run in WordPress
if (!defined('ABSPATH')) {
    require_once '../../../wp-config.php';
}

// Security check
if (!is_admin() && !defined('WP_CLI')) {
    wp_die('This script should only be run in WordPress admin context.');
}

echo "<h2>Quick Admin Menu Test</h2>\n";

// Check if we're an admin
if (!current_user_can('manage_options')) {
    echo "❌ You need administrator privileges to run this test.\n";
    exit;
}

// Force add capabilities
echo "<h3>Step 1: Adding Capabilities</h3>\n";
$admin_role = get_role('administrator');
if ($admin_role) {
    $admin_role->add_cap('manage_waza');
    echo "✓ Added manage_waza capability\n";
} else {
    echo "❌ Administrator role not found\n";
    exit;
}

// Force register menu
echo "\n<h3>Step 2: Force Register Admin Menu</h3>\n";
add_action('admin_menu', function() {
    add_menu_page(
        'Waza Booking',
        'Waza Test',
        'manage_waza',
        'waza-test',
        function() {
            echo '<div class="wrap">';
            echo '<h1>🎉 Waza Menu is Working!</h1>';
            echo '<p>The admin menu is now functional. You should see "Waza Test" in the admin menu.</p>';
            echo '<p><strong>Next steps:</strong></p>';
            echo '<ol>';
            echo '<li>Refresh your admin dashboard</li>';
            echo '<li>Look for "Waza Test" in the sidebar menu</li>';
            echo '<li>If it appears, the plugin should work correctly</li>';
            echo '</ol>';
            echo '</div>';
        },
        'dashicons-calendar-alt',
        30
    );
    
    add_submenu_page('waza-test', 'Dashboard', 'Dashboard', 'manage_waza', 'waza-test');
    add_submenu_page('waza-test', 'Email Templates', 'Email Templates', 'manage_waza', 'waza-test-emails', function() {
        echo '<div class="wrap"><h1>Email Templates</h1><p>This would be the email templates page.</p></div>';
    });
    
    echo "✓ Test menu registered successfully\n";
}, 5); // High priority

// Check current user
echo "\n<h3>Step 3: User Verification</h3>\n";
$user = wp_get_current_user();
echo "User: " . $user->user_login . "\n";
echo "Roles: " . implode(', ', $user->roles) . "\n";
echo "Has manage_waza: " . (current_user_can('manage_waza') ? '✅ Yes' : '❌ No') . "\n";
echo "Has manage_options: " . (current_user_can('manage_options') ? '✅ Yes' : '❌ No') . "\n";

// Check if plugin is loaded
echo "\n<h3>Step 4: Plugin Status</h3>\n";
if (class_exists('WazaBooking\Core\Plugin')) {
    echo "✅ Waza Booking plugin class exists\n";
    
    try {
        $plugin = \WazaBooking\Core\Plugin::get_instance();
        echo "✅ Plugin instance created\n";
        
        if (method_exists($plugin, 'get_admin_manager')) {
            echo "✅ get_admin_manager method exists\n";
            $admin_manager = $plugin->get_admin_manager();
            if ($admin_manager) {
                echo "✅ Admin manager is available\n";
            } else {
                echo "⚠ Admin manager is null\n";
            }
        } else {
            echo "⚠ get_admin_manager method missing\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Plugin error: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Waza Booking plugin not loaded\n";
}

echo "\n<h3>Instructions</h3>\n";
echo "1. After running this script, refresh your WordPress admin\n";
echo "2. Look for 'Waza Test' in the admin sidebar menu\n";
echo "3. If it appears, click on it to test functionality\n";
echo "4. If successful, the main Waza plugin should work\n";

echo "\n<h3>Troubleshooting</h3>\n";
echo "• If 'Waza Test' menu doesn't appear: Check user roles and capabilities\n";
echo "• If you see errors: Check WordPress error logs\n";
echo "• If plugin class not found: Check plugin activation\n";

// Add styling
if (isset($_SERVER['HTTP_HOST'])) {
    echo "<style>";
    echo "body { font-family: monospace; white-space: pre-wrap; line-height: 1.4; margin: 20px; }";
    echo "h2, h3 { color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-top: 20px; }";
    echo "✅ { color: #27ae60; } ❌ { color: #e74c3c; } ⚠ { color: #f39c12; }";
    echo "</style>";
}
?>