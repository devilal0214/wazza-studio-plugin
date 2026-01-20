<?php
/**
 * Clean up pending bookings older than 24 hours
 * and create posts for confirmed bookings
 */

define('WP_USE_THEMES', false);
require_once(__DIR__ . '/../../../wp-load.php');

global $wpdb;

echo "=== CLEANING PENDING BOOKINGS ===\n\n";

// Delete bookings pending for more than 24 hours
$deleted = $wpdb->query("
    DELETE FROM {$wpdb->prefix}waza_bookings
    WHERE payment_status = 'pending'
    AND booking_status = 'pending'
    AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
");

echo "Deleted {$deleted} old pending bookings\n\n";

// Find confirmed bookings without posts
echo "=== CREATING MISSING BOOKING POSTS ===\n\n";

$bookings_without_posts = $wpdb->get_results("
    SELECT b.*, s.start_datetime, s.end_datetime, p.post_title as activity_title
    FROM {$wpdb->prefix}waza_bookings b
    LEFT JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
    LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
    WHERE b.booking_status = 'confirmed'
    AND NOT EXISTS (
        SELECT 1 FROM {$wpdb->postmeta}
        WHERE meta_key = '_waza_booking_id' AND meta_value = b.id
    )
");

echo "Found " . count($bookings_without_posts) . " confirmed bookings without posts\n\n";

foreach ($bookings_without_posts as $booking) {
    echo "Creating post for Booking #{$booking->id}...\n";
    
    $post_data = [
        'post_title' => sprintf(
            'Booking #%d - %s - %s',
            $booking->id,
            $booking->user_name,
            $booking->activity_title
        ),
        'post_type' => 'waza_booking',
        'post_status' => 'publish',
        'post_author' => $booking->user_id ?: 1
    ];
    
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
        
        echo "  Created post #{$post_id}\n";
    } else {
        echo "  FAILED to create post\n";
    }
}

echo "\n=== SUMMARY ===\n\n";

$total_bookings = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}waza_bookings");
$pending_bookings = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}waza_bookings WHERE payment_status = 'pending'");
$confirmed_bookings = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}waza_bookings WHERE booking_status = 'confirmed'");

echo "Total bookings: {$total_bookings}\n";
echo "Pending payment: {$pending_bookings}\n";
echo "Confirmed: {$confirmed_bookings}\n";
