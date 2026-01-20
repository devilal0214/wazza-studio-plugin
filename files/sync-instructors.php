<?php
/**
 * Sync existing instructors with WordPress users
 * 
 * This script:
 * 1. Finds all instructors without linked user accounts
 * 2. Creates WordPress user accounts for them (or links to existing email)
 * 3. Sends welcome email with password reset link
 */

require_once '../../../wp-load.php';

global $wpdb;

echo "=== INSTRUCTOR SYNC TOOL ===\n\n";

$instructors = $wpdb->get_results("
    SELECT ID, post_title, post_status
    FROM {$wpdb->posts}
    WHERE post_type = 'waza_instructor'
    AND post_status IN ('publish', 'pending')
    ORDER BY ID
");

$synced = 0;
$skipped = 0;
$errors = 0;

foreach ($instructors as $instructor) {
    echo "\nProcessing: {$instructor->post_title} (ID: {$instructor->ID})\n";
    
    // Check if already linked
    $existing_user_id = get_post_meta($instructor->ID, '_waza_user_id', true);
    if ($existing_user_id) {
        $user = get_userdata($existing_user_id);
        if ($user) {
            echo "  ✓ Already linked to user: {$user->user_login}\n";
            $skipped++;
            continue;
        } else {
            echo "  ⚠ Linked to deleted user ({$existing_user_id}), will re-link\n";
        }
    }
    
    // Get instructor email
    $email = get_post_meta($instructor->ID, '_waza_email', true);
    
    if (!$email || !is_email($email)) {
        echo "  ✗ No valid email found, skipping\n";
        $errors++;
        continue;
    }
    
    // Check if user already exists with this email
    $user = get_user_by('email', $email);
    
    if ($user) {
        echo "  ℹ User already exists: {$user->user_login}\n";
        // Link to existing user
        update_post_meta($instructor->ID, '_waza_user_id', $user->ID);
        echo "  ✓ Linked to existing user\n";
        $synced++;
        continue;
    }
    
    // Create new WordPress user
    $username = sanitize_user(strtolower(str_replace(' ', '_', $instructor->post_title)), true);
    
    // Ensure unique username
    $base_username = $username;
    $counter = 1;
    while (username_exists($username)) {
        $username = $base_username . '_' . $counter;
        $counter++;
    }
    
    // Generate random password (user will set via reset link)
    $password = wp_generate_password(20, true, true);
    
    $user_id = wp_create_user($username, $password, $email);
    
    if (is_wp_error($user_id)) {
        echo "  ✗ Error creating user: " . $user_id->get_error_message() . "\n";
        $errors++;
        continue;
    }
    
    echo "  ✓ Created user: {$username}\n";
    
    // Link instructor to user
    update_post_meta($instructor->ID, '_waza_user_id', $user_id);
    
    // Copy other meta fields if they don't exist
    $phone = get_post_meta($instructor->ID, '_waza_phone', true);
    $bio = get_post_meta($instructor->ID, '_waza_bio', true);
    $experience = get_post_meta($instructor->ID, '_waza_experience', true);
    
    // Get activity type from assigned activities
    $activity = $wpdb->get_row($wpdb->prepare("
        SELECT p.post_title
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = 'waza_activity'
        AND pm.meta_key = '_waza_instructor'
        AND pm.meta_value = %d
        LIMIT 1
    ", $instructor->ID));
    
    if ($activity && !get_post_meta($instructor->ID, '_waza_activity_type', true)) {
        update_post_meta($instructor->ID, '_waza_activity_type', $activity->post_title);
    }
    
    if ($experience && !get_post_meta($instructor->ID, '_waza_experience_years', true)) {
        update_post_meta($instructor->ID, '_waza_experience_years', $experience);
    }
    
    // Send welcome email with password reset link
    $reset_key = get_password_reset_key(get_userdata($user_id));
    
    if (!is_wp_error($reset_key)) {
        $reset_url = network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($username), 'login');
        $dashboard_url = home_url('/instructor-dashboard/');
        
        $subject = sprintf('[%s] Instructor Account Created - Set Your Password', get_bloginfo('name'));
        
        $message = sprintf('Dear %s,', $instructor->post_title) . "\r\n\r\n";
        $message .= "Your instructor account has been set up in our system!\r\n\r\n";
        $message .= "Please set your password to access the instructor dashboard:\r\n";
        $message .= $reset_url . "\r\n\r\n";
        $message .= "Your username: {$username}\r\n";
        $message .= "Your email: {$email}\r\n\r\n";
        $message .= "After setting your password, you can access your dashboard here:\r\n";
        $message .= $dashboard_url . "\r\n\r\n";
        $message .= "In the dashboard, you can:\r\n";
        $message .= "- View your workshops and schedule\r\n";
        $message .= "- Generate QR codes for workshops\r\n";
        $message .= "- Track your students\r\n";
        $message .= "- Update your profile\r\n\r\n";
        $message .= sprintf("Best regards,\r\n%s Team", get_bloginfo('name'));
        
        $sent = wp_mail($email, $subject, $message);
        
        if ($sent) {
            echo "  ✓ Welcome email sent to {$email}\n";
        } else {
            echo "  ⚠ Failed to send email to {$email}\n";
        }
    }
    
    $synced++;
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "SYNC COMPLETE\n";
echo "Total Instructors: " . count($instructors) . "\n";
echo "Synced: {$synced}\n";
echo "Skipped (already linked): {$skipped}\n";
echo "Errors: {$errors}\n";
