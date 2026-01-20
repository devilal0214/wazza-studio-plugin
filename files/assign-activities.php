<?php
/**
 * Assign all existing activities to Wazza Instructor
 */

require_once '../../../wp-load.php';

global $wpdb;

$instructor_id = 78; // Wazza Instructor

echo "=== ASSIGNING ACTIVITIES TO WAZZA INSTRUCTOR ===\n\n";

$activities = get_posts([
    'post_type' => 'waza_activity',
    'post_status' => 'publish',
    'posts_per_page' => -1
]);

foreach ($activities as $activity) {
    // Check current assignment
    $current = get_post_meta($activity->ID, '_waza_instructor', true);
    
    if ($current) {
        $curr_instructor = get_post($current);
        echo "⊘ {$activity->post_title} - Already assigned to {$curr_instructor->post_title}\n";
        continue;
    }
    
    // Assign to Wazza Instructor
    update_post_meta($activity->ID, '_waza_instructor', $instructor_id);
    
    // Get slots and bookings count
    $slots = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM {$wpdb->prefix}waza_slots WHERE activity_id = %d
    ", $activity->ID));
    
    $bookings = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(b.id)
        FROM {$wpdb->prefix}waza_bookings b
        INNER JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
        WHERE s.activity_id = %d
    ", $activity->ID));
    
    echo "✓ {$activity->post_title} - {$slots} slots, {$bookings} bookings\n";
}

echo "\n=== VERIFICATION ===\n\n";

$assigned = $wpdb->get_results($wpdb->prepare("
    SELECT p.ID, p.post_title
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
    WHERE p.post_type = 'waza_activity'
    AND pm.meta_key = '_waza_instructor'
    AND pm.meta_value = %d
", $instructor_id));

echo "Wazza Instructor now has " . count($assigned) . " activities:\n";
foreach ($assigned as $act) {
    echo "  - {$act->post_title}\n";
}

// Get stats
$total_slots = $wpdb->get_var($wpdb->prepare("
    SELECT COUNT(DISTINCT s.id)
    FROM {$wpdb->prefix}waza_slots s
    INNER JOIN {$wpdb->posts} a ON s.activity_id = a.ID
    INNER JOIN {$wpdb->postmeta} pm ON a.ID = pm.post_id
    WHERE pm.meta_key = '_waza_instructor'
    AND pm.meta_value = %d
", $instructor_id));

$total_bookings = $wpdb->get_var($wpdb->prepare("
    SELECT COUNT(b.id)
    FROM {$wpdb->prefix}waza_bookings b
    INNER JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
    INNER JOIN {$wpdb->posts} a ON s.activity_id = a.ID
    INNER JOIN {$wpdb->postmeta} pm ON a.ID = pm.post_id
    WHERE pm.meta_key = '_waza_instructor'
    AND pm.meta_value = %d
", $instructor_id));

$total_students = $wpdb->get_var($wpdb->prepare("
    SELECT COUNT(DISTINCT b.user_id)
    FROM {$wpdb->prefix}waza_bookings b
    INNER JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
    INNER JOIN {$wpdb->posts} a ON s.activity_id = a.ID
    INNER JOIN {$wpdb->postmeta} pm ON a.ID = pm.post_id
    WHERE pm.meta_key = '_waza_instructor'
    AND pm.meta_value = %d
    AND b.booking_status = 'confirmed'
", $instructor_id));

echo "\n=== DASHBOARD STATS ===\n";
echo "Total Slots: {$total_slots}\n";
echo "Total Bookings: {$total_bookings}\n";
echo "Total Students: {$total_students}\n";
echo "\n✅ Instructor dashboard will now show data!\n";
