<?php
/**
 * Run attendance migration
 */

define('WP_USE_THEMES', false);
require_once(__DIR__ . '/../../../wp-load.php');

global $wpdb;

echo "Running attendance migration...\n\n";

$sql = file_get_contents(__DIR__ . '/database/migration-attendance.sql');

// Execute each statement
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if (empty($statement) || strpos($statement, '--') === 0) {
        continue;
    }
    
    echo "Executing: " . substr($statement, 0, 80) . "...\n";
    $result = $wpdb->query($statement);
    
    if ($result === false) {
        echo "ERROR: " . $wpdb->last_error . "\n\n";
    } else {
        echo "SUCCESS\n\n";
    }
}

echo "Migration complete!\n\n";

// Verify tables
$tables = $wpdb->get_results("SHOW TABLES LIKE 'wp_waza_%'");
echo "Waza tables:\n";
foreach ($tables as $table) {
    $table_name = array_values((array)$table)[0];
    $count = $wpdb->get_var("SELECT COUNT(*) FROM `{$table_name}`");
    echo "  - {$table_name} ({$count} records)\n";
}
