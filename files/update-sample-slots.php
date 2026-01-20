<?php
require_once __DIR__ . '/../../../wp-load.php';
global $wpdb;

echo "=== UPDATING SAMPLE SLOTS WITH INSTRUCTOR ===\n\n";

// Get instructor post for user 2
$instructors = get_posts([
    'post_type' => 'waza_instructor',
    'meta_key' => '_waza_user_id',
    'meta_value' => 2,
    'posts_per_page' => 1
]);

if (empty($instructors)) {
    echo "✗ Instructor not found for user ID 2\n";
    exit;
}

$instructor_id = $instructors[0]->ID;
echo "Found instructor: {$instructors[0]->post_title} (ID: {$instructor_id})\n\n";

// Get the slots that have attendance records
$slots_with_attendance = $wpdb->get_col("
    SELECT DISTINCT slot_id 
    FROM {$wpdb->prefix}waza_attendance
");

echo "Slots with attendance records: " . count($slots_with_attendance) . "\n";
echo "Slot IDs: " . implode(', ', $slots_with_attendance) . "\n\n";

// Update those slots to have the instructor
foreach ($slots_with_attendance as $slot_id) {
    $updated = $wpdb->update(
        "{$wpdb->prefix}waza_slots",
        ['instructor_id' => $instructor_id],
        ['id' => $slot_id],
        ['%d'],
        ['%d']
    );
    
    if ($updated !== false) {
        echo "✓ Updated slot {$slot_id} with instructor {$instructor_id}\n";
    } else {
        echo "✗ Failed to update slot {$slot_id}\n";
    }
}

echo "\n=== UPDATE COMPLETE ===\n";
