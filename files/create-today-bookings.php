<?php
/**
 * Create sample bookings for today to test the scanner
 */

require_once __DIR__ . '/../../../wp-load.php';

global $wpdb;

echo "=== CREATING SAMPLE BOOKINGS FOR TODAY ===\n\n";

$today = current_time('Y-m-d');

// Find or create a slot for today
$today_slot = $wpdb->get_row($wpdb->prepare("
    SELECT * FROM {$wpdb->prefix}waza_slots 
    WHERE DATE(start_datetime) = %s 
    AND status = 'active'
    LIMIT 1
", $today));

if (!$today_slot) {
    echo "Creating a new slot for today...\n";
    
    // Get an activity
    $activity = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'waza_activity' AND post_status = 'publish' LIMIT 1");
    
    if (!$activity) {
        echo "❌ No activities found. Please create an activity first.\n";
        exit;
    }
    
    // Get an instructor
    $instructor = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'waza_instructor' AND post_status = 'publish' LIMIT 1");
    
    // Create slot for today, 2 hours from now
    $start_time = date('Y-m-d H:i:s', strtotime('+2 hours'));
    $end_time = date('Y-m-d H:i:s', strtotime('+4 hours'));
    
    $wpdb->insert(
        "{$wpdb->prefix}waza_slots",
        [
            'activity_id' => $activity,
            'instructor_id' => $instructor,
            'start_datetime' => $start_time,
            'end_datetime' => $end_time,
            'capacity' => 10,
            'price' => 500,
            'booked_count' => 0,
            'status' => 'active',
            'location' => 'Main Studio',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ],
        ['%d', '%d', '%s', '%s', '%d', '%f', '%d', '%s', '%s', '%s', '%s']
    );
    
    $today_slot = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}waza_slots WHERE id = %d", $wpdb->insert_id));
    echo "✓ Created slot ID: {$today_slot->id}\n";
}

echo "Using slot ID: {$today_slot->id}\n";
$activity_title = $wpdb->get_var($wpdb->prepare("SELECT post_title FROM {$wpdb->posts} WHERE ID = %d", $today_slot->activity_id));
echo "Activity: {$activity_title}\n";
echo "Time: " . date('g:i A', strtotime($today_slot->start_datetime)) . " - " . date('g:i A', strtotime($today_slot->end_datetime)) . "\n\n";

// Create 3 types of bookings:
// 1. Single booking (1 person) - confirmed, paid
// 2. Group booking (5 people) - confirmed, paid
// 3. Pending payment booking - confirmed, but payment pending

// Type 1: Single booking
echo "1. Creating SINGLE booking (valid, should work)...\n";
$booking1_id = $wpdb->insert(
    "{$wpdb->prefix}waza_bookings",
    [
        'slot_id' => $today_slot->id,
        'user_id' => 11, // Student 1
        'user_name' => 'Test Student Single',
        'user_email' => 'single@test.com',
        'user_phone' => '1234567890',
        'attendees_count' => 1,
        'total_amount' => 500,
        'payment_status' => 'completed',
        'booking_status' => 'confirmed',
        'payment_method' => 'razorpay',
        'qr_token' => wp_generate_uuid4(),
        'created_at' => current_time('mysql')
    ],
    ['%d', '%d', '%s', '%s', '%s', '%d', '%f', '%s', '%s', '%s', '%s', '%s']
);

if ($booking1_id) {
    $booking1_id = $wpdb->insert_id;
    echo "   ✓ Created booking ID: {$booking1_id}\n";
    echo "   Status: confirmed | Payment: completed\n";
    echo "   Attendees: 1\n";
}

// Type 2: Group booking (5 people)
echo "\n2. Creating GROUP booking (5 people, valid)...\n";
$booking2_id = $wpdb->insert(
    "{$wpdb->prefix}waza_bookings",
    [
        'slot_id' => $today_slot->id,
        'user_id' => 12, // Student 2
        'user_name' => 'Test Student Group',
        'user_email' => 'group@test.com',
        'user_phone' => '0987654321',
        'attendees_count' => 5,
        'total_amount' => 2500,
        'payment_status' => 'completed',
        'booking_status' => 'confirmed',
        'payment_method' => 'stripe',
        'qr_token' => wp_generate_uuid4(),
        'created_at' => current_time('mysql')
    ],
    ['%d', '%d', '%s', '%s', '%s', '%d', '%f', '%s', '%s', '%s', '%s', '%s']
);

if ($booking2_id) {
    $booking2_id = $wpdb->insert_id;
    echo "   ✓ Created booking ID: {$booking2_id}\n";
    echo "   Status: confirmed | Payment: completed\n";
    echo "   Attendees: 5 (group booking)\n";
}

// Type 3: Payment pending
echo "\n3. Creating PENDING PAYMENT booking (should fail validation)...\n";
$booking3_id = $wpdb->insert(
    "{$wpdb->prefix}waza_bookings",
    [
        'slot_id' => $today_slot->id,
        'user_id' => 13, // Student 3
        'user_name' => 'Test Student Pending',
        'user_email' => 'pending@test.com',
        'user_phone' => '5555555555',
        'attendees_count' => 1,
        'total_amount' => 500,
        'payment_status' => 'pending',
        'booking_status' => 'confirmed',
        'payment_method' => 'cod',
        'qr_token' => wp_generate_uuid4(),
        'created_at' => current_time('mysql')
    ],
    ['%d', '%d', '%s', '%s', '%s', '%d', '%f', '%s', '%s', '%s', '%s', '%s']
);

if ($booking3_id) {
    $booking3_id = $wpdb->insert_id;
    echo "   ✓ Created booking ID: {$booking3_id}\n";
    echo "   Status: confirmed | Payment: PENDING ⚠️\n";
    echo "   Attendees: 1\n";
}

echo "\n=== SUMMARY ===\n";
echo "Test bookings created for " . date('F j, Y') . "\n\n";

echo "To test the scanner:\n";
echo "1. Go to WordPress Admin → Wazza Studio → 📱 Scanner\n";
echo "2. Click 'Manual Search' tab\n";
echo "3. Try these booking IDs:\n\n";

if ($booking1_id) {
    echo "   Booking ID {$booking1_id} - ✅ Should PASS (single, paid)\n";
}
if ($booking2_id) {
    echo "   Booking ID {$booking2_id} - ✅ Should PASS (group of 5, paid)\n";
    echo "      → Try scanning 5 times to mark all attendees\n";
    echo "      → 6th scan should fail (capacity reached)\n";
}
if ($booking3_id) {
    echo "   Booking ID {$booking3_id} - ❌ Should FAIL (payment pending)\n";
}

echo "\n✅ Sample data created successfully!\n";
