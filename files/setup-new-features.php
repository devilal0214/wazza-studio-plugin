<?php
/**
 * Setup Script for New Features
 * 
 * Run this once to set up activities post type and sample data
 * Access: yoursite.com/wp-content/plugins/waza-studio-app/setup-new-features.php
 * 
 * @package WazaBooking
 */

// Load WordPress
require_once('../../../wp-load.php');

// Security check
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to access this page.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Waza Booking - New Features Setup</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        h1 { color: #2271b1; margin-bottom: 10px; }
        .section { margin: 30px 0; padding: 20px; background: #f8f9fa; border-radius: 8px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .button { background: #2271b1; color: white; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; margin: 5px; }
        .button:hover { background: #135e96; }
        .info { background: #e7f3ff; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #2271b1; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 4px; font-family: monospace; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 8px; overflow-x: auto; }
        .step { margin: 20px 0; }
        .step-number { background: #2271b1; color: white; width: 30px; height: 30px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎯 Waza Booking - New Features Setup</h1>
        <p>This setup wizard will help you configure the new Activity Browser and Studio Rental features.</p>
        
        <?php
        if (isset($_POST['setup_action'])) {
            $action = $_POST['setup_action'];
            
            switch ($action) {
                case 'create_pages':
                    echo '<div class="success">';
                    
                    // Create Activities page
                    $activities_page = wp_insert_post([
                        'post_type' => 'page',
                        'post_title' => 'Browse Activities',
                        'post_content' => '[waza_activity_browser]',
                        'post_status' => 'publish',
                        'post_name' => 'activities'
                    ]);
                    
                    // Create Activity Booking page
                    $booking_page = wp_insert_post([
                        'post_type' => 'page',
                        'post_title' => 'Book Activity',
                        'post_content' => '[waza_activity_slots]',
                        'post_status' => 'publish',
                        'post_name' => 'activity-booking'
                    ]);
                    
                    // Create Studio Rental page
                    $rental_page = wp_insert_post([
                        'post_type' => 'page',
                        'post_title' => 'Studio Rental',
                        'post_content' => '[waza_studio_rental]',
                        'post_status' => 'publish',
                        'post_name' => 'studio-rental'
                    ]);
                    
                    echo '<strong>✅ Pages Created Successfully!</strong><br><br>';
                    echo 'Activities Page: <a href="' . get_permalink($activities_page) . '">' . get_permalink($activities_page) . '</a><br>';
                    echo 'Booking Page: <a href="' . get_permalink($booking_page) . '">' . get_permalink($booking_page) . '</a><br>';
                    echo 'Rental Page: <a href="' . get_permalink($rental_page) . '">' . get_permalink($rental_page) . '</a>';
                    echo '</div>';
                    break;
                    
                case 'create_sample_activities':
                    echo '<div class="success"><strong>✅ Sample Activities Created!</strong><br><br>';
                    
                    $activities = [
                        [
                            'title' => 'Zumba Dance Class',
                            'content' => 'High-energy dance workout combining Latin and international rhythms. Perfect for burning calories while having fun!',
                            'price' => 500,
                            'duration' => 60,
                            'slug' => 'zumba',
                            'category' => 'dance'
                        ],
                        [
                            'title' => 'Yoga & Meditation',
                            'content' => 'Calm your mind and strengthen your body with our yoga sessions. Suitable for all levels.',
                            'price' => 400,
                            'duration' => 90,
                            'slug' => 'yoga',
                            'category' => 'yoga'
                        ],
                        [
                            'title' => 'Aerobics Fitness',
                            'content' => 'Cardiovascular workout designed to improve endurance and overall fitness. Get ready to sweat!',
                            'price' => 350,
                            'duration' => 45,
                            'slug' => 'aerobics',
                            'category' => 'aerobics'
                        ],
                        [
                            'title' => 'Martial Arts Training',
                            'content' => 'Learn self-defense techniques while improving discipline, focus, and physical fitness.',
                            'price' => 600,
                            'duration' => 75,
                            'slug' => 'martial-arts',
                            'category' => 'martial-arts'
                        ],
                        [
                            'title' => 'Hip Hop Dance',
                            'content' => 'Street-style dance classes featuring the latest hip hop moves and choreography.',
                            'price' => 550,
                            'duration' => 60,
                            'slug' => 'hip-hop',
                            'category' => 'dance'
                        ]
                    ];
                    
                    foreach ($activities as $activity) {
                        $activity_id = wp_insert_post([
                            'post_type' => 'waza_activity',
                            'post_title' => $activity['title'],
                            'post_content' => $activity['content'],
                            'post_status' => 'publish'
                        ]);
                        
                        if ($activity_id) {
                            update_post_meta($activity_id, '_waza_activity_price', $activity['price']);
                            update_post_meta($activity_id, '_waza_activity_duration', $activity['duration']);
                            update_post_meta($activity_id, '_waza_activity_slug', $activity['slug']);
                            update_post_meta($activity_id, '_waza_activity_rating', rand(40, 50) / 10);
                            update_post_meta($activity_id, '_waza_booking_count', rand(10, 100));
                            
                            wp_set_object_terms($activity_id, [$activity['category']], 'waza_instructor_specialty');
                            
                            echo '• ' . $activity['title'] . ' - ₹' . $activity['price'] . ' (' . $activity['duration'] . ' mins)<br>';
                        }
                    }
                    echo '</div>';
                    break;
                    
                case 'verify_database':
                    global $wpdb;
                    echo '<div class="section">';
                    echo '<strong>Database Tables Status:</strong><br><br>';
                    
                    $tables = [
                        'waza_rentals' => $wpdb->prefix . 'waza_rentals',
                        'waza_slots' => $wpdb->prefix . 'waza_slots',
                        'waza_bookings' => $wpdb->prefix . 'waza_bookings'
                    ];
                    
                    foreach ($tables as $name => $table) {
                        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
                        if ($exists) {
                            $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
                            echo '✅ ' . $name . ' - Exists (' . $count . ' records)<br>';
                        } else {
                            echo '❌ ' . $name . ' - Not found<br>';
                        }
                    }
                    echo '</div>';
                    break;
            }
        }
        ?>
        
        <div class="section">
            <h2>📋 Setup Steps</h2>
            
            <div class="step">
                <span class="step-number">1</span>
                <strong>Create Required Pages</strong>
                <p>This will create three pages with the necessary shortcodes:</p>
                <ul>
                    <li><code>/activities/</code> - Browse all activities</li>
                    <li><code>/activity-booking/</code> - Book specific activity</li>
                    <li><code>/studio-rental/</code> - Studio rental form</li>
                </ul>
                <form method="post" style="display: inline;">
                    <input type="hidden" name="setup_action" value="create_pages">
                    <button type="submit" class="button">Create Pages</button>
                </form>
            </div>
            
            <div class="step">
                <span class="step-number">2</span>
                <strong>Create Sample Activities</strong>
                <p>Add sample activities to test the system:</p>
                <form method="post" style="display: inline;">
                    <input type="hidden" name="setup_action" value="create_sample_activities">
                    <button type="submit" class="button">Create Sample Activities</button>
                </form>
            </div>
            
            <div class="step">
                <span class="step-number">3</span>
                <strong>Verify Database Tables</strong>
                <p>Check if all required database tables exist:</p>
                <form method="post" style="display: inline;">
                    <input type="hidden" name="setup_action" value="verify_database">
                    <button type="submit" class="button">Verify Database</button>
                </form>
            </div>
        </div>
        
        <div class="info">
            <strong>ℹ️ Important Notes:</strong>
            <ul>
                <li>After setup, delete this file for security: <code>setup-new-features.php</code></li>
                <li>Configure your payment gateway to handle rental and activity bookings</li>
                <li>Add featured images to activities for better visual appeal</li>
                <li>Check admin panel for Studio Rentals management page</li>
            </ul>
        </div>
        
        <div class="section">
            <h3>📚 Documentation</h3>
            <p>For detailed information, see: <code>NEW-FEATURES-GUIDE.md</code></p>
            
            <h4>Shortcodes Reference:</h4>
            <pre>[waza_activity_browser per_page="12" show_filters="yes"]
[waza_activity_slots activity_id="123"]
[waza_studio_rental]</pre>
            
            <h4>Admin Pages:</h4>
            <ul>
                <li><a href="<?php echo admin_url('admin.php?page=waza-rentals'); ?>">Studio Rentals Management</a></li>
                <li><a href="<?php echo admin_url('edit.php?post_type=waza_activity'); ?>">Manage Activities</a></li>
            </ul>
        </div>
    </div>
</body>
</html>
