<?php
require_once('d:/xam/htdocs/wazza/wp-load.php');
global $wpdb;

echo "=== USER ACCOUNTS CHECK ===" . PHP_EOL . PHP_EOL;

// Get all users created
$users = $wpdb->get_results("SELECT ID, user_login, user_email, display_name FROM wp_users WHERE ID > 1");

echo "Total users (excluding admin): " . count($users) . PHP_EOL . PHP_EOL;

foreach ($users as $user) {
    $user_data = get_userdata($user->ID);
    echo "User ID: {$user->ID}" . PHP_EOL;
    echo "  Email: {$user->user_email}" . PHP_EOL;
    echo "  Login: {$user->user_login}" . PHP_EOL;
    echo "  Display Name: {$user->display_name}" . PHP_EOL;
    echo "  Roles: " . implode(', ', $user_data->roles) . PHP_EOL;
    echo "  Phone: " . get_user_meta($user->ID, 'phone', true) . PHP_EOL;
    echo PHP_EOL;
}

// Check recent bookings
echo "=== RECENT BOOKINGS ===" . PHP_EOL . PHP_EOL;
$bookings = $wpdb->get_results("SELECT id, user_id, user_email, user_name, booking_status, payment_status, created_at FROM wp_waza_bookings ORDER BY id DESC LIMIT 5");

foreach ($bookings as $b) {
    echo "Booking #{$b->id}" . PHP_EOL;
    echo "  User ID: {$b->user_id}" . PHP_EOL;
    echo "  Email: {$b->user_email}" . PHP_EOL;
    echo "  Name: {$b->user_name}" . PHP_EOL;
    echo "  Status: {$b->booking_status} / {$b->payment_status}" . PHP_EOL;
    echo "  Created: {$b->created_at}" . PHP_EOL;
    echo PHP_EOL;
}
