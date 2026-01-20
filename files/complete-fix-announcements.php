<?php
/**
 * Force Announcements Display - Complete Fix
 */

require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

echo "<!DOCTYPE html><html><head><title>Announcements Complete Fix</title></head><body style='font-family: Arial; padding: 20px;'>";
echo "<h1>🔧 Announcements Complete Fix & Test</h1>";

// Step 1: Check Database
echo "<h2>Step 1: Database Check</h2>";
global $wpdb;
$all_announcements = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}waza_announcements ORDER BY created_at DESC");
echo "<p><strong>Total announcements in database:</strong> " . count($all_announcements) . "</p>";

if (empty($all_announcements)) {
    echo "<p style='color: red;'>❌ NO ANNOUNCEMENTS FOUND! Please create some in the admin panel first.</p>";
    echo "</body></html>";
    exit;
}

// Step 2: Show Current Data
echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin: 20px 0;'>";
echo "<tr style='background: #333; color: white;'>";
echo "<th>ID</th><th>Title</th><th>Active</th><th>Type</th><th>Target</th><th>Starts At</th><th>Expires At</th>";
echo "</tr>";

$current_time = current_time('mysql');
echo "<p><strong>Current Server Time:</strong> $current_time</p>";

foreach ($all_announcements as $ann) {
    $color = $ann->is_active ? '#d1fae5' : '#fee2e2';
    echo "<tr style='background: $color;'>";
    echo "<td>{$ann->id}</td>";
    echo "<td><strong>{$ann->title}</strong></td>";
    echo "<td>" . ($ann->is_active ? '✓ YES' : '✗ NO') . "</td>";
    echo "<td>{$ann->announcement_type}</td>";
    echo "<td>{$ann->target_audience}</td>";
    echo "<td>" . ($ann->starts_at ?: 'NULL') . "</td>";
    echo "<td>" . ($ann->expires_at ?: 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Step 3: Test Query
echo "<h2>Step 2: Test Active Announcements Query</h2>";
$where = ["is_active = 1"];
$where[] = "(starts_at IS NULL OR starts_at = '' OR starts_at = '0000-00-00 00:00:00' OR starts_at <= '$current_time')";
$where[] = "(expires_at IS NULL OR expires_at = '' OR expires_at = '0000-00-00 00:00:00' OR expires_at >= '$current_time')";

$active_announcements = $wpdb->get_results("
    SELECT * FROM {$wpdb->prefix}waza_announcements
    WHERE " . implode(' AND ', $where) . "
    ORDER BY priority DESC, created_at DESC
");

echo "<p><strong>Active announcements that should display:</strong> " . count($active_announcements) . "</p>";

if (empty($active_announcements)) {
    echo "<p style='color: red;'>❌ NO ACTIVE ANNOUNCEMENTS MATCH THE QUERY!</p>";
    echo "<h3>Debugging each announcement:</h3>";
    foreach ($all_announcements as $ann) {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
        echo "<strong>#{$ann->id}: {$ann->title}</strong><br>";
        echo "is_active = {$ann->is_active}: " . ($ann->is_active == 1 ? '✓' : '✗') . "<br>";
        
        $starts_ok = ($ann->starts_at === null || $ann->starts_at === '' || $ann->starts_at === '0000-00-00 00:00:00' || $ann->starts_at <= $current_time);
        echo "starts_at check: " . ($starts_ok ? '✓' : '✗') . " (value: " . ($ann->starts_at ?: 'NULL') . ")<br>";
        
        $expires_ok = ($ann->expires_at === null || $ann->expires_at === '' || $ann->expires_at === '0000-00-00 00:00:00' || $ann->expires_at >= $current_time);
        echo "expires_at check: " . ($expires_ok ? '✓' : '✗') . " (value: " . ($ann->expires_at ?: 'NULL') . ")<br>";
        
        if (!$starts_ok) {
            echo "<span style='color: red;'>❌ PROBLEM: starts_at '{$ann->starts_at}' is in the future!</span><br>";
        }
        if (!$expires_ok) {
            echo "<span style='color: red;'>❌ PROBLEM: expires_at '{$ann->expires_at}' is in the past!</span><br>";
        }
        
        echo "</div>";
    }
} else {
    echo "<ul style='color: green;'>";
    foreach ($active_announcements as $ann) {
        echo "<li>✓ {$ann->title}</li>";
    }
    echo "</ul>";
}

// Step 4: Test Shortcode
echo "<h2>Step 3: Test Shortcode Registration</h2>";
global $shortcode_tags;
if (isset($shortcode_tags['waza_announcements'])) {
    echo "<p style='color: green;'>✓ Shortcode '[waza_announcements]' IS registered</p>";
} else {
    echo "<p style='color: red;'>❌ Shortcode '[waza_announcements]' is NOT registered!</p>";
    echo "<p>Attempting to register manually...</p>";
    
    if (class_exists('WazaBooking\Admin\AnnouncementsManager')) {
        $manager = new \WazaBooking\Admin\AnnouncementsManager();
        $manager->init();
        echo "<p style='color: orange;'>⚠ Manually initialized AnnouncementsManager</p>";
    }
}

// Step 5: Display Shortcode Output
echo "<h2>Step 4: Shortcode Output</h2>";
echo "<div style='border: 3px solid blue; padding: 20px; background: #f5f5f5;'>";
$output = do_shortcode('[waza_announcements]');
if (empty(trim(strip_tags($output)))) {
    echo "<p style='color: red;'>❌ Shortcode returned EMPTY output!</p>";
} else {
    echo $output;
}
echo "</div>";

// Step 6: Clear Caches
echo "<h2>Step 5: Clear Caches</h2>";

// Clear WordPress object cache
wp_cache_flush();
echo "<p>✓ WordPress object cache cleared</p>";

// Clear any transients
delete_transient('waza_announcements_cache');
echo "<p>✓ Transients cleared</p>";

// Step 7: Instructions
echo "<h2>📋 Next Steps:</h2>";
echo "<ol style='font-size: 16px; line-height: 1.8;'>";
echo "<li>If you see announcements above in the blue box → shortcode works! ✓</li>";
echo "<li>Clear your browser cache (Ctrl+Shift+Delete)</li>";
echo "<li>Log out and log back in</li>";
echo "<li>Visit your student/instructor dashboard again</li>";
echo "<li>Hard refresh the page (Ctrl+F5)</li>";
echo "</ol>";

echo "<h2>🔗 Quick Links:</h2>";
echo "<ul>";
echo "<li><a href='" . admin_url('admin.php?page=waza-announcements') . "' target='_blank'>Manage Announcements</a></li>";
echo "<li><a href='" . home_url('/my-account/') . "' target='_blank'>Student Dashboard</a></li>";
echo "</ul>";

echo "</body></html>";
