<?php
/**
 * Database Migration - Add price and instructor_id columns to waza_slots
 * 
 * Run this file ONCE by navigating to:
 * http://localhost/wazza/wp-content/plugins/waza-studio-app/migrate_slots_table.php
 * 
 * After running, DELETE this file for security.
 */

// Load WordPress
require_once('../../../wp-load.php');

// Security check - only admins can run this
if (!current_user_can('manage_options')) {
    die('Access denied. Only administrators can run database migrations.');
}

global $wpdb;

$table_name = $wpdb->prefix . 'waza_slots';

echo "<h1>Waza Slots Table Migration</h1>";
echo "<p>Adding <code>price</code> and <code>instructor_id</code> columns...</p>";

// Check if columns already exist
$columns = $wpdb->get_results("SHOW COLUMNS FROM {$table_name}");
$column_names = wp_list_pluck($columns, 'Field');

$has_price = in_array('price', $column_names);
$has_instructor = in_array('instructor_id', $column_names);

echo "<h2>Current Status:</h2>";
echo "<ul>";
echo "<li>Price column: " . ($has_price ? '✅ Already exists' : '❌ Missing') . "</li>";
echo "<li>Instructor column: " . ($has_instructor ? '✅ Already exists' : '❌ Missing') . "</li>";
echo "</ul>";

// Add price column if missing
if (!$has_price) {
    echo "<p>Adding <code>price</code> column...</p>";
    $result = $wpdb->query("
        ALTER TABLE {$table_name} 
        ADD COLUMN price decimal(10,2) NOT NULL DEFAULT 0.00 
        AFTER capacity
    ");
    
    if ($result !== false) {
        echo "<p style='color: green;'>✅ Price column added successfully!</p>";
    } else {
        echo "<p style='color: red;'>❌ Error adding price column: " . $wpdb->last_error . "</p>";
    }
} else {
    echo "<p>✅ Price column already exists, skipping.</p>";
}

// Add instructor_id column if missing
if (!$has_instructor) {
    echo "<p>Adding <code>instructor_id</code> column...</p>";
    $result = $wpdb->query("
        ALTER TABLE {$table_name} 
        ADD COLUMN instructor_id bigint(20) DEFAULT NULL 
        AFTER activity_id,
        ADD KEY instructor_id (instructor_id)
    ");
    
    if ($result !== false) {
        echo "<p style='color: green;'>✅ Instructor_id column added successfully!</p>";
    } else {
        echo "<p style='color: red;'>❌ Error adding instructor_id column: " . $wpdb->last_error . "</p>";
    }
} else {
    echo "<p>✅ Instructor_id column already exists, skipping.</p>";
}

// Show final table structure
echo "<h2>Final Table Structure:</h2>";
$columns = $wpdb->get_results("SHOW COLUMNS FROM {$table_name}");
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
foreach ($columns as $column) {
    echo "<tr>";
    echo "<td><strong>{$column->Field}</strong></td>";
    echo "<td>{$column->Type}</td>";
    echo "<td>{$column->Null}</td>";
    echo "<td>" . ($column->Default ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<h2 style='color: green;'>✅ Migration Complete!</h2>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Verify the table structure above shows both <code>price</code> and <code>instructor_id</code></li>";
echo "<li>Go back to WordPress admin and try creating a slot</li>";
echo "<li><strong>DELETE THIS FILE</strong> for security: <code>migrate_slots_table.php</code></li>";
echo "</ol>";
