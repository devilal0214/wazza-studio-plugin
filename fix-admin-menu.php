<?php
/**
 * Fix Waza Admin Menu - Manually add capabilities
 * 
 * Run this script to manually assign required capabilities for Waza menu
 */

// Only run in WordPress
if (!defined('ABSPATH')) {
    require_once '../../../wp-config.php';
}

// Security check - only allow administrators
if (!current_user_can('manage_options')) {
    wp_die('You need administrator privileges to run this script.');
}

echo "<h2>Fixing Waza Admin Menu Capabilities</h2>\n";

$success_count = 0;
$error_count = 0;

// Capabilities to add
$capabilities = [
    'manage_waza' => 'Main Waza management capability',
    'edit_waza_slots' => 'Edit activity slots',
    'view_waza_bookings' => 'View bookings',
    'manage_waza_instructors' => 'Manage instructors',
    'scan_waza_qr' => 'Scan QR codes for attendance',
    'export_waza_data' => 'Export data'
];

// Add capabilities to administrator role
echo "<h3>Adding Capabilities to Administrator Role</h3>\n";
$admin_role = get_role('administrator');

if ($admin_role) {
    foreach ($capabilities as $cap => $description) {
        try {
            $admin_role->add_cap($cap);
            echo "✓ Added '{$cap}' - {$description}\n";
            $success_count++;
        } catch (Exception $e) {
            echo "✗ Failed to add '{$cap}' - " . $e->getMessage() . "\n";
            $error_count++;
        }
    }
} else {
    echo "✗ Administrator role not found!\n";
    $error_count++;
}

// Add some capabilities to editor role
echo "\n<h3>Adding Limited Capabilities to Editor Role</h3>\n";
$editor_role = get_role('editor');

if ($editor_role) {
    $editor_caps = [
        'edit_waza_slots' => 'Edit activity slots',
        'view_waza_bookings' => 'View bookings',
        'scan_waza_qr' => 'Scan QR codes for attendance'
    ];
    
    foreach ($editor_caps as $cap => $description) {
        try {
            $editor_role->add_cap($cap);
            echo "✓ Added '{$cap}' to editor - {$description}\n";
            $success_count++;
        } catch (Exception $e) {
            echo "✗ Failed to add '{$cap}' to editor - " . $e->getMessage() . "\n";
            $error_count++;
        }
    }
} else {
    echo "✗ Editor role not found!\n";
}

// Verify capabilities were added
echo "\n<h3>Verification</h3>\n";
$current_user = wp_get_current_user();

foreach ($capabilities as $cap => $description) {
    if (current_user_can($cap)) {
        echo "✓ Current user has '{$cap}'\n";
    } else {
        echo "✗ Current user missing '{$cap}'\n";
    }
}

// Try to trigger plugin initialization to ensure menu is registered
echo "\n<h3>Plugin Initialization</h3>\n";

if (class_exists('WazaBooking\Core\Plugin')) {
    try {
        $plugin = \WazaBooking\Core\Plugin::get_instance();
        
        // Force admin initialization
        if (method_exists($plugin, 'init_admin')) {
            $plugin->init_admin();
            echo "✓ Admin initialization triggered\n";
        }
        
        // Get admin manager and force menu registration
        if (method_exists($plugin, 'get_admin_manager')) {
            $admin_manager = $plugin->get_admin_manager();
            if ($admin_manager && method_exists($admin_manager, 'add_admin_menu')) {
                $admin_manager->add_admin_menu();
                echo "✓ Admin menu registration triggered\n";
            } else {
                echo "⚠ Admin manager not available or method missing\n";
            }
        } else {
            echo "⚠ get_admin_manager method not found - trying alternative approach\n";
            
            // Alternative: Force menu registration directly
            if (current_user_can('manage_waza')) {
                add_menu_page(
                    __('Waza Booking', 'waza-booking'),
                    __('Waza', 'waza-booking'),
                    'manage_waza',
                    'waza-booking',
                    function() {
                        echo '<div class="wrap"><h1>Waza Booking Dashboard</h1><p>Welcome to Waza Booking management dashboard.</p></div>';
                    },
                    'dashicons-calendar-alt',
                    30
                );
                echo "✓ Alternative menu registration completed\n";
            }
        }
        
    } catch (Exception $e) {
        echo "✗ Error during plugin initialization: " . $e->getMessage() . "\n";
        $error_count++;
    }
} else {
    echo "✗ Waza Booking plugin not loaded\n";
    $error_count++;
}

// Clear any caches
echo "\n<h3>Cache Clearing</h3>\n";
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
    echo "✓ WordPress cache flushed\n";
}

if (function_exists('wp_cache_delete')) {
    wp_cache_delete('alloptions', 'options');
    echo "✓ Options cache cleared\n";
}

// Summary
echo "\n<h3>Summary</h3>\n";
echo "✅ Successful operations: {$success_count}\n";
if ($error_count > 0) {
    echo "❌ Errors encountered: {$error_count}\n";
}

echo "\n<h3>Next Steps</h3>\n";
echo "1. Refresh your WordPress admin page\n";
echo "2. Look for 'Waza' in the admin menu\n";
echo "3. If still not visible, check user roles and permissions\n";
echo "4. Consider deactivating and reactivating the plugin\n";

// Add some styling if running in browser
if (isset($_SERVER['HTTP_HOST'])) {
    echo "<style>";
    echo "body { font-family: monospace; white-space: pre-wrap; line-height: 1.4; margin: 20px; }";
    echo "h2, h3 { color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 5px; }";
    echo "h3 { margin-top: 25px; }";
    echo "✓ { color: #27ae60; }";
    echo "✗ { color: #e74c3c; }";
    echo "</style>";
}
?>