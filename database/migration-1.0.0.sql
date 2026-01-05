-- Waza Booking Plugin Database Migration
-- Version: 1.0.0
-- Description: Creates custom tables for high-concurrency booking management

-- Bookings table for managing all bookings with concurrency control
CREATE TABLE IF NOT EXISTS wp_waza_bookings (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    slot_id bigint(20) NOT NULL,
    user_id bigint(20) DEFAULT NULL,
    user_email varchar(100) NOT NULL,
    user_name varchar(100) NOT NULL,
    user_phone varchar(20) DEFAULT NULL,
    attendees_count int(11) NOT NULL DEFAULT 1,
    total_amount decimal(10,2) NOT NULL DEFAULT 0.00,
    discount_amount decimal(10,2) NOT NULL DEFAULT 0.00,
    coupon_code varchar(50) DEFAULT NULL,
    payment_status varchar(20) NOT NULL DEFAULT 'pending',
    payment_method varchar(50) DEFAULT NULL,
    payment_id varchar(255) DEFAULT NULL,
    payment_data longtext DEFAULT NULL,
    booking_status varchar(20) NOT NULL DEFAULT 'confirmed',
    booking_type varchar(20) NOT NULL DEFAULT 'regular',
    special_requests text DEFAULT NULL,
    qr_token varchar(255) DEFAULT NULL,
    attended tinyint(1) NOT NULL DEFAULT 0,
    attended_at datetime DEFAULT NULL,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY slot_id (slot_id),
    KEY user_id (user_id),
    KEY user_email (user_email),
    KEY payment_status (payment_status),
    KEY booking_status (booking_status),
    KEY qr_token (qr_token),
    KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- QR Tokens table for secure token management
CREATE TABLE IF NOT EXISTS wp_waza_qr_tokens (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    token varchar(255) NOT NULL,
    token_hash varchar(255) NOT NULL,
    booking_id bigint(20) NOT NULL,
    slot_id bigint(20) NOT NULL,
    token_type varchar(20) NOT NULL DEFAULT 'single',
    max_uses int(11) NOT NULL DEFAULT 1,
    used_count int(11) NOT NULL DEFAULT 0,
    expires_at datetime NOT NULL,
    is_active tinyint(1) NOT NULL DEFAULT 1,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_used_at datetime DEFAULT NULL,
    scanner_device varchar(100) DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY token_hash (token_hash),
    KEY booking_id (booking_id),
    KEY slot_id (slot_id),
    KEY expires_at (expires_at),
    KEY is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attendance table for detailed attendance tracking
CREATE TABLE IF NOT EXISTS wp_waza_attendance (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    booking_id bigint(20) NOT NULL,
    slot_id bigint(20) NOT NULL,
    user_id bigint(20) DEFAULT NULL,
    qr_token_id bigint(20) DEFAULT NULL,
    attendance_status varchar(20) NOT NULL DEFAULT 'present',
    check_in_time datetime NOT NULL,
    scanner_device varchar(100) DEFAULT NULL,
    scanner_user_id bigint(20) DEFAULT NULL,
    notes text DEFAULT NULL,
    ip_address varchar(45) DEFAULT NULL,
    user_agent text DEFAULT NULL,
    PRIMARY KEY (id),
    KEY booking_id (booking_id),
    KEY slot_id (slot_id),
    KEY user_id (user_id),
    KEY check_in_time (check_in_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payments table for detailed payment tracking
CREATE TABLE IF NOT EXISTS wp_waza_payments (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    booking_id bigint(20) NOT NULL,
    payment_method varchar(50) NOT NULL,
    payment_gateway varchar(50) NOT NULL,
    gateway_payment_id varchar(255) NOT NULL,
    gateway_order_id varchar(255) DEFAULT NULL,
    amount decimal(10,2) NOT NULL,
    currency varchar(10) NOT NULL DEFAULT 'INR',
    status varchar(20) NOT NULL DEFAULT 'pending',
    gateway_response longtext DEFAULT NULL,
    refund_amount decimal(10,2) NOT NULL DEFAULT 0.00,
    refund_status varchar(20) DEFAULT NULL,
    refund_id varchar(255) DEFAULT NULL,
    refund_reason text DEFAULT NULL,
    webhook_verified tinyint(1) NOT NULL DEFAULT 0,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY gateway_payment_id (gateway_payment_id),
    KEY booking_id (booking_id),
    KEY status (status),
    KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Waitlist table for managing waiting lists
CREATE TABLE IF NOT EXISTS wp_waza_waitlist (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    slot_id bigint(20) NOT NULL,
    user_id bigint(20) DEFAULT NULL,
    user_email varchar(100) NOT NULL,
    user_name varchar(100) NOT NULL,
    user_phone varchar(20) DEFAULT NULL,
    requested_seats int(11) NOT NULL DEFAULT 1,
    priority int(11) NOT NULL DEFAULT 0,
    status varchar(20) NOT NULL DEFAULT 'waiting',
    notified_at datetime DEFAULT NULL,
    expires_at datetime DEFAULT NULL,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY slot_id (slot_id),
    KEY user_email (user_email),
    KEY status (status),
    KEY priority (priority),
    KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;