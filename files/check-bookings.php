<?php
require_once('d:/xam/htdocs/wazza/wp-load.php');
global $wpdb;
$count = $wpdb->get_var("SELECT COUNT(*) FROM wp_waza_bookings");
echo "Total bookings in database: $count" . PHP_EOL . PHP_EOL;
if ($count > 0) {
    $bookings = $wpdb->get_results("SELECT id, user_id, slot_id, status, created_at FROM wp_waza_bookings LIMIT 5");
    foreach ($bookings as $b) {
        echo "ID: {$b->id} | User: {$b->user_id} | Slot: {$b->slot_id} | Status: {$b->status} | Created: {$b->created_at}" . PHP_EOL;
    }
}
