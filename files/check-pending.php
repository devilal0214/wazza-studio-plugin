<?php
/**
 * Get pending account password
 */

define('WP_USE_THEMES', false);
require_once(__DIR__ . '/../../../wp-load.php');

$booking_id = 5;

// Check option
$pending = get_option("waza_pending_account_{$booking_id}");
echo "Option waza_pending_account_5:\n";
print_r($pending);
echo "\n\n";

// Check post meta
global $wpdb;
$post_id = $wpdb->get_var($wpdb->prepare(
    "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_waza_booking_id' AND meta_value = %d LIMIT 1",
    $booking_id
));

if ($post_id) {
    echo "Booking Post ID: {$post_id}\n";
    $password = get_post_meta($post_id, '_pending_account_password', true);
    echo "Saved Password in Post Meta: {$password}\n";
}

// Get user
$user = get_user_by('email', 'team@jaiveeru.co.in');
if ($user) {
    echo "\nUser exists: {$user->ID}\n";
    echo "Email: {$user->user_email}\n";
}
