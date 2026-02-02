<?php
/**
 * Database Migration: Add image_url to slots table
 * Run this file once to update existing database
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

global $wpdb;

$slots_table = $wpdb->prefix . 'waza_slots';

// Check if column already exists
$column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$slots_table} LIKE 'image_url'");

if (empty($column_exists)) {
    $sql = "ALTER TABLE {$slots_table} ADD COLUMN image_url VARCHAR(500) DEFAULT NULL AFTER notes";
    $result = $wpdb->query($sql);
    
    if ($result !== false) {
        echo "✓ Successfully added image_url column to waza_slots table\n";
    } else {
        echo "✗ Error adding column: " . $wpdb->last_error . "\n";
    }
} else {
    echo "✓ Column image_url already exists in waza_slots table\n";
}

// Create promo codes table
$promo_table = $wpdb->prefix . 'waza_promo_codes';
$charset_collate = $wpdb->get_charset_collate();

$promo_sql = "CREATE TABLE IF NOT EXISTS {$promo_table} (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    code varchar(50) NOT NULL UNIQUE,
    description text DEFAULT NULL,
    discount_type varchar(20) NOT NULL DEFAULT 'percentage',
    discount_amount decimal(10,2) NOT NULL,
    usage_limit int(11) DEFAULT NULL,
    used_count int(11) NOT NULL DEFAULT 0,
    expiry_date datetime DEFAULT NULL,
    applicable_activities text DEFAULT NULL,
    min_booking_amount decimal(10,2) DEFAULT NULL,
    status varchar(20) NOT NULL DEFAULT 'active',
    created_by bigint(20) DEFAULT NULL,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY code (code),
    KEY status (status),
    KEY expiry_date (expiry_date)
) {$charset_collate}";

$promo_result = $wpdb->query($promo_sql);

if ($promo_result !== false) {
    echo "✓ Successfully created waza_promo_codes table\n";
} else {
    echo "✗ Error creating promo codes table: " . $wpdb->last_error . "\n";
}

echo "\nMigration complete!\n";
