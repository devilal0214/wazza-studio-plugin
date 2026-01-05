<?php
/**
 * Force create all database tables
 * Run this file directly to create all required tables
 */

// Load WordPress (3 levels up: plugin folder -> plugins -> wp-content -> WordPress root)
require_once(__DIR__ . '/../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied. You must be an administrator.');
}

echo "<h2>Creating Database Tables...</h2>";

// Include the DatabaseManager
require_once(__DIR__ . '/src/Database/DatabaseManager.php');

use WazaBooking\Database\DatabaseManager;

$db_manager = new DatabaseManager();
$db_manager->create_tables();

echo "<p><strong>✅ All tables created successfully!</strong></p>";
echo "<p>You can now close this page and refresh your admin pages.</p>";

// List all created tables
global $wpdb;
$tables = [
    'waza_bookings',
    'waza_slots',
    'waza_qr_tokens',
    'waza_attendance',
    'waza_payments',
    'waza_waitlist',
    'waza_email_templates',
    'waza_workshops',
    'waza_workshop_students',
    'waza_activity_logs',
    'waza_announcements',
    'waza_qr_groups',
    'waza_qr_group_members'
];

echo "<h3>Tables Status:</h3><ul>";
foreach ($tables as $table) {
    $full_table = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table}'") === $full_table;
    $status = $exists ? '✅ EXISTS' : '❌ NOT FOUND';
    echo "<li><strong>{$full_table}</strong>: {$status}</li>";
}
echo "</ul>";
