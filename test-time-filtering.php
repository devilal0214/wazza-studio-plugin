<?php
/**
 * Test Time-Based Slot Filtering
 */

require_once __DIR__ . '/../../../wp-load.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== SLOT TIME FILTERING TEST ===\n\n";

$current_time = current_time('mysql');
$current_date = current_time('Y-m-d');
$current_time_display = current_time('g:i A');

echo "Current DateTime: {$current_time}\n";
echo "Current Date: {$current_date}\n";
echo "Current Time: {$current_time_display}\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

global $wpdb;

// Get all slots for today
$today_slots_all = $wpdb->get_results($wpdb->prepare(
    "SELECT s.*, p.post_title as activity_title,
            (s.capacity - s.booked_count) as available_spots
     FROM {$wpdb->prefix}waza_slots s
     LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
     WHERE DATE(s.start_datetime) = %s
     ORDER BY s.start_datetime ASC",
    $current_date
));

echo "ALL SLOTS FOR TODAY ({$current_date}):\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

if ($today_slots_all) {
    foreach ($today_slots_all as $slot) {
        $slot_time = date('g:i A', strtotime($slot->start_datetime));
        $is_past = strtotime($slot->start_datetime) < strtotime($current_time);
        $status = $is_past ? '❌ PAST' : '✅ FUTURE';
        
        echo "ID: {$slot->id}\n";
        echo "Activity: {$slot->activity_title}\n";
        echo "Time: {$slot_time}\n";
        echo "Start DateTime: {$slot->start_datetime}\n";
        echo "Status: {$status}\n";
        echo "Available: {$slot->available_spots}/{$slot->capacity}\n";
        echo "---\n";
    }
} else {
    echo "No slots found for today.\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Test the filtered query (what the calendar uses)
$today_slots_future = $wpdb->get_results($wpdb->prepare(
    "SELECT s.*, p.post_title as activity_title,
            (s.capacity - s.booked_count) as available_spots
     FROM {$wpdb->prefix}waza_slots s
     LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
     WHERE DATE(s.start_datetime) = %s
     AND s.status = 'available'
     AND s.start_datetime >= %s
     ORDER BY s.start_datetime ASC",
    $current_date,
    $current_time
));

echo "FUTURE SLOTS FOR TODAY (What Calendar Shows):\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

if ($today_slots_future) {
    foreach ($today_slots_future as $slot) {
        $slot_time = date('g:i A', strtotime($slot->start_datetime));
        
        echo "✅ ID: {$slot->id}\n";
        echo "   Activity: {$slot->activity_title}\n";
        echo "   Time: {$slot_time}\n";
        echo "   Available: {$slot->available_spots}/{$slot->capacity}\n";
        echo "---\n";
    }
    echo "\nTotal future slots: " . count($today_slots_future) . "\n";
} else {
    echo "No future slots available for today.\n";
    echo "All slots have passed or are full.\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "EXPECTED BEHAVIOR:\n";
echo "- Current time is 7:44 PM (19:44)\n";
echo "- Slot at 7:00 AM (07:00) should be ❌ PAST (not shown in calendar)\n";
echo "- Slot at 11:00 AM (11:00) should be ❌ PAST (not shown in calendar)\n";
echo "- Only future slots should appear in the 'FUTURE SLOTS' section above\n\n";

echo "RESULT:\n";
if (count($today_slots_future) === 0 && count($today_slots_all) > 0) {
    echo "✅ CORRECT - All today's slots have passed, calendar should show:\n";
    echo "   'No more available time slots for today. All slots have passed or are full.'\n";
    echo "   Today (Jan 3) should NOT have green background in calendar.\n";
} elseif (count($today_slots_future) > 0) {
    echo "⚠️  Calendar will show " . count($today_slots_future) . " slot(s) for today\n";
    echo "   These slots are still in the future.\n";
} else {
    echo "ℹ️  No slots exist for today in database.\n";
}

echo "\n=== END TEST ===\n";
