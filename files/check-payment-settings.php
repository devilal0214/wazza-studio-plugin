<?php
/**
 * Check Payment Gateway Settings
 */

define('WP_USE_THEMES', false);
require_once __DIR__ . '/../../../wp-load.php';

$settings = get_option('waza_booking_settings', []);

echo "=== PAYMENT GATEWAY SETTINGS ===\n\n";

echo "Razorpay Enabled: ";
var_export(isset($settings['razorpay_enabled']) ? $settings['razorpay_enabled'] : 'NOT SET');
echo "\n";

echo "Razorpay Key ID: ";
var_export(isset($settings['razorpay_key_id']) ? $settings['razorpay_key_id'] : 'NOT SET');
echo "\n\n";

echo "Stripe Enabled: ";
var_export(isset($settings['stripe_enabled']) ? $settings['stripe_enabled'] : 'NOT SET');
echo "\n\n";

echo "PhonePe Enabled: ";
var_export(isset($settings['phonepe_enabled']) ? $settings['phonepe_enabled'] : 'NOT SET');
echo "\n\n";

echo "All Settings Keys:\n";
print_r(array_keys($settings));

echo "\n=== CHECKING COMPARISON ===\n";
$razorpay = isset($settings['razorpay_enabled']) ? $settings['razorpay_enabled'] : '';
echo "Value: '" . $razorpay . "'\n";
echo "Type: " . gettype($razorpay) . "\n";
echo "=== '1': " . ($razorpay === '1' ? 'TRUE' : 'FALSE') . "\n";
echo "== '1': " . ($razorpay == '1' ? 'TRUE' : 'FALSE') . "\n";
echo "=== 1: " . ($razorpay === 1 ? 'TRUE' : 'FALSE') . "\n";
echo "== 1: " . ($razorpay == 1 ? 'TRUE' : 'FALSE') . "\n";
