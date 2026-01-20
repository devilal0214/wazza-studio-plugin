<?php
/**
 * Check for instructor with email instructor@waza.studio
 */

require_once '../../../wp-load.php';

global $wpdb;

echo "=== SEARCHING FOR INSTRUCTOR ===\n\n";

// Search by email meta
$instructor_by_meta = $wpdb->get_results($wpdb->prepare("
    SELECT p.ID, p.post_title, p.post_status
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
    WHERE p.post_type = 'waza_instructor'
    AND pm.meta_key = '_waza_email'
    AND pm.meta_value = %s
", 'instructor@waza.studio'));

if (!empty($instructor_by_meta)) {
    foreach ($instructor_by_meta as $instructor) {
        echo "Found Instructor Post:\n";
        echo "  ID: {$instructor->ID}\n";
        echo "  Name: {$instructor->post_title}\n";
        echo "  Status: {$instructor->post_status}\n";
        
        $user_id = get_post_meta($instructor->ID, '_waza_user_id', true);
        echo "  User ID: " . ($user_id ?: 'NOT SET') . "\n";
        
        if ($user_id) {
            $user = get_userdata($user_id);
            if ($user) {
                echo "  WordPress User: {$user->user_login}\n";
                echo "  User Email: {$user->user_email}\n";
            } else {
                echo "  WordPress User: DELETED\n";
            }
        }
        
        // Get all meta
        echo "\nAll Meta:\n";
        $all_meta = get_post_meta($instructor->ID);
        foreach ($all_meta as $key => $value) {
            echo "  {$key}: {$value[0]}\n";
        }
    }
} else {
    echo "No instructor found with email: instructor@waza.studio\n\n";
}

// Check if WordPress user exists with this email
echo "\n=== CHECKING WORDPRESS USERS ===\n\n";
$wp_user = get_user_by('email', 'instructor@waza.studio');

if ($wp_user) {
    echo "WordPress User Found:\n";
    echo "  ID: {$wp_user->ID}\n";
    echo "  Username: {$wp_user->user_login}\n";
    echo "  Email: {$wp_user->user_email}\n";
    echo "  Display Name: {$wp_user->display_name}\n";
    
    // Check if linked to any instructor
    $linked = $wpdb->get_results($wpdb->prepare("
        SELECT p.ID, p.post_title
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = 'waza_instructor'
        AND pm.meta_key = '_waza_user_id'
        AND pm.meta_value = %d
    ", $wp_user->ID));
    
    if (!empty($linked)) {
        echo "\nLinked to Instructor:\n";
        foreach ($linked as $inst) {
            echo "  - {$inst->post_title} (ID: {$inst->ID})\n";
        }
    } else {
        echo "\nNOT linked to any instructor post\n";
    }
} else {
    echo "No WordPress user found with email: instructor@waza.studio\n";
}

// List all instructors
echo "\n=== ALL INSTRUCTORS ===\n\n";
$all_instructors = get_posts([
    'post_type' => 'waza_instructor',
    'post_status' => ['publish', 'pending', 'draft'],
    'posts_per_page' => -1
]);

foreach ($all_instructors as $inst) {
    echo "{$inst->post_title} (ID: {$inst->ID}, Status: {$inst->post_status})\n";
    $email = get_post_meta($inst->ID, '_waza_email', true);
    $user_id = get_post_meta($inst->ID, '_waza_user_id', true);
    echo "  Email: {$email}\n";
    echo "  User ID: " . ($user_id ?: 'NOT SET') . "\n";
    if ($user_id) {
        $u = get_userdata($user_id);
        if ($u) {
            echo "  WP User: {$u->user_login}\n";
        }
    }
    echo "\n";
}
