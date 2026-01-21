<?php
/**
 * Create Slots Page for Activity Booking
 * 
 * This creates the /slots/ page with [waza_activity_slots] shortcode
 * Upload this file to your server and visit: wazastudio.com/wp-content/plugins/waza-studio-app/create-slots-page.php
 * 
 * @package WazaBooking
 */

// Load WordPress
require_once('../../../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Access denied. Please login as administrator.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Slots Page - Waza Studio</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h1 { color: #2271b1; margin-top: 0; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; border: 1px solid #c3e6cb; margin: 20px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; border: 1px solid #f5c6cb; margin: 20px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; border: 1px solid #bee5eb; margin: 20px 0; }
        .btn { display: inline-block; padding: 12px 24px; background: #2271b1; color: white; text-decoration: none; border-radius: 6px; border: none; cursor: pointer; font-size: 16px; margin: 10px 5px; }
        .btn:hover { background: #135e96; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        pre { background: #f0f0f0; padding: 15px; border-radius: 6px; overflow-x: auto; }
        .step { background: #f8f9fa; padding: 15px; margin: 15px 0; border-left: 4px solid #2271b1; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎯 Create Activity Slots Page</h1>

<?php
if (isset($_POST['create_page'])) {
    // Check if page already exists
    $existing_page = get_page_by_path('slots');
    
    if ($existing_page) {
        echo '<div class="info">';
        echo '<strong>ℹ️ Page Already Exists</strong><br>';
        echo 'The <code>/slots/</code> page already exists (ID: ' . $existing_page->ID . ')<br>';
        echo 'URL: <a href="' . get_permalink($existing_page->ID) . '" target="_blank">' . get_permalink($existing_page->ID) . '</a><br><br>';
        
        // Check if it has the correct shortcode
        if (strpos($existing_page->post_content, 'waza_activity_slots') !== false) {
            echo '✅ Page contains the <code>[waza_activity_slots]</code> shortcode';
        } else {
            echo '⚠️ Page does NOT contain the <code>[waza_activity_slots]</code> shortcode<br>';
            echo '<strong>Action Required:</strong> Edit the page and add: <code>[waza_activity_slots]</code>';
        }
        echo '</div>';
    } else {
        // Create the page
        $page_data = [
            'post_title'    => 'Activity Slots',
            'post_name'     => 'slots',
            'post_content'  => '[waza_activity_slots]',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_author'   => get_current_user_id(),
            'comment_status' => 'closed',
            'ping_status'   => 'closed'
        ];
        
        $page_id = wp_insert_post($page_data);
        
        if ($page_id && !is_wp_error($page_id)) {
            echo '<div class="success">';
            echo '<strong>✅ Success!</strong><br>';
            echo 'Created <code>/slots/</code> page (ID: ' . $page_id . ')<br>';
            echo 'URL: <a href="' . get_permalink($page_id) . '" target="_blank">' . get_permalink($page_id) . '</a><br>';
            echo 'Shortcode: <code>[waza_activity_slots]</code><br><br>';
            echo '<strong>Next Steps:</strong><br>';
            echo '1. Visit an activity and click "Book Now"<br>';
            echo '2. You\'ll be redirected to /slots/?activity_id=XXX<br>';
            echo '3. Select a date and time slot to book';
            echo '</div>';
            
            // Flush rewrite rules
            flush_rewrite_rules();
        } else {
            echo '<div class="error">';
            echo '<strong>❌ Error Creating Page</strong><br>';
            echo 'Error: ' . (is_wp_error($page_id) ? $page_id->get_error_message() : 'Unknown error');
            echo '</div>';
        }
    }
}
?>

        <div class="info">
            <strong>📋 What This Does:</strong><br>
            Creates a page at <code>/slots/</code> with the <code>[waza_activity_slots]</code> shortcode.<br>
            This page shows available time slots when users click "Book Now" on an activity.
        </div>

        <div class="step">
            <strong>Current Status:</strong><br>
            <?php
            $slots_page = get_page_by_path('slots');
            if ($slots_page) {
                echo '✅ Page exists: <a href="' . get_permalink($slots_page->ID) . '" target="_blank">/slots/</a><br>';
                if (strpos($slots_page->post_content, 'waza_activity_slots') !== false) {
                    echo '✅ Shortcode present: <code>[waza_activity_slots]</code>';
                } else {
                    echo '❌ Missing shortcode!';
                }
            } else {
                echo '❌ Page does not exist';
            }
            ?>
        </div>

        <?php if (!get_page_by_path('slots') || strpos(get_page_by_path('slots')->post_content, 'waza_activity_slots') === false) : ?>
        <form method="post">
            <button type="submit" name="create_page" class="btn">
                <?php echo get_page_by_path('slots') ? 'Update Page' : 'Create Page'; ?>
            </button>
        </form>
        <?php endif; ?>

        <div class="step">
            <strong>🧪 Test After Creation:</strong><br>
            1. Go to <code>/activities-2/</code><br>
            2. Click "Book Now" on any activity<br>
            3. Should redirect to <code>/slots/?activity_id=XXX</code><br>
            4. Should show calendar and available time slots
        </div>

        <div class="info">
            <strong>🗑️ Delete This File:</strong><br>
            After creating the page, you can safely delete:<br>
            <code>/wp-content/plugins/waza-studio-app/create-slots-page.php</code>
        </div>
    </div>
</body>
</html>
