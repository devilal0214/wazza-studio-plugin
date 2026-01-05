<?php
/**
 * Test Settings Registration
 * Run this to check if settings are properly registered
 */

// Load WordPress
require_once(__DIR__ . '/../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied.');
}

echo "<h2>Settings Registration Test</h2>";

// Force trigger admin_init
echo "<h3>Triggering admin_init...</h3>";
do_action('admin_init');

echo "<h3>Checking registered settings...</h3>";

global $wp_registered_settings, $new_allowed_options;

echo "<h4>Option Groups (new_allowed_options):</h4>";
echo "<pre>";
print_r($new_allowed_options);
echo "</pre>";

echo "<h4>Registered Settings:</h4>";
if (isset($wp_registered_settings['waza_booking_settings'])) {
    echo "<p style='color: green;'><strong>✅ waza_booking_settings IS registered!</strong></p>";
    echo "<pre>";
    print_r($wp_registered_settings['waza_booking_settings']);
    echo "</pre>";
} else {
    echo "<p style='color: red;'><strong>❌ waza_booking_settings is NOT registered!</strong></p>";
    echo "<p>All registered settings:</p>";
    echo "<pre>";
    print_r(array_keys($wp_registered_settings));
    echo "</pre>";
}

// Check if the option group exists
echo "<h4>Option Group Check:</h4>";
if (isset($new_allowed_options['waza_booking_settings'])) {
    echo "<p style='color: green;'>✅ Option group 'waza_booking_settings' exists</p>";
    echo "<p>Options in group:</p>";
    echo "<pre>";
    print_r($new_allowed_options['waza_booking_settings']);
    echo "</pre>";
} else {
    echo "<p style='color: red;'>❌ Option group 'waza_booking_settings' does NOT exist!</p>";
}

// Check debug log
echo "<hr>";
echo "<h3>Check Debug Log</h3>";
echo "<p>Look for lines starting with 'Waza: Registering settings...' in:</p>";
echo "<code>wp-content/debug.log</code>";

echo "<hr>";
echo "<p><a href='javascript:history.back()'>← Go Back</a> | <a href='" . admin_url('admin.php?page=waza-settings') . "'>Go to Settings</a></p>";
