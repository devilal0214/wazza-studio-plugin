<?php
/**
 * Diagnose Payment Gateway Issues
 */

define('WP_USE_THEMES', false);
require_once __DIR__ . '/../../../wp-load.php';

echo "=== PAYMENT GATEWAY DIAGNOSTIC ===\n\n";

// 1. Check Settings
$settings = get_option('waza_booking_settings', []);
echo "1. Settings Check:\n";
echo "   Razorpay Enabled: " . ($settings['razorpay_enabled'] ?? 'NOT SET') . "\n";
echo "   Razorpay Key ID: " . (isset($settings['razorpay_key_id']) ? substr($settings['razorpay_key_id'], 0, 15) . '...' : 'NOT SET') . "\n";
echo "   Razorpay Key Secret: " . (isset($settings['razorpay_key_secret']) && !empty($settings['razorpay_key_secret']) ? 'SET (hidden)' : 'NOT SET') . "\n\n";

// 2. Check Razorpay SDK
echo "2. Razorpay SDK Check:\n";
$razorpay_sdk_file = __DIR__ . '/vendor/razorpay/razorpay/src/Api.php';
if (file_exists($razorpay_sdk_file)) {
    echo "   ✓ Razorpay SDK file exists\n";
    require_once __DIR__ . '/vendor/autoload.php';
    
    if (class_exists('Razorpay\Api\Api')) {
        echo "   ✓ Razorpay\Api\Api class is available\n";
        
        // Try to initialize
        try {
            $key_id = $settings['razorpay_key_id'] ?? '';
            $key_secret = $settings['razorpay_key_secret'] ?? '';
            
            if ($key_id && $key_secret) {
                $api = new \Razorpay\Api\Api($key_id, $key_secret);
                echo "   ✓ Razorpay API initialized successfully\n";
                
                // Test API connection
                echo "\n3. Testing Razorpay Connection:\n";
                try {
                    // Try to fetch payment methods (this will verify credentials)
                    $api->request->request('GET', 'methods');
                    echo "   ✓ Razorpay connection successful!\n";
                    echo "   ✓ Credentials are valid\n";
                } catch (\Exception $e) {
                    echo "   ✗ Connection failed: " . $e->getMessage() . "\n";
                    echo "   Error Code: " . $e->getCode() . "\n";
                }
            } else {
                echo "   ✗ Razorpay credentials not set\n";
            }
        } catch (\Exception $e) {
            echo "   ✗ Failed to initialize: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ✗ Razorpay\Api\Api class NOT found\n";
    }
} else {
    echo "   ✗ Razorpay SDK NOT installed\n";
    echo "   Path checked: " . $razorpay_sdk_file . "\n";
}

// 4. Check database tables
echo "\n4. Database Tables Check:\n";
global $wpdb;
$table_name = $wpdb->prefix . 'waza_payments';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
echo "   waza_payments table: " . ($table_exists ? '✓ EXISTS' : '✗ MISSING') . "\n";

// 5. Test creating payment order data
echo "\n5. Payment Order Test:\n";
if (isset($api) && $api) {
    try {
        $test_order_data = [
            'receipt' => 'waza_test_' . time(),
            'amount' => 100, // 1 rupee
            'currency' => 'INR',
            'payment_capture' => 1
        ];
        
        echo "   Creating test order with data:\n";
        echo "   - Amount: 100 paisa (1 INR)\n";
        echo "   - Currency: INR\n";
        
        $order = $api->order->create($test_order_data);
        echo "   ✓ Test order created successfully!\n";
        echo "   Order ID: " . $order['id'] . "\n";
        echo "   Status: " . $order['status'] . "\n";
    } catch (\Exception $e) {
        echo "   ✗ Failed to create test order\n";
        echo "   Error: " . $e->getMessage() . "\n";
        
        if (method_exists($e, 'getHttpStatusCode')) {
            echo "   HTTP Status: " . $e->getHttpStatusCode() . "\n";
        }
    }
}

// 6. Check PHP error log
echo "\n6. Recent PHP Errors:\n";
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    echo "   Error log: " . $error_log . "\n";
    $lines = file($error_log);
    $recent = array_slice($lines, -10);
    foreach ($recent as $line) {
        if (stripos($line, 'waza') !== false || stripos($line, 'razorpay') !== false) {
            echo "   " . trim($line) . "\n";
        }
    }
} else {
    echo "   No error log configured or accessible\n";
}

echo "\n=== DIAGNOSTIC COMPLETE ===\n";
