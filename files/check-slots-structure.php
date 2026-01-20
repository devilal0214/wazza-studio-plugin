<?php
require_once '../../../wp-load.php';

global $wpdb;

echo "=== WAZA_SLOTS TABLE STRUCTURE ===\n\n";
$columns = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}waza_slots");

foreach ($columns as $col) {
    echo "{$col->Field} ({$col->Type})\n";
}
