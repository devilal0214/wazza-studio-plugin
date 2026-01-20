<?php
/**
 * Check and Add QR Code Columns
 */

define('WP_USE_THEMES', false);
require_once __DIR__ . '/../../../wp-load.php';

global $wpdb;
$bookings_table = $wpdb->prefix . 'waza_bookings';

echo "=== CURRENT BOOKINGS TABLE STRUCTURE ===\n\n";

$columns = $wpdb->get_results("SHOW COLUMNS FROM $bookings_table");
foreach ($columns as $column) {
    echo sprintf("%-20s %-20s %s\n", $column->Field, $column->Type, $column->Null);
}

echo "\n=== ADDING MISSING QR COLUMNS ===\n\n";

// Add qr_code_url if missing
$has_qr_code_url = false;
foreach ($columns as $column) {
    if ($column->Field === 'qr_code_url') {
        $has_qr_code_url = true;
        break;
    }
}

if (!$has_qr_code_url) {
    echo "Adding qr_code_url column...\n";
    $wpdb->query("ALTER TABLE $bookings_table ADD COLUMN qr_code_url VARCHAR(500) NULL AFTER qr_token");
    echo "✓ qr_code_url added\n";
} else {
    echo "✓ qr_code_url already exists\n";
}

// Add qr_code if missing
$has_qr_code = false;
foreach ($columns as $column) {
    if ($column->Field === 'qr_code') {
        $has_qr_code = true;
        break;
    }
}

if (!$has_qr_code) {
    echo "Adding qr_code column...\n";
    $wpdb->query("ALTER TABLE $bookings_table ADD COLUMN qr_code TEXT NULL AFTER qr_token");
    echo "✓ qr_code added\n";
} else {
    echo "✓ qr_code already exists\n";
}

echo "\n=== UPDATED STRUCTURE ===\n\n";
$columns = $wpdb->get_results("SHOW COLUMNS FROM $bookings_table LIKE '%qr%'");
foreach ($columns as $column) {
    echo sprintf("%-20s %-20s %s\n", $column->Field, $column->Type, $column->Null);
}
