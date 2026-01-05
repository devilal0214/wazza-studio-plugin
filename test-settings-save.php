<?php
/**
 * Test Settings Save
 * Access this file to check if settings are being saved correctly
 */

// Load WordPress
require_once(__DIR__ . '/../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied. You must be an administrator.');
}

echo "<h2>Waza Booking Settings Test</h2>";

// Get current settings
$settings = get_option('waza_booking_settings', []);

echo "<h3>Current Settings:</h3>";
echo "<pre>";
print_r($settings);
echo "</pre>";

echo "<hr>";
echo "<h3>Test Save</h3>";

// Test saving some data
$test_data = [
    'business_name' => 'Test Business ' . time(),
    'contact_email' => 'test@example.com',
    'contact_phone' => '1234567890',
    'razorpay_enabled' => '1',
    'enable_csv_export' => '1'
];

echo "<p>Attempting to save test data...</p>";
echo "<pre>";
print_r($test_data);
echo "</pre>";

$result = update_option('waza_booking_settings', $test_data);

if ($result) {
    echo "<p style='color: green;'><strong>✅ Settings saved successfully!</strong></p>";
} else {
    echo "<p style='color: orange;'><strong>⚠️ Settings already exist with same values or update failed</strong></p>";
}

echo "<h3>Settings After Save:</h3>";
$settings_after = get_option('waza_booking_settings', []);
echo "<pre>";
print_r($settings_after);
echo "</pre>";

// Check if registration is correct
echo "<hr>";
echo "<h3>Settings Registration Check:</h3>";

global $wp_registered_settings;
if (isset($wp_registered_settings['waza_booking_settings'])) {
    echo "<p style='color: green;'>✅ Settings are registered</p>";
    echo "<pre>";
    print_r($wp_registered_settings['waza_booking_settings']);
    echo "</pre>";
} else {
    echo "<p style='color: red;'>❌ Settings are NOT registered!</p>";
}

echo "<hr>";
echo "<h3>Debug Log Location:</h3>";
if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
    echo "<p>Debug logging is enabled. Check: <code>wp-content/debug.log</code></p>";
} else {
    echo "<p style='color: orange;'>Debug logging is disabled. Enable it by adding to wp-config.php:</p>";
    echo "<pre>define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);</pre>";
}

echo "<hr>";
echo "<p><a href='" . admin_url('admin.php?page=waza-settings') . "'>← Back to Settings Page</a></p>";
