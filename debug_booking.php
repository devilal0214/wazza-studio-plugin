<?php
// Test script for debugging process_booking logic
require_once( dirname(dirname(dirname(dirname( __FILE__ )))) . '/wp-load.php' );

$slot_id = 32; // Assuming this ID exists from previous tests or change as needed
$quantity = 1;
$customer_name = "Debug User";
$customer_email = "debug@test.com";
$customer_phone = "1112223333";
$payment_method = "phonepe";

echo "Starting debug...\n";

global $wpdb;

// 1. Get Slot
$slot = get_post($slot_id);
if (!$slot) {
    die("Slot not found\n");
}
echo "Slot found: " . $slot->ID . "\n";

// 2. Get Meta
$activity_id = get_post_meta($slot_id, '_waza_activity_id', true);
echo "Activity ID: " . $activity_id . "\n";

$price = floatval(get_post_meta($activity_id, '_waza_price', true));
echo "Price: " . $price . "\n";

$total_amount = $price * $quantity;
echo "Total Amount: " . $total_amount . "\n";

// 3. User Creation (Guest)
$user_id = 0; // Simulate guest
if (!$user_id) {
    echo "Creating guest user...\n";
    $user_id = username_exists($customer_email);
    if (!$user_id && email_exists($customer_email) == false) {
        $random_password = wp_generate_password($length = 12, $include_standard_special_chars = false);
        $user_id = wp_create_user($customer_email, $random_password, $customer_email);
        if (is_wp_error($user_id)) {
            echo "Error creating user: " . $user_id->get_error_message() . "\n";
        } else {
             wp_update_user([
                'ID' => $user_id, 
                'display_name' => $customer_name,
                'first_name' => $customer_name
             ]);
             echo "User created: " . $user_id . "\n";
        }
    } else {
        echo "User exists: " . $user_id . "\n";
    }
}

// 4. Insert Booking
$booking_data = [
    'user_id' => $user_id,
    'activity_id' => $activity_id,
    'slot_id' => $slot_id,
    'quantity' => $quantity,
    'total_amount' => $total_amount,
    'discount_amount' => 0,
    'discount_code' => '',
    'customer_name' => $customer_name,
    'customer_email' => $customer_email,
    'customer_phone' => $customer_phone,
    'payment_method' => $payment_method,
    'status' => 'pending',
    'booking_date' => current_time('mysql'),
    'created_at' => current_time('mysql')
];

echo "Inserting booking data...\n";
print_r($booking_data);

$result = $wpdb->insert(
    $wpdb->prefix . 'waza_bookings',
    $booking_data
    // omitting format array for auto-detection test
);

if ($result === false) {
    echo "DB Insert Error: " . $wpdb->last_error . "\n";
} else {
    echo "Booking ID: " . $wpdb->insert_id . "\n";
}

echo "Debug complete.\n";
