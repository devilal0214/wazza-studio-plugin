<?php
/**
 * Check Database Structure
 * Identifies which tables/methods are being used for slots
 */

require_once __DIR__ . '/../../../wp-load.php';

header('Content-Type: text/plain; charset=utf-8');

global $wpdb;

echo "=== DATABASE STRUCTURE CHECK ===\n\n";

// Check for custom table
$table_name = $wpdb->prefix . 'waza_slots';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");

echo "1. Custom Table Check:\n";
echo "   Table name: {$table_name}\n";
echo "   Exists: " . ($table_exists ? "YES ✓" : "NO ✗") . "\n\n";

if ($table_exists) {
    $row_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
    echo "   Rows in table: {$row_count}\n\n";
    
    echo "   Sample data from {$table_name}:\n";
    $custom_slots = $wpdb->get_results("SELECT * FROM {$table_name} LIMIT 5");
    
    if ($custom_slots) {
        foreach ($custom_slots as $slot) {
            echo "   - ID: {$slot->id}, Start: {$slot->start_date} {$slot->start_time}\n";
        }
    } else {
        echo "   (No data)\n";
    }
    
    echo "\n   All columns in {$table_name}:\n";
    $columns = $wpdb->get_results("SHOW COLUMNS FROM {$table_name}");
    foreach ($columns as $col) {
        echo "   - {$col->Field} ({$col->Type})\n";
    }
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Check for custom post type
echo "2. Custom Post Type Check:\n";
$post_count = $wpdb->get_var("
    SELECT COUNT(*) 
    FROM {$wpdb->posts} 
    WHERE post_type = 'waza_slot' 
    AND post_status = 'publish'
");

echo "   Published waza_slot posts: {$post_count}\n\n";

if ($post_count > 0) {
    echo "   Sample waza_slot posts:\n";
    $posts = $wpdb->get_results("
        SELECT ID, post_title, post_date 
        FROM {$wpdb->posts} 
        WHERE post_type = 'waza_slot' 
        AND post_status = 'publish'
        LIMIT 5
    ");
    
    foreach ($posts as $post) {
        $start_date = get_post_meta($post->ID, '_waza_start_date', true);
        $start_time = get_post_meta($post->ID, '_waza_start_time', true);
        echo "   - ID: {$post->ID}, Title: {$post->post_title}, Date: {$start_date} {$start_time}\n";
    }
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Check what AjaxHandler is actually using
echo "3. Checking AjaxHandler get_day_slots() method:\n\n";

$ajax_handler_file = __DIR__ . '/src/Frontend/AjaxHandler.php';
if (file_exists($ajax_handler_file)) {
    $content = file_get_contents($ajax_handler_file);
    
    // Find the get_day_slots method
    if (preg_match('/private function get_day_slots.*?\{(.*?)(?=private function|public function|\}[\s]*$)/s', $content, $matches)) {
        $method_content = $matches[0];
        
        echo "   Query method found!\n\n";
        
        if (strpos($method_content, 'wp_waza_slots') !== false) {
            echo "   ⚠️  USES CUSTOM TABLE: wp_waza_slots\n";
        } elseif (strpos($method_content, 'WP_Query') !== false || strpos($method_content, 'waza_slot') !== false) {
            echo "   ✓ USES CUSTOM POST TYPE: waza_slot\n";
        }
        
        // Show actual query
        if (preg_match('/wpdb->get_results.*?;/s', $method_content, $query_match)) {
            echo "\n   SQL Query found:\n";
            echo "   " . trim($query_match[0]) . "\n";
        } elseif (preg_match('/new WP_Query.*?\);/s', $method_content, $wp_query_match)) {
            echo "\n   WP_Query found:\n";
            echo "   " . trim($wp_query_match[0]) . "\n";
        }
    } else {
        echo "   ⚠️  Could not parse get_day_slots method\n";
    }
} else {
    echo "   ✗ AjaxHandler.php not found\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "4. DIAGNOSIS:\n\n";

if ($table_exists && $post_count > 0) {
    echo "   ⚠️  BOTH methods exist!\n";
    echo "   Custom table rows: {$row_count}\n";
    echo "   Custom post type count: {$post_count}\n\n";
    
    if ($row_count != $post_count) {
        echo "   ❌ DATA MISMATCH DETECTED!\n";
        echo "   The calendar might be querying the custom table ({$row_count} rows)\n";
        echo "   while the admin panel shows custom post types ({$post_count} posts)\n\n";
        
        echo "   SOLUTION NEEDED:\n";
        echo "   - Either sync both sources\n";
        echo "   - Or update calendar to use the same source as admin panel\n";
    }
} elseif ($table_exists) {
    echo "   System uses CUSTOM TABLE only\n";
    echo "   Rows: {$row_count}\n";
} elseif ($post_count > 0) {
    echo "   System uses CUSTOM POST TYPE only\n";
    echo "   Posts: {$post_count}\n";
} else {
    echo "   ❌ NO SLOTS FOUND in either location!\n";
}

echo "\n=== END CHECK ===\n";
