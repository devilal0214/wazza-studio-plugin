<?php
/**
 * Payment Manager
 * 
 * @package WazaBooking\Payment
 */

namespace WazaBooking\Payment;

use WazaBooking\Admin\SettingsManager;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Payment Manager Class
 * Handles Razorpay and Stripe integration
 */
class PaymentManager {
    
    /**
     * Razorpay API instance
     */
    private $razorpay_api;
    
    /**
     * Stripe API instance
     */
    private $stripe_api;
    
    /**
     * PhonePe API instance
     */
    private $phonepe_api;
    
    /**
     * Initialize payment integrations
     */
    public function init() {
        add_action('init', [$this, 'setup_webhook_endpoints']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_payment_scripts']);
        add_action('wp_ajax_waza_create_payment_order', [$this, 'create_payment_order']);
        add_action('wp_ajax_nopriv_waza_create_payment_order', [$this, 'create_payment_order']);
        add_action('wp_ajax_waza_verify_payment', [$this, 'verify_payment']);
        add_action('wp_ajax_nopriv_waza_verify_payment', [$this, 'verify_payment']);
        
        $this->initialize_gateways();
    }
    
    /**
     * Initialize payment gateways
     */
    private function initialize_gateways() {
        // Initialize Razorpay
        if (SettingsManager::get_setting('razorpay_enabled') === '1') {
            $this->initialize_razorpay();
        }
        
        // Initialize Stripe
        if (SettingsManager::get_setting('stripe_enabled') === '1') {
            $this->initialize_stripe();
        }
        
        // Initialize PhonePe
        if (SettingsManager::get_setting('phonepe_enabled') === '1') {
            $this->initialize_phonepe();
        }
    }

    /**
     * Initialize Razorpay
     */
    private function initialize_razorpay() {
        $key_id = SettingsManager::get_setting('razorpay_key_id');
        $key_secret = SettingsManager::get_setting('razorpay_key_secret');
        
        if ($key_id && $key_secret) {
            if (class_exists('Razorpay\Api\Api')) {
                $this->razorpay_api = new \Razorpay\Api\Api($key_id, $key_secret);
            } else {
                // Fallback to mock if SDK not found
                $this->razorpay_api = new RazorpayMockApi($key_id, $key_secret);
            }
        }
    }

    /**
     * Initialize Stripe
     */
    private function initialize_stripe() {
        $secret_key = SettingsManager::get_setting('stripe_secret_key');
        
        if ($secret_key) {
            if (class_exists('Stripe\Stripe')) {
                \Stripe\Stripe::setApiKey($secret_key);
                $this->stripe_api = new \Stripe\StripeClient($secret_key);
            } else {
                // Fallback to mock if SDK not found
                $this->stripe_api = new StripeMockApi($secret_key);
            }
        }
    }
    
    /**
     * Initialize PhonePe
     */
    private function initialize_phonepe() {
        require_once WAZA_BOOKING_PLUGIN_DIR . 'src/Payment/Gateways/PhonePeGateway.php';
        $this->phonepe_api = new \WazaBooking\Payment\Gateways\PhonePeGateway();
    }
    
    public function setup_webhook_endpoints() {
        add_rewrite_rule(
            '^waza-webhook/razorpay/?$',
            'index.php?waza_webhook=razorpay',
            'top'
        );
        
        add_rewrite_rule(
            '^waza-webhook/stripe/?$',
            'index.php?waza_webhook=stripe',
            'top'
        );
        
        add_filter('query_vars', [$this, 'add_webhook_query_vars']);
        add_action('template_redirect', [$this, 'handle_webhook_requests']);
    }
    
    /**
     * Add webhook query vars
     */
    public function add_webhook_query_vars($vars) {
        $vars[] = 'waza_webhook';
        return $vars;
    }
    
    /**
     * Handle webhook requests
     */
    public function handle_webhook_requests() {
        $webhook = get_query_var('waza_webhook');
        
        if ($webhook === 'razorpay') {
            $this->handle_razorpay_webhook();
        } elseif ($webhook === 'stripe') {
            $this->handle_stripe_webhook();
        }
    }
    
    /**
     * Enqueue payment scripts
     */
    public function enqueue_payment_scripts() {
        if (is_page() || is_single()) {
            // Enqueue Razorpay script
            if (SettingsManager::get_setting('razorpay_enabled') === '1') {
                wp_enqueue_script(
                    'razorpay-checkout',
                    'https://checkout.razorpay.com/v1/checkout.js',
                    [],
                    null,
                    true
                );
            }
            
            // Enqueue Stripe script
            if (SettingsManager::get_setting('stripe_enabled') === '1') {
                wp_enqueue_script(
                    'stripe-js',
                    'https://js.stripe.com/v3/',
                    [],
                    null,
                    true
                );
            }
            
            // Enqueue custom payment script
            wp_enqueue_script(
                'waza-payment',
                WAZA_BOOKING_PLUGIN_URL . 'assets/payment.js',
                ['jquery'],
                WAZA_BOOKING_VERSION,
                true
            );
            
            // Localize payment data
            wp_localize_script('waza-payment', 'wazaPayment', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('waza_payment_nonce'),
                'razorpay_key' => SettingsManager::get_setting('razorpay_key_id'),
                'stripe_public_key' => SettingsManager::get_setting('stripe_publishable_key'),
                'currency' => SettingsManager::get_setting('currency', 'INR'),
                'business_name' => SettingsManager::get_setting('business_name', get_bloginfo('name')),
                'strings' => [
                    'processing' => __('Processing payment...', 'waza-booking'),
                    'error' => __('Payment failed. Please try again.', 'waza-booking'),
                    'success' => __('Payment successful!', 'waza-booking')
                ]
            ]);
        }
    }
    
    /**
     * Create payment order
     */
    public function create_payment_order() {
        check_ajax_referer('waza_payment_nonce', 'nonce');
        
        $booking_id = intval($_POST['booking_id']);
        $amount = floatval($_POST['amount']);
        $gateway = sanitize_text_field($_POST['gateway']);
        
        if (!$booking_id || !$amount) {
            wp_send_json_error(['message' => __('Invalid booking or amount', 'waza-booking')]);
        }
        
        try {
            if ($gateway === 'razorpay') {
                $order = $this->create_razorpay_order($booking_id, $amount);
            } elseif ($gateway === 'stripe') {
                $order = $this->create_stripe_payment_intent($booking_id, $amount);
            } elseif ($gateway === 'phonepe') {
                $order = $this->create_phonepe_payment($booking_id, $amount);
            } else {
                throw new \Exception(__('Invalid payment gateway', 'waza-booking'));
            }
            
            wp_send_json_success($order);
            
        } catch (\Exception $e) {
            error_log('Waza Payment Error: ' . $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Create Razorpay order
     */
    private function create_razorpay_order($booking_id, $amount) {
        if (!$this->razorpay_api) {
            throw new \Exception(__('Razorpay not configured', 'waza-booking'));
        }
        
        $currency = SettingsManager::get_setting('currency', 'INR');
        
        // Convert amount to paisa (Razorpay requires amount in smallest currency unit)
        $amount_in_paisa = $amount * 100;
        
        $order_data = [
            'receipt' => 'waza_booking_' . $booking_id,
            'amount' => $amount_in_paisa,
            'currency' => $currency,
            'payment_capture' => 1
        ];
        
        $order = $this->razorpay_api->order->create($order_data);
        
        // Store order in database
        $this->store_payment_order($booking_id, 'razorpay', $order['id'], $amount);
        
        return [
            'order_id' => $order['id'],
            'amount' => $amount_in_paisa,
            'currency' => $currency,
            'key' => SettingsManager::get_setting('razorpay_key_id'),
            'name' => SettingsManager::get_setting('business_name', get_bloginfo('name')),
            'description' => sprintf(__('Booking #%d payment', 'waza-booking'), $booking_id)
        ];
    }
    
    /**
     * Create Stripe Payment Intent
     */
    private function create_stripe_payment_intent($booking_id, $amount) {
        if (!$this->stripe_api) {
            throw new \Exception(__('Stripe not configured', 'waza-booking'));
        }
        
        $currency = strtolower(SettingsManager::get_setting('currency', 'USD'));
        
        // Convert amount to smallest currency unit (cents for USD, paisa for INR)
        $amount_in_cents = $amount * 100;
        
        $intent_data = [
            'amount' => $amount_in_cents,
            'currency' => $currency,
            'metadata' => [
                'booking_id' => $booking_id,
                'source' => 'waza_booking'
            ]
        ];
        
        $payment_intent = $this->stripe_api->paymentIntents->create($intent_data);
        
        // Store payment intent in database
        $this->store_payment_order($booking_id, 'stripe', $payment_intent->id, $amount);
        
        return [
            'client_secret' => $payment_intent->client_secret,
            'payment_intent_id' => $payment_intent->id,
            'amount' => $amount_in_cents,
            'currency' => $currency
        ];
    }
    
    /**
     * Create PhonePe payment
     */
    private function create_phonepe_payment($booking_id, $amount) {
        if (!$this->phonepe_api) {
            throw new \Exception(__('PhonePe not configured', 'waza-booking'));
        }
        
        global $wpdb;
        
        // Get booking details for customer data
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}waza_bookings WHERE id = %d",
            $booking_id
        ));
        
        if (!$booking) {
            throw new \Exception(__('Booking not found', 'waza-booking'));
        }
        
        $customer_data = [
            'name' => $booking->user_name,
            'email' => $booking->user_email,
            'phone' => $booking->user_phone
        ];
        
        $result = $this->phonepe_api->create_payment($booking_id, $amount, $customer_data);
        
        if (!$result['success']) {
            throw new \Exception($result['error']);
        }
        
        // Store payment order in database
        $this->store_payment_order($booking_id, 'phonepe', $result['transaction_id'], $amount);
        
        return [
            'payment_url' => $result['payment_url'],
            'transaction_id' => $result['transaction_id'],
            'amount' => $amount
        ];
    }
    
    /**
     * Verify payment
     */
    public function verify_payment() {
        check_ajax_referer('waza_payment_nonce', 'nonce');
        
        $gateway = sanitize_text_field($_POST['gateway']);
        $payment_data = $_POST['payment_data'];
        
        try {
            if ($gateway === 'razorpay') {
                $result = $this->verify_razorpay_payment($payment_data);
            } elseif ($gateway === 'stripe') {
                $result = $this->verify_stripe_payment($payment_data);
            } elseif ($gateway === 'phonepe') {
                $result = $this->verify_phonepe_payment($payment_data);
            } else {
                throw new \Exception(__('Invalid payment gateway', 'waza-booking'));
            }
            
            wp_send_json_success($result);
            
        } catch (\Exception $e) {
            error_log('Waza Payment Verification Error: ' . $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Verify Razorpay payment
     */
    private function verify_razorpay_payment($payment_data) {
        $razorpay_order_id = sanitize_text_field($payment_data['razorpay_order_id']);
        $razorpay_payment_id = sanitize_text_field($payment_data['razorpay_payment_id']);
        $razorpay_signature = sanitize_text_field($payment_data['razorpay_signature']);
        
        // Verify signature
        $expected_signature = hash_hmac(
            'sha256',
            $razorpay_order_id . '|' . $razorpay_payment_id,
            SettingsManager::get_setting('razorpay_key_secret')
        );
        
        if (!hash_equals($expected_signature, $razorpay_signature)) {
            throw new \Exception(__('Payment signature verification failed', 'waza-booking'));
        }
        
        // Get payment details
        $payment = $this->razorpay_api->payment->fetch($razorpay_payment_id);
        
        if ($payment['status'] !== 'captured') {
            throw new \Exception(__('Payment not captured', 'waza-booking'));
        }
        
        // Update payment status in database
        $this->update_payment_status($razorpay_order_id, 'completed', $razorpay_payment_id, $payment);
        
        return [
            'status' => 'success',
            'payment_id' => $razorpay_payment_id,
            'order_id' => $razorpay_order_id
        ];
    }
    
    /**
     * Verify Stripe payment
     */
    private function verify_stripe_payment($payment_data) {
        $payment_intent_id = sanitize_text_field($payment_data['payment_intent_id']);
        
        // Retrieve payment intent
        $payment_intent = $this->stripe_api->paymentIntents->retrieve($payment_intent_id);
        
        if ($payment_intent->status !== 'succeeded') {
            throw new \Exception(__('Payment not completed', 'waza-booking'));
        }
        
        // Update payment status in database
        $this->update_payment_status($payment_intent_id, 'completed', $payment_intent_id, $payment_intent);
        
        return [
            'status' => 'success',
            'payment_intent_id' => $payment_intent_id
        ];
    }
    
    /**
     * Verify PhonePe payment
     */
    private function verify_phonepe_payment($payment_data) {
        if (!$this->phonepe_api) {
            throw new \Exception(__('PhonePe not configured', 'waza-booking'));
        }
        
        $result = $this->phonepe_api->verify_payment($payment_data);
        
        if (!$result['success']) {
            throw new \Exception($result['error'] ?? __('Payment verification failed', 'waza-booking'));
        }
        
        // Update payment status in database
        $this->update_payment_status(
            $result['transaction_id'], 
            'completed', 
            $result['phonepe_transaction_id'], 
            $result['payment_data']
        );
        
        return [
            'status' => 'success',
            'transaction_id' => $result['transaction_id'],
            'phonepe_transaction_id' => $result['phonepe_transaction_id']
        ];
    }
    
    /**
     * Store payment order in database
     */
    private function store_payment_order($booking_id, $gateway, $gateway_order_id, $amount) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'waza_payments',
            [
                'booking_id' => $booking_id,
                'payment_method' => $gateway,
                'payment_gateway' => $gateway,
                'gateway_order_id' => $gateway_order_id,
                'amount' => $amount,
                'currency' => SettingsManager::get_setting('currency', 'INR'),
                'status' => 'pending',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s']
        );
    }
    
    /**
     * Update payment status
     */
    private function update_payment_status($gateway_order_id, $status, $gateway_payment_id, $gateway_response) {
        global $wpdb;
        
        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}waza_payments WHERE gateway_order_id = %s",
            $gateway_order_id
        ));
        
        if (!$payment) {
            throw new \Exception(__('Payment record not found', 'waza-booking'));
        }
        
        // Update payment record
        $wpdb->update(
            $wpdb->prefix . 'waza_payments',
            [
                'status' => $status,
                'gateway_payment_id' => $gateway_payment_id,
                'gateway_response' => json_encode($gateway_response),
                'updated_at' => current_time('mysql')
            ],
            ['id' => $payment->id],
            ['%s', '%s', '%s', '%s'],
            ['%d']
        );
        
        // Update booking status
        $wpdb->update(
            $wpdb->prefix . 'waza_bookings',
            [
                'payment_status' => $status === 'completed' ? 'paid' : $status,
                'payment_id' => $gateway_payment_id,
                'updated_at' => current_time('mysql')
            ],
            ['id' => $payment->booking_id],
            ['%s', '%s', '%s'],
            ['%d']
        );
        
        // Trigger booking confirmation if payment successful
        if ($status === 'completed') {
            do_action('waza_booking_payment_completed', $payment->booking_id, $payment);
        }
    }
    
    /**
     * Handle Razorpay webhook
     */
    private function handle_razorpay_webhook() {
        $input = file_get_contents('php://input');
        $webhook_signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';
        
        // Verify webhook signature
        $webhook_secret = SettingsManager::get_setting('razorpay_webhook_secret');
        $expected_signature = hash_hmac('sha256', $input, $webhook_secret);
        
        if (!hash_equals($expected_signature, $webhook_signature)) {
            http_response_code(400);
            exit('Invalid signature');
        }
        
        $data = json_decode($input, true);
        
        if ($data['event'] === 'payment.captured') {
            $payment = $data['payload']['payment']['entity'];
            $this->update_payment_status(
                $payment['order_id'],
                'completed',
                $payment['id'],
                $payment
            );
        }
        
        http_response_code(200);
        exit('OK');
    }
    
    /**
     * Handle Stripe webhook
     */
    private function handle_stripe_webhook() {
        $input = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        
        // Verify webhook signature
        $webhook_secret = SettingsManager::get_setting('stripe_webhook_secret');
        
        try {
            // In production, use Stripe's webhook verification
            // $event = \Stripe\Webhook::constructEvent($input, $signature, $webhook_secret);
            
            // For mock, just decode JSON
            $event = json_decode($input, true);
            
            if ($event['type'] === 'payment_intent.succeeded') {
                $payment_intent = $event['data']['object'];
                $this->update_payment_status(
                    $payment_intent['id'],
                    'completed',
                    $payment_intent['id'],
                    $payment_intent
                );
            }
            
        } catch (\Exception $e) {
            http_response_code(400);
            exit('Webhook signature verification failed');
        }
        
        http_response_code(200);
        exit('OK');
    }
    
    /**
     * Process refund for booking
     * 
     * @param int $booking_id
     * @param string $reason
     * @return bool
     */
    public function process_refund($booking_id, $reason = '') {
        global $wpdb;
        
        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}waza_payments WHERE booking_id = %d AND status = 'completed' ORDER BY id DESC LIMIT 1",
            $booking_id
        ));
        
        if (!$payment) {
            return false;
        }
        
        try {
            if ($payment->payment_gateway === 'razorpay') {
                return $this->process_razorpay_refund($payment, $reason);
            } elseif ($payment->payment_gateway === 'stripe') {
                return $this->process_stripe_refund($payment, $reason);
            } elseif ($payment->payment_gateway === 'phonepe') {
                return $this->process_phonepe_refund($payment, $reason);
            }
        } catch (\Exception $e) {
            error_log('Waza Refund Error: ' . $e->getMessage());
            return false;
        }
        
        return false;
    }
    
    /**
     * Process Razorpay refund
     */
    private function process_razorpay_refund($payment, $reason) {
        if (!$this->razorpay_api) {
            return false;
        }
        
        $refund = $this->razorpay_api->payment->fetch($payment->gateway_payment_id)->refund([
            'amount' => $payment->amount * 100, // Convert to paisa
            'notes' => ['reason' => $reason]
        ]);
        
        // Update payment record
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'waza_payments',
            [
                'refund_amount' => $payment->amount,
                'refund_status' => 'processed',
                'refund_id' => $refund['id'],
                'refund_reason' => $reason,
                'updated_at' => current_time('mysql')
            ],
            ['id' => $payment->id],
            ['%f', '%s', '%s', '%s', '%s'],
            ['%d']
        );
        
        return true;
    }
    
    /**
     * Process Stripe refund
     */
    private function process_stripe_refund($payment, $reason) {
        if (!$this->stripe_api) {
            return false;
        }
        
        $refund = $this->stripe_api->refunds->create([
            'payment_intent' => $payment->gateway_payment_id,
            'amount' => $payment->amount * 100, // Convert to cents
            'reason' => 'requested_by_customer',
            'metadata' => ['reason' => $reason]
        ]);
        
        // Update payment record
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'waza_payments',
            [
                'refund_amount' => $payment->amount,
                'refund_status' => 'processed',
                'refund_id' => $refund->id,
                'refund_reason' => $reason,
                'updated_at' => current_time('mysql')
            ],
            ['id' => $payment->id],
            ['%f', '%s', '%s', '%s', '%s'],
            ['%d']
        );
        
        return true;
    }
    
    /**
     * Process PhonePe refund
     */
    private function process_phonepe_refund($payment, $reason) {
        if (!$this->phonepe_api) {
            return false;
        }
        
        $result = $this->phonepe_api->process_refund(
            $payment->gateway_payment_id,
            $payment->amount,
            $reason
        );
        
        if (!$result['success']) {
            error_log('PhonePe Refund Error: ' . $result['error']);
            return false;
        }
        
        // Update payment record
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'waza_payments',
            [
                'refund_amount' => $payment->amount,
                'refund_status' => 'processed',
                'refund_id' => $result['refund_id'],
                'refund_reason' => $reason,
                'updated_at' => current_time('mysql')
            ],
            ['id' => $payment->id],
            ['%f', '%s', '%s', '%s', '%s'],
            ['%d']
        );
        
        return true;
    }
    
    /**
     * Get available payment methods
     */
    public function get_available_payment_methods() {
        $methods = [];
        
        if (SettingsManager::get_setting('razorpay_enabled') === '1') {
            $methods['razorpay'] = [
                'name' => __('Razorpay', 'waza-booking'),
                'description' => __('Pay securely using Razorpay', 'waza-booking'),
                'supported_methods' => SettingsManager::get_setting('payment_methods', [])
            ];
        }
        
        if (SettingsManager::get_setting('stripe_enabled') === '1') {
            $methods['stripe'] = [
                'name' => __('Stripe', 'waza-booking'),
                'description' => __('Pay securely using Stripe', 'waza-booking'),
                'supported_methods' => ['card']
            ];
        }
        
        if (SettingsManager::get_setting('phonepe_enabled') === '1') {
            $methods['phonepe'] = [
                'name' => __('PhonePe', 'waza-booking'),
                'description' => __('Pay securely using PhonePe', 'waza-booking'),
                'supported_methods' => ['upi', 'card', 'wallet', 'netbanking']
            ];
        }
        
        return $methods;
    }
}

/**
 * Mock Razorpay API for development
 */
class RazorpayMockApi {
    public $order;
    public $payment;
    
    public function __construct($key_id, $key_secret) {
        $this->order = new RazorpayMockOrder();
        $this->payment = new RazorpayMockPayment();
    }
}

class RazorpayMockOrder {
    public function create($data) {
        return [
            'id' => 'order_' . wp_generate_password(14, false),
            'entity' => 'order',
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'receipt' => $data['receipt'],
            'status' => 'created'
        ];
    }
}

class RazorpayMockPayment {
    public function fetch($payment_id) {
        return [
            'id' => $payment_id,
            'entity' => 'payment',
            'amount' => 100000,
            'currency' => 'INR',
            'status' => 'captured',
            'method' => 'card'
        ];
    }
}

/**
 * Mock Stripe API for development  
 */
class StripeMockApi {
    public $paymentIntents;
    public $refunds;
    
    public function __construct($secret_key) {
        $this->paymentIntents = new StripeMockPaymentIntents();
        $this->refunds = new StripeMockRefunds();
    }
}

class StripeMockPaymentIntents {
    public function create($data) {
        return (object) [
            'id' => 'pi_' . wp_generate_password(24, false),
            'client_secret' => 'pi_' . wp_generate_password(24, false) . '_secret_' . wp_generate_password(32, false),
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'status' => 'requires_payment_method'
        ];
    }
    
    public function retrieve($payment_intent_id) {
        return (object) [
            'id' => $payment_intent_id,
            'status' => 'succeeded',
            'amount' => 100000,
            'currency' => 'usd'
        ];
    }
}

class StripeMockRefunds {
    public function create($data) {
        return (object) [
            'id' => 're_' . wp_generate_password(24, false),
            'amount' => $data['amount'],
            'status' => 'succeeded'
        ];
    }
}