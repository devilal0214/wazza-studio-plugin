<?php
/**
 * Create sample attendance data for testing
 */

// Load WordPress  
$wp_load = __DIR__ . '/../../../wp-load.php';
require_once $wp_load;
define('WP_CLI', true);

global $wpdb;

echo "=== CREATING SAMPLE ATTENDANCE DATA ===\n\n";

// Get instructor user
$instructor_user = get_user_by('email', 'instructor@waza.studio');
if (!$instructor_user) {
    die("Instructor user not found\n");
}

// Get some active slots for Wazza Instructor (ID 78)
$slots = $wpdb->get_results("
    SELECT s.*, p.post_title
    FROM {$wpdb->prefix}waza_slots s
    LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_waza_instructor'
    WHERE pm.meta_value = 78
    AND s.status IN ('active', 'available')
    AND s.start_datetime <= NOW()
    ORDER BY s.start_datetime DESC
    LIMIT 3
");

echo "Found " . count($slots) . " past slots for sample data\n\n";

if (empty($slots)) {
    echo "No past slots found. Creating a past slot...\n";
    
    // Get Morning Yoga activity (ID 28)
    $activity_id = 28;
    
    // Create a past slot
    $wpdb->insert(
        $wpdb->prefix . 'waza_slots',
        [
            'activity_id' => $activity_id,
            'instructor_id' => 78,
            'start_datetime' => date('Y-m-d 09:00:00', strtotime('-1 day')),
            'end_datetime' => date('Y-m-d 10:30:00', strtotime('-1 day')),
            'capacity' => 20,
            'price' => 1500,
            'status' => 'active',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ],
        ['%d', '%d', '%s', '%s', '%d', '%f', '%s', '%s', '%s']
    );
    
    $slot_id = $wpdb->insert_id;
    echo "Created past slot ID: {$slot_id}\n";
    
    $slots = [$wpdb->get_row("SELECT * FROM {$wpdb->prefix}waza_slots WHERE id = {$slot_id}")];
}

$created_bookings = 0;
$created_attendance = 0;

foreach ($slots as $slot) {
    echo "\nProcessing slot {$slot->id}: {$slot->post_title}\n";
    
    // Create 3 sample students and bookings
    for ($i = 1; $i <= 3; $i++) {
        $student_email = "student{$i}@waza.studio";
        
        // Get or create student user
        $student = get_user_by('email', $student_email);
        if (!$student) {
            $student_id = wp_create_user($student_email, 'password123', $student_email);
            wp_update_user([
                'ID' => $student_id,
                'display_name' => "Student {$i}",
                'first_name' => "Student",
                'last_name' => "{$i}"
            ]);
            echo "  Created user: {$student_email} (ID: {$student_id})\n";
        } else {
            $student_id = $student->ID;
        }
        
        // Create booking
        $booking_id = $wpdb->insert(
            $wpdb->prefix . 'waza_bookings',
            [
                'slot_id' => $slot->id,
                'user_id' => $student_id,
                'user_email' => $student_email,
                'user_name' => "Student {$i}",
                'user_phone' => "123456789{$i}",
                'attendees_count' => 1,
                'total_amount' => $slot->price,
                'payment_status' => 'completed',
                'payment_method' => 'cash',
                'booking_status' => 'confirmed',
                'attended' => 1,
                'attended_at' => date('Y-m-d H:i:s', strtotime($slot->start_datetime . ' +5 minutes')),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ],
            ['%d', '%d', '%s', '%s', '%s', '%d', '%f', '%s', '%s', '%s', '%d', '%s', '%s', '%s']
        );
        
        if ($booking_id) {
            $booking_id = $wpdb->insert_id;
            $created_bookings++;
            echo "  Created booking ID: {$booking_id}\n";
            
            // Create attendance record
            $att_id = $wpdb->insert(
                $wpdb->prefix . 'waza_attendance',
                [
                    'booking_id' => $booking_id,
                    'slot_id' => $slot->id,
                    'user_id' => $student_id,
                    'attendance_status' => 'present',
                    'check_in_time' => date('Y-m-d H:i:s', strtotime($slot->start_datetime . ' +5 minutes')),
                    'scanner_device' => 'QR Scanner',
                    'ip_address' => '127.0.0.1'
                ],
                ['%d', '%d', '%d', '%s', '%s', '%s', '%s']
            );
            
            if ($att_id) {
                $created_attendance++;
                echo "  Created attendance record ID: {$wpdb->insert_id}\n";
            }
        }
    }
}

echo "\n=== CREATION COMPLETE ===\n";
echo "Bookings created: {$created_bookings}\n";
echo "Attendance records created: {$created_attendance}\n";

// Verify
$total_bookings = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}waza_bookings");
$total_attendance = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}waza_attendance");

echo "\nFinal counts:\n";
echo "Total bookings: {$total_bookings}\n";
echo "Total attendance: {$total_attendance}\n";

echo "\n✓ Sample data created successfully!\n";
