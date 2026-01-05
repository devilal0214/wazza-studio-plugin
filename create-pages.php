<?php
/**
 * Create Required Frontend Pages
 * Run this file once to create all necessary frontend pages
 */

// Load WordPress (3 levels up from plugin folder)
require_once(__DIR__ . '/../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied. You must be an administrator.');
}

echo "<h2>Creating Frontend Pages...</h2>";

$pages_to_create = [
    [
        'title' => 'Student Login',
        'slug' => 'student-login',
        'content' => '[waza_login_form]
        
<p style="text-align: center;">Don\'t have an account? <a href="' . home_url('/student-register') . '">Register here</a></p>',
        'description' => 'Student login page with automatic session timeout'
    ],
    [
        'title' => 'Student Register',
        'slug' => 'student-register',
        'content' => '[waza_register_form]

<p style="text-align: center;">Already have an account? <a href="' . home_url('/student-login') . '">Login here</a></p>',
        'description' => 'Student registration page'
    ],
    [
        'title' => 'Announcements',
        'slug' => 'announcements',
        'content' => '<h2>Studio Announcements</h2>

[waza_announcements]

<p><em>Stay updated with the latest news and announcements from our studio.</em></p>',
        'description' => 'Public announcements page'
    ],
    [
        'title' => 'Workshops',
        'slug' => 'workshops',
        'content' => '<h2>Available Workshops</h2>

[waza_workshop_invite]

<p><em>Join instructor-led workshops and enhance your skills.</em></p>',
        'description' => 'Workshop listing and enrollment page'
    ],
    [
        'title' => 'My Attendance',
        'slug' => 'my-attendance',
        'content' => '<h2>My Attendance History</h2>

[waza_my_attendance]

<p><em>View your attendance records for all your bookings.</em></p>',
        'description' => 'Student attendance history page'
    ],
    [
        'title' => 'My Bookings',
        'slug' => 'my-bookings',
        'content' => '<h2>My Bookings</h2>

[waza_my_bookings]

<p><em>View and manage all your bookings.</em></p>',
        'description' => 'User bookings management page'
    ],
    [
        'title' => 'My Account',
        'slug' => 'my-account',
        'content' => '<h2>My Account Dashboard</h2>

[waza_user_dashboard]

<p><em>Manage your profile and view your activity.</em></p>',
        'description' => 'User account dashboard'
    ]
];

$created_pages = [];
$existing_pages = [];

foreach ($pages_to_create as $page_data) {
    // Check if page already exists
    $existing_page = get_page_by_path($page_data['slug']);
    
    if ($existing_page) {
        $existing_pages[] = $page_data['title'];
        echo "<p>⚠️ <strong>{$page_data['title']}</strong> already exists at: <a href='" . get_permalink($existing_page->ID) . "' target='_blank'>" . get_permalink($existing_page->ID) . "</a></p>";
        continue;
    }
    
    // Create the page
    $page_id = wp_insert_post([
        'post_title' => $page_data['title'],
        'post_name' => $page_data['slug'],
        'post_content' => $page_data['content'],
        'post_status' => 'publish',
        'post_type' => 'page',
        'post_author' => get_current_user_id(),
        'comment_status' => 'closed',
        'ping_status' => 'closed'
    ]);
    
    if ($page_id && !is_wp_error($page_id)) {
        $created_pages[] = [
            'title' => $page_data['title'],
            'url' => get_permalink($page_id),
            'description' => $page_data['description']
        ];
        echo "<p>✅ <strong>{$page_data['title']}</strong> created at: <a href='" . get_permalink($page_id) . "' target='_blank'>" . get_permalink($page_id) . "</a></p>";
    } else {
        echo "<p>❌ Failed to create <strong>{$page_data['title']}</strong></p>";
    }
}

echo "<hr>";
echo "<h3>Summary</h3>";
echo "<p><strong>Created:</strong> " . count($created_pages) . " pages</p>";
echo "<p><strong>Already Existed:</strong> " . count($existing_pages) . " pages</p>";

if (!empty($created_pages)) {
    echo "<h4>Newly Created Pages:</h4>";
    echo "<ul>";
    foreach ($created_pages as $page) {
        echo "<li><strong>{$page['title']}</strong> - {$page['description']}<br>";
        echo "<a href='{$page['url']}' target='_blank'>{$page['url']}</a></li>";
    }
    echo "</ul>";
}

echo "<hr>";
echo "<h3>Auto-Logout Configuration</h3>";
echo "<p>To enable automatic logout after slot timeout for student sessions, add this code to your theme's <code>functions.php</code>:</p>";
echo "<pre style='background:#f5f5f5; padding:15px; border:1px solid #ddd;'>";
echo htmlspecialchars("
// Auto-logout after booking slot expires
add_action('init', function() {
    if (is_user_logged_in()) {
        \$user = wp_get_current_user();
        
        // Only for students (not admins or instructors)
        if (in_array('waza_student', \$user->roles)) {
            global \$wpdb;
            
            // Get user's upcoming bookings
            \$bookings = \$wpdb->get_results(\$wpdb->prepare(\"
                SELECT b.*, s.end_datetime
                FROM {\$wpdb->prefix}waza_bookings b
                LEFT JOIN {\$wpdb->prefix}waza_slots s ON b.slot_id = s.id
                WHERE b.user_id = %d 
                AND b.booking_status = 'confirmed'
                AND s.end_datetime >= NOW()
                ORDER BY s.end_datetime ASC
                LIMIT 1
            \", \$user->ID));
            
            // If no upcoming bookings, logout
            if (empty(\$bookings)) {
                \$last_booking = \$wpdb->get_var(\$wpdb->prepare(\"
                    SELECT MAX(s.end_datetime)
                    FROM {\$wpdb->prefix}waza_bookings b
                    LEFT JOIN {\$wpdb->prefix}waza_slots s ON b.slot_id = s.id
                    WHERE b.user_id = %d
                \", \$user->ID));
                
                // Logout if last slot ended more than 1 hour ago
                if (\$last_booking && strtotime(\$last_booking) < (time() - 3600)) {
                    wp_logout();
                    wp_redirect(home_url('/student-login?auto_logout=1'));
                    exit;
                }
            }
        }
    }
});
");
echo "</pre>";

echo "<p><strong>Note:</strong> This will automatically log out students 1 hour after their last booked slot expires.</p>";

echo "<hr>";
echo "<p style='color: green; font-size: 16px; font-weight: bold;'>✅ Page creation complete! You can now close this window.</p>";
