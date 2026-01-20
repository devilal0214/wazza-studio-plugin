<?php
/**
 * Waza Booking Plugin - Complete Installation Script
 * 
 * This script will:
 * - Create all required database tables
 * - Create required WordPress pages
 * - Add sample activities and slots
 * - Configure default settings
 * 
 * Run once: http://yoursite.com/wp-content/plugins/waza-studio-app/install-plugin.php
 * 
 * @package WazaBooking
 */

// Load WordPress
$wp_load_path = __DIR__ . '/../../../wp-load.php';
if (!file_exists($wp_load_path)) {
    die('❌ Error: WordPress installation not found. Please check the file path.');
}
require_once($wp_load_path);

// Security check - only admin can run this
if (!current_user_can('manage_options')) {
    die('❌ Permission denied. Only administrators can run this installation.');
}

global $wpdb;
$errors = [];
$success = [];

echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Waza Booking Plugin Installation</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #2563EB; margin-bottom: 10px; }
        h2 { color: #1E40AF; margin-top: 30px; border-bottom: 2px solid #E5E7EB; padding-bottom: 10px; }
        .success { background: #D1FAE5; color: #065F46; padding: 12px 16px; border-radius: 6px; margin: 10px 0; border-left: 4px solid #10B981; }
        .error { background: #FEE2E2; color: #991B1B; padding: 12px 16px; border-radius: 6px; margin: 10px 0; border-left: 4px solid #EF4444; }
        .info { background: #DBEAFE; color: #1E40AF; padding: 12px 16px; border-radius: 6px; margin: 10px 0; border-left: 4px solid #3B82F6; }
        .warning { background: #FEF3C7; color: #92400E; padding: 12px 16px; border-radius: 6px; margin: 10px 0; border-left: 4px solid #F59E0B; }
        .btn { display: inline-block; background: #2563EB; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin-top: 20px; }
        .btn:hover { background: #1D4ED8; }
        code { background: #F3F4F6; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        .step { margin: 20px 0; }
    </style>
</head>
<body>
<div class="container">
    <h1>🎯 Waza Booking Plugin Installation</h1>
    <p style="color: #6B7280; margin-bottom: 30px;">Setting up your booking system with all required components...</p>';

// ============================================
// STEP 1: CREATE DATABASE TABLES
// ============================================
echo '<div class="step"><h2>Step 1: Database Tables</h2>';

$tables_sql = "
-- Slots table
CREATE TABLE IF NOT EXISTS {$wpdb->prefix}waza_slots (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    activity_id bigint(20) NOT NULL,
    instructor_id bigint(20) DEFAULT NULL,
    start_datetime datetime NOT NULL,
    end_datetime datetime NOT NULL,
    capacity int(11) NOT NULL DEFAULT 10,
    price decimal(10,2) NOT NULL DEFAULT 0.00,
    slot_status varchar(20) NOT NULL DEFAULT 'active',
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY activity_id (activity_id),
    KEY instructor_id (instructor_id),
    KEY start_datetime (start_datetime),
    KEY slot_status (slot_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bookings table
CREATE TABLE IF NOT EXISTS {$wpdb->prefix}waza_bookings (
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
    KEY booking_status (booking_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attendance table
CREATE TABLE IF NOT EXISTS {$wpdb->prefix}waza_attendance (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    booking_id bigint(20) DEFAULT NULL,
    rental_id bigint(20) DEFAULT NULL,
    slot_id bigint(20) DEFAULT NULL,
    user_id bigint(20) DEFAULT NULL,
    attendance_status varchar(20) NOT NULL DEFAULT 'present',
    check_in_time datetime NOT NULL,
    check_out_time datetime DEFAULT NULL,
    scanner_user_id bigint(20) DEFAULT NULL,
    notes text DEFAULT NULL,
    PRIMARY KEY (id),
    KEY booking_id (booking_id),
    KEY rental_id (rental_id),
    KEY slot_id (slot_id),
    KEY user_id (user_id),
    KEY check_in_time (check_in_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payments table
CREATE TABLE IF NOT EXISTS {$wpdb->prefix}waza_payments (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    booking_id bigint(20) NOT NULL,
    payment_method varchar(50) NOT NULL,
    payment_gateway varchar(50) NOT NULL,
    gateway_payment_id varchar(255) NOT NULL,
    gateway_order_id varchar(255) DEFAULT NULL,
    transaction_id varchar(255) DEFAULT NULL,
    amount decimal(10,2) NOT NULL,
    currency varchar(10) NOT NULL DEFAULT 'INR',
    payment_status varchar(20) NOT NULL DEFAULT 'pending',
    payment_data longtext DEFAULT NULL,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY booking_id (booking_id),
    KEY payment_status (payment_status),
    KEY gateway_payment_id (gateway_payment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rentals table
CREATE TABLE IF NOT EXISTS {$wpdb->prefix}waza_rentals (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) DEFAULT NULL,
    customer_name varchar(100) NOT NULL,
    customer_email varchar(100) NOT NULL,
    customer_phone varchar(20) NOT NULL,
    rental_type varchar(50) NOT NULL,
    duration_type varchar(50) NOT NULL,
    rental_date date NOT NULL,
    start_time time NOT NULL,
    end_time time NOT NULL,
    total_amount decimal(10,2) NOT NULL,
    special_requirements text DEFAULT NULL,
    payment_status varchar(20) NOT NULL DEFAULT 'pending',
    booking_status varchar(20) NOT NULL DEFAULT 'pending',
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY rental_date (rental_date),
    KEY booking_status (booking_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

$queries = array_filter(array_map('trim', explode(';', $tables_sql)));
foreach ($queries as $query) {
    if (empty($query)) continue;
    
    // Extract table name for display
    preg_match('/CREATE TABLE IF NOT EXISTS (\S+)/', $query, $matches);
    $table_name = $matches[1] ?? 'unknown';
    
    $result = $wpdb->query($query);
    if ($result !== false) {
        echo "<div class='success'>✅ Table <code>{$table_name}</code> created/verified</div>";
        $success[] = "Table {$table_name} created";
    } else {
        echo "<div class='error'>❌ Failed to create <code>{$table_name}</code>: " . $wpdb->last_error . "</div>";
        $errors[] = "Table creation failed: {$table_name}";
    }
}

echo '</div>';

// ============================================
// STEP 2: CREATE REQUIRED PAGES
// ============================================
echo '<div class="step"><h2>Step 2: WordPress Pages</h2>';

$pages = [
    [
        'title' => 'Login',
        'slug' => 'login',
        'content' => '<!-- wp:shortcode -->[waza_user_login]<!-- /wp:shortcode -->',
        'template' => ''
    ],
    [
        'title' => 'My Bookings',
        'slug' => 'my-bookings',
        'content' => '<!-- wp:shortcode -->[waza_my_bookings]<!-- /wp:shortcode -->',
        'template' => ''
    ],
    [
        'title' => 'My Account',
        'slug' => 'my-account',
        'content' => '<!-- wp:shortcode -->[waza_my_account]<!-- /wp:shortcode -->',
        'template' => ''
    ],
    [
        'title' => 'Instructor Registration',
        'slug' => 'instructor-register',
        'content' => '<!-- wp:shortcode -->[waza_instructor_registration]<!-- /wp:shortcode -->',
        'template' => ''
    ],
    [
        'title' => 'Instructor Dashboard',
        'slug' => 'instructor-dashboard',
        'content' => '<!-- wp:shortcode -->[waza_instructor_dashboard]<!-- /wp:shortcode -->',
        'template' => ''
    ],
    [
        'title' => 'Activities',
        'slug' => 'activities',
        'content' => '<!-- wp:shortcode -->[waza_activity_browser]<!-- /wp:shortcode -->',
        'template' => ''
    ],
    [
        'title' => 'Announcements',
        'slug' => 'announcements',
        'content' => '<!-- wp:shortcode -->[waza_announcements]<!-- /wp:shortcode -->',
        'template' => ''
    ],
    [
        'title' => 'QR Scanner',
        'slug' => 'qr-scanner',
        'content' => '<!-- wp:shortcode -->[waza_qr_scanner]<!-- /wp:shortcode -->',
        'template' => ''
    ],
    [
        'title' => 'Booking',
        'slug' => 'booking',
        'content' => '<!-- wp:shortcode -->[waza_booking_form]<!-- /wp:shortcode -->',
        'template' => ''
    ],
    [
        'title' => 'Checkout',
        'slug' => 'checkout',
        'content' => '<!-- wp:shortcode -->[waza_checkout]<!-- /wp:shortcode -->',
        'template' => ''
    ],
    [
        'title' => 'Booking Confirmation',
        'slug' => 'booking-confirmation',
        'content' => '<!-- wp:shortcode -->[waza_booking_confirmation]<!-- /wp:shortcode -->',
        'template' => ''
    ],
    [
        'title' => 'Studio Rental',
        'slug' => 'studio-rental',
        'content' => '<!-- wp:shortcode -->[waza_studio_rental]<!-- /wp:shortcode -->',
        'template' => ''
    ]
];

foreach ($pages as $page_data) {
    $existing_page = get_page_by_path($page_data['slug']);
    
    if ($existing_page) {
        echo "<div class='info'>ℹ️ Page <code>{$page_data['title']}</code> already exists (ID: {$existing_page->ID})</div>";
    } else {
        $page_id = wp_insert_post([
            'post_title' => $page_data['title'],
            'post_name' => $page_data['slug'],
            'post_content' => $page_data['content'],
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => get_current_user_id()
        ]);
        
        if ($page_id && !is_wp_error($page_id)) {
            echo "<div class='success'>✅ Page <code>{$page_data['title']}</code> created (ID: {$page_id})</div>";
            $success[] = "Page created: {$page_data['title']}";
        } else {
            echo "<div class='error'>❌ Failed to create page <code>{$page_data['title']}</code></code></div>";
            $errors[] = "Page creation failed: {$page_data['title']}";
        }
    }
}

echo '</div>';

// ============================================
// STEP 3: CREATE SAMPLE ACTIVITIES
// ============================================
echo '<div class="step"><h2>Step 3: Sample Activities</h2>';

$activities = [
    [
        'title' => 'Dance Class - Beginners',
        'content' => 'Perfect for those starting their dance journey. Learn basic moves and build confidence.',
        'category' => 'Dance',
        'duration' => 60,
        'price' => 500
    ],
    [
        'title' => 'Yoga Session',
        'content' => 'Relaxing yoga session for mind and body wellness. Suitable for all levels.',
        'category' => 'Fitness',
        'duration' => 75,
        'price' => 600
    ],
    [
        'title' => 'Music Practice - Guitar',
        'content' => 'One-on-one guitar lessons from experienced instructors.',
        'category' => 'Music',
        'duration' => 45,
        'price' => 800
    ],
    [
        'title' => 'Acting Workshop',
        'content' => 'Improve your acting skills with practical exercises and scene work.',
        'category' => 'Theatre',
        'duration' => 90,
        'price' => 1000
    ],
    [
        'title' => 'Zumba Fitness',
        'content' => 'High-energy dance fitness class. Burn calories while having fun!',
        'category' => 'Fitness',
        'duration' => 60,
        'price' => 450
    ]
];

$activity_ids = [];
foreach ($activities as $activity) {
    // Check if activity already exists
    $existing = get_page_by_title($activity['title'], OBJECT, 'waza_activity');
    
    if ($existing) {
        echo "<div class='info'>ℹ️ Activity <code>{$activity['title']}</code> already exists</div>";
        $activity_ids[] = $existing->ID;
    } else {
        $activity_id = wp_insert_post([
            'post_title' => $activity['title'],
            'post_content' => $activity['content'],
            'post_status' => 'publish',
            'post_type' => 'waza_activity',
            'post_author' => get_current_user_id()
        ]);
        
        if ($activity_id && !is_wp_error($activity_id)) {
            update_post_meta($activity_id, '_waza_duration', $activity['duration']);
            update_post_meta($activity_id, '_waza_price', $activity['price']);
            update_post_meta($activity_id, '_waza_category', $activity['category']);
            
            echo "<div class='success'>✅ Activity <code>{$activity['title']}</code> created (ID: {$activity_id})</div>";
            $success[] = "Activity created: {$activity['title']}";
            $activity_ids[] = $activity_id;
        } else {
            echo "<div class='error'>❌ Failed to create activity <code>{$activity['title']}</code></div>";
            $errors[] = "Activity creation failed: {$activity['title']}";
        }
    }
}

echo '</div>';

// ============================================
// STEP 4: CREATE SAMPLE SLOTS (FUTURE DATES)
// ============================================
echo '<div class="step"><h2>Step 4: Sample Time Slots</h2>';

if (!empty($activity_ids)) {
    $slots_created = 0;
    
    // Create slots for next 7 days, 2 slots per day per activity
    for ($day = 1; $day <= 7; $day++) {
        $date = date('Y-m-d', strtotime("+{$day} days"));
        
        foreach ($activity_ids as $idx => $activity_id) {
            // Morning slot (10:00 AM)
            $morning_start = $date . ' 10:00:00';
            $morning_end = $date . ' 11:00:00';
            
            $result = $wpdb->insert(
                $wpdb->prefix . 'waza_slots',
                [
                    'activity_id' => $activity_id,
                    'instructor_id' => 1, // Admin user
                    'start_datetime' => $morning_start,
                    'end_datetime' => $morning_end,
                    'capacity' => 10,
                    'price' => get_post_meta($activity_id, '_waza_price', true) ?: 500,
                    'slot_status' => 'active'
                ]
            );
            
            if ($result) $slots_created++;
            
            // Evening slot (6:00 PM)
            $evening_start = $date . ' 18:00:00';
            $evening_end = $date . ' 19:00:00';
            
            $result = $wpdb->insert(
                $wpdb->prefix . 'waza_slots',
                [
                    'activity_id' => $activity_id,
                    'instructor_id' => 1,
                    'start_datetime' => $evening_start,
                    'end_datetime' => $evening_end,
                    'capacity' => 10,
                    'price' => get_post_meta($activity_id, '_waza_price', true) ?: 500,
                    'slot_status' => 'active'
                ]
            );
            
            if ($result) $slots_created++;
            
            // Only create 2 activities per day to avoid clutter
            if ($idx >= 1) break;
        }
    }
    
    echo "<div class='success'>✅ Created {$slots_created} time slots for the next 7 days</div>";
    $success[] = "Created {$slots_created} time slots";
} else {
    echo "<div class='warning'>⚠️ No activities available to create slots</div>";
}

echo '</div>';

// ============================================
// STEP 5: CONFIGURE DEFAULT SETTINGS
// ============================================
echo '<div class="step"><h2>Step 5: Plugin Settings</h2>';

$default_settings = [
    'waza_booking_settings' => [
        'payment_gateway' => 'razorpay',
        'currency' => 'INR',
        'currency_symbol' => '₹',
        'timezone' => 'Asia/Kolkata',
        'booking_buffer' => 30,
        'cancellation_hours' => 24
    ],
    'waza_rental_settings' => [
        'rental_types' => [
            ['id' => 'rehearsal', 'name' => 'Rehearsal Space', 'enabled' => true],
            ['id' => 'recording', 'name' => 'Recording Studio', 'enabled' => true],
            ['id' => 'event', 'name' => 'Event Space', 'enabled' => true]
        ],
        'durations' => [
            ['id' => 'hourly', 'name' => 'Hourly', 'enabled' => true],
            ['id' => 'half_day', 'name' => 'Half Day (4 hours)', 'enabled' => true],
            ['id' => 'full_day', 'name' => 'Full Day (8 hours)', 'enabled' => true]
        ],
        'pricing' => [
            'rehearsal_hourly' => 500,
            'rehearsal_half_day' => 1800,
            'rehearsal_full_day' => 3500,
            'recording_hourly' => 1000,
            'recording_half_day' => 3500,
            'recording_full_day' => 6500,
            'event_hourly' => 1500,
            'event_half_day' => 5000,
            'event_full_day' => 9000
        ],
        'currency_symbol' => '₹',
        'tax_percentage' => 18,
        'advance_payment_percentage' => 50
    ]
];

foreach ($default_settings as $option_name => $option_value) {
    $existing = get_option($option_name);
    
    if ($existing === false) {
        update_option($option_name, $option_value);
        echo "<div class='success'>✅ Settings <code>{$option_name}</code> configured</div>";
        $success[] = "Settings configured: {$option_name}";
    } else {
        echo "<div class='info'>ℹ️ Settings <code>{$option_name}</code> already exist</div>";
    }
}

echo '</div>';

// ============================================
// INSTALLATION SUMMARY
// ============================================
echo '<div class="step"><h2>Installation Summary</h2>';

if (empty($errors)) {
    echo "<div class='success' style='font-size: 18px; padding: 20px;'>
        <strong>🎉 Installation Completed Successfully!</strong><br><br>
        ✅ All database tables created<br>
        ✅ Required pages created<br>
        ✅ Sample activities added<br>
        ✅ Time slots created for next 7 days<br>
        ✅ Default settings configured
    </div>";
    
    echo "<div class='info'><strong>Next Steps:</strong><br>
        1. Configure payment gateway credentials in WordPress Admin → Waza Booking → Settings<br>
        2. Update rental pricing in WordPress Admin → Waza Booking → Rental Settings<br>
        3. Customize activities and add more slots as needed<br>
        4. Set up email templates for notifications<br>
        5. Test the booking flow from frontend
    </div>";
    
    echo "<div class='warning'><strong>Important:</strong> Delete or restrict access to this installation file after completion for security.</div>";
} else {
    echo "<div class='error' style='font-size: 18px; padding: 20px;'>
        <strong>⚠️ Installation completed with some errors</strong><br><br>
        " . count($errors) . " error(s) occurred during installation.<br>
        Please check the messages above and resolve the issues.
    </div>";
}

echo '</div>';

echo '<div style="text-align: center; margin-top: 40px;">
    <a href="/wp-admin/" class="btn">Go to WordPress Dashboard</a>
    <a href="/booking/" class="btn" style="background: #10B981;">View Booking Page</a>
</div>';

echo '</div></body></html>';
