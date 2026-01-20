<?php
/**
 * Check if attendance table exists
 */

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

global $wpdb;

$table_name = $wpdb->prefix . 'waza_attendance';

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

echo "Checking table: $table_name\n";
echo "Table exists: " . ($table_exists ? "YES" : "NO") . "\n\n";

if ($table_exists) {
    echo "Table structure:\n";
    $columns = $wpdb->get_results("DESCRIBE $table_name");
    foreach ($columns as $column) {
        echo "  - {$column->Field} ({$column->Type})\n";
    }
    
    echo "\nRow count: " . $wpdb->get_var("SELECT COUNT(*) FROM $table_name") . "\n";
} else {
    echo "Table does NOT exist. The query will fail.\n";
    echo "\nAvailable waza tables:\n";
    $tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}waza_%'", ARRAY_N);
    foreach ($tables as $table) {
        echo "  - {$table[0]}\n";
    }
}
