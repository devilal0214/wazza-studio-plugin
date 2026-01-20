<?php
/**
 * Update instructor name
 */

require_once '../../../wp-load.php';

$instructor_id = 78;

wp_update_post([
    'ID' => $instructor_id,
    'post_title' => 'Wazza Instructor'
]);

echo "✓ Updated instructor name to 'Wazza Instructor'\n\n";

$instructor = get_post($instructor_id);
$user_id = get_post_meta($instructor_id, '_waza_user_id', true);
$user = get_userdata($user_id);

echo "=== INSTRUCTOR DETAILS ===\n";
echo "Name: {$instructor->post_title}\n";
echo "Status: {$instructor->post_status}\n";
echo "Email: " . get_post_meta($instructor_id, '_waza_email', true) . "\n";
echo "\nWordPress Login:\n";
echo "  Username: {$user->user_login}\n";
echo "  Email: {$user->user_email}\n";
echo "\n✅ Can now login and access /instructor-dashboard/\n";
