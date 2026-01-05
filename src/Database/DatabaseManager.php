<?php
/**
 * Database Manager
 * 
 * @package WazaBooking\Database
 */

namespace WazaBooking\Database;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database Manager Class
 * Handles custom table creation and database operations
 */
class DatabaseManager {
    
    /**
     * Database version
     */
    const DB_VERSION = '1.1.0';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('plugins_loaded', [$this, 'check_database_version']);
    }
    
    /**
     * Check if database needs updating
     */
    public function check_database_version() {
        $installed_version = get_option('waza_booking_db_version', '0');
        
        if (version_compare($installed_version, self::DB_VERSION, '<')) {
            $this->create_tables();
            update_option('waza_booking_db_version', self::DB_VERSION);
        }
    }
    
    /**
     * Create custom database tables
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Bookings table for high-concurrency support
        $bookings_table = $wpdb->prefix . 'waza_bookings';
        $bookings_sql = "CREATE TABLE IF NOT EXISTS {$bookings_table} (
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
        ) {$charset_collate};";
        
        // QR Tokens table
        $qr_tokens_table = $wpdb->prefix . 'waza_qr_tokens';
        $qr_tokens_sql = "CREATE TABLE IF NOT EXISTS {$qr_tokens_table} (
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
        ) {$charset_collate};";
        
        // Attendance table for detailed tracking
        $attendance_table = $wpdb->prefix . 'waza_attendance';
        $attendance_sql = "CREATE TABLE IF NOT EXISTS {$attendance_table} (
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
        ) {$charset_collate};";
        
        // Payments table for detailed payment tracking
        $payments_table = $wpdb->prefix . 'waza_payments';
        $payments_sql = "CREATE TABLE IF NOT EXISTS {$payments_table} (
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
        ) {$charset_collate};";
        
        // Waitlist table
        $waitlist_table = $wpdb->prefix . 'waza_waitlist';
        $waitlist_sql = "CREATE TABLE IF NOT EXISTS {$waitlist_table} (
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
        ) {$charset_collate};";
        
        // Slots table for activity scheduling
        $slots_table = $wpdb->prefix . 'waza_slots';
        $slots_sql = "CREATE TABLE IF NOT EXISTS {$slots_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            activity_id bigint(20) NOT NULL,
            instructor_id bigint(20) DEFAULT NULL,
            start_datetime datetime NOT NULL,
            end_datetime datetime NOT NULL,
            capacity int(11) NOT NULL DEFAULT 10,
            booked_count int(11) NOT NULL DEFAULT 0,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            status varchar(20) NOT NULL DEFAULT 'available',
            location varchar(255) DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY activity_id (activity_id),
            KEY instructor_id (instructor_id),
            KEY start_datetime (start_datetime),
            KEY status (status)
        ) {$charset_collate};";
        
        // Email Templates table
        $email_templates_table = $wpdb->prefix . 'waza_email_templates';
        $email_templates_sql = "CREATE TABLE IF NOT EXISTS {$email_templates_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            template_type varchar(50) NOT NULL,
            subject varchar(255) NOT NULL,
            content longtext NOT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY template_type (template_type),
            KEY is_active (is_active)
        ) {$charset_collate};";
        
        // Workshops table
        $workshops_table = $wpdb->prefix . 'waza_workshops';
        $workshops_sql = "CREATE TABLE IF NOT EXISTS {$workshops_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            booking_id bigint(20) NOT NULL,
            workshop_title varchar(255) NOT NULL,
            workshop_description text DEFAULT NULL,
            instructor_id bigint(20) NOT NULL,
            max_students int(11) NOT NULL DEFAULT 0,
            is_paid tinyint(1) NOT NULL DEFAULT 0,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            invite_token varchar(255) NOT NULL,
            invite_link varchar(500) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY invite_token (invite_token),
            KEY booking_id (booking_id),
            KEY instructor_id (instructor_id)
        ) {$charset_collate};";
        
        // Workshop Students table
        $workshop_students_table = $wpdb->prefix . 'waza_workshop_students';
        $workshop_students_sql = "CREATE TABLE IF NOT EXISTS {$workshop_students_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            workshop_id bigint(20) NOT NULL,
            booking_id bigint(20) NOT NULL,
            user_email varchar(100) NOT NULL,
            joined_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY workshop_id (workshop_id),
            KEY booking_id (booking_id),
            KEY user_email (user_email)
        ) {$charset_collate};";
        
        // Activity Logs table
        $activity_logs_table = $wpdb->prefix . 'waza_activity_logs';
        $activity_logs_sql = "CREATE TABLE IF NOT EXISTS {$activity_logs_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            action_type varchar(50) NOT NULL,
            object_type varchar(50) DEFAULT NULL,
            object_id bigint(20) DEFAULT NULL,
            description text DEFAULT NULL,
            metadata longtext DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY action_type (action_type),
            KEY object_type (object_type),
            KEY created_at (created_at)
        ) {$charset_collate};";
        
        // Studio Announcements table
        $announcements_table = $wpdb->prefix . 'waza_announcements';
        $announcements_sql = "CREATE TABLE IF NOT EXISTS {$announcements_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            message text NOT NULL,
            announcement_type varchar(50) NOT NULL DEFAULT 'general',
            target_audience varchar(50) NOT NULL DEFAULT 'all',
            is_active tinyint(1) NOT NULL DEFAULT 1,
            priority int(11) NOT NULL DEFAULT 0,
            starts_at datetime DEFAULT NULL,
            expires_at datetime DEFAULT NULL,
            created_by bigint(20) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY is_active (is_active),
            KEY announcement_type (announcement_type),
            KEY expires_at (expires_at)
        ) {$charset_collate};";
        
        // QR Groups table (for choreographer group bookings)
        $qr_groups_table = $wpdb->prefix . 'waza_qr_groups';
        $qr_groups_sql = "CREATE TABLE IF NOT EXISTS {$qr_groups_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            master_booking_id bigint(20) NOT NULL,
            master_qr_token varchar(255) NOT NULL,
            slot_id bigint(20) NOT NULL,
            total_members int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY master_booking_id (master_booking_id),
            KEY slot_id (slot_id)
        ) {$charset_collate};";
        
        // QR Group Members table
        $qr_group_members_table = $wpdb->prefix . 'waza_qr_group_members';
        $qr_group_members_sql = "CREATE TABLE IF NOT EXISTS {$qr_group_members_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            group_id bigint(20) NOT NULL,
            booking_id bigint(20) NOT NULL,
            qr_token varchar(255) NOT NULL,
            member_number int(11) NOT NULL,
            PRIMARY KEY (id),
            KEY group_id (group_id),
            KEY booking_id (booking_id)
        ) {$charset_collate};";
        
        // Execute table creation queries
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($bookings_sql);
        dbDelta($qr_tokens_sql);
        dbDelta($attendance_sql);
        dbDelta($payments_sql);
        dbDelta($waitlist_sql);
        dbDelta($slots_sql);
        dbDelta($email_templates_sql);
        dbDelta($workshops_sql);
        dbDelta($workshop_students_sql);
        dbDelta($activity_logs_sql);
        dbDelta($announcements_sql);
        dbDelta($qr_groups_sql);
        dbDelta($qr_group_members_sql);
    }
    
    /**
     * Get booking with row locking for concurrency control
     * 
     * @param int $slot_id
     * @return object|null
     */
    public function get_slot_for_booking($slot_id) {
        global $wpdb;
        
        // Use WordPress transactional approach with SELECT FOR UPDATE
        $wpdb->query('START TRANSACTION');
        
        $slot = $wpdb->get_row($wpdb->prepare("
            SELECT p.*, pm.meta_value as capacity, pm2.meta_value as booked_seats
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_waza_capacity'
            LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_waza_booked_seats'
            WHERE p.ID = %d AND p.post_type = 'waza_slot' AND p.post_status = 'publish'
            FOR UPDATE
        ", $slot_id));
        
        return $slot;
    }
    
    /**
     * Complete booking transaction
     */
    public function commit_booking() {
        global $wpdb;
        $wpdb->query('COMMIT');
    }
    
    /**
     * Rollback booking transaction
     */
    public function rollback_booking() {
        global $wpdb;
        $wpdb->query('ROLLBACK');
    }
    
    /**
     * Insert booking record
     * 
     * @param array $booking_data
     * @return int|false
     */
    public function insert_booking($booking_data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'waza_bookings';
        
        $defaults = [
            'attendees_count' => 1,
            'total_amount' => 0.00,
            'discount_amount' => 0.00,
            'payment_status' => 'pending',
            'booking_status' => 'confirmed',
            'booking_type' => 'regular',
            'attended' => 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];
        
        $booking_data = wp_parse_args($booking_data, $defaults);
        
        $result = $wpdb->insert($table, $booking_data);
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Update booking
     * 
     * @param int $booking_id
     * @param array $update_data
     * @return bool
     */
    public function update_booking($booking_id, $update_data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'waza_bookings';
        $update_data['updated_at'] = current_time('mysql');
        
        return $wpdb->update($table, $update_data, ['id' => $booking_id]) !== false;
    }
    
    /**
     * Get booking by ID
     * 
     * @param int $booking_id
     * @return object|null
     */
    public function get_booking($booking_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'waza_bookings';
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$table} WHERE id = %d
        ", $booking_id));
    }
    
    /**
     * Get bookings by slot
     * 
     * @param int $slot_id
     * @param array $args
     * @return array
     */
    public function get_bookings_by_slot($slot_id, $args = []) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'waza_bookings';
        
        $defaults = [
            'status' => 'confirmed',
            'limit' => 50,
            'offset' => 0
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $where = ['slot_id = %d'];
        $values = [$slot_id];
        
        if (!empty($args['status'])) {
            $where[] = 'booking_status = %s';
            $values[] = $args['status'];
        }
        
        $where_clause = implode(' AND ', $where);
        $limit_clause = $args['limit'] > 0 ? $wpdb->prepare('LIMIT %d OFFSET %d', $args['limit'], $args['offset']) : '';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$table} 
            WHERE {$where_clause}
            ORDER BY created_at DESC
            {$limit_clause}
        ", ...$values));
    }
}