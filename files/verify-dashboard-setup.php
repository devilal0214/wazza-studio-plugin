<?php
/**
 * Verify instructor dashboard setup
 */

require_once '../../../wp-load.php';

// Check if user is logged in
$current_user = wp_get_current_user();
echo "=== CURRENT USER ===\n";
echo "ID: {$current_user->ID}\n";
echo "Username: {$current_user->user_login}\n";
echo "Email: {$current_user->user_email}\n\n";

// Check instructor
$instructors = get_posts([
    'post_type' => 'waza_instructor',
    'meta_key' => '_waza_user_id',
    'meta_value' => $current_user->ID,
    'posts_per_page' => 1,
    'post_status' => 'publish'
]);

if (!empty($instructors)) {
    $instructor = $instructors[0];
    echo "=== INSTRUCTOR ===\n";
    echo "ID: {$instructor->ID}\n";
    echo "Name: {$instructor->post_title}\n";
    echo "Status: {$instructor->post_status}\n\n";
    
    // Check workshops
    global $wpdb;
    $workshops = $wpdb->get_results($wpdb->prepare("
        SELECT 
            s.*,
            p.post_title as activity_title
        FROM {$wpdb->prefix}waza_slots s
        LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
        LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_waza_instructor'
        WHERE pm.meta_value = %d
        ORDER BY s.start_datetime ASC
        LIMIT 5
    ", $instructor->ID));
    
    echo "=== WORKSHOPS (First 5) ===\n";
    foreach ($workshops as $w) {
        echo "- {$w->activity_title} on " . date('M j, Y g:i A', strtotime($w->start_datetime)) . "\n";
    }
}

// Check if JavaScript file exists
$js_file = __DIR__ . '/../assets/instructor.js';
echo "\n=== JAVASCRIPT FILE ===\n";
echo "Path: {$js_file}\n";
echo "Exists: " . (file_exists($js_file) ? 'YES' : 'NO') . "\n";
echo "Size: " . (file_exists($js_file) ? filesize($js_file) . ' bytes' : 'N/A') . "\n";
echo "Modified: " . (file_exists($js_file) ? date('Y-m-d H:i:s', filemtime($js_file)) : 'N/A') . "\n";

// Check CSS file
$css_file = __DIR__ . '/../assets/instructor.css';
echo "\n=== CSS FILE ===\n";
echo "Path: {$css_file}\n";
echo "Exists: " . (file_exists($css_file) ? 'YES' : 'NO') . "\n";
echo "Size: " . (file_exists($css_file) ? filesize($css_file) . ' bytes' : 'N/A') . "\n";

// Test AJAX endpoint
echo "\n=== TESTING AJAX ===\n";
echo "AJAX URL: " . admin_url('admin-ajax.php') . "\n";

// Simulate getting workshops
$_POST['action'] = 'waza_get_instructor_workshops';
$_POST['nonce'] = wp_create_nonce('waza_instructor_nonce');
$_POST['filter'] = 'upcoming';

echo "\n✓ Setup complete\n";
echo "\nTo debug:\n";
echo "1. Open browser console (F12)\n";
echo "2. Check for JavaScript errors\n";
echo "3. Type: console.log('Test')\n";
echo "4. Hard refresh: Ctrl+Shift+R (clear cache)\n";
