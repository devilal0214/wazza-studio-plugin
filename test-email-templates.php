<?php
/**
 * Test script for Email Template System
 * 
 * This script tests the EmailTemplateManager functionality
 * Run this from WordPress admin or via WP CLI
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // For testing outside WordPress, include WordPress
    require_once '../../../wp-config.php';
}

// Load the plugin if not already loaded
if (!class_exists('WazaBooking\Core\Plugin')) {
    require_once __DIR__ . '/waza-booking.php';
}

// Get plugin instance
$plugin = \WazaBooking\Core\Plugin::get_instance();
$email_manager = $plugin->get_email_template_manager();

echo "<h2>Email Template System Test</h2>\n";

// Test 1: Check if manager is initialized
echo "<h3>Test 1: Manager Initialization</h3>\n";
if ($email_manager) {
    echo "✓ Email Template Manager initialized successfully\n";
} else {
    echo "✗ Email Template Manager failed to initialize\n";
    exit;
}

// Test 2: Get available templates
echo "<h3>Test 2: Available Templates</h3>\n";
$templates = $email_manager->get_available_templates();
if (!empty($templates)) {
    echo "✓ Found " . count($templates) . " available templates:\n";
    foreach ($templates as $key => $template) {
        echo "  - {$key}: {$template['name']}\n";
    }
} else {
    echo "✗ No templates found\n";
}

// Test 3: Get template content
echo "<h3>Test 3: Template Content</h3>\n";
$booking_confirmation = $email_manager->get_template('booking_confirmation');
if ($booking_confirmation) {
    echo "✓ Booking confirmation template loaded\n";
    echo "Subject: " . $booking_confirmation['subject'] . "\n";
    echo "Content length: " . strlen($booking_confirmation['content']) . " characters\n";
} else {
    echo "✗ Failed to load booking confirmation template\n";
}

// Test 4: Variable replacement
echo "<h3>Test 4: Variable Replacement</h3>\n";
$test_data = [
    'site_name' => 'Test Site',
    'user_name' => 'John Doe',
    'user_email' => 'john@example.com',
    'booking_id' => 'B12345',
    'activity_name' => 'Test Activity',
    'slot_date' => '2024-01-15',
    'slot_time' => '10:00 AM',
    'instructor_name' => 'Jane Smith',
    'payment_amount' => '$50.00'
];

$processed_content = $email_manager->replace_variables($booking_confirmation['content'], $test_data);
if (strlen($processed_content) > 0 && $processed_content !== $booking_confirmation['content']) {
    echo "✓ Variable replacement working\n";
    echo "Original variables found and replaced\n";
} else {
    echo "✗ Variable replacement failed or no variables found\n";
}

// Test 5: Available variables
echo "<h3>Test 5: Available Variables</h3>\n";
$variables = $email_manager->get_available_variables();
if (!empty($variables)) {
    echo "✓ Found " . count($variables) . " variable groups:\n";
    foreach ($variables as $group => $vars) {
        echo "  - {$group}: " . count($vars) . " variables\n";
    }
} else {
    echo "✗ No variables found\n";
}

// Test 6: Template saving (if we have write permissions)
echo "<h3>Test 6: Template Saving</h3>\n";
try {
    $test_template = [
        'subject' => 'Test Subject - {{user_name}}',
        'content' => '<p>Hello {{user_name}}, this is a test template for {{activity_name}}.</p>'
    ];
    
    $result = $email_manager->save_template('booking_confirmation', $test_template);
    if ($result) {
        echo "✓ Template saving successful\n";
        
        // Restore original template
        $email_manager->reset_template('booking_confirmation');
        echo "✓ Template reset to default\n";
    } else {
        echo "✗ Template saving failed\n";
    }
} catch (Exception $e) {
    echo "✗ Template saving error: " . $e->getMessage() . "\n";
}

// Test 7: Database table check
echo "<h3>Test 7: Database Table</h3>\n";
global $wpdb;
$table_name = $wpdb->prefix . 'waza_email_templates';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;

if ($table_exists) {
    echo "✓ Email templates table exists\n";
    
    // Check if we have any custom templates
    $custom_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
    echo "  Custom templates in database: {$custom_count}\n";
} else {
    echo "✗ Email templates table missing\n";
    echo "  Run plugin activation to create required tables\n";
}

echo "<h3>Test Summary</h3>\n";
echo "Email Template System tests completed.\n";
echo "Check the results above for any issues that need attention.\n";

// If running via web browser, add some HTML formatting
if (isset($_SERVER['HTTP_HOST'])) {
    echo "<style>";
    echo "body { font-family: monospace; white-space: pre-wrap; }";
    echo "h2, h3 { color: #333; }";
    echo "</style>";
}
?>