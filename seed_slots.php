<?php
// seed_slots.php
require_once( dirname(dirname(dirname(dirname( __FILE__ )))) . '/wp-load.php' );

// 1. Get or Create an Instructor
$instructor = get_page_by_title('Demo Instructor', OBJECT, 'waza_instructor');
if (!$instructor) {
    $instructor_id = wp_insert_post([
        'post_type' => 'waza_instructor',
        'post_title' => 'Demo Instructor',
        'post_status' => 'publish',
        'meta_input' => [
            '_waza_bio' => 'Expert yoga instructor.',
            '_waza_experience' => '5 years'
        ]
    ]);
} else {
    $instructor_id = $instructor->ID;
}

// 2. Get or Create an Activity
$activity = get_page_by_title('Morning Yoga', OBJECT, 'waza_activity');
if (!$activity) {
    $activity_id = wp_insert_post([
        'post_type' => 'waza_activity',
        'post_title' => 'Morning Yoga',
        'post_status' => 'publish',
        'meta_input' => [
            '_waza_duration' => '60',
            '_waza_price' => '150',
            '_waza_skill_level' => 'Beginner'
        ]
    ]);
} else {
    $activity_id = $activity->ID;
}

echo "Using Instructor ID: $instructor_id, Activity ID: $activity_id<br>";

// 3. Create Slots for Next 5 Days
$start_date = new DateTime();
for ($i = 0; $i < 5; $i++) {
    $current_date = clone $start_date;
    $current_date->modify("+$i days");
    $date_str = $current_date->format('Y-m-d');
    
    // Create 3 slots per day: 09:00, 14:00, 18:00
    $times = ['09:00', '14:00', '18:00'];
    
    foreach ($times as $time) {
        $end_time = date('H:i', strtotime("$time +1 hour"));
        
        // Check if slot exists
        $args = [
            'post_type' => 'waza_slot',
            'meta_query' => [
                'relation' => 'AND',
                ['key' => '_waza_start_date', 'value' => $date_str],
                ['key' => '_waza_start_time', 'value' => $time]
            ]
        ];
        $query = new WP_Query($args);
        
        if (!$query->have_posts()) {
            $slot_id = wp_insert_post([
                'post_type' => 'waza_slot',
                'post_title' => "Yoga $date_str $time",
                'post_status' => 'publish',
                'meta_input' => [
                    '_waza_start_date' => $date_str,
                    '_waza_start_time' => $time,
                    '_waza_end_time' => $end_time,
                    '_waza_activity_id' => $activity_id,
                    '_waza_instructor_id' => $instructor_id,
                    '_waza_capacity' => 20,
                    '_waza_booked_seats' => 0,
                    '_waza_price' => 150,
                    '_waza_room' => 'Studio A'
                ]
            ]);
            echo "Created Slot: $date_str at $time (ID: $slot_id)<br>";
        } else {
            echo "Slot already exists: $date_str at $time<br>";
        }
    }
}
echo "Seeding complete.";
