<?php
/**
 * Check booking post sync
 */

define('WP_USE_THEMES', false);
require_once(__DIR__ . '/../../../wp-load.php');

global $wpdb;

echo "=== BOOKINGS IN DATABASE TABLE ===\n\n";
$bookings = $wpdb->get_results("
    SELECT b.*, s.start_datetime, p.post_title as activity_title
    FROM {$wpdb->prefix}waza_bookings b
    LEFT JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
    LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
    WHERE b.user_email = 'team@jaiveeru.co.in'
    ORDER BY b.id ASC
");

foreach ($bookings as $booking) {
    echo "Booking #{$booking->id}\n";
    echo "  Activity: {$booking->activity_title}\n";
    echo "  User ID: {$booking->user_id}\n";
    echo "  Attendees: {$booking->attendees_count}\n";
    echo "  Amount: {$booking->total_amount}\n";
    echo "  Payment: {$booking->payment_status}\n";
    echo "  Status: {$booking->booking_status}\n";
    echo "  Created: {$booking->created_at}\n";
    
    // Check if post exists
    $post_id = $wpdb->get_var($wpdb->prepare("
        SELECT post_id FROM {$wpdb->postmeta}
        WHERE meta_key = '_waza_booking_id' AND meta_value = %d
    ", $booking->id));
    
    echo "  Post ID: " . ($post_id ?: 'NOT CREATED') . "\n\n";
}

echo "\n=== BOOKING POSTS IN ADMIN PANEL ===\n\n";
$posts = get_posts([
    'post_type' => 'waza_booking',
    'posts_per_page' => -1,
    'post_status' => 'any'
]);

echo "Total booking posts: " . count($posts) . "\n\n";

foreach ($posts as $post) {
    $booking_id = get_post_meta($post->ID, '_waza_booking_id', true);
    $user_email = get_post_meta($post->ID, '_waza_user_email', true);
    echo "Post #{$post->ID}: Booking #{$booking_id}, Email: {$user_email}, Status: {$post->post_status}\n";
}
