-- Add Attendance Tracking Table
CREATE TABLE IF NOT EXISTS wp_waza_attendance (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    booking_id bigint(20) NOT NULL,
    slot_id bigint(20) NOT NULL,
    user_id bigint(20) NOT NULL,
    check_in_time datetime DEFAULT NULL,
    check_out_time datetime DEFAULT NULL,
    marked_by bigint(20) DEFAULT NULL COMMENT 'Admin/Instructor who marked attendance',
    entry_method varchar(20) DEFAULT 'qr' COMMENT 'qr, manual',
    exit_method varchar(20) DEFAULT 'auto' COMMENT 'auto, manual, qr',
    notes text DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY booking_id (booking_id),
    KEY slot_id (slot_id),
    KEY user_id (user_id),
    KEY check_in_time (check_in_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add Booking Attendees Table for Multi-Seat Bookings
CREATE TABLE IF NOT EXISTS wp_waza_booking_attendees (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    booking_id bigint(20) NOT NULL,
    attendee_name varchar(100) NOT NULL,
    attendee_email varchar(100) DEFAULT NULL,
    attendee_phone varchar(20) DEFAULT NULL,
    seat_number int(11) NOT NULL DEFAULT 1,
    qr_token varchar(255) NOT NULL,
    user_id bigint(20) DEFAULT NULL COMMENT 'Optional individual account',
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY booking_id (booking_id),
    UNIQUE KEY qr_token (qr_token),
    KEY user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes to existing bookings table for better performance
ALTER TABLE wp_waza_bookings 
ADD INDEX idx_attended (attended),
ADD INDEX idx_qr_token (qr_token);
