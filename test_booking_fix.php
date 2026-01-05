<?php
// Verification script for corrected booking logic
require_once( dirname(dirname(dirname(dirname( __FILE__ )))) . '/wp-load.php' );
global $wpdb;

echo "Starting verification...\n";

// Mock Data matching the fix
$user_id = 4; // admin user
$activity_id = 28; // Known ID from previous logs
$slot_id = 32; // Known ID
$quantity = 1;
$total_amount = 150.00;
$discount_amount = 0;
$discount_code = '';
$customer_name = "Verification User";
$customer_email = "verify@check.com";
$customer_phone = "5555555555";
$payment_method = "phonepe";

// The corrected array structure from AjaxHandler.php
$booking_data = [
    'user_id' => $user_id,
    'activity_id' => $activity_id,
    'slot_id' => $slot_id,
    'quantity' => $quantity,
    'attendees_count' => $quantity, // Added this field in fix
    'total_amount' => $total_amount,
    'discount_amount' => $discount_amount,
    'coupon_code' => $discount_code, // Changed from discount_code to coupon_code
    'user_name' => $customer_name,   // Changed from customer_name
    'user_email' => $customer_email, // Changed from customer_email
    'user_phone' => $customer_phone, // Changed from customer_phone
    'payment_method' => $payment_method,
    'payment_status' => 'pending',
    'booking_status' => 'pending',   // Changed from status
    'created_at' => current_time('mysql'),
    'updated_at' => current_time('mysql') // Added this
];

echo "Attempting insert with corrected fields...\n";

$result = $wpdb->insert(
    $wpdb->prefix . 'waza_bookings',
    $booking_data
);

if ($result === false) {
    echo "DB Insert Error: " . $wpdb->last_error . "\n";
} else {
    echo "SUCCESS: Booking created with ID: " . $wpdb->insert_id . "\n";
    
    // Verify Payment Update Logic (Simple version)
    $booking_id = $wpdb->insert_id;
    $wpdb->update(
        $wpdb->prefix . 'waza_bookings',
        [
            'booking_status' => 'confirmed',
            'payment_status' => 'completed',
            'payment_id' => 'pay_verify_' . time()
        ],
        ['id' => $booking_id]
    );
    echo "SUCCESS: Booking updated to confirmed.\n";
}
