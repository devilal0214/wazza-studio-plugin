<?php
/**
 * Debug Slot Timezone - Check if slots are stored in UTC or IST
 */

// Load WordPress
require_once('../../../wp-load.php');

global $wpdb;

// Get booking #58
$booking = $wpdb->get_row($wpdb->prepare("
    SELECT b.*, s.start_datetime, s.end_datetime
    FROM {$wpdb->prefix}waza_bookings b
    LEFT JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
    WHERE b.id = %d
", 58));

if (!$booking) {
    die('Booking #58 not found');
}

echo "<h1>Slot Timezone Debug for Booking #58</h1>";
echo "<hr>";

echo "<h2>Raw Database Values:</h2>";
echo "<strong>start_datetime:</strong> " . $booking->start_datetime . "<br>";
echo "<strong>end_datetime:</strong> " . $booking->end_datetime . "<br>";
echo "<hr>";

echo "<h2>WordPress Timezone Setting:</h2>";
echo "<strong>wp_timezone_string():</strong> " . wp_timezone_string() . "<br>";
echo "<strong>get_option('timezone_string'):</strong> " . get_option('timezone_string') . "<br>";
echo "<strong>get_option('gmt_offset'):</strong> " . get_option('gmt_offset') . "<br>";
echo "<hr>";

echo "<h2>Interpretation 1: Database is UTC</h2>";
$start_dt_utc = new DateTime($booking->start_datetime, new DateTimeZone('UTC'));
$start_dt_utc->setTimezone(new DateTimeZone(wp_timezone_string()));
$end_dt_utc = new DateTime($booking->end_datetime, new DateTimeZone('UTC'));
$end_dt_utc->setTimezone(new DateTimeZone(wp_timezone_string()));

echo "<strong>If UTC → IST:</strong><br>";
echo "Date: " . $start_dt_utc->format('F j, Y') . "<br>";
echo "Time: " . $start_dt_utc->format('g:i A') . " - " . $end_dt_utc->format('g:i A') . "<br>";
echo "<hr>";

echo "<h2>Interpretation 2: Database is already IST</h2>";
$start_dt_ist = new DateTime($booking->start_datetime, new DateTimeZone(wp_timezone_string()));
$end_dt_ist = new DateTime($booking->end_datetime, new DateTimeZone(wp_timezone_string()));

echo "<strong>If IST (no conversion):</strong><br>";
echo "Date: " . $start_dt_ist->format('F j, Y') . "<br>";
echo "Time: " . $start_dt_ist->format('g:i A') . " - " . $end_dt_ist->format('g:i A') . "<br>";
echo "<hr>";

echo "<h2>Current Time:</h2>";
echo "<strong>current_time('mysql'):</strong> " . current_time('mysql') . "<br>";
echo "<strong>current_time('mysql', true) [GMT]:</strong> " . current_time('mysql', true) . "<br>";
echo "<strong>date('Y-m-d H:i:s'):</strong> " . date('Y-m-d H:i:s') . "<br>";
echo "<hr>";

echo "<h2>Expected Result:</h2>";
echo "<strong>Should show:</strong> January 22, 2026 at 12:00 PM - 1:00 PM<br>";
echo "<hr>";

echo "<h2>Conclusion:</h2>";
echo "Compare the two interpretations above with the expected result.<br>";
echo "The one that matches is the correct interpretation.<br>";
