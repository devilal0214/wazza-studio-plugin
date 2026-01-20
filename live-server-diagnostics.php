<?php
/**
 * Live Server Diagnostics & Fixes
 * 
 * Run this file on wazastudio.com to diagnose and fix all 7 reported issues
 * 
 * URL: https://wazastudio.com/wp-content/plugins/waza-studio-app/live-server-diagnostics.php
 * 
 * After running, delete this file for security.
 */

// Load WordPress
$wp_load = __DIR__ . '/../../../wp-load.php';
require_once $wp_load;

// Security check
if (!current_user_can('manage_options')) {
    die('Access denied. Admin only.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Waza Booking - Live Server Diagnostics</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #23282d; border-bottom: 3px solid #0073aa; padding-bottom: 10px; }
        h2 { color: #0073aa; margin-top: 30px; }
        .issue { background: #fff; border-left: 4px solid #ddd; padding: 15px; margin: 15px 0; }
        .issue.error { border-color: #dc3232; background: #fff8f8; }
        .issue.warning { border-color: #ffb900; background: #fffbf0; }
        .issue.success { border-color: #46b450; background: #f8fff8; }
        .issue h3 { margin-top: 0; color: #23282d; }
        .code { background: #f0f0f1; padding: 10px; border-radius: 4px; font-family: monospace; overflow-x: auto; margin: 10px 0; }
        .label { font-weight: bold; display: inline-block; min-width: 200px; }
        .fix-button { background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 10px 5px 10px 0; }
        .fix-button:hover { background: #005a87; }
        .fix-button.danger { background: #dc3232; }
        .fix-button.danger:hover { background: #a00; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f0f0f1; font-weight: 600; }
        .step { background: #e5f5fa; padding: 10px; margin: 10px 0; border-radius: 4px; }
        .step-number { background: #0073aa; color: white; border-radius: 50%; width: 25px; height: 25px; display: inline-block; text-align: center; line-height: 25px; margin-right: 10px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Waza Booking - Live Server Diagnostics</h1>
    <p>Running diagnostics for all 7 reported issues on <?php echo home_url(); ?></p>
    
    <?php
    
    // ISSUE #1: Social link buttons not working (already fixed in code)
    echo '<div class="issue success">';
    echo '<h3>Issue #1: Social Link Buttons Not Working</h3>';
    echo '<p><strong>Status:</strong> ✅ FIXED in code (commit required)</p>';
    echo '<p><strong>Problem:</strong> Shortcode name mismatch in InstructorFrontend.php line 45</p>';
    echo '<p><strong>Fix Applied:</strong> Changed <code>waza_instructor_register</code> → <code>waza_instructor_registration</code></p>';
    echo '<p><strong>Action Required:</strong> Git pull latest code on live server</p>';
    echo '</div>';
    
    // ISSUE #2: Razorpay payment blocked
    echo '<div class="issue warning">';
    echo '<h3>Issue #2: Razorpay Payment Blocked - Website Mismatch</h3>';
    echo '<p><strong>Status:</strong> ⚠️ Manual Configuration Required</p>';
    echo '<p><strong>Problem:</strong> <code>wazastudio.com</code> not whitelisted in Razorpay dashboard</p>';
    echo '<p><strong>Solution:</strong></p>';
    echo '<div class="step"><span class="step-number">1</span> Log in to <a href="https://dashboard.razorpay.com/" target="_blank">Razorpay Dashboard</a></div>';
    echo '<div class="step"><span class="step-number">2</span> Go to: Settings → Website & App Settings → Whitelisted Domains</div>';
    echo '<div class="step"><span class="step-number">3</span> Add domain: <code>wazastudio.com</code></div>';
    echo '<div class="step"><span class="step-number">4</span> Save changes and test payment</div>';
    
    // Check current Razorpay config
    $razorpay_key = get_option('waza_razorpay_key_id');
    $razorpay_secret = get_option('waza_razorpay_key_secret');
    echo '<p><strong>Current Config:</strong></p>';
    echo '<div class="code">';
    echo 'Key ID: ' . ($razorpay_key ? substr($razorpay_key, 0, 10) . '...' : '❌ NOT SET') . '<br>';
    echo 'Secret: ' . ($razorpay_secret ? '✅ SET (' . strlen($razorpay_secret) . ' chars)' : '❌ NOT SET');
    echo '</div>';
    echo '</div>';
    
    // ISSUE #3: /activities-2/ not showing activities
    echo '<div class="issue">';
    echo '<h3>Issue #3: Activities Page Not Showing Activities</h3>';
    
    // Check if activities exist
    $activities = get_posts([
        'post_type' => 'waza_activity',
        'post_status' => 'publish',
        'numberposts' => -1
    ]);
    
    echo '<p><strong>Published Activities:</strong> ' . count($activities) . '</p>';
    
    if (count($activities) > 0) {
        echo '<p style="color: green;">✅ Activities exist in database</p>';
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
            echo '<td>' . ($price ? '$' . $price : '❌ Not set') . '</td>';
            echo '<td>' . ($duration ? $duration . ' min' : '❌ Not set') . '</td>';
            echo '<td>' . ($instructor ? esc_html($instructor->post_title) : 'None') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        
        // Check /activities-2/ page
        $activities_page = get_page_by_path('activities-2');
        if ($activities_page) {
            echo '<p>✅ Page exists: <a href="' . get_permalink($activities_page) . '" target="_blank">' . get_permalink($activities_page) . '</a></p>';
            echo '<p><strong>Page content:</strong></p>';
            echo '<div class="code">' . esc_html($activities_page->post_content) . '</div>';
            
            if (strpos($activities_page->post_content, 'waza_activity_browser') !== false) {
                echo '<p style="color: green;">✅ Shortcode found in page content</p>';
            } else {
                echo '<p style="color: red;">❌ Shortcode NOT found - page may have wrong shortcode</p>';
            }
        } else {
            echo '<p style="color: red;">❌ /activities-2/ page does not exist</p>';
        }
        
        echo '<p><strong>Status:</strong> Activities exist. If page is blank, it may be a template/theme issue.</p>';
        echo '<p><strong>Action:</strong> Visit <a href="' . home_url('/activities-2/') . '" target="_blank">/activities-2/</a> and check browser console for JavaScript errors</p>';
    } else {
        echo '<p style="color: red;">❌ NO activities found</p>';
        echo '<p><strong>Action:</strong> Create some activities in WordPress admin</p>';
    }
    echo '</div>';
    
    // ISSUE #4: Instructor registration not saving
    echo '<div class="issue">';
    echo '<h3>Issue #4: Instructor Registration Not Saving Data</h3>';
    echo '<p><strong>Root Cause:</strong> Same as Issue #1 - JavaScript not loading due to shortcode mismatch</p>';
    echo '<p><strong>Fix:</strong> Pull latest code (fixes shortcode name)</p>';
    
    // Check registration page
    $reg_page = get_page_by_path('instructor-register');
    if ($reg_page) {
        echo '<p>✅ Page exists: <a href="' . get_permalink($reg_page) . '" target="_blank">' . get_permalink($reg_page) . '</a></p>';
        echo '<p><strong>Page content:</strong></p>';
        echo '<div class="code">' . esc_html($reg_page->post_content) . '</div>';
    } else {
        echo '<p style="color: red;">❌ /instructor-register/ page not found</p>';
    }
    
    // Check if AJAX is working
    echo '<p><strong>AJAX Handler Registered:</strong> ';
    echo has_action('wp_ajax_nopriv_waza_submit_instructor_application') ? '✅ Yes' : '❌ No';
    echo '</p>';
    echo '</div>';
    
    // ISSUE #5: Sync creates Subscriber instead of waza_instructor
    echo '<div class="issue success">';
    echo '<h3>Issue #5: Sync Button Creating Subscriber Role Instead of waza_instructor</h3>';
    echo '<p><strong>Status:</strong> ✅ FIXED in code</p>';
    echo '<p><strong>Problem:</strong> sync_single_instructor() didn\'t assign role after creating user</p>';
    echo '<p><strong>Fix Applied:</strong> Added <code>$user->set_role(\'waza_instructor\')</code> in InstructorManager.php</p>';
    echo '<p><strong>Action Required:</strong> Git pull latest code on live server</p>';
    
    // Check if role exists
    $role_exists = get_role('waza_instructor') !== null;
    echo '<p><strong>waza_instructor Role:</strong> ' . ($role_exists ? '✅ Exists' : '❌ NOT REGISTERED') . '</p>';
    
    if (!$role_exists) {
        echo '<p style="color: red;">⚠️ CRITICAL: waza_instructor role not registered!</p>';
        echo '<p>This role should be created on plugin activation. Try deactivating and reactivating the plugin.</p>';
    }
    
    // Check existing instructors
    $instructor_posts = get_posts([
        'post_type' => 'waza_instructor',
        'numberposts' => -1,
        'post_status' => ['publish', 'pending', 'draft']
    ]);
    
    echo '<p><strong>Instructor Posts:</strong> ' . count($instructor_posts) . '</p>';
    
    if (count($instructor_posts) > 0) {
        echo '<table>';
        echo '<tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>User ID</th><th>User Role</th></tr>';
        foreach ($instructor_posts as $instructor) {
            $email = get_post_meta($instructor->ID, '_waza_email', true);
            $user_id = get_post_meta($instructor->ID, '_waza_user_id', true);
            $user_role = 'N/A';
            
            if ($user_id) {
                $user = get_userdata($user_id);
                if ($user) {
                    $user_role = implode(', ', $user->roles);
                } else {
                    $user_role = '❌ User not found';
                }
            }
            
            $status_color = $user_role === 'waza_instructor' ? 'green' : ($user_role === 'subscriber' ? 'red' : 'orange');
            
            echo '<tr>';
            echo '<td>' . $instructor->ID . '</td>';
            echo '<td>' . esc_html($instructor->post_title) . '</td>';
            echo '<td>' . esc_html($email) . '</td>';
            echo '<td>' . $instructor->post_status . '</td>';
            echo '<td>' . ($user_id ? $user_id : '❌ Not linked') . '</td>';
            echo '<td style="color: ' . $status_color . '"><strong>' . $user_role . '</strong></td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    echo '</div>';
    
    // ISSUE #6: Slots not being created
    echo '<div class="issue">';
    echo '<h3>Issue #6: Slots Not Creating - "Failed to create slot. Please try again."</h3>';
    
    global $wpdb;
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}waza_slots'") === $wpdb->prefix . 'waza_slots';
    
    echo '<p><strong>waza_slots Table:</strong> ' . ($table_exists ? '✅ Exists' : '❌ NOT FOUND') . '</p>';
    
    if (!$table_exists) {
        echo '<p style="color: red;">⚠️ CRITICAL: waza_slots table does not exist!</p>';
        echo '<p>The table should be created on plugin activation. Try deactivating and reactivating the plugin.</p>';
        echo '<form method="post" style="display: inline;">';
        echo '<input type="hidden" name="fix_action" value="create_slots_table">';
        echo '<button type="submit" class="fix-button">Create waza_slots Table Now</button>';
        echo '</form>';
    } else {
        // Check table structure
        $columns = $wpdb->get_results("DESCRIBE {$wpdb->prefix}waza_slots");
        echo '<p><strong>Table Structure:</strong></p>';
        echo '<table>';
        echo '<tr><th>Column</th><th>Type</th><th>Null</th><th>Default</th></tr>';
        foreach ($columns as $column) {
            echo '<tr>';
            echo '<td>' . $column->Field . '</td>';
            echo '<td>' . $column->Type . '</td>';
            echo '<td>' . $column->Null . '</td>';
            echo '<td>' . ($column->Default ?? 'NULL') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        
        // Count slots
        $slot_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}waza_slots");
        echo '<p><strong>Existing Slots:</strong> ' . $slot_count . '</p>';
        
        // Show recent slots
        if ($slot_count > 0) {
            $recent_slots = $wpdb->get_results("
                SELECT s.*, p.post_title as activity_name, i.post_title as instructor_name
                FROM {$wpdb->prefix}waza_slots s
                LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
                LEFT JOIN {$wpdb->posts} i ON s.instructor_id = i.ID
                ORDER BY s.id DESC
                LIMIT 10
            ");
            
            echo '<p><strong>Recent Slots:</strong></p>';
            echo '<table>';
            echo '<tr><th>ID</th><th>Activity</th><th>Instructor</th><th>Date/Time</th><th>Capacity</th><th>Status</th></tr>';
            foreach ($recent_slots as $slot) {
                echo '<tr>';
                echo '<td>' . $slot->id . '</td>';
                echo '<td>' . esc_html($slot->activity_name) . '</td>';
                echo '<td>' . esc_html($slot->instructor_name ?? 'None') . '</td>';
                echo '<td>' . $slot->start_datetime . '</td>';
                echo '<td>' . $slot->capacity . '</td>';
                echo '<td>' . $slot->status . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        
        echo '<p><strong>Possible Issues:</strong></p>';
        echo '<ul>';
        echo '<li>Check JavaScript console for errors when trying to create slot</li>';
        echo '<li>Verify AJAX nonce is valid</li>';
        echo '<li>Check that activity_id is valid</li>';
        echo '<li>Ensure no database permission issues</li>';
        echo '</ul>';
    }
    echo '</div>';
    
    // ISSUE #7: Instructor dashboard - Invalid activity selected
    echo '<div class="issue">';
    echo '<h3>Issue #7: Instructor Dashboard Slot Creation - "Invalid activity selected"</h3>';
    
    echo '<p><strong>Root Cause:</strong> Instructors must be assigned to activities via <code>_waza_instructor</code> meta key</p>';
    
    // Check instructor-activity assignments
    $assignments = $wpdb->get_results("
        SELECT p.ID, p.post_title, pm.meta_value as instructor_id, i.post_title as instructor_name
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        LEFT JOIN {$wpdb->posts} i ON pm.meta_value = i.ID
        WHERE p.post_type = 'waza_activity'
        AND pm.meta_key = '_waza_instructor'
        AND pm.meta_value != ''
        ORDER BY p.post_title
    ");
    
    echo '<p><strong>Activity → Instructor Assignments:</strong> ' . count($assignments) . '</p>';
    
    if (count($assignments) > 0) {
        echo '<table>';
        echo '<tr><th>Activity ID</th><th>Activity Name</th><th>Instructor ID</th><th>Instructor Name</th></tr>';
        foreach ($assignments as $assignment) {
            echo '<tr>';
            echo '<td>' . $assignment->ID . '</td>';
            echo '<td>' . esc_html($assignment->post_title) . '</td>';
            echo '<td>' . $assignment->instructor_id . '</td>';
            echo '<td>' . esc_html($assignment->instructor_name ?? '❌ Instructor not found') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '<p style="color: green;">✅ Assignments exist</p>';
        echo '<p><strong>Note:</strong> Instructors can only create slots for activities they are assigned to</p>';
    } else {
        echo '<p style="color: red;">❌ NO assignments found</p>';
        echo '<p><strong>Solution:</strong> In WordPress admin, edit activities and assign instructors</p>';
    }
    
    echo '</div>';
    
    // Handle fix actions
    if (isset($_POST['fix_action'])) {
        echo '<div class="issue">';
        echo '<h3>Fix Action Results</h3>';
        
        if ($_POST['fix_action'] === 'create_slots_table') {
            // Create slots table
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE {$wpdb->prefix}waza_slots (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                activity_id bigint(20) unsigned NOT NULL,
                instructor_id bigint(20) unsigned DEFAULT NULL,
                start_datetime datetime NOT NULL,
                end_datetime datetime NOT NULL,
                capacity int(11) NOT NULL DEFAULT 20,
                booked_count int(11) NOT NULL DEFAULT 0,
                price decimal(10,2) NOT NULL DEFAULT 0.00,
                location varchar(255) DEFAULT NULL,
                notes text,
                status varchar(50) NOT NULL DEFAULT 'active',
                created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY activity_id (activity_id),
                KEY instructor_id (instructor_id),
                KEY start_datetime (start_datetime),
                KEY status (status)
            ) $charset_collate;";
            
            dbDelta($sql);
            
            echo '<p style="color: green;">✅ Table created successfully!</p>';
            echo '<p>Refresh this page to verify.</p>';
        }
        
        echo '</div>';
    }
    
    ?>
    
    <h2>📋 Summary & Action Plan</h2>
    
    <div class="issue">
        <h3>Required Actions</h3>
        <ol>
            <li><strong>Pull Latest Code:</strong>
                <div class="code">cd /path/to/plugins/waza-studio-app<br>git pull origin main</div>
                This fixes Issues #1 (social links) and #5 (sync role)
            </li>
            <li><strong>Configure Razorpay:</strong>
                <div class="code">Add wazastudio.com to Razorpay dashboard whitelist</div>
            </li>
            <li><strong>Verify Activities:</strong> Check if activities exist and are published</li>
            <li><strong>Check Database:</strong> Ensure waza_slots table exists</li>
            <li><strong>Assign Instructors:</strong> Edit activities and assign instructors via _waza_instructor meta</li>
            <li><strong>Test Registration:</strong> After code pull, test instructor registration form</li>
            <li><strong>Delete This File:</strong>
                <div class="code">rm <?php echo __FILE__; ?></div>
                For security, delete this diagnostic file after use
            </li>
        </ol>
    </div>
    
    <div class="issue warning">
        <h3>⚠️ Security Notice</h3>
        <p><strong>DELETE THIS FILE AFTER USE!</strong></p>
        <p>This diagnostic file contains sensitive information and should not remain on the live server.</p>
        <form method="post" onsubmit="return confirm('Are you sure you want to delete this file? Make sure you have saved any important information first.');">
            <input type="hidden" name="fix_action" value="delete_self">
            <button type="submit" class="fix-button danger">🗑️ Delete This Diagnostic File</button>
        </form>
    </div>
    
    <?php
    // Handle self-delete
    if (isset($_POST['fix_action']) && $_POST['fix_action'] === 'delete_self') {
        if (unlink(__FILE__)) {
            echo '<div class="issue success">';
            echo '<h3>✅ File Deleted Successfully</h3>';
            echo '<p>This diagnostic file has been removed from the server.</p>';
            echo '</div>';
        } else {
            echo '<div class="issue error">';
            echo '<h3>❌ Failed to Delete File</h3>';
            echo '<p>Please manually delete: <code>' . __FILE__ . '</code></p>';
            echo '</div>';
        }
    }
    ?>
    
    <p style="text-align: center; color: #666; margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd;">
        Waza Booking System v1.0 | Diagnostics Generated: <?php echo date('Y-m-d H:i:s'); ?>
    </p>
</div>
</body>
</html>
