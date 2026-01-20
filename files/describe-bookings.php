<?php
require_once('d:/xam/htdocs/wazza/wp-load.php');
global $wpdb;
$columns = $wpdb->get_results("DESCRIBE wp_waza_bookings");
echo "wp_waza_bookings table structure:" . PHP_EOL . PHP_EOL;
foreach ($columns as $col) {
    echo "{$col->Field} | {$col->Type} | {$col->Null} | {$col->Key} | {$col->Default}" . PHP_EOL;
}
echo PHP_EOL . "Sample booking:" . PHP_EOL;
$booking = $wpdb->get_row("SELECT * FROM wp_waza_bookings LIMIT 1");
print_r($booking);
