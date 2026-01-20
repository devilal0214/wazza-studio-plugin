<?php
/**
 * Create Sample Slots for Activities
 * 
 * Run this to create test slots that link to your activities
 * Access: yoursite.com/wp-content/plugins/waza-studio-app/create-sample-slots.php
 * 
 * @package WazaBooking
 */

// Load WordPress
require_once('../../../wp-load.php');

// Security check
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to access this page.');
}

global $wpdb;

// Get all activities
$activities = get_posts([
    'post_type' => 'waza_activity',
    'post_status' => 'publish',
    'posts_per_page' => -1
]);

// Get all instructors
$instructors = get_posts([
    'post_type' => 'waza_instructor',
    'post_status' => 'publish',
    'posts_per_page' => -1
]);

if (empty($instructors)) {
    echo '<div style="padding: 20px; background: #ffebee; color: #c62828; margin: 20px; border-radius: 4px;">❌ No instructors found. Please create instructors first.</div>';
    exit;
}

// Will rotate through instructors for variety
$instructor_count = count($instructors);
$instructor_index = 0;

$created_count = 0;

foreach ($activities as $activity) {
    // Create slots for next 7 days
    for ($day = 1; $day <= 7; $day++) {
        $date = date('Y-m-d', strtotime("+$day days"));
        
        // Rotate instructor for variety
        $current_instructor = $instructors[$instructor_index]->ID;
        $instructor_index = ($instructor_index + 1) % $instructor_count;
        
        // Morning slot (10 AM)
        $wpdb->insert(
            $wpdb->prefix . 'waza_slots',
            [
                'activity_id' => $activity->ID,
                'instructor_id' => $current_instructor,
                'start_datetime' => $date . ' 10:00:00',
                'end_datetime' => $date . ' 11:00:00',
                'capacity' => 20,
                'price' => get_post_meta($activity->ID, '_waza_activity_price', true) ?: 500,
                'status' => 'active'
            ],
            ['%d', '%d', '%s', '%s', '%d', '%f', '%s']
        );
        
        // Afternoon slot (2 PM)
        $wpdb->insert(
            $wpdb->prefix . 'waza_slots',
            [
                'activity_id' => $activity->ID,
                'instructor_id' => $current_instructor,
                'start_datetime' => $date . ' 14:00:00',
                'end_datetime' => $date . ' 15:00:00',
                'capacity' => 20,
                'price' => get_post_meta($activity->ID, '_waza_activity_price', true) ?: 500,
                'status' => 'active'
            ],
            ['%d', '%d', '%s', '%s', '%d', '%f', '%s']
        );
        
        // Evening slot (6 PM)
        $wpdb->insert(
            $wpdb->prefix . 'waza_slots',
            [
                'activity_id' => $activity->ID,
                'instructor_id' => $current_instructor,
                'start_datetime' => $date . ' 18:00:00',
                'end_datetime' => $date . ' 19:00:00',
                'capacity' => 20,
                'price' => get_post_meta($activity->ID, '_waza_activity_price', true) ?: 500,
                'status' => 'active'
            ],
            ['%d', '%d', '%s', '%s', '%d', '%f', '%s']
        );
        
        $created_count += 3;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Sample Slots Created</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; color: #155724; padding: 20px; border-radius: 8px; text-align: center; }
        .info { background: #e7f3ff; padding: 15px; border-radius: 8px; margin: 20px 0; }
        h1 { color: #2271b1; }
    </style>
</head>
<body>
    <div class="success">
        <h1>✅ Sample Slots Created!</h1>
        <p>Created <strong><?php echo $created_count; ?></strong> time slots across <strong><?php echo count($activities); ?></strong> activities.</p>
    </div>
    
    <div class="info">
        <h3>What was created:</h3>
        <ul>
            <?php foreach ($activities as $activity) : ?>
                <li>
                    <strong><?php echo $activity->post_title; ?></strong>
                    (<?php echo get_post_meta($activity->ID, '_waza_activity_slug', true); ?>)
                    - 21 slots (7 days × 3 time slots)
                </li>
            <?php endforeach; ?>
        </ul>
        
        <h3>Time Slots:</h3>
        <ul>
            <li>10:00 AM - 11:00 AM</li>
            <li>2:00 PM - 3:00 PM</li>
            <li>6:00 PM - 7:00 PM</li>
        </ul>
        
        <h3>Next Steps:</h3>
        <ol>
            <li>Visit <a href="<?php echo home_url('/activities/'); ?>">/activities/</a></li>
            <li>Click on any activity</li>
            <li>Select tomorrow's date</li>
            <li>You should see 3 available slots!</li>
        </ol>
        
        <p><strong>⚠️ Delete this file after use:</strong> <code>create-sample-slots.php</code></p>
    </div>
</body>
</html>
