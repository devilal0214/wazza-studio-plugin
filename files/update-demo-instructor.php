<?php
/**
 * Update Demo Instructor with email and sync
 */

require_once '../../../wp-load.php';

// Update Demo Instructor email
update_post_meta(27, '_waza_email', 'demo@instructor.com');

echo "✓ Updated Demo Instructor email to demo@instructor.com\n";

// Now verify all instructors
echo "\n=== INSTRUCTOR STATUS ===\n\n";

$instructors = get_posts([
    'post_type' => 'waza_instructor',
    'post_status' => ['publish', 'pending'],
    'posts_per_page' => -1
]);

foreach ($instructors as $instructor) {
    echo "Instructor: {$instructor->post_title}\n";
    
    $user_id = get_post_meta($instructor->ID, '_waza_user_id', true);
    $email = get_post_meta($instructor->ID, '_waza_email', true);
    
    echo "  Email: {$email}\n";
    
    if ($user_id) {
        $user = get_userdata($user_id);
        if ($user) {
            echo "  ✓ Linked to user: {$user->user_login}\n";
            echo "  User email: {$user->user_email}\n";
        } else {
            echo "  ✗ User not found (ID: {$user_id})\n";
        }
    } else {
        echo "  ⚠ Not linked to user\n";
    }
    echo "\n";
}
