<?php
/**
 * Quick Setup and Database Refresh Script
 * 
 * Run this to ensure database tables are created and sample data is added
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Force database table creation
function waza_force_database_setup() {
    global $wpdb;
    
    // Create the database manager and force table creation
    $database_manager = new \WazaBooking\Database\DatabaseManager();
    $database_manager->create_tables();
    
    // Update database version to force recreation
    update_option('waza_booking_db_version', '1.0.1');
    
    echo '<div class="notice notice-success"><p><strong>Waza Booking:</strong> Database tables have been created/updated successfully!</p></div>';
}

// Create sample data if not exists
function waza_ensure_sample_data() {
    // Remove the flag so sample data gets recreated
    delete_option('waza_sample_data_created');
    
    // Include and run sample data creation
    require_once WAZA_BOOKING_PLUGIN_DIR . 'includes/sample-data.php';
    waza_create_sample_data();
    
    echo '<div class="notice notice-success"><p><strong>Waza Booking:</strong> Sample data has been created!</p></div>';
}

// Admin page to run setup
function waza_setup_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    if (isset($_POST['setup_database'])) {
        waza_force_database_setup();
    }
    
    if (isset($_POST['create_sample_data'])) {
        waza_ensure_sample_data();
    }
    
    ?>
    <div class="wrap">
        <h1>Waza Booking - Quick Setup</h1>
        
        <div class="card" style="max-width: 600px;">
            <h2>Database Setup</h2>
            <p>Click this button to ensure all database tables are created properly.</p>
            <form method="post">
                <button type="submit" name="setup_database" class="button button-primary">
                    Setup Database Tables
                </button>
            </form>
        </div>
        
        <div class="card" style="max-width: 600px; margin-top: 20px;">
            <h2>Sample Data</h2>
            <p>Create sample activities and time slots for testing the booking system.</p>
            <form method="post">
                <button type="submit" name="create_sample_data" class="button button-secondary">
                    Create Sample Data
                </button>
            </form>
        </div>
        
        <div class="card" style="max-width: 600px; margin-top: 20px;">
            <h2>Next Steps</h2>
            <ol>
                <li><strong>Setup Database:</strong> Click "Setup Database Tables" above</li>
                <li><strong>Add Sample Data:</strong> Click "Create Sample Data" above</li>
                <li><strong>Configure Settings:</strong> Go to <a href="<?php echo admin_url('admin.php?page=waza-settings'); ?>">Waza > Settings</a></li>
                <li><strong>Manage Time Slots:</strong> Go to <a href="<?php echo admin_url('admin.php?page=waza-slots'); ?>">Waza > Time Slots</a></li>
                <li><strong>Test Frontend:</strong> Create a page with shortcode <code>[waza_booking_calendar]</code></li>
            </ol>
        </div>
    </div>
    <?php
}

// Add setup page to admin menu
add_action('admin_menu', function() {
    add_submenu_page(
        'waza-booking',
        'Quick Setup',
        'Quick Setup',
        'manage_options',
        'waza-setup',
        'waza_setup_admin_page'
    );
}, 20);
?>