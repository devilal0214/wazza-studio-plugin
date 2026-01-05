<?php
/**
 * Create Test Slots for Current Month
 * Creates slots matching those shown in the admin screenshot
 */

require_once __DIR__ . '/../../../wp-load.php';

if (!current_user_can('manage_options')) {
    // Allow from command line
    if (php_sapi_name() !== 'cli') {
        die('Unauthorized');
    }
}

echo "Creating test slots for January 2026...\n\n";

// Get or create activity and instructor
$activities = get_posts(['post_type' => 'waza_activity', 'posts_per_page' => 1]);
$instructors = get_posts(['post_type' => 'waza_instructor', 'posts_per_page' => 1]);

if (empty($activities)) {
    echo "Creating Morning Yoga activity...\n";
    $activity_id = wp_insert_post([
        'post_title' => 'Morning Yoga',
        'post_type' => 'waza_activity',
        'post_status' => 'publish'
    ]);
    update_post_meta($activity_id, '_waza_price', '150');
    update_post_meta($activity_id, '_waza_duration', '120');
} else {
    $activity_id = $activities[0]->ID;
}

if (empty($instructors)) {
    echo "Creating instructor...\n";
    $instructor_id = wp_insert_post([
        'post_title' => 'Sarah Johnson',
        'post_type' => 'waza_instructor',
        'post_status' => 'publish'
    ]);
} else {
    $instructor_id = $instructors[0]->ID;
}

// Create Zumba activity
$zumba_posts = get_posts(['post_type' => 'waza_activity', 'title' => 'Zumba Fitness', 'posts_per_page' => 1]);
if (empty($zumba_posts)) {
    echo "Creating Zumba Fitness activity...\n";
    $zumba_id = wp_insert_post([
        'post_title' => 'Zumba Fitness',
        'post_type' => 'waza_activity',
        'post_status' => 'publish'
    ]);
    update_post_meta($zumba_id, '_waza_price', '120');
    update_post_meta($zumba_id, '_waza_duration', '120');
} else {
    $zumba_id = $zumba_posts[0]->ID;
}

// Slots to create (matching screenshot)
$slots_to_create = [
    // Jan 3, 2026 - Morning Yoga
    [
        'date' => '2026-01-03',
        'start' => '07:00',
        'end' => '09:00',
        'activity_id' => $activity_id,
        'activity_name' => 'Morning Yoga',
        'capacity' => 15
    ],
    [
        'date' => '2026-01-03',
        'start' => '11:03',
        'end' => '14:02',
        'activity_id' => $activity_id,
        'activity_name' => 'Morning Yoga',
        'capacity' => 15
    ],
    // Jan 5, 2026 - Morning Yoga
    [
        'date' => '2026-01-05',
        'start' => '07:00',
        'end' => '09:00',
        'activity_id' => $activity_id,
        'activity_name' => 'Morning Yoga',
        'capacity' => 15
    ],
    // Jan 6, 2026 - Zumba Fitness
    [
        'date' => '2026-01-06',
        'start' => '08:00',
        'end' => '10:00',
        'activity_id' => $zumba_id,
        'activity_name' => 'Zumba Fitness',
        'capacity' => 15
    ],
    // Additional slots for testing
    [
        'date' => '2026-01-08',
        'start' => '10:00',
        'end' => '12:00',
        'activity_id' => $activity_id,
        'activity_name' => 'Morning Yoga',
        'capacity' => 20
    ],
    [
        'date' => '2026-01-08',
        'start' => '14:00',
        'end' => '16:00',
        'activity_id' => $activity_id,
        'activity_name' => 'Morning Yoga',
        'capacity' => 20
    ],
    [
        'date' => '2026-01-10',
        'start' => '09:00',
        'end' => '11:00',
        'activity_id' => $zumba_id,
        'activity_name' => 'Zumba Fitness',
        'capacity' => 25
    ],
    [
        'date' => '2026-01-12',
        'start' => '07:00',
        'end' => '09:00',
        'activity_id' => $activity_id,
        'activity_name' => 'Morning Yoga',
        'capacity' => 15
    ],
    [
        'date' => '2026-01-15',
        'start' => '10:00',
        'end' => '12:00',
        'activity_id' => $zumba_id,
        'activity_name' => 'Zumba Fitness',
        'capacity' => 20
    ],
    [
        'date' => '2026-01-18',
        'start' => '14:00',
        'end' => '16:00',
        'activity_id' => $activity_id,
        'activity_name' => 'Morning Yoga',
        'capacity' => 15
    ],
    [
        'date' => '2026-01-20',
        'start' => '09:00',
        'end' => '11:00',
        'activity_id' => $zumba_id,
        'activity_name' => 'Zumba Fitness',
        'capacity' => 25
    ],
    [
        'date' => '2026-01-22',
        'start' => '07:00',
        'end' => '09:00',
        'activity_id' => $activity_id,
        'activity_name' => 'Morning Yoga',
        'capacity' => 15
    ],
    [
        'date' => '2026-01-25',
        'start' => '10:00',
        'end' => '12:00',
        'activity_id' => $activity_id,
        'activity_name' => 'Morning Yoga',
        'capacity' => 20
    ],
    [
        'date' => '2026-01-28',
        'start' => '14:00',
        'end' => '16:00',
        'activity_id' => $zumba_id,
        'activity_name' => 'Zumba Fitness',
        'capacity' => 20
    ]
];

$created = 0;
$skipped = 0;

foreach ($slots_to_create as $slot) {
    // Check if slot already exists
    $existing = get_posts([
        'post_type' => 'waza_slot',
        'posts_per_page' => 1,
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => '_waza_start_date',
                'value' => $slot['date'],
                'compare' => '='
            ],
            [
                'key' => '_waza_start_time',
                'value' => $slot['start'],
                'compare' => '='
            ],
            [
                'key' => '_waza_activity_id',
                'value' => $slot['activity_id'],
                'compare' => '='
            ]
        ]
    ]);

    if (!empty($existing)) {
        echo "⏭️  Skipping {$slot['date']} {$slot['start']} - already exists\n";
        $skipped++;
        continue;
    }

    // Create slot post
    $post_id = wp_insert_post([
        'post_title' => "{$slot['activity_name']} {$slot['date']} {$slot['start']}",
        'post_type' => 'waza_slot',
        'post_status' => 'publish'
    ]);

    if (is_wp_error($post_id)) {
        echo "❌ Error creating slot: " . $post_id->get_error_message() . "\n";
        continue;
    }

    // Add meta data
    update_post_meta($post_id, '_waza_start_date', $slot['date']);
    update_post_meta($post_id, '_waza_start_time', $slot['start']);
    update_post_meta($post_id, '_waza_end_time', $slot['end']);
    update_post_meta($post_id, '_waza_activity_id', $slot['activity_id']);
    update_post_meta($post_id, '_waza_instructor_id', $instructor_id);
    update_post_meta($post_id, '_waza_capacity', $slot['capacity']);
    update_post_meta($post_id, '_waza_booked_seats', 0);
    update_post_meta($post_id, '_waza_price', get_post_meta($slot['activity_id'], '_waza_price', true));

    echo "✅ Created: {$slot['activity_name']} on {$slot['date']} at {$slot['start']}-{$slot['end']} (ID: {$post_id})\n";
    $created++;
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ Created: {$created} slots\n";
echo "⏭️  Skipped: {$skipped} slots (already exist)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "Now test the calendar at:\n";
echo "http://localhost/wazza/wp-content/plugins/waza-studio-app/test-calendar-debug.php\n\n";

echo "Or add this shortcode to any page:\n";
echo "[waza_booking_calendar]\n";
