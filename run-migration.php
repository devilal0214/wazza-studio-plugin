<?php
/**
 * Quick Migration Runner
 * Access via: /wp-admin/admin.php?page=waza-run-migration
 */

// Add to WordPress admin
add_action('admin_menu', function() {
    add_submenu_page(
        null, // Hidden from menu
        'Run Migration',
        'Run Migration',
        'manage_options',
        'waza-run-migration',
        'waza_run_migration_page'
    );
});

function waza_run_migration_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    global $wpdb;
    
    echo '<div class="wrap"><h1>Waza Database Migration</h1>';
    
    // Add image_url to slots
    $slots_table = $wpdb->prefix . 'waza_slots';
    $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$slots_table} LIKE 'image_url'");
    
    if (empty($column_exists)) {
        $sql = "ALTER TABLE {$slots_table} ADD COLUMN image_url VARCHAR(500) DEFAULT NULL AFTER notes";
        $result = $wpdb->query($sql);
        
        if ($result !== false) {
            echo '<p style="color:green;">✓ Successfully added image_url column to waza_slots table</p>';
        } else {
            echo '<p style="color:red;">✗ Error: ' . $wpdb->last_error . '</p>';
        }
    } else {
        echo '<p>✓ Column image_url already exists</p>';
    }
    
    // Create promo codes table
    $promo_table = $wpdb->prefix . 'waza_promo_codes';
    $charset_collate = $wpdb->get_charset_collate();
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$promo_table}'");
    
    if (!$table_exists) {
        $promo_sql = "CREATE TABLE {$promo_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            code varchar(50) NOT NULL,
            description text DEFAULT NULL,
            discount_type varchar(20) NOT NULL DEFAULT 'percentage',
            discount_amount decimal(10,2) NOT NULL,
            usage_limit int(11) DEFAULT NULL,
            used_count int(11) NOT NULL DEFAULT 0,
            expiry_date datetime DEFAULT NULL,
            applicable_activities text DEFAULT NULL,
            min_booking_amount decimal(10,2) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_by bigint(20) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY code (code),
            KEY status (status),
            KEY expiry_date (expiry_date)
        ) {$charset_collate}";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($promo_sql);
        
        echo '<p style="color:green;">✓ Promo codes table created</p>';
    } else {
        echo '<p>✓ Promo codes table already exists</p>';
    }
    
    echo '<p><strong>Migration Complete!</strong></p>';
    echo '<p><a href="' . admin_url('admin.php?page=waza-booking') . '">← Back to Waza Dashboard</a></p>';
    echo '</div>';
}
