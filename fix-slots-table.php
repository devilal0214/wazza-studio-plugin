<?php
/**
 * Fix waza_slots Table Structure
 * 
 * Adds missing columns to the slots table
 * URL: http://localhost/wazza/wp-content/plugins/waza-studio-app/fix-slots-table.php
 * OR: https://wazastudio.com/wp-content/plugins/waza-studio-app/fix-slots-table.php
 */
$wp_load = __DIR__ . '/../../../wp-load.php';
require_once $wp_load;

if (!current_user_can('manage_options')) {
    die('Access denied. Admin only.');
}

global $wpdb;
$table_name = $wpdb->prefix . 'waza_slots';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Slots Table - Waza Booking</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #23282d; border-bottom: 3px solid #0073aa; padding-bottom: 10px; }
        .error { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 10px 0; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 10px 0; }
        .warning { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 10px 0; }
        pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }
        .btn { background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin: 10px 5px 10px 0; }
        .btn:hover { background: #005a87; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔧 Fix Slots Table Structure</h1>
    
    <?php
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if (!$table_exists) {
        echo '<div class="error">';
        echo '<h2>❌ Table Does Not Exist</h2>';
        echo '<p>Table <code>' . $table_name . '</code> does not exist!</p>';
        echo '<p>The plugin activation should have created it. Try deactivating and reactivating the plugin.</p>';
        echo '</div>';
        echo '</div></body></html>';
        exit;
    }
    
    echo '<div class="success">';
    echo '<p>✅ Table <code>' . $table_name . '</code> exists</p>';
    echo '</div>';
    
    // Get current table structure
    $current_columns = $wpdb->get_results("DESCRIBE $table_name");
    
    echo '<h2>Current Table Structure</h2>';
    echo '<table>';
    echo '<tr><th>Column</th><th>Type</th><th>Null</th><th>Default</th><th>Extra</th></tr>';
    
    $existing_columns = [];
    foreach ($current_columns as $col) {
        $existing_columns[] = $col->Field;
        echo '<tr>';
        echo '<td><strong>' . $col->Field . '</strong></td>';
        echo '<td>' . $col->Type . '</td>';
        echo '<td>' . $col->Null . '</td>';
        echo '<td>' . ($col->Default ?? 'NULL') . '</td>';
        echo '<td>' . $col->Extra . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    
    // Define required columns
    $required_columns = [
        'id' => 'bigint(20) unsigned NOT NULL AUTO_INCREMENT',
        'activity_id' => 'bigint(20) unsigned NOT NULL',
        'instructor_id' => 'bigint(20) unsigned DEFAULT NULL',
        'start_datetime' => 'datetime NOT NULL',
        'end_datetime' => 'datetime NOT NULL',
        'capacity' => 'int(11) NOT NULL DEFAULT 20',
        'booked_count' => 'int(11) NOT NULL DEFAULT 0',
        'price' => 'decimal(10,2) NOT NULL DEFAULT 0.00',
        'location' => 'varchar(255) DEFAULT NULL',
        'notes' => 'text',
        'status' => "varchar(50) NOT NULL DEFAULT 'active'",
        'created_at' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    ];
    
    // Find missing columns
    $missing_columns = [];
    foreach ($required_columns as $col_name => $col_def) {
        if (!in_array($col_name, $existing_columns)) {
            $missing_columns[$col_name] = $col_def;
        }
    }
    
    if (empty($missing_columns)) {
        echo '<div class="success">';
        echo '<h2>✅ Table Structure is Correct!</h2>';
        echo '<p>All required columns exist. No changes needed.</p>';
        echo '</div>';
    } else {
        echo '<div class="warning">';
        echo '<h2>⚠️ Missing Columns Found</h2>';
        echo '<p>The following columns are missing and need to be added:</p>';
        echo '<ul>';
        foreach ($missing_columns as $col_name => $col_def) {
            echo '<li><strong>' . $col_name . '</strong>: <code>' . $col_def . '</code></li>';
        }
        echo '</ul>';
        echo '</div>';
        
        // If form submitted, apply the fix
        if (isset($_POST['apply_fix'])) {
            echo '<h2>🔧 Applying Fix...</h2>';
            
            $success = true;
            foreach ($missing_columns as $col_name => $col_def) {
                $sql = "ALTER TABLE $table_name ADD COLUMN `$col_name` $col_def";
                
                echo '<p>Running: <code>' . $sql . '</code></p>';
                
                $result = $wpdb->query($sql);
                
                if ($result === false) {
                    echo '<div class="error">';
                    echo '<p>❌ Failed to add column <strong>' . $col_name . '</strong></p>';
                    echo '<p>Error: ' . $wpdb->last_error . '</p>';
                    echo '</div>';
                    $success = false;
                } else {
                    echo '<div class="success">';
                    echo '<p>✅ Added column <strong>' . $col_name . '</strong></p>';
                    echo '</div>';
                }
            }
            
            if ($success) {
                echo '<div class="success">';
                echo '<h2>✅ Table Updated Successfully!</h2>';
                echo '<p>All missing columns have been added.</p>';
                echo '<p><a href="?" class="btn">Refresh to Verify</a></p>';
                echo '</div>';
            }
            
        } else {
            // Show button to apply fix
            echo '<form method="post">';
            echo '<input type="hidden" name="apply_fix" value="1">';
            echo '<button type="submit" class="btn" onclick="return confirm(\'Add missing columns to the table?\')">🔧 Add Missing Columns</button>';
            echo '</form>';
        }
    }
    
    // Check indexes
    echo '<h2>Table Indexes</h2>';
    $indexes = $wpdb->get_results("SHOW INDEX FROM $table_name");
    
    if (empty($indexes)) {
        echo '<div class="warning">';
        echo '<p>⚠️ No indexes found. This may cause slow queries.</p>';
        echo '</div>';
        
        if (isset($_POST['add_indexes'])) {
            echo '<h3>Adding Indexes...</h3>';
            
            $index_queries = [
                "ALTER TABLE $table_name ADD INDEX idx_activity_id (activity_id)",
                "ALTER TABLE $table_name ADD INDEX idx_instructor_id (instructor_id)",
                "ALTER TABLE $table_name ADD INDEX idx_start_datetime (start_datetime)",
                "ALTER TABLE $table_name ADD INDEX idx_status (status)"
            ];
            
            foreach ($index_queries as $sql) {
                echo '<p>Running: <code>' . $sql . '</code></p>';
                $result = $wpdb->query($sql);
                
                if ($result === false) {
                    echo '<div class="error"><p>❌ Error: ' . $wpdb->last_error . '</p></div>';
                } else {
                    echo '<div class="success"><p>✅ Index added</p></div>';
                }
            }
            
            echo '<p><a href="?" class="btn">Refresh to Verify</a></p>';
            
        } else {
            echo '<form method="post">';
            echo '<input type="hidden" name="add_indexes" value="1">';
            echo '<button type="submit" class="btn">Add Performance Indexes</button>';
            echo '</form>';
        }
    } else {
        echo '<table>';
        echo '<tr><th>Key Name</th><th>Column</th><th>Unique</th></tr>';
        foreach ($indexes as $idx) {
            echo '<tr>';
            echo '<td>' . $idx->Key_name . '</td>';
            echo '<td>' . $idx->Column_name . '</td>';
            echo '<td>' . ($idx->Non_unique == 0 ? 'Yes' : 'No') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    
    ?>
    
    <h2>🧪 Test Slot Creation</h2>
    <p>After fixing the table structure, test slot creation:</p>
    <ol>
        <li>Go to WordPress Admin → Slots → Add New Slot</li>
        <li>Fill in the form with valid data</li>
        <li>Click Save</li>
        <li>Should succeed without "Unknown column" error ✅</li>
    </ol>
    
    <h2>⚠️ Security Notice</h2>
    <div class="warning">
        <p><strong>DELETE THIS FILE AFTER USE!</strong></p>
        <p>This file contains database modification capabilities and should not remain on the server.</p>
        <form method="post" onsubmit="return confirm('Delete this file? Make sure the table is fixed first!');">
            <input type="hidden" name="delete_self" value="1">
            <button type="submit" class="btn btn-danger">🗑️ Delete This File</button>
        </form>
    </div>
    
    <?php
    
    if (isset($_POST['delete_self'])) {
        if (unlink(__FILE__)) {
            echo '<div class="success"><p>✅ File deleted successfully!</p></div>';
        } else {
            echo '<div class="error"><p>❌ Failed to delete. Please remove manually: <code>' . __FILE__ . '</code></p></div>';
        }
    }
    
    ?>
    
    <p style="text-align: center; color: #666; margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd;">
        Waza Booking - Table Structure Fix | <?php echo date('Y-m-d H:i:s'); ?>
    </p>
</div>
</body>
</html>
