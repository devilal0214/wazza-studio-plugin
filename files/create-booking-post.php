<?php
require_once('d:/xam/htdocs/wazza/wp-load.php');
global $wpdb;

// Get the existing booking
$booking = $wpdb->get_row(
    "SELECT b.*, s.start_datetime, s.end_datetime, p.post_title as activity_title
     FROM wp_waza_bookings b
     LEFT JOIN wp_waza_slots s ON b.slot_id = s.id
     LEFT JOIN wp_posts p ON b.activity_id = p.ID
     WHERE b.id = 1"
);

if ($booking) {
    // Create post for this booking
    $post_data = array(
        'post_title' => sprintf(
            'Booking #%d - %s - %s',
            $booking->id,
            $booking->user_name,
            $booking->activity_title
        ),
        'post_type' => 'waza_booking',
        'post_status' => 'publish',
        'post_author' => $booking->user_id ?: 1
    );
    
    $post_id = wp_insert_post($post_data);
    
    if ($post_id) {
        update_post_meta($post_id, '_waza_booking_id', $booking->id);
        update_post_meta($post_id, '_waza_slot_id', $booking->slot_id);
        update_post_meta($post_id, '_waza_activity_id', $booking->activity_id);
        update_post_meta($post_id, '_waza_user_email', $booking->user_email);
        update_post_meta($post_id, '_waza_user_phone', $booking->user_phone);
        update_post_meta($post_id, '_waza_total_amount', $booking->total_amount);
        update_post_meta($post_id, '_waza_payment_status', $booking->payment_status);
        update_post_meta($post_id, '_waza_booking_status', $booking->booking_status);
        
        echo "Created booking post #$post_id for booking #" . $booking->id . PHP_EOL;
        echo "Title: " . $post_data['post_title'] . PHP_EOL;
    } else {
        echo "Failed to create post" . PHP_EOL;
    }
} else {
    echo "Booking not found" . PHP_EOL;
}
