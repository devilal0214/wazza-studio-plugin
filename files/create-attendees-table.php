<?php
define('WP_USE_THEMES', false);
require_once(__DIR__ . '/../../../wp-load.php');

global $wpdb;

$result = $wpdb->get_results("SHOW TABLES LIKE '%booking_attendees%'");
echo "Booking attendees table:\n";
print_r($result);

if (empty($result)) {
    echo "\nTable doesn't exist. Creating it now...\n";
    $wpdb->query("
        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}waza_booking_attendees (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            booking_id bigint(20) NOT NULL,
            attendee_name varchar(100) NOT NULL,
            attendee_email varchar(100) DEFAULT NULL,
            attendee_phone varchar(20) DEFAULT NULL,
            seat_number int(11) NOT NULL DEFAULT 1,
            qr_token varchar(255) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY booking_id (booking_id),
            UNIQUE KEY qr_token (qr_token),
            KEY user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Table created!\n";
}
