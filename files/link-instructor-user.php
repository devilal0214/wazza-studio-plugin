<?php
/**
 * Create instructor post for existing WordPress user
 */

require_once '../../../wp-load.php';

$user = get_user_by('email', 'instructor@waza.studio');

if (!$user) {
    die("User not found!\n");
}

echo "Found WordPress User:\n";
echo "  ID: {$user->ID}\n";
echo "  Username: {$user->user_login}\n";
echo "  Email: {$user->user_email}\n";
echo "  Display Name: {$user->display_name}\n\n";

// Check if instructor post already exists
global $wpdb;
$existing = $wpdb->get_var($wpdb->prepare("
    SELECT p.ID
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
    WHERE p.post_type = 'waza_instructor'
    AND pm.meta_key = '_waza_user_id'
    AND pm.meta_value = %d
", $user->ID));

if ($existing) {
    echo "✓ Instructor post already exists (ID: {$existing})\n";
    exit;
}

// Create instructor post
$instructor_data = [
    'post_type' => 'waza_instructor',
    'post_title' => $user->display_name ?: 'Wazza Instructor',
    'post_status' => 'publish', // Approved by default
    'post_content' => '',
];

$instructor_id = wp_insert_post($instructor_data);

if (is_wp_error($instructor_id)) {
    die("Error creating instructor: " . $instructor_id->get_error_message() . "\n");
}

echo "✓ Created instructor post (ID: {$instructor_id})\n";

// Add meta data
update_post_meta($instructor_id, '_waza_user_id', $user->ID);
update_post_meta($instructor_id, '_waza_email', $user->user_email);
update_post_meta($instructor_id, '_waza_phone', '');

echo "✓ Linked user to instructor post\n";
echo "✓ Added email meta: {$user->user_email}\n\n";

echo "=== VERIFICATION ===\n";
$check_user_id = get_post_meta($instructor_id, '_waza_user_id', true);
echo "Instructor #{$instructor_id} linked to User #{$check_user_id}\n";
echo "Status: " . get_post_status($instructor_id) . "\n\n";

echo "✅ The user can now:\n";
echo "1. Login at /wp-login.php\n";
echo "   Username: {$user->user_login}\n";
echo "   Email: {$user->user_email}\n";
echo "2. Access dashboard at /instructor-dashboard/\n";
