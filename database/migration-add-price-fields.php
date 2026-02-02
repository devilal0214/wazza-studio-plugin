<?php
/**
 * Migration: Add original_price and sale_price fields to slots table
 * 
 * Run this file once via: /wp-admin/admin.php?page=waza-run-price-migration
 */

// Load WordPress
require_once dirname(__FILE__) . '/../../../../wp-load.php';

// Security check
if (!current_user_can('manage_options')) {
    wp_die('Unauthorized access');
}

global $wpdb;

echo '<h1>Waza Booking - Price Fields Migration</h1>';
echo '<div style="background: #fff; padding: 20px; margin: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';

$slots_table = $wpdb->prefix . 'waza_slots';

// Check if original_price column exists
$original_price_exists = $wpdb->get_results("SHOW COLUMNS FROM `{$slots_table}` LIKE 'original_price'");

if (empty($original_price_exists)) {
    echo '<p><strong>Adding original_price column...</strong></p>';
    
    $sql = "ALTER TABLE `{$slots_table}` 
            ADD COLUMN `original_price` DECIMAL(10,2) DEFAULT NULL AFTER `price`";
    
    $result = $wpdb->query($sql);
    
    if ($result !== false) {
        echo '<p style="color: green;">✓ Successfully added original_price column</p>';
    } else {
        echo '<p style="color: red;">✗ Failed to add original_price column: ' . $wpdb->last_error . '</p>';
    }
} else {
    echo '<p style="color: blue;">ℹ original_price column already exists</p>';
}

// Check if sale_price column exists
$sale_price_exists = $wpdb->get_results("SHOW COLUMNS FROM `{$slots_table}` LIKE 'sale_price'");

if (empty($sale_price_exists)) {
    echo '<p><strong>Adding sale_price column...</strong></p>';
    
    $sql = "ALTER TABLE `{$slots_table}` 
            ADD COLUMN `sale_price` DECIMAL(10,2) DEFAULT NULL AFTER `original_price`";
    
    $result = $wpdb->query($sql);
    
    if ($result !== false) {
        echo '<p style="color: green;">✓ Successfully added sale_price column</p>';
    } else {
        echo '<p style="color: red;">✗ Failed to add sale_price column: ' . $wpdb->last_error . '</p>';
    }
} else {
    echo '<p style="color: blue;">ℹ sale_price column already exists</p>';
}

echo '<hr>';
echo '<h2>Migration Complete!</h2>';
echo '<p><a href="' . admin_url('admin.php?page=waza-slots') . '" class="button button-primary">Go to Slots Manager</a></p>';
echo '</div>';
