<?php
/**
 * Sample Data Creator
 * 
 * Creates sample activities and slots for testing
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create sample data for Waza Booking
 */
function waza_create_sample_data() {
    global $wpdb;
    
    // Check if we already have activities
    $existing_activities = get_posts([
        'post_type' => 'waza_activity',
        'numberposts' => 1,
        'post_status' => 'any'
    ]);
    
    if (!empty($existing_activities)) {
        return; // Sample data already exists
    }
    
    // Create sample activities
    $activities = [
        [
            'title' => 'Yoga Class',
            'content' => 'A relaxing yoga session suitable for all levels. Learn basic poses and breathing techniques.',
            'meta' => [
                '_waza_duration' => '60',
                '_waza_price' => '25.00',
                '_waza_capacity' => '15',
                '_waza_difficulty' => 'beginner'
            ]
        ],
        [
            'title' => 'Fitness Bootcamp',
            'content' => 'High-intensity interval training to build strength and endurance.',
            'meta' => [
                '_waza_duration' => '45',
                '_waza_price' => '35.00',
                '_waza_capacity' => '12',
                '_waza_difficulty' => 'intermediate'
            ]
        ],
        [
            'title' => 'Dance Workshop',
            'content' => 'Learn contemporary dance moves in this fun and energetic workshop.',
            'meta' => [
                '_waza_duration' => '90',
                '_waza_price' => '40.00',
                '_waza_capacity' => '20',
                '_waza_difficulty' => 'all_levels'
            ]
        ],
        [
            'title' => 'Meditation Session',
            'content' => 'Guided meditation to reduce stress and improve mental clarity.',
            'meta' => [
                '_waza_duration' => '30',
                '_waza_price' => '15.00',
                '_waza_capacity' => '25',
                '_waza_difficulty' => 'beginner'
            ]
        ]
    ];
    
    $created_activities = [];
    
    foreach ($activities as $activity_data) {
        $activity_id = wp_insert_post([
            'post_title' => $activity_data['title'],
            'post_content' => $activity_data['content'],
            'post_status' => 'publish',
            'post_type' => 'waza_activity',
            'post_author' => 1
        ]);
        
        if ($activity_id && !is_wp_error($activity_id)) {
            // Add meta data
            foreach ($activity_data['meta'] as $key => $value) {
                update_post_meta($activity_id, $key, $value);
            }
            
            $created_activities[] = $activity_id;
        }
    }
    
    // Create sample slots for the next 7 days
    if (!empty($created_activities)) {
        $start_date = strtotime('tomorrow');
        
        for ($i = 0; $i < 7; $i++) {
            $current_date = strtotime("+{$i} days", $start_date);
            $date_string = date('Y-m-d', $current_date);
            
            // Create morning slots (9:00 AM - 10:30 AM)
            foreach ($created_activities as $activity_id) {
                $wpdb->insert(
                    $wpdb->prefix . 'waza_slots',
                    [
                        'activity_id' => $activity_id,
                        'start_datetime' => $date_string . ' 09:00:00',
                        'end_datetime' => $date_string . ' 10:30:00',
                        'capacity' => rand(10, 20),
                        'status' => 'available',
                        'location' => 'Studio A'
                    ],
                    ['%d', '%s', '%s', '%d', '%s', '%s']
                );
            }
            
            // Create afternoon slots (2:00 PM - 3:30 PM) for some activities
            if ($i % 2 == 0) { // Every other day
                foreach (array_slice($created_activities, 0, 2) as $activity_id) {
                    $wpdb->insert(
                        $wpdb->prefix . 'waza_slots',
                        [
                            'activity_id' => $activity_id,
                            'start_datetime' => $date_string . ' 14:00:00',
                            'end_datetime' => $date_string . ' 15:30:00',
                            'capacity' => rand(8, 15),
                            'status' => 'available',
                            'location' => 'Studio B'
                        ],
                        ['%d', '%s', '%s', '%d', '%s', '%s']
                    );
                }
            }
            
            // Create evening slots (6:00 PM - 7:00 PM)
            if ($i % 3 == 0) { // Every third day
                foreach (array_slice($created_activities, 1, 2) as $activity_id) {
                    $wpdb->insert(
                        $wpdb->prefix . 'waza_slots',
                        [
                            'activity_id' => $activity_id,
                            'start_datetime' => $date_string . ' 18:00:00',
                            'end_datetime' => $date_string . ' 19:00:00',
                            'capacity' => rand(12, 18),
                            'status' => 'available',
                            'location' => 'Main Hall'
                        ],
                        ['%d', '%s', '%s', '%d', '%s', '%s']
                    );
                }
            }
        }
    }
    
    // Add admin notice
    add_option('waza_sample_data_created', time());
}

// Hook to create sample data after plugin activation
add_action('admin_init', function() {
    if (get_option('waza_sample_data_created') === false) {
        waza_create_sample_data();
        
        // Show admin notice
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>' . esc_html__('Waza Booking:', 'waza-booking') . '</strong> ';
            echo esc_html__('Sample activities and time slots have been created to help you get started!', 'waza-booking');
            echo '</p></div>';
        });
    }
});