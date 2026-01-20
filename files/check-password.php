<?php
/**
 * Check user password
 */

define('WP_USE_THEMES', false);
require_once(__DIR__ . '/../../../wp-load.php');

$user = get_user_by('email', 'team@jaiveeru.co.in');
if ($user) {
    echo "User ID: {$user->ID}\n";
    echo "Email: {$user->user_email}\n";
    echo "Login: {$user->user_login}\n";
    echo "Role: " . implode(', ', $user->roles) . "\n";
    
    // Get the saved password from booking meta
    global $wpdb;
    $booking = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}waza_bookings WHERE user_email = %s ORDER BY id DESC LIMIT 1",
        'team@jaiveeru.co.in'
    ));
    
    if ($booking) {
        echo "\nBooking ID: {$booking->id}\n";
        
        $booking_post_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_waza_booking_id' AND meta_value = %d LIMIT 1",
            $booking->id
        ));
        
        if ($booking_post_id) {
            echo "Booking Post ID: {$booking_post_id}\n";
            $password = get_post_meta($booking_post_id, '_pending_account_password', true);
            echo "Saved Password: {$password}\n";
            
            // Test password
            if (!empty($password)) {
                $check = wp_check_password($password, $user->user_pass);
                echo "Password matches: " . ($check ? 'YES' : 'NO') . "\n";
            }
        }
    }
} else {
    echo "User not found\n";
}
