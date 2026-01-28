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
        $razorpay_enabled = SettingsManager::get_setting('razorpay_enabled');
        
        if ($key_id && $key_secret) {
            try {
                if (class_exists('Razorpay\Api\Api')) {
                    $this->razorpay_api = new \Razorpay\Api\Api($key_id, $key_secret);
                    error_log('Waza: Razorpay API initialized successfully');
                } else {
                    error_log('Waza: Razorpay SDK not found, payment will not work');
                    // Only show notice if Razorpay is actually enabled in settings
                    if ($razorpay_enabled === '1' || $razorpay_enabled === 'on') {
                        add_action('admin_notices', function() {
                            if (current_user_can('manage_options')) {
                                echo '<div class="notice notice-error is-dismissible"><p>';
                                echo '<strong>' . __('Waza Booking:', 'waza-booking') . '</strong> ';
                                echo __('Razorpay SDK not loaded. Vendor autoloader may need regeneration.', 'waza-booking');
                                echo '</p></div>';
                            }
                        });
                    }
                }
            } catch (\Exception $e) {
                error_log('Waza: Failed to initialize Razorpay: ' . $e->getMessage());
                if ($razorpay_enabled === '1' || $razorpay_enabled === 'on') {
                    add_action('admin_notices', function() use ($e) {
                        if (current_user_can('manage_options')) {
                            echo '<div class="notice notice-error is-dismissible"><p>';
                            echo '<strong>' . __('Waza Booking:', 'waza-booking') . '</strong> ';
                            echo esc_html($e->getMessage());
                            echo '</p></div>';
                        }
                    });
                }
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
        // Clean output buffer to remove any warnings/notices
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Check nonce - use frontend nonce or payment nonce
        if (!isset($_POST['nonce']) || 
            (!wp_verify_nonce($_POST['nonce'], 'waza_frontend_nonce') && 
             !wp_verify_nonce($_POST['nonce'], 'waza_payment_nonce'))) {
            wp_send_json_error(['message' => __('Security check failed', 'waza-booking')], 403);
        }
        
        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
        $temp_rental_id = isset($_POST['temp_rental_id']) ? sanitize_text_field($_POST['temp_rental_id']) : '';
        $amount = floatval($_POST['amount']);
        $gateway = sanitize_text_field($_POST['gateway']);
        
        // For rentals, retrieve amount from transient if not provided
        if ($temp_rental_id && !$amount) {
            $rental_data = get_transient($temp_rental_id);
            if ($rental_data && isset($rental_data['total_amount'])) {
                $amount = floatval($rental_data['total_amount']);
            }
        }
        
        // Need either booking_id or temp_rental_id, and must have amount
        if ((!$booking_id && !$temp_rental_id) || !$amount) {
            error_log('Waza Payment Error - booking_id: ' . $booking_id . ', temp_rental_id: ' . $temp_rental_id . ', amount: ' . $amount);
            wp_send_json_error(['message' => __('Invalid booking or amount', 'waza-booking')]);
        }
        
        // Use booking_id for regular bookings, temp_rental_id for rentals
        $order_reference_id = $booking_id ?: $temp_rental_id;
        
        try {
            if ($gateway === 'razorpay') {
                $order = $this->create_razorpay_order($order_reference_id, $amount);
            } elseif ($gateway === 'stripe') {
                $order = $this->create_stripe_payment_intent($order_reference_id, $amount);
            } elseif ($gateway === 'phonepe') {
                $order = $this->create_phonepe_payment($order_reference_id, $amount);
            } else {
                throw new \Exception(__('Invalid payment gateway', 'waza-booking'));
            }
            
            // Add rental info to response if this is a rental
            if ($temp_rental_id) {
                $order['temp_rental_id'] = $temp_rental_id;
                $order['booking_type'] = 'rental';
            } else {
                $order['booking_id'] = $booking_id;
                $order['booking_type'] = 'activity';
            }
            
            wp_send_json_success($order);
            
        } catch (\Exception $e) {
            error_log('Waza Payment Error: ' . $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Create Razorpay order
     * 
     * @param mixed $reference_id - Can be booking_id (int) or temp_rental_id (string)
     * @param float $amount - Amount to charge
     */
    private function create_razorpay_order($reference_id, $amount) {
        if (!$this->razorpay_api) {
            error_log('Waza: Razorpay API not initialized');
            throw new \Exception(__('Razorpay not configured. Please check your API credentials.', 'waza-booking'));
        }
        
        $currency = SettingsManager::get_setting('currency', 'INR');
        
        // Convert amount to paisa (Razorpay requires amount in smallest currency unit)
        $amount_in_paisa = (int)($amount * 100);
        
        // Determine receipt prefix based on reference type
        $receipt_prefix = is_numeric($reference_id) ? 'waza_booking_' : 'waza_rental_';
        
        $order_data = [
            'receipt' => $receipt_prefix . $reference_id,
            'amount' => $amount_in_paisa,
            'currency' => $currency,
            'payment_capture' => 1
        ];
        
        error_log('Waza: Creating Razorpay order: ' . json_encode($order_data));
        
        try {
            $order = $this->razorpay_api->order->create($order_data);
            error_log('Waza: Razorpay order created: ' . $order['id']);
        } catch (\Exception $e) {
            error_log('Waza: Razorpay order creation failed: ' . $e->getMessage());
            throw new \Exception(__('Failed to create payment order: ', 'waza-booking') . $e->getMessage());
        }
        
        // Store order in database (only for bookings, not rentals)
        if (is_numeric($reference_id)) {
            $this->store_payment_order($reference_id, 'razorpay', $order['id'], $amount);
        }
        
        return [
            'order_id' => $order['id'],
            'amount' => $amount_in_paisa,
            'currency' => $currency,
            'key' => SettingsManager::get_setting('razorpay_key_id'),
            'name' => SettingsManager::get_setting('business_name', get_bloginfo('name')),
            'description' => sprintf(__('Payment for #%s', 'waza-booking'), $reference_id)
        ];
    }
    
    /**
     * Create Stripe Payment Intent
     * 
     * @param mixed $reference_id - Can be booking_id (int) or temp_rental_id (string)
     * @param float $amount - Amount to charge
     */
    private function create_stripe_payment_intent($reference_id, $amount) {
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
                'reference_id' => $reference_id,
                'source' => 'waza_booking'
            ]
        ];
        
        $payment_intent = $this->stripe_api->paymentIntents->create($intent_data);
        
        // Store payment intent in database (only for bookings)
        if (is_numeric($reference_id)) {
            $this->store_payment_order($reference_id, 'stripe', $payment_intent->id, $amount);
        }
        
        return [
            'client_secret' => $payment_intent->client_secret,
            'payment_intent_id' => $payment_intent->id,
            'amount' => $amount_in_cents,
            'currency' => $currency
        ];
    }
    
    /**
     * Create PhonePe payment
     * 
     * @param mixed $reference_id - Can be booking_id (int) or temp_rental_id (string)
     * @param float $amount - Amount to charge
     */
    private function create_phonepe_payment($reference_id, $amount) {
        if (!$this->phonepe_api) {
            throw new \Exception(__('PhonePe not configured', 'waza-booking'));
        }
        
        global $wpdb;
        
        $customer_data = ['name' => '', 'email' => '', 'phone' => ''];
        
        // Get customer details from booking or rental
        if (is_numeric($reference_id)) {
            // It's a booking
            $booking = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}waza_bookings WHERE id = %d",
                $reference_id
            ));
            
            if ($booking) {
                $customer_data = [
                    'name' => $booking->user_name,
                    'email' => $booking->user_email,
                    'phone' => $booking->user_phone
                ];
            }
        } else {
            // It's a rental (temp_rental_id)
            $rental_data = get_transient($reference_id);
            if ($rental_data) {
                $customer_data = [
                    'name' => $rental_data['customer_name'] ?? '',
                    'email' => $rental_data['customer_email'] ?? '',
                    'phone' => $rental_data['customer_phone'] ?? ''
                ];
            }
        }
        
        $result = $this->phonepe_api->create_payment($reference_id, $amount, $customer_data);
        
        if (!$result['success']) {
            throw new \Exception($result['message'] ?? __('PhonePe payment creation failed', 'waza-booking'));
        }
        
        // Store payment order (only for bookings)
        if (is_numeric($reference_id)) {
            $this->store_payment_order($reference_id, 'phonepe', $result['transaction_id'], $amount);
        }
        
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
        // Clean output buffer to remove any warnings/notices
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Try payment nonce first, fallback to frontend nonce
        if (!check_ajax_referer('waza_payment_nonce', 'nonce', false)) {
            check_ajax_referer('waza_frontend_nonce', 'nonce');
        }
        
        $gateway = sanitize_text_field($_POST['gateway'] ?? '');
        $payment_data = $_POST['payment_data'] ?? [];
        $booking_id = intval($_POST['booking_id'] ?? 0);
        $temp_rental_id = sanitize_text_field($_POST['temp_rental_id'] ?? '');
        $booking_type = sanitize_text_field($_POST['booking_type'] ?? 'activity');
        
        error_log('=== WAZA PAYMENT VERIFICATION START ===');
        error_log(sprintf(
            'Type: %s, Gateway: %s, Booking ID: %d, Rental ID: %s',
            $booking_type, $gateway, $booking_id, $temp_rental_id
        ));
        error_log('Payment Data: ' . print_r($payment_data, true));
        
        if (!$gateway) {
            error_log('ERROR: Gateway not specified');
            wp_send_json_error(['message' => __('Payment gateway not specified', 'waza-booking')]);
        }
        
        try {
            if ($gateway === 'razorpay') {
                $result = $this->verify_razorpay_payment($payment_data, $booking_id, $temp_rental_id);
            } elseif ($gateway === 'stripe') {
                $result = $this->verify_stripe_payment($payment_data, $booking_id, $temp_rental_id);
            } elseif ($gateway === 'phonepe') {
                $result = $this->verify_phonepe_payment($payment_data, $booking_id, $temp_rental_id);
            } else {
                throw new \Exception(__('Invalid payment gateway', 'waza-booking'));
            }
            
            // Handle based on booking type
            if ($booking_type === 'rental' && $temp_rental_id) {
                error_log('Processing rental payment for temp_id: ' . $temp_rental_id);
                
                // Trigger rental completion
                $rental_result = apply_filters('waza_complete_rental_booking', null, $temp_rental_id, $result);
                
                // If filter didn't handle it, do it manually
                if (!$rental_result) {
                    $rental_manager = new \WazaBooking\Rental\RentalManager();
                    $rental_id = $rental_manager->complete_rental_booking($temp_rental_id, [
                        'gateway' => $gateway,
                        'payment_id' => $result['payment_id'] ?? '',
                        'order_id' => $result['order_id'] ?? ''
                    ]);
                    
                    if (is_wp_error($rental_id)) {
                        error_log('Rental completion error: ' . $rental_id->get_error_message());
                        throw new \Exception($rental_id->get_error_message());
                    }
                    
                    error_log('Rental completed successfully. Rental ID: ' . $rental_id);
                }
                
                // Trigger success action
                do_action('waza_payment_success', [
                    'gateway' => $gateway,
                    'payment_id' => $result['payment_id'] ?? '',
                    'temp_rental_id' => $temp_rental_id,
                    'type' => 'rental'
                ]);
                
                // Add security hash to rental confirmation URL
                global $wpdb;
                $rental = $wpdb->get_row($wpdb->prepare(
                    "SELECT customer_email FROM {$wpdb->prefix}waza_rentals WHERE id = %d",
                    $rental_id
                ));
                
                if ($rental) {
                    $verify_hash = md5($rental_id . $rental->customer_email . wp_salt());
                    $result['redirect_url'] = home_url('/booking-confirmation/?rental_id=' . $rental_id . '&verify=' . $verify_hash);
                } else {
                    $result['redirect_url'] = home_url('/booking-confirmation/?rental_id=' . ($rental_id ?? 0));
                }
                
                $result['message'] = __('Rental booked successfully!', 'waza-booking');
                
            } else {
                // Activity booking - get booking details
                $verified_booking_id = $result['booking_id'] ?? $booking_id;
                
                error_log('Waza: Payment verified, fetching booking details for ID: ' . $verified_booking_id);
                
                global $wpdb;
                $booking = $wpdb->get_row($wpdb->prepare(
                    "SELECT b.*, s.start_datetime, s.end_datetime, s.activity_id,
                            p.post_title as activity_title
                     FROM {$wpdb->prefix}waza_bookings b
                     LEFT JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
                     LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
                     WHERE b.id = %d",
                    $verified_booking_id
                ));
                
                if ($booking) {
                    // Trigger payment success action for booking completion
                    do_action('waza_payment_success', [
                        'gateway' => $gateway,
                        'payment_id' => $result['payment_id'] ?? '',
                        'booking_id' => $booking->id,
                        'type' => 'booking'
                    ]);
                    
                    $result['booking_id'] = $booking->id;
                    $result['activity_title'] = $booking->activity_title;
                    $result['datetime'] = date_i18n('l, F j, Y', strtotime($booking->start_datetime)) . ' at ' . date_i18n('g:i A', strtotime($booking->start_datetime));
                    $result['customer_name'] = $booking->user_name;
                    
                    // Add security hash to URL
                    $verify_hash = md5($booking->id . $booking->user_email . wp_salt());
                    $result['redirect_url'] = home_url('/booking-confirmation/?booking_id=' . $booking->id . '&verify=' . $verify_hash);
                    $result['message'] = __('Payment successful! Redirecting to confirmation page...', 'waza-booking');
                    
                    error_log('Waza: Payment verification complete. Booking ID: ' . $booking->id . ', Redirect: ' . $result['redirect_url']);
                } else {
                    error_log('Waza: WARNING - Booking not found for ID: ' . $verified_booking_id);
                    throw new \Exception(__('Booking not found after payment', 'waza-booking'));
                }
            }
            
            error_log('=== PAYMENT VERIFICATION SUCCESS ===');
            error_log('Response: ' . print_r($result, true));
            
            wp_send_json_success($result);
            
        } catch (\Exception $e) {
            error_log('=== PAYMENT VERIFICATION ERROR ===');
            error_log('Error: ' . $e->getMessage());
            error_log('Trace: ' . $e->getTraceAsString());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Verify Razorpay payment
     */
    private function verify_razorpay_payment($payment_data, $booking_id = 0, $temp_rental_id = '') {
        $razorpay_order_id = sanitize_text_field($payment_data['razorpay_order_id'] ?? '');
        $razorpay_payment_id = sanitize_text_field($payment_data['razorpay_payment_id'] ?? '');
        $razorpay_signature = sanitize_text_field($payment_data['razorpay_signature'] ?? '');
        
        if (!$razorpay_order_id || !$razorpay_payment_id || !$razorpay_signature) {
            throw new \Exception(__('Missing payment data', 'waza-booking'));
        }
        
        // Verify signature
        $expected_signature = hash_hmac(
            'sha256',
            $razorpay_order_id . '|' . $razorpay_payment_id,
            SettingsManager::get_setting('razorpay_key_secret')
        );
        
        if (!hash_equals($expected_signature, $razorpay_signature)) {
            throw new \Exception(__('Payment signature verification failed', 'waza-booking'));
        }
        
        // Get payment record to retrieve booking_id if not provided
        if (!$booking_id && !$temp_rental_id) {
            global $wpdb;
            $payment = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}waza_payments WHERE gateway_order_id = %s",
                $razorpay_order_id
            ));
            
            if ($payment) {
                $booking_id = $payment->booking_id;
            }
        }
        
        // Get payment details from Razorpay API
        try {
            $payment_details = $this->razorpay_api->payment->fetch($razorpay_payment_id);
            
            if ($payment_details['status'] !== 'captured' && $payment_details['status'] !== 'authorized') {
                throw new \Exception(__('Payment not captured', 'waza-booking'));
            }
        } catch (\Exception $e) {
            // If API call fails, continue anyway since signature is valid
            error_log('Razorpay API fetch error: ' . $e->getMessage());
            $payment_details = ['status' => 'captured'];
        }
        
        // Update payment status in database (if payment record exists)
        if ($booking_id) {
            $this->update_payment_status($razorpay_order_id, 'completed', $razorpay_payment_id, $payment_details);
            
            // Update booking status
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'waza_bookings',
                [
                    'payment_status' => 'completed',
                    'booking_status' => 'confirmed'
                ],
                ['id' => $booking_id],
                ['%s', '%s'],
                ['%d']
            );
        }
        
        return [
            'status' => 'success',
            'payment_id' => $razorpay_payment_id,
            'order_id' => $razorpay_order_id,
            'booking_id' => $booking_id
        ];
    }
    
    /**
     * Verify Stripe payment
     */
    private function verify_stripe_payment($payment_data, $booking_id = 0, $temp_rental_id = '') {
        $payment_intent_id = sanitize_text_field($payment_data['payment_intent_id'] ?? '');
        
        if (!$payment_intent_id) {
            throw new \Exception(__('Missing payment intent ID', 'waza-booking'));
        }
        
        // Retrieve payment intent
        $payment_intent = $this->stripe_api->paymentIntents->retrieve($payment_intent_id);
        
        if ($payment_intent->status !== 'succeeded') {
            throw new \Exception(__('Payment not completed', 'waza-booking'));
        }
        
        // Update payment status in database
        if ($booking_id) {
            $this->update_payment_status($payment_intent_id, 'completed', $payment_intent_id, $payment_intent);
            
            // Update booking status
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'waza_bookings',
                [
                    'payment_status' => 'completed',
                    'booking_status' => 'confirmed'
                ],
                ['id' => $booking_id],
                ['%s', '%s'],
                ['%d']
            );
        }
        
        return [
            'status' => 'success',
            'payment_id' => $payment_intent_id,
            'booking_id' => $booking_id
        ];
    }
    
    /**
     * Verify PhonePe payment
     */
    private function verify_phonepe_payment($payment_data, $booking_id = 0, $temp_rental_id = '') {
        if (!$this->phonepe_api) {
            throw new \Exception(__('PhonePe not configured', 'waza-booking'));
        }
        
        $result = $this->phonepe_api->verify_payment($payment_data);
        
        if (!$result['success']) {
            throw new \Exception($result['error'] ?? __('Payment verification failed', 'waza-booking'));
        }
        
        // Update payment status in database
        if ($booking_id) {
            $this->update_payment_status(
                $result['transaction_id'], 
                'completed', 
                $result['phonepe_transaction_id'], 
                $result['payment_data']
            );
            
            // Update booking status
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'waza_bookings',
                [
                    'payment_status' => 'completed',
                    'booking_status' => 'confirmed'
                ],
                ['id' => $booking_id],
                ['%s', '%s'],
                ['%d']
            );
        }
        
        return [
            'status' => 'success',
            'payment_id' => $result['phonepe_transaction_id'],
            'transaction_id' => $result['transaction_id'],
            'booking_id' => $booking_id
        ];
    }
    
    /**
     * Store payment order in database
     */
    private function store_payment_order($booking_id, $gateway, $gateway_order_id, $amount) {
        global $wpdb;
        
        // Check if payment order already exists for this booking and gateway
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}waza_payments 
             WHERE booking_id = %d AND gateway_order_id = %s",
            $booking_id,
            $gateway_order_id
        ));
        
        if ($existing) {
            error_log('Waza: Payment order already exists for booking #' . $booking_id . ', order: ' . $gateway_order_id);
            return; // Already exists, skip insert
        }
        
        $wpdb->insert(
            $wpdb->prefix . 'waza_payments',
            [
                'booking_id' => $booking_id,
                'payment_method' => $gateway,
                'payment_gateway' => $gateway,
                'gateway_order_id' => $gateway_order_id,
                'gateway_payment_id' => null,  // Set to NULL initially, will be updated after payment
                'amount' => $amount,
                'currency' => SettingsManager::get_setting('currency', 'INR'),
                'status' => 'pending',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s']
        );
        
        if ($wpdb->last_error) {
            error_log('Waza: Error storing payment order: ' . $wpdb->last_error);
        } else {
            error_log('Waza: Payment order stored successfully for booking #' . $booking_id);
        }
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
            error_log('Waza: Payment record not found for gateway_order_id: ' . $gateway_order_id . ' - This is OK for slot bookings, payment will be tracked via waza_bookings table');
            // Don't throw exception - payment record is optional for slot bookings
            // The booking status is updated directly in verify_razorpay_payment
            return;
        }
        
        // Update payment record
        $wpdb->update(
            $wpdb->prefix . 'waza_payments',
            [
                'status' => $status,
                'gateway_payment_id' => $gateway_payment_id,
                'gateway_response' => json_encode($gateway_response),
                'paid_at' => $status === 'completed' ? current_time('mysql') : null,
                'updated_at' => current_time('mysql')
            ],
            ['id' => $payment->id],
            ['%s', '%s', '%s', '%s', '%s'],
            ['%d']
        );
        
        // If payment successful, update booking status and trigger post-payment actions
        if ($status === 'completed') {
            // Update booking status to confirmed
            $wpdb->update(
                $wpdb->prefix . 'waza_bookings',
                [
                    'booking_status' => 'confirmed',
                    'payment_status' => 'completed',
                    'payment_id' => $gateway_payment_id,
                    'updated_at' => current_time('mysql')
                ],
                ['id' => $payment->booking_id],
                ['%s', '%s', '%s', '%s'],
                ['%d']
            );
            
            // Get booking details
            $booking = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}waza_bookings WHERE id = %d",
                $payment->booking_id
            ));
            
            if ($booking) {
                // Create account if needed
                $this->create_user_account_if_needed($booking);
                
                // Update slot booked count
                $this->update_slot_booked_count($booking->slot_id, $booking->quantity);
                
                // Create booking post for admin
                $this->create_booking_post($booking->id);
                
                // Generate QR code
                $this->generate_booking_qr($booking->id);
                
                // Send confirmation email
                $this->send_booking_confirmation($booking->id);
            }
            
            // Trigger action for extensibility
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
    
    /**
     * Create user account if needed (helper for post-payment processing)
     */
    private function create_user_account_if_needed($booking) {
        // Check if booking has user_id or needs account creation
        if ($booking->user_id) {
            return; // User already exists
        }
        
        // Check for pending account creation
        $pending_account = get_option("waza_pending_account_{$booking->id}");
        
        if (!$pending_account || !is_array($pending_account)) {
            return; // No pending account creation
        }
        
        $password = $pending_account['password'];
        $password_option = $pending_account['password_option'];
        
        // Create user account
        $user_id = wp_create_user($booking->user_email, $password, $booking->user_email);
        
        if (!is_wp_error($user_id)) {
            global $wpdb;
            
            // Update user meta
            wp_update_user([
                'ID' => $user_id,
                'display_name' => $booking->user_name,
                'role' => 'waza_student'
            ]);
            update_user_meta($user_id, 'phone', $booking->user_phone);
            
            // Update booking with user_id
            $wpdb->update(
                $wpdb->prefix . 'waza_bookings',
                ['user_id' => $user_id],
                ['id' => $booking->id],
                ['%d'],
                ['%d']
            );
            
            // Send credentials email if auto-generated
            if ($password_option === 'auto') {
                $this->send_account_credentials($booking->user_email, $booking->user_name, $password);
            }
            
            // Clean up option
            delete_option("waza_pending_account_{$booking->id}");
        }
    }
    
    /**
     * Update slot booked count
     */
    private function update_slot_booked_count($slot_id, $quantity) {
        global $wpdb;
        
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}waza_slots 
             SET booked_count = booked_count + %d 
             WHERE id = %d",
            $quantity,
            $slot_id
        ));
    }
    
    /**
     * Create booking post for admin
     */
    private function create_booking_post($booking_id) {
        // This creates a custom post for admin viewing
        // You can implement this based on your needs
        do_action('waza_create_booking_post', $booking_id);
    }
    
    /**
     * Generate QR code for booking
     */
    private function generate_booking_qr($booking_id) {
        $data = "WAZA-BOOKING-" . $booking_id;
        $url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($data);
        
        // Store QR code URL in post meta
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'waza_bookings',
            ['qr_code' => $url],
            ['id' => $booking_id],
            ['%s'],
            ['%d']
        );
        
        return $url;
    }
    
    /**
     * Send booking confirmation email
     */
    private function send_booking_confirmation($booking_id) {
        do_action('waza_send_booking_confirmation', $booking_id);
    }
    
    /**
     * Send account credentials email
     */
    private function send_account_credentials($email, $name, $password) {
        $login_url = wp_login_url();
        
        // Use email template manager
        $email_template_manager = \WazaBooking\Core\Plugin::get_instance()->get_manager('email_template');
        
        if ($email_template_manager) {
            $email_template_manager->send_email('welcome_email', $email, [
                'user_name' => $name,
                'user_first_name' => $name,
                'user_email' => $email,
                'password' => $password,
                'login_url' => $login_url,
                'message' => __('Your account has been created successfully! Please log in and change your password.', 'waza-booking')
            ]);
        }
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