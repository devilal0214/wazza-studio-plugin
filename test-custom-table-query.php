<?php
/**
 * Test Custom Table Query
 */

require_once __DIR__ . '/../../../wp-load.php';

header('Content-Type: text/plain; charset=utf-8');

global $wpdb;

echo "=== TESTING CUSTOM TABLE QUERIES ===\n\n";

// Get all slots from custom table
$slots = $wpdb->get_results("
    SELECT s.*, p.post_title as activity_title 
    FROM {$wpdb->prefix}waza_slots s
    LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
    ORDER BY s.start_datetime ASC
");

echo "Total slots in wp_waza_slots table: " . count($slots) . "\n\n";

if ($slots) {
    echo "Slot Details:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    foreach ($slots as $slot) {
        $start_date = date('Y-m-d', strtotime($slot->start_datetime));
        $start_time = date('H:i', strtotime($slot->start_datetime));
        $end_time = date('H:i', strtotime($slot->end_datetime));
        $available = $slot->capacity - $slot->booked_count;
        
        echo "ID: {$slot->id}\n";
        echo "Activity: {$slot->activity_title}\n";
        echo "Date: {$start_date}\n";
        echo "Time: {$start_time} - {$end_time}\n";
        echo "Capacity: {$slot->capacity}\n";
        echo "Booked: {$slot->booked_count}\n";
        echo "Available: {$available}\n";
        echo "Status: {$slot->status}\n";
        echo "Location: {$slot->location}\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    }
}

echo "\n\n=== TESTING get_day_slots QUERY ===\n\n";

// Test query for January 3, 2026
$test_date = '2026-01-03';

$day_slots = $wpdb->get_results($wpdb->prepare(
    "SELECT s.*, p.post_title as activity_title, i.post_title as instructor_name,
            (s.capacity - s.booked_count) as available_spots
     FROM {$wpdb->prefix}waza_slots s
     LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
     LEFT JOIN {$wpdb->posts} i ON s.instructor_id = i.ID
     WHERE DATE(s.start_datetime) = %s
     AND s.status = 'available'
     ORDER BY s.start_datetime ASC",
    $test_date
));

echo "Slots for {$test_date}: " . count($day_slots) . "\n\n";

if ($day_slots) {
    foreach ($day_slots as $slot) {
        echo "- {$slot->activity_title} at " . date('H:i', strtotime($slot->start_datetime)) . "\n";
    }
} else {
    echo "No slots found for this date.\n";
}

echo "\n=== END TEST ===\n";
