<?php
/**
 * Update slot statuses and create test future workshops
 */

// Load WordPress  
$wp_load = __DIR__ . '/../../../wp-load.php';
require_once $wp_load;
define('WP_CLI', true);

global $wpdb;

echo "=== Updating Slot Statuses ===\n\n";

// Update all 'available' status to 'active' (except cancelled ones)
$updated = $wpdb->query("
    UPDATE {$wpdb->prefix}waza_slots 
    SET status = 'active' 
    WHERE status = 'available'
");

echo "Updated {$updated} slots from 'available' to 'active'\n\n";

echo "=== Creating Future Test Workshops ===\n\n";

// Get Wazza Instructor by ID
$instructor = get_post(78);

if (!$instructor || $instructor->post_type !== 'waza_instructor') {
    echo "Instructor not found\n";
    exit;
}

$instructor_id = $instructor->ID;
echo "Instructor: {$instructor->post_title} (ID: {$instructor_id})\n\n";

// Get activities for this instructor
$activities = get_posts([
    'post_type' => 'waza_activity',
    'meta_key' => '_waza_instructor',
    'meta_value' => $instructor_id,
    'posts_per_page' => 3
]);

if (empty($activities)) {
    echo "No activities found for this instructor\n";
    exit;
}

$created = 0;
$start_date = strtotime('+2 days');

foreach ($activities as $activity) {
    // Create 2 future slots for each activity
    for ($i = 0; $i < 2; $i++) {
        $slot_date = strtotime("+{$i} weeks", $start_date);
        $start_time = date('Y-m-d 10:00:00', $slot_date);
        $end_time = date('Y-m-d 11:30:00', $slot_date);
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'waza_slots',
            [
                'activity_id' => $activity->ID,
                'instructor_id' => $instructor_id,
                'start_datetime' => $start_time,
                'end_datetime' => $end_time,
                'capacity' => 20,
                'price' => 1500,
                'status' => 'active',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ],
            ['%d', '%d', '%s', '%s', '%d', '%f', '%s', '%s', '%s']
        );
        
        if ($result) {
            echo "Created slot for {$activity->post_title} on {$start_time}\n";
            $created++;
        }
    }
}

echo "\nCreated {$created} new future workshop slots\n\n";

// Show summary
echo "=== Summary ===\n";
$total_active = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}waza_slots WHERE status = 'active'");
$total_pending = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}waza_slots WHERE status = 'pending_approval'");
$total_cancelled = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}waza_slots WHERE status = 'cancelled'");

echo "Active slots: {$total_active}\n";
echo "Pending approval: {$total_pending}\n";
echo "Cancelled: {$total_cancelled}\n";

echo "\nDone!\n";
