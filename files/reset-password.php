<?php
/**
 * Reset user password
 */

define('WP_USE_THEMES', false);
require_once(__DIR__ . '/../../../wp-load.php');

$user = get_user_by('email', 'team@jaiveeru.co.in');
if ($user) {
    $new_password = 'Password@123';
    wp_set_password($new_password, $user->ID);
    
    echo "Password reset successfully for user: {$user->user_email}\n";
    echo "New password: {$new_password}\n";
    echo "User ID: {$user->ID}\n";
} else {
    echo "User not found\n";
}
