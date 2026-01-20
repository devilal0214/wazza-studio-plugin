<?php
require_once __DIR__ . '/../../../wp-load.php';
global $wpdb;

echo "=== WAZA_SLOTS TABLE SCHEMA ===\n\n";
$columns = $wpdb->get_results("DESCRIBE {$wpdb->prefix}waza_slots");

foreach ($columns as $col) {
    echo "{$col->Field} ({$col->Type})\n";
}
