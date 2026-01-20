<?php
/**
 * Check existing instructors and their metadata
 */

require_once '../../../wp-load.php';

global $wpdb;

echo "=== EXISTING INSTRUCTORS ===\n\n";

$instructors = $wpdb->get_results("
    SELECT p.ID, p.post_title, p.post_status, p.post_date
    FROM {$wpdb->posts} p
    WHERE p.post_type = 'waza_instructor'
    ORDER BY p.ID
");

foreach ($instructors as $instructor) {
    echo "ID: {$instructor->ID}\n";
    echo "Name: {$instructor->post_title}\n";
    echo "Status: {$instructor->post_status}\n";
    echo "Created: {$instructor->post_date}\n";
    
    // Get all meta
    $meta = get_post_meta($instructor->ID);
    echo "Meta Data:\n";
    foreach ($meta as $key => $value) {
        echo "  {$key}: " . print_r($value[0], true) . "\n";
    }
    
    // Check if linked to user
    $user_id = get_post_meta($instructor->ID, '_waza_user_id', true);
    if ($user_id) {
        $user = get_userdata($user_id);
        echo "Linked User: {$user->user_login} ({$user->user_email})\n";
    } else {
        echo "Linked User: NONE\n";
    }
    
    // Count assigned slots
    $slots_count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT s.id)
        FROM {$wpdb->prefix}waza_slots s
        INNER JOIN {$wpdb->posts} a ON s.activity_id = a.ID
        WHERE a.post_type = 'waza_activity'
        AND EXISTS (
            SELECT 1 FROM {$wpdb->postmeta} 
            WHERE post_id = a.ID 
            AND meta_key = '_waza_instructor' 
            AND meta_value = %d
        )
    ", $instructor->ID));
    
    echo "Assigned Slots: {$slots_count}\n";
    
    // Count bookings
    $bookings_count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT b.id)
        FROM {$wpdb->prefix}waza_bookings b
        INNER JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
        INNER JOIN {$wpdb->posts} a ON s.activity_id = a.ID
        WHERE a.post_type = 'waza_activity'
        AND EXISTS (
            SELECT 1 FROM {$wpdb->postmeta} 
            WHERE post_id = a.ID 
            AND meta_key = '_waza_instructor' 
            AND meta_value = %d
        )
    ", $instructor->ID));
    
    echo "Total Bookings: {$bookings_count}\n";
    echo str_repeat("-", 50) . "\n\n";
}

echo "\n=== SUMMARY ===\n";
echo "Total Instructors: " . count($instructors) . "\n";
$linked = array_filter($instructors, function($i) {
    return get_post_meta($i->ID, '_waza_user_id', true);
});
echo "Linked to Users: " . count($linked) . "\n";
echo "Need User Link: " . (count($instructors) - count($linked)) . "\n";
