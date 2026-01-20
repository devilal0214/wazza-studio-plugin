<?php
/**
 * Check login pages
 */

define('WP_USE_THEMES', false);
require_once(__DIR__ . '/../../../wp-load.php');

// Get login page
$page1 = get_page_by_path('login');
$page2 = get_page_by_path('login-2');

if ($page1) {
    echo "Login Page 1 (ID: {$page1->ID}):\n";
    echo "URL: " . get_permalink($page1->ID) . "\n";
    echo "Content:\n{$page1->post_content}\n\n";
}

if ($page2) {
    echo "Login Page 2 (ID: {$page2->ID}):\n";
    echo "URL: " . get_permalink($page2->ID) . "\n";
    echo "Content:\n{$page2->post_content}\n\n";
}

// Check registered shortcodes
global $shortcode_tags;
echo "Registered Waza shortcodes:\n";
foreach ($shortcode_tags as $tag => $callback) {
    if (strpos($tag, 'waza') !== false) {
        echo "- [{$tag}]\n";
    }
}
