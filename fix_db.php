<?php
require_once( dirname(dirname(dirname(dirname( __FILE__ )))) . '/wp-load.php' );
global $wpdb;

$table_name = $wpdb->prefix . 'waza_bookings';

// Check Quantity Column
$col_quantity = 'quantity';
$row_q = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '$table_name' AND COLUMN_NAME = '$col_quantity'");

if (empty($row_q)) {
    echo "Column $col_quantity does not exist. Adding it...<br>";
    $wpdb->query("ALTER TABLE $table_name ADD $col_quantity int(11) NOT NULL DEFAULT 1 AFTER slot_id");
    echo "Column $col_quantity added.<br>";
} else {
    echo "Column $col_quantity already exists.<br>";
}

// Check Activity ID Column (Again to be safe)
$col_activity = 'activity_id';
$row_a = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '$table_name' AND COLUMN_NAME = '$col_activity'");

if (empty($row_a)) {
    echo "Column $col_activity does not exist. Adding it...<br>";
    $wpdb->query("ALTER TABLE $table_name ADD $col_activity bigint(20) unsigned NOT NULL AFTER user_id");
    echo "Column $col_activity added.<br>";
} else {
    echo "Column $col_activity already exists.<br>";
}
