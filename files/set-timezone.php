<?php
/**
 * Set WordPress Timezone to Asia/Kolkata (India)
 * 
 * Run this script once to configure the timezone
 */

// Load WordPress
require_once __DIR__ . '/../../../wp-load.php';

// Set timezone to Asia/Kolkata
$timezone = 'Asia/Kolkata';

// Update WordPress option
update_option('timezone_string', $timezone);

// Verify
$current_timezone = wp_timezone_string();
$current_time = current_time('Y-m-d H:i:s');
$utc_time = gmdate('Y-m-d H:i:s');

echo "✅ Timezone Configuration\n";
echo "========================\n\n";
echo "WordPress Timezone: {$current_timezone}\n";
echo "Current Local Time: {$current_time}\n";
echo "Current UTC Time:   {$utc_time}\n\n";

// Calculate offset
$dt = new DateTime('now', new DateTimeZone($current_timezone));
$offset_seconds = $dt->getOffset();
$offset_hours = $offset_seconds / 3600;
echo "Timezone Offset: GMT" . ($offset_hours >= 0 ? '+' : '') . $offset_hours . "\n";

// Test date functions
echo "\n📅 Date Function Tests\n";
echo "======================\n\n";

echo "current_time('Y-m-d H:i:s'): " . current_time('Y-m-d H:i:s') . "\n";
echo "current_time('timestamp'):   " . current_time('timestamp') . " (" . date('Y-m-d H:i:s', current_time('timestamp')) . ")\n";
echo "date_i18n('Y-m-d H:i:s'):    " . date_i18n('Y-m-d H:i:s') . "\n";

echo "\n✅ Timezone set to Asia/Kolkata successfully!\n";
echo "\nNote: All booking times, attendance records, and slot times will now use Indian Standard Time (IST).\n";
