<?php
/**
 * Check and fix waza_payments table structure
 */

define('WP_USE_THEMES', false);
require_once __DIR__ . '/../../../wp-load.php';

global $wpdb;
$table = $wpdb->prefix . 'waza_payments';

echo "=== FIXING WAZA_PAYMENTS TABLE ===\n\n";

// Step 1: Update existing empty gateway_payment_id to NULL
echo "1. Updating existing empty gateway_payment_id to NULL...\n";
$wpdb->query("UPDATE $table SET gateway_payment_id = NULL WHERE gateway_payment_id = ''");
echo "   Done.\n\n";

// Step 2: Modify column to allow NULL
echo "2. Modifying gateway_payment_id column to allow NULL...\n";
$wpdb->query("ALTER TABLE $table MODIFY gateway_payment_id VARCHAR(255) NULL");
echo "   Done.\n\n";

// Step 3: Drop unique key if exists
echo "3. Dropping unique key on gateway_payment_id...\n";
$wpdb->query("ALTER TABLE $table DROP INDEX gateway_payment_id");
echo "   Done.\n\n";

// Step 4: Add unique key that allows NULL
echo "4. Adding new unique key that allows NULL...\n";
$wpdb->query("ALTER TABLE $table ADD UNIQUE KEY gateway_payment_id (gateway_payment_id)");
echo "   Done.\n\n";

echo "=== VERIFICATION ===\n\n";

// Check current structure
$result = $wpdb->get_results("SHOW CREATE TABLE $table");
if ($result) {
    echo $result[0]->{'Create Table'};
    echo "\n\n";
}

// Check for NULL values
$count = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE gateway_payment_id IS NULL");
echo "Records with NULL gateway_payment_id: $count\n\n";

// Show recent payments
echo "=== RECENT PAYMENT RECORDS ===\n";
$payments = $wpdb->get_results("SELECT id, booking_id, gateway_order_id, gateway_payment_id, status FROM $table ORDER BY id DESC LIMIT 5");
foreach ($payments as $payment) {
    echo sprintf("ID: %d, Booking: %d, Order ID: %s, Payment ID: %s, Status: %s\n",
        $payment->id,
        $payment->booking_id,
        $payment->gateway_order_id,
        $payment->gateway_payment_id ?: '(NULL)',
        $payment->status
    );
}

echo "\n=== FIX COMPLETE ===\n";
echo "You can now create multiple payment orders without duplicate key errors.\n";
