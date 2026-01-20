<?php
/**
 * Check activities assigned to instructors
 */

require_once '../../../wp-load.php';

global $wpdb;

echo "=== ACTIVITIES WITH INSTRUCTORS ===\n\n";

$activities = $wpdb->get_results("
    SELECT p.ID, p.post_title, p.post_status
    FROM {$wpdb->posts} p
    WHERE p.post_type = 'waza_activity'
    AND p.post_status IN ('publish', 'draft')
    ORDER BY p.ID
");

foreach ($activities as $activity) {
    $instructor_id = get_post_meta($activity->ID, '_waza_instructor', true);
    
    if ($instructor_id) {
        $instructor = get_post($instructor_id);
        $instructor_name = $instructor ? $instructor->post_title : "DELETED ({$instructor_id})";
        
        echo "Activity: {$activity->post_title} (ID: {$activity->ID})\n";
        echo "Status: {$activity->post_status}\n";
        echo "Instructor: {$instructor_name} (ID: {$instructor_id})\n";
        
        // Count slots
        $slots_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}waza_slots WHERE activity_id = %d
        ", $activity->ID));
        
        echo "Slots: {$slots_count}\n";
        
        // Count bookings
        $bookings_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(b.id)
            FROM {$wpdb->prefix}waza_bookings b
            INNER JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
            WHERE s.activity_id = %d
        ", $activity->ID));
        
        echo "Bookings: {$bookings_count}\n";
        echo str_repeat("-", 50) . "\n\n";
    }
}
