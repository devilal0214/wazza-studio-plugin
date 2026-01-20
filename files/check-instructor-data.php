<?php
/**
 * Check activities, slots, and bookings in the system
 */

require_once '../../../wp-load.php';

global $wpdb;

echo "=== ACTIVITIES IN SYSTEM ===\n\n";

$activities = get_posts([
    'post_type' => 'waza_activity',
    'post_status' => 'publish',
    'posts_per_page' => -1
]);

foreach ($activities as $activity) {
    echo "Activity: {$activity->post_title} (ID: {$activity->ID})\n";
    
    $instructor_id = get_post_meta($activity->ID, '_waza_instructor', true);
    if ($instructor_id) {
        $instructor = get_post($instructor_id);
        echo "  Assigned to: " . ($instructor ? $instructor->post_title : "DELETED ({$instructor_id})") . "\n";
    } else {
        echo "  Assigned to: NONE\n";
    }
    
    // Count slots
    $slots = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM {$wpdb->prefix}waza_slots WHERE activity_id = %d
    ", $activity->ID));
    
    echo "  Slots: {$slots}\n";
    
    // Count bookings
    $bookings = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(b.id)
        FROM {$wpdb->prefix}waza_bookings b
        INNER JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
        WHERE s.activity_id = %d
    ", $activity->ID));
    
    echo "  Bookings: {$bookings}\n\n";
}

echo "\n=== WAZZA INSTRUCTOR DETAILS ===\n\n";

$wazza_instructor = get_posts([
    'post_type' => 'waza_instructor',
    'meta_key' => '_waza_email',
    'meta_value' => 'instructor@waza.studio',
    'posts_per_page' => 1
]);

if (!empty($wazza_instructor)) {
    $instructor = $wazza_instructor[0];
    echo "Instructor: {$instructor->post_title} (ID: {$instructor->ID})\n";
    
    // Check assigned activities
    $assigned = $wpdb->get_results($wpdb->prepare("
        SELECT p.ID, p.post_title
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = 'waza_activity'
        AND pm.meta_key = '_waza_instructor'
        AND pm.meta_value = %d
    ", $instructor->ID));
    
    echo "Assigned Activities: " . count($assigned) . "\n";
    foreach ($assigned as $act) {
        echo "  - {$act->post_title}\n";
    }
}

echo "\n=== SUGGESTION ===\n";
if (count($activities) > 0 && count($assigned) == 0) {
    echo "✓ Activities exist but none assigned to Wazza Instructor\n";
    echo "→ We can assign existing activities to this instructor\n";
}
