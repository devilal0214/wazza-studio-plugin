<?php
/**
 * Debug Activities Not Showing
 * 
 * Checks why /activities-2/ page is empty
 * URL: http://localhost/wazza/wp-content/plugins/waza-studio-app/debug-activities.php
 * OR: https://wazastudio.com/wp-content/plugins/waza-studio-app/debug-activities.php
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
    <title>Debug Activities - Waza Booking</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #23282d; border-bottom: 3px solid #0073aa; padding-bottom: 10px; }
        .error { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 10px 0; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 10px 0; }
        .warning { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 10px 0; }
        pre { background: #f0f0f0; padding: 10px; overflow-x: auto; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }
        .code { background: #f0f0f1; padding: 3px 6px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Debug Activities Not Showing</h1>
    
    <?php
    
    // CHECK 1: Count published activities
    echo '<h2>1️⃣ Check Published Activities</h2>';
    
    $activities = get_posts([
        'post_type' => 'waza_activity',
        'post_status' => 'publish',
        'numberposts' => -1
    ]);
    
    $count = count($activities);
    echo '<p><strong>Published Activities:</strong> ' . $count . '</p>';
    
    if ($count === 0) {
        echo '<div class="error">';
        echo '<h3>❌ NO ACTIVITIES FOUND!</h3>';
        echo '<p>The database has <strong>0 published waza_activity posts</strong></p>';
        echo '<p>This is why the activity browser is empty.</p>';
        echo '<p><strong>Solution:</strong></p>';
        echo '<ol>';
        echo '<li>Go to WordPress Admin → Activities</li>';
        echo '<li>Check if any activities exist (they might be in Draft/Pending status)</li>';
        echo '<li>If no activities exist, create some new ones</li>';
        echo '<li>If activities exist but are drafts, publish them</li>';
        echo '</ol>';
        echo '</div>';
        
        // Check draft activities
        $draft_activities = get_posts([
            'post_type' => 'waza_activity',
            'post_status' => 'draft',
            'numberposts' => -1
        ]);
        
        if (count($draft_activities) > 0) {
            echo '<div class="warning">';
            echo '<h3>⚠️ Found ' . count($draft_activities) . ' Draft Activities</h3>';
            echo '<p>These activities exist but are not published:</p>';
            echo '<table>';
            echo '<tr><th>ID</th><th>Title</th><th>Status</th><th>Action</th></tr>';
            foreach ($draft_activities as $activity) {
                echo '<tr>';
                echo '<td>' . $activity->ID . '</td>';
                echo '<td>' . esc_html($activity->post_title) . '</td>';
                echo '<td><strong>DRAFT</strong></td>';
                echo '<td><a href="' . admin_url('post.php?post=' . $activity->ID . '&action=edit') . '" target="_blank">Edit & Publish</a></td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '</div>';
        }
        
    } else {
        echo '<div class="success">';
        echo '<p>✅ Found ' . $count . ' published activities</p>';
        echo '</div>';
        
        echo '<table>';
        echo '<tr><th>ID</th><th>Title</th><th>Price</th><th>Duration</th><th>Instructor</th><th>Image</th></tr>';
        
        foreach ($activities as $activity) {
            $price = get_post_meta($activity->ID, '_waza_price', true);
            $duration = get_post_meta($activity->ID, '_waza_duration', true);
            $instructor_id = get_post_meta($activity->ID, '_waza_instructor', true);
            $instructor = $instructor_id ? get_post($instructor_id) : null;
            $has_image = has_post_thumbnail($activity->ID);
            
            echo '<tr>';
            echo '<td>' . $activity->ID . '</td>';
            echo '<td>' . esc_html($activity->post_title) . '</td>';
            echo '<td>' . ($price ? '$' . number_format($price, 2) : '❌ Not set') . '</td>';
            echo '<td>' . ($duration ? $duration . ' min' : '❌ Not set') . '</td>';
            echo '<td>' . ($instructor ? esc_html($instructor->post_title) : '❌ None') . '</td>';
            echo '<td>' . ($has_image ? '✅' : '❌') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    
    // CHECK 2: Verify page exists
    echo '<h2>2️⃣ Check /activities-2/ Page</h2>';
    
    $page = get_page_by_path('activities-2');
    
    if (!$page) {
        echo '<div class="error">';
        echo '<h3>❌ Page Does Not Exist</h3>';
        echo '<p>The page with slug <code>activities-2</code> does not exist.</p>';
        echo '<p><strong>Solution:</strong></p>';
        echo '<ol>';
        echo '<li>Go to WordPress Admin → Pages → Add New</li>';
        echo '<li>Title: "Activities"</li>';
        echo '<li>URL slug: "activities-2"</li>';
        echo '<li>Content: <code>[waza_activity_browser]</code></li>';
        echo '<li>Publish</li>';
        echo '</ol>';
        echo '</div>';
    } else {
        echo '<div class="success">';
        echo '<p>✅ Page exists</p>';
        echo '<p><strong>URL:</strong> <a href="' . get_permalink($page) . '" target="_blank">' . get_permalink($page) . '</a></p>';
        echo '</div>';
        
        echo '<p><strong>Page Content:</strong></p>';
        echo '<pre>' . esc_html($page->post_content) . '</pre>';
        
        // Check if shortcode is present
        if (strpos($page->post_content, 'waza_activity_browser') === false) {
            echo '<div class="warning">';
            echo '<h3>⚠️ Shortcode Missing!</h3>';
            echo '<p>The page does not contain the <code>[waza_activity_browser]</code> shortcode.</p>';
            echo '<p><strong>Solution:</strong> Edit the page and add: <code>[waza_activity_browser]</code></p>';
            echo '</div>';
        } else {
            echo '<div class="success">';
            echo '<p>✅ Shortcode found in page content</p>';
            echo '</div>';
        }
    }
    
    // CHECK 3: Verify shortcode is registered
    echo '<h2>3️⃣ Check Shortcode Registration</h2>';
    
    global $shortcode_tags;
    
    if (isset($shortcode_tags['waza_activity_browser'])) {
        echo '<div class="success">';
        echo '<p>✅ Shortcode <code>[waza_activity_browser]</code> is registered</p>';
        echo '</div>';
    } else {
        echo '<div class="error">';
        echo '<h3>❌ Shortcode Not Registered!</h3>';
        echo '<p>The shortcode <code>[waza_activity_browser]</code> is not registered.</p>';
        echo '<p>This could mean:</p>';
        echo '<ul>';
        echo '<li>Plugin is not active</li>';
        echo '<li>ActivityBrowserManager class is not loaded</li>';
        echo '<li>Code issue in plugin initialization</li>';
        echo '</ul>';
        echo '</div>';
    }
    
    // CHECK 4: Test shortcode output
    if ($count > 0 && $page && isset($shortcode_tags['waza_activity_browser'])) {
        echo '<h2>4️⃣ Test Shortcode Output</h2>';
        
        ob_start();
        echo do_shortcode('[waza_activity_browser]');
        $output = ob_get_clean();
        
        if (empty(trim(strip_tags($output)))) {
            echo '<div class="warning">';
            echo '<h3>⚠️ Shortcode Returns Empty Output</h3>';
            echo '<p>The shortcode is registered but returns no content.</p>';
            echo '<p><strong>Raw output:</strong></p>';
            echo '<pre>' . esc_html($output) . '</pre>';
            echo '</div>';
        } else {
            echo '<div class="success">';
            echo '<h3>✅ Shortcode Generates Output</h3>';
            echo '<p>Preview (first 500 chars):</p>';
            echo '<pre>' . esc_html(substr($output, 0, 500)) . '...</pre>';
            echo '</div>';
        }
    }
    
    // CHECK 5: Check template file
    echo '<h2>5️⃣ Check Template File</h2>';
    
    $template_file = WAZA_BOOKING_PLUGIN_DIR . 'templates/activity-browser.php';
    
    if (!file_exists($template_file)) {
        echo '<div class="error">';
        echo '<h3>❌ Template File Missing!</h3>';
        echo '<p>File not found: <code>' . $template_file . '</code></p>';
        echo '<p>This template is required to display activities.</p>';
        echo '</div>';
    } else {
        echo '<div class="success">';
        echo '<p>✅ Template file exists</p>';
        echo '<p><code>' . $template_file . '</code></p>';
        echo '</div>';
    }
    
    // Summary
    echo '<h2>📋 Summary & Next Steps</h2>';
    
    if ($count === 0) {
        echo '<div class="error">';
        echo '<h3>🎯 Root Cause: No Published Activities</h3>';
        echo '<p><strong>The page is empty because there are NO published activities in the database.</strong></p>';
        echo '<p><strong>Solution:</strong></p>';
        echo '<ol>';
        echo '<li>Create activities in WordPress Admin → Activities</li>';
        echo '<li>Make sure they are <strong>Published</strong> (not Draft)</li>';
        echo '<li>Add required meta: price, duration, instructor</li>';
        echo '<li>Add featured image (optional but recommended)</li>';
        echo '<li>Refresh /activities-2/ page</li>';
        echo '</ol>';
        echo '</div>';
    } elseif (!$page) {
        echo '<div class="error">';
        echo '<h3>🎯 Root Cause: Page Does Not Exist</h3>';
        echo '<p><strong>Activities exist but the /activities-2/ page is missing.</strong></p>';
        echo '<p><strong>Solution:</strong> Create the page with slug "activities-2"</p>';
        echo '</div>';
    } elseif (strpos($page->post_content, 'waza_activity_browser') === false) {
        echo '<div class="error">';
        echo '<h3>🎯 Root Cause: Missing Shortcode</h3>';
        echo '<p><strong>Page exists but does not have the [waza_activity_browser] shortcode.</strong></p>';
        echo '<p><strong>Solution:</strong> Edit the page and add the shortcode</p>';
        echo '</div>';
    } elseif (!isset($shortcode_tags['waza_activity_browser'])) {
        echo '<div class="error">';
        echo '<h3>🎯 Root Cause: Shortcode Not Registered</h3>';
        echo '<p><strong>The shortcode is not registered (plugin issue).</strong></p>';
        echo '<p><strong>Solution:</strong> Check plugin is active and ActivityBrowserManager is loaded</p>';
        echo '</div>';
    } else {
        echo '<div class="success">';
        echo '<h3>✅ Everything Looks Good!</h3>';
        echo '<p>Activities exist, page exists, shortcode is registered.</p>';
        echo '<p><strong>If still showing empty:</strong></p>';
        echo '<ul>';
        echo '<li>Clear browser cache (Ctrl+Shift+Del)</li>';
        echo '<li>Hard reload page (Ctrl+F5)</li>';
        echo '<li>Check browser console for JavaScript errors</li>';
        echo '<li>Try different browser</li>';
        echo '<li>Check if theme is interfering</li>';
        echo '</ul>';
        echo '</div>';
    }
    
    ?>
    
    <p style="text-align: center; color: #666; margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd;">
        Delete this file after debugging: <code><?php echo __FILE__; ?></code>
    </p>
</div>
</body>
</html>
