<?php
/**
 * Check Slots Table Structure
 * 
 * @package WazaBooking
 */

// Load WordPress
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to access this page.');
}

global $wpdb;

// Get table structure
$table_name = $wpdb->prefix . 'waza_slots';
$columns = $wpdb->get_results("DESCRIBE $table_name");

echo "<h2>Slots Table Structure:</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
foreach ($columns as $column) {
    echo "<tr>";
    echo "<td>{$column->Field}</td>";
    echo "<td>{$column->Type}</td>";
    echo "<td>{$column->Null}</td>";
    echo "<td>{$column->Key}</td>";
    echo "<td>{$column->Default}</td>";
    echo "</tr>";
}
echo "</table>";

// Check for existing activities
echo "<h2>Existing Activity Post Types:</h2>";
$post_types = get_post_types(['public' => true], 'objects');
foreach ($post_types as $post_type) {
    if (strpos($post_type->name, 'activity') !== false || strpos($post_type->name, 'waza') !== false) {
        echo "<p><strong>{$post_type->name}:</strong> {$post_type->label}</p>";
    }
}

// Check sample slot
$sample_slot = $wpdb->get_row("SELECT * FROM $table_name LIMIT 1");
echo "<h2>Sample Slot Data:</h2>";
echo "<pre>";
print_r($sample_slot);
echo "</pre>";
