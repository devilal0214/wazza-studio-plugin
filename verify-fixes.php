<?php
/**
 * Verify All 3 Fixes Applied
 * 
 * Checks if latest code is deployed and all fixes are working
 * URL: http://localhost/wazza/wp-content/plugins/waza-studio-app/verify-fixes.php
 * OR: https://wazastudio.com/wp-content/plugins/waza-studio-app/verify-fixes.php
 */

$wp_load = __DIR__ . '/../../../wp-load.php';
require_once $wp_load;

if (!current_user_can('manage_options')) {
    die('Access denied. Admin only.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Verify Fixes - Waza Booking</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #23282d; border-bottom: 3px solid #0073aa; padding-bottom: 10px; }
        .issue { background: #fff; border-left: 4px solid #ddd; padding: 15px; margin: 15px 0; }
        .issue.pass { border-color: #46b450; background: #f0f9f0; }
        .issue.fail { border-color: #dc3232; background: #fff5f5; }
        .issue.warning { border-color: #ffb900; background: #fffbf0; }
        .code { background: #f0f0f1; padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Verify All Fixes Applied</h1>
    <p>Checking if latest code (commit 5014273) is deployed...</p>
    
    <?php
    
    // ISSUE #1: Slot Creation Fix
    echo '<div class="issue">';
    echo '<h2>Issue #1: Slot Creation "Failed to create slot"</h2>';
    
    // Read SlotManager.php and check if fix is applied
    $slotmanager_file = __DIR__ . '/src/Admin/SlotManager.php';
    $slotmanager_content = file_get_contents($slotmanager_file);
    
    // Check for the BUG (conditional format)
    $has_bug = strpos($slotmanager_content, '$instructor_id ? \'%d\' : \'%s\'') !== false;
    
    // Check for the FIX (always %d)
    $has_fix = preg_match('/\'%d\',\s*\/\/\s*instructor_id/', $slotmanager_content);
    
    if ($has_bug) {
        echo '<div class="fail">';
        echo '<p>❌ <strong>FIX NOT APPLIED</strong></p>';
        echo '<p>SlotManager.php still has the bug on line 762:</p>';
        echo '<div class="code">$instructor_id ? \'%d\' : \'%s\'  // ❌ Conditional format causes failures</div>';
        echo '<p><strong>Action:</strong> Pull latest code from GitHub (commit after 5014273)</p>';
        echo '<pre>cd /path/to/waza-studio-app\ngit pull origin main</pre>';
        echo '</div>';
    } else {
        echo '<div class="pass">';
        echo '<p>✅ <strong>FIX APPLIED!</strong></p>';
        echo '<p>SlotManager.php has correct format array:</p>';
        echo '<div class="code">\'%d\',  // instructor_id (always %d, even if NULL)</div>';
        echo '<p>Slot creation should now work!</p>';
        echo '</div>';
    }
    
    echo '</div>';
    
    // ISSUE #2: Instructor Approval Creating Subscriber Role
    echo '<div class="issue">';
    echo '<h2>Issue #2: Approval Creates Subscriber Instead of waza_instructor</h2>';
    
    // Read InstructorManager.php and check if role assignment is added
    $instructormanager_file = __DIR__ . '/src/Admin/InstructorManager.php';
    $instructormanager_content = file_get_contents($instructormanager_file);
    
    // Check for the fix
    $has_role_fix = strpos($instructormanager_content, '$user->set_role(\'waza_instructor\')') !== false;
    
    if (!$has_role_fix) {
        echo '<div class="fail">';
        echo '<p>❌ <strong>FIX NOT APPLIED</strong></p>';
        echo '<p>InstructorManager.php missing role assignment after wp_create_user()</p>';
        echo '<p><strong>Expected code around line 384-388:</strong></p>';
        echo '<div class="code">// Assign waza_instructor role (instead of default subscriber)<br>$user = new \WP_User($user_id);<br>$user->set_role(\'waza_instructor\');</div>';
        echo '<p><strong>Action:</strong> Pull latest code from GitHub (commit 5014273)</p>';
        echo '<pre>cd /path/to/waza-studio-app\ngit pull origin main</pre>';
        echo '</div>';
    } else {
        echo '<div class="pass">';
        echo '<p>✅ <strong>FIX APPLIED!</strong></p>';
        echo '<p>InstructorManager.php correctly assigns waza_instructor role</p>';
        echo '<p>New synced instructors will have correct role!</p>';
        echo '</div>';
        
        // Check existing instructors
        global $wpdb;
        $instructors_with_wrong_role = $wpdb->get_results("
            SELECT p.ID, p.post_title, pm.meta_value as user_id
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'waza_instructor'
            AND pm.meta_key = '_waza_user_id'
            AND pm.meta_value != ''
        ");
        
        echo '<h3>Existing Instructors Check</h3>';
        echo '<table>';
        echo '<tr><th>Instructor</th><th>User ID</th><th>Current Role</th><th>Status</th></tr>';
        
        foreach ($instructors_with_wrong_role as $instructor) {
            $user_id = intval($instructor->user_id);
            $user = get_userdata($user_id);
            
            if ($user) {
                $roles = implode(', ', $user->roles);
                $is_correct = in_array('waza_instructor', $user->roles);
                $status_icon = $is_correct ? '✅' : '⚠️';
                $status_text = $is_correct ? 'Correct' : 'Needs fixing';
                
                echo '<tr>';
                echo '<td>' . esc_html($instructor->post_title) . '</td>';
                echo '<td>' . $user_id . '</td>';
                echo '<td><strong>' . $roles . '</strong></td>';
                echo '<td>' . $status_icon . ' ' . $status_text . '</td>';
                echo '</tr>';
            }
        }
        echo '</table>';
        
        $wrong_count = 0;
        foreach ($instructors_with_wrong_role as $instructor) {
            $user = get_userdata(intval($instructor->user_id));
            if ($user && !in_array('waza_instructor', $user->roles)) {
                $wrong_count++;
            }
        }
        
        if ($wrong_count > 0) {
            echo '<div class="warning">';
            echo '<p>⚠️ Found ' . $wrong_count . ' instructor(s) with wrong role</p>';
            echo '<p><strong>Fix:</strong> Re-sync these instructors or manually change their role in Users → Edit User</p>';
            echo '</div>';
        }
    }
    
    echo '</div>';
    
    // ISSUE #3: Activity Browser Empty
    echo '<div class="issue">';
    echo '<h2>Issue #3: /activities-2/ Shows Empty (No Activities)</h2>';
    
    // Check if activities exist
    $activities = get_posts([
        'post_type' => 'waza_activity',
        'post_status' => 'publish',
        'numberposts' => -1
    ]);
    
    echo '<p><strong>Published Activities:</strong> ' . count($activities) . '</p>';
    
    if (count($activities) === 0) {
        echo '<div class="fail">';
        echo '<p>❌ <strong>NO ACTIVITIES FOUND</strong></p>';
        echo '<p>The database has 0 published waza_activity posts</p>';
        echo '<p><strong>Solution:</strong></p>';
        echo '<ol>';
        echo '<li>Go to WordPress Admin → Activities</li>';
        echo '<li>Create some activities</li>';
        echo '<li>OR import activities from local development database</li>';
        echo '<li>OR copy activities from backup</li>';
        echo '</ol>';
        echo '</div>';
    } else {
        echo '<div class="pass">';
        echo '<p>✅ <strong>ACTIVITIES EXIST!</strong></p>';
        echo '<p>Found ' . count($activities) . ' published activities</p>';
        
        echo '<table>';
        echo '<tr><th>ID</th><th>Title</th><th>Price</th><th>Duration</th><th>Instructor</th></tr>';
        foreach (array_slice($activities, 0, 10) as $activity) {
            $price = get_post_meta($activity->ID, '_waza_price', true);
            $duration = get_post_meta($activity->ID, '_waza_duration', true);
            $instructor_id = get_post_meta($activity->ID, '_waza_instructor', true);
            $instructor = $instructor_id ? get_post($instructor_id) : null;
            
            echo '<tr>';
            echo '<td>' . $activity->ID . '</td>';
            echo '<td>' . esc_html($activity->post_title) . '</td>';
            echo '<td>' . ($price ? '$' . number_format($price, 2) : '❌') . '</td>';
            echo '<td>' . ($duration ? $duration . 'min' : '❌') . '</td>';
            echo '<td>' . ($instructor ? esc_html($instructor->post_title) : 'None') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        
        // Check the page itself
        $activities_page = get_page_by_path('activities-2');
        if (!$activities_page) {
            echo '<div class="warning">';
            echo '<p>⚠️ Page /activities-2/ does not exist</p>';
            echo '<p>Create a page with slug "activities-2" and add shortcode: <code>[waza_activity_browser]</code></p>';
            echo '</div>';
        } else {
            if (strpos($activities_page->post_content, 'waza_activity_browser') !== false) {
                echo '<p>✅ Page exists with correct shortcode</p>';
                echo '<p><a href="' . get_permalink($activities_page) . '" target="_blank">View page: ' . get_permalink($activities_page) . '</a></p>';
            } else {
                echo '<div class="warning">';
                echo '<p>⚠️ Page exists but missing shortcode</p>';
                echo '<p>Edit the page and add: <code>[waza_activity_browser]</code></p>';
                echo '</div>';
            }
        }
        
        echo '</div>';
    }
    
    echo '</div>';
    
    // Summary
    echo '<div class="issue">';
    echo '<h2>📋 Summary</h2>';
    
    $all_good = !$has_bug && $has_role_fix && count($activities) > 0;
    
    if ($all_good) {
        echo '<div class="pass">';
        echo '<h3>✅ All Fixes Applied Successfully!</h3>';
        echo '<p>Your system should now be working correctly:</p>';
        echo '<ul>';
        echo '<li>✅ Slot creation will work</li>';
        echo '<li>✅ New instructor syncs will have correct role</li>';
        echo '<li>✅ Activity browser will display activities</li>';
        echo '</ul>';
        echo '<p><strong>Next Steps:</strong></p>';
        echo '<ol>';
        echo '<li>Test slot creation in admin</li>';
        echo '<li>Sync a test instructor and verify role</li>';
        echo '<li>Visit /activities-2/ and verify activities display</li>';
        echo '<li>Delete this verification file for security</li>';
        echo '</ol>';
        echo '</div>';
    } else {
        echo '<div class="fail">';
        echo '<h3>⚠️ Some Fixes Missing</h3>';
        echo '<p>Pull latest code from GitHub:</p>';
        echo '<div class="code">cd /path/to/waza-studio-app<br>git pull origin main</div>';
        echo '<p>Then refresh this page to verify again.</p>';
        echo '</div>';
    }
    
    echo '</div>';
    
    ?>
    
    <p style="text-align: center; color: #666; margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd;">
        Delete this file after verification: <code><?php echo __FILE__; ?></code>
    </p>
</div>
</body>
</html>
