<?php
/**
 * Fix Bookings Table Structure
 * 
 * Adds missing columns to tmn_waza_bookings table on live server
 * Upload this file and visit: wazastudio.com/wp-content/plugins/waza-studio-app/fix-bookings-table.php
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
    <title>Fix Bookings Table - Waza Studio</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h1 { color: #2271b1; margin-top: 0; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; border: 1px solid #c3e6cb; margin: 20px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; border: 1px solid #f5c6cb; margin: 20px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; border: 1px solid #ffeaa7; margin: 20px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; border: 1px solid #bee5eb; margin: 20px 0; }
        .btn { display: inline-block; padding: 12px 24px; background: #2271b1; color: white; text-decoration: none; border-radius: 6px; border: none; cursor: pointer; font-size: 16px; margin: 10px 5px; }
        .btn:hover { background: #135e96; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        pre { background: #f0f0f0; padding: 15px; border-radius: 6px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #f8f9fa; font-weight: 600; }
        .missing { color: #d63384; font-weight: 600; }
        .exists { color: #28a745; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Fix Bookings Table Structure</h1>

<?php
global $wpdb;
$table_name = $wpdb->prefix . 'waza_bookings';

// Get current table structure
$current_columns = $wpdb->get_results("DESCRIBE {$table_name}");
$column_names = array_column($current_columns, 'Field');

// Required columns based on AjaxHandler.php process_booking()
$required_columns = [
    'id' => 'bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY',
    'user_id' => 'bigint(20) DEFAULT NULL',
    'activity_id' => 'bigint(20) DEFAULT NULL',  // MISSING in migration!
    'slot_id' => 'bigint(20) NOT NULL',
    'quantity' => 'int(11) NOT NULL DEFAULT 1',  // MISSING in migration!
    'attendees_count' => 'int(11) NOT NULL DEFAULT 1',
    'total_amount' => 'decimal(10,2) NOT NULL DEFAULT 0.00',
    'discount_amount' => 'decimal(10,2) NOT NULL DEFAULT 0.00',
    'coupon_code' => 'varchar(50) DEFAULT NULL',
    'user_name' => 'varchar(100) NOT NULL',
    'user_email' => 'varchar(100) NOT NULL',
    'user_phone' => 'varchar(20) DEFAULT NULL',
    'payment_method' => 'varchar(50) DEFAULT NULL',
    'payment_status' => 'varchar(20) NOT NULL DEFAULT \'pending\'',
    'booking_status' => 'varchar(20) NOT NULL DEFAULT \'pending\'',
    'created_at' => 'datetime NOT NULL DEFAULT CURRENT_TIMESTAMP',
    'updated_at' => 'datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
];

// Check what's missing
$missing_columns = [];
foreach ($required_columns as $col => $definition) {
    if (!in_array($col, $column_names)) {
        $missing_columns[$col] = $definition;
    }
}

if (isset($_POST['fix_table'])) {
    echo '<div class="info"><strong>🔄 Running Fixes...</strong></div>';
    
    $errors = [];
    $success = [];
    
    // Add missing columns
    foreach ($missing_columns as $col => $definition) {
        $sql = "ALTER TABLE {$table_name} ADD COLUMN {$col} {$definition}";
        
        if ($wpdb->query($sql) !== false) {
            $success[] = "✅ Added column: <code>{$col}</code>";
        } else {
            $errors[] = "❌ Failed to add <code>{$col}</code>: " . $wpdb->last_error;
        }
    }
    
    // Add indexes if missing
    $indexes_to_add = [
        'activity_id' => "ALTER TABLE {$table_name} ADD INDEX idx_activity_id (activity_id)",
        'user_email' => "ALTER TABLE {$table_name} ADD INDEX idx_user_email (user_email)",
        'payment_status' => "ALTER TABLE {$table_name} ADD INDEX idx_payment_status (payment_status)"
    ];
    
    foreach ($indexes_to_add as $idx_name => $sql) {
        // Check if index exists
        $index_exists = $wpdb->get_results("SHOW INDEX FROM {$table_name} WHERE Key_name = 'idx_{$idx_name}'");
        
        if (empty($index_exists)) {
            if ($wpdb->query($sql) !== false) {
                $success[] = "✅ Added index: <code>idx_{$idx_name}</code>";
            } else {
                // Index might already exist with different name, ignore error
                if (strpos($wpdb->last_error, 'Duplicate key') === false) {
                    $errors[] = "⚠️ Index <code>idx_{$idx_name}</code>: " . $wpdb->last_error;
                }
            }
        }
    }
    
    if (!empty($success)) {
        echo '<div class="success">';
        echo '<strong>✅ Table Fixed Successfully!</strong><br><br>';
        echo implode('<br>', $success);
        echo '</div>';
    }
    
    if (!empty($errors)) {
        echo '<div class="error">';
        echo '<strong>⚠️ Some Issues Encountered:</strong><br><br>';
        echo implode('<br>', $errors);
        echo '</div>';
    }
    
    // Refresh column list
    $current_columns = $wpdb->get_results("DESCRIBE {$table_name}");
    $column_names = array_column($current_columns, 'Field');
    $missing_columns = [];
    foreach ($required_columns as $col => $definition) {
        if (!in_array($col, $column_names)) {
            $missing_columns[$col] = $definition;
        }
    }
}
?>

        <div class="info">
            <strong>📊 Current Table Status</strong><br>
            Table: <code><?php echo $table_name; ?></code><br>
            Total Columns: <?php echo count($current_columns); ?><br>
            Missing Columns: <strong class="missing"><?php echo count($missing_columns); ?></strong>
        </div>

        <h3>Required vs Current Columns:</h3>
        <table>
            <thead>
                <tr>
                    <th>Column Name</th>
                    <th>Status</th>
                    <th>Definition</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($required_columns as $col => $definition) : ?>
                <tr>
                    <td><code><?php echo $col; ?></code></td>
                    <td>
                        <?php if (in_array($col, $column_names)) : ?>
                            <span class="exists">✅ Exists</span>
                        <?php else : ?>
                            <span class="missing">❌ Missing</span>
                        <?php endif; ?>
                    </td>
                    <td><code><?php echo $definition; ?></code></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (!empty($missing_columns)) : ?>
        <div class="warning">
            <strong>⚠️ Missing Columns Detected!</strong><br>
            The following columns are required by the booking system but missing from the table:<br><br>
            <?php foreach ($missing_columns as $col => $def) : ?>
                • <code><?php echo $col; ?></code><br>
            <?php endforeach; ?>
            <br>
            Click the button below to add them automatically.
        </div>
        
        <form method="post">
            <button type="submit" name="fix_table" class="btn">
                🔧 Fix Table Structure (Add Missing Columns)
            </button>
        </form>
        <?php else : ?>
        <div class="success">
            <strong>✅ Table Structure is Correct!</strong><br>
            All required columns are present.
        </div>
        <?php endif; ?>

        <div class="info">
            <strong>🗑️ Delete This File:</strong><br>
            After fixing the table, you can safely delete:<br>
            <code>/wp-content/plugins/waza-studio-app/fix-bookings-table.php</code>
        </div>
    </div>
</body>
</html>
