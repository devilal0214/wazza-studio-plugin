<?php
/**
 * PhonePe Payment Gateway
 * 
 * Handles PhonePe payment processing
 * 
 * @package WazaBooking\Payment\Gateways
 */

namespace WazaBooking\Payment\Gateways;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * PhonePe Gateway Class
 */
class PhonePeGateway {
    
    /**
     * Merchant ID
     */
    private $merchant_id;
    
    /**
     * Salt Key
     */
    private $salt_key;
    
    /**
     * Salt Index
     */
    private $salt_index;
    
    /**
     * Is Test Mode
     */
    private $test_mode;
    
    /**
     * API Base URL
     */
    private $api_url;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->merchant_id = get_option('waza_phonepe_merchant_id', '');
        $this->salt_key = get_option('waza_phonepe_salt_key', '');
        $this->salt_index = get_option('waza_phonepe_salt_index', '1');
        $this->test_mode = get_option('waza_phonepe_test_mode', 'yes') === 'yes';
        
        // Set API URL based on mode
        $this->api_url = $this->test_mode 
            ? 'https://api-preprod.phonepe.com/apis/pg-sandbox'
            : 'https://api.phonepe.com/apis/hermes';
    }
    
    /**
     * Create payment
     * 
     * @param int $booking_id Booking ID
     * @param float $amount Amount in INR
     * @param array $customer_data Customer information
     * @return array Payment response
     */
    public function create_payment($booking_id, $amount, $customer_data) {
        try {
            // Generate unique transaction ID
            $transaction_id = 'WAZA_' . $booking_id . '_' . time();
            
            // Convert amount to paise (PhonePe requires amount in paise)
            $amount_paise = intval($amount * 100);
            
            // Prepare payment payload
            $payload = [
                'merchantId' => $this->merchant_id,
                'merchantTransactionId' => $transaction_id,
                'merchantUserId' => 'WAZA_USER_' . $booking_id,
                'amount' => $amount_paise,
                'redirectUrl' => home_url('/booking-confirmation?booking_id=' . $booking_id),
                'redirectMode' => 'POST',
                'callbackUrl' => home_url('/wp-json/waza-booking/v1/phonepe/callback'),
                'mobileNumber' => preg_replace('/[^0-9]/', '', $customer_data['phone']),
                'paymentInstrument' => [
                    'type' => 'PAY_PAGE'
                ]
            ];
            
            // Encode payload to base64
            $encoded_payload = base64_encode(json_encode($payload));
            
            // Generate X-VERIFY header
            $string_to_hash = $encoded_payload . '/pg/v1/pay' . $this->salt_key;
            $x_verify = hash('sha256', $string_to_hash) . '###' . $this->salt_index;
            
            // Make API request
            $response = wp_remote_post($this->api_url . '/pg/v1/pay', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-VERIFY' => $x_verify
                ],
                'body' => json_encode([
                    'request' => $encoded_payload
                ]),
                'timeout' => 30
            ]);
            
            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }
            
            $body = json_decode(wp_remote_retrieve_body($response), true);
            
            if (!isset($body['success']) || !$body['success']) {
                $error_message = $body['message'] ?? 'PhonePe payment initiation failed';
                throw new \Exception($error_message);
            }
            
            // Store transaction details
            update_post_meta($booking_id, '_phonepe_transaction_id', $transaction_id);
            update_post_meta($booking_id, '_phonepe_payment_data', $body);
            
            return [
                'success' => true,
                'transaction_id' => $transaction_id,
                'payment_url' => $body['data']['instrumentResponse']['redirectInfo']['url'],
                'message' => __('PhonePe payment initiated successfully', 'waza-booking')
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Verify payment callback
     * 
     * @param array $callback_data Callback data from PhonePe
     * @return array Verification result
     */
    public function verify_payment($callback_data) {
        try {
            $merchant_transaction_id = $callback_data['transactionId'] ?? '';
            
            if (empty($merchant_transaction_id)) {
                throw new \Exception('Transaction ID not provided');
            }
            
            // Generate X-VERIFY header for status check
            $string_to_hash = '/pg/v1/status/' . $this->merchant_id . '/' . $merchant_transaction_id . $this->salt_key;
            $x_verify = hash('sha256', $string_to_hash) . '###' . $this->salt_index;
            
            // Check payment status
            $response = wp_remote_get(
                $this->api_url . '/pg/v1/status/' . $this->merchant_id . '/' . $merchant_transaction_id,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'X-VERIFY' => $x_verify
                    ],
                    'timeout' => 30
                ]
            );
            
            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }
            
            $body = json_decode(wp_remote_retrieve_body($response), true);
            
            if (!isset($body['success']) || !$body['success']) {
                throw new \Exception('Payment verification failed');
            }
            
            $payment_state = $body['data']['state'] ?? '';
            $payment_code = $body['data']['responseCode'] ?? '';
            
            // Check if payment is successful
            if ($payment_state === 'COMPLETED' && $payment_code === 'PAYMENT_SUCCESS') {
                return [
                    'success' => true,
                    'transaction_id' => $merchant_transaction_id,
                    'phonepe_transaction_id' => $body['data']['transactionId'] ?? '',
                    'amount' => ($body['data']['amount'] ?? 0) / 100, // Convert paise to rupees
                    'payment_method' => $body['data']['paymentInstrument']['type'] ?? 'PhonePe',
                    'payment_data' => $body['data']
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $body['data']['responseCodeDescription'] ?? 'Payment failed'
                ];
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Process refund
     * 
     * @param string $transaction_id Original transaction ID
     * @param float $amount Refund amount
     * @param string $reason Refund reason
     * @return array Refund result
     */
    public function process_refund($transaction_id, $amount, $reason = '') {
        try {
            // Generate unique refund transaction ID
            $refund_transaction_id = 'REFUND_' . $transaction_id . '_' . time();
            
            // Convert amount to paise
            $amount_paise = intval($amount * 100);
            
            // Prepare refund payload
            $payload = [
                'merchantId' => $this->merchant_id,
                'merchantTransactionId' => $refund_transaction_id,
                'originalTransactionId' => $transaction_id,
                'amount' => $amount_paise,
                'callbackUrl' => home_url('/wp-json/waza-booking/v1/phonepe/refund-callback')
            ];
            
            // Encode payload
            $encoded_payload = base64_encode(json_encode($payload));
            
            // Generate X-VERIFY header
            $string_to_hash = $encoded_payload . '/pg/v1/refund' . $this->salt_key;
            $x_verify = hash('sha256', $string_to_hash) . '###' . $this->salt_index;
            
            // Make refund API request
            $response = wp_remote_post($this->api_url . '/pg/v1/refund', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-VERIFY' => $x_verify
                ],
                'body' => json_encode([
                    'request' => $encoded_payload
                ]),
                'timeout' => 30
            ]);
            
            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }
            
            $body = json_decode(wp_remote_retrieve_body($response), true);
            
            if (!isset($body['success']) || !$body['success']) {
                $error_message = $body['message'] ?? 'PhonePe refund failed';
                throw new \Exception($error_message);
            }
            
            return [
                'success' => true,
                'refund_id' => $refund_transaction_id,
                'phonepe_refund_id' => $body['data']['transactionId'] ?? '',
                'amount' => $amount,
                'status' => $body['data']['state'] ?? 'PENDING',
                'message' => __('Refund initiated successfully', 'waza-booking')
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check refund status
     * 
     * @param string $refund_transaction_id Refund transaction ID
     * @return array Refund status
     */
    public function check_refund_status($refund_transaction_id) {
        try {
            // Generate X-VERIFY header
            $string_to_hash = '/pg/v1/status/' . $this->merchant_id . '/' . $refund_transaction_id . $this->salt_key;
            $x_verify = hash('sha256', $string_to_hash) . '###' . $this->salt_index;
            
            // Check refund status
            $response = wp_remote_get(
                $this->api_url . '/pg/v1/status/' . $this->merchant_id . '/' . $refund_transaction_id,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'X-VERIFY' => $x_verify
                    ],
                    'timeout' => 30
                ]
            );
            
            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }
            
            $body = json_decode(wp_remote_retrieve_body($response), true);
            
            return [
                'success' => true,
                'status' => $body['data']['state'] ?? 'PENDING',
                'data' => $body['data'] ?? []
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Validate webhook signature
     * 
     * @param string $x_verify X-VERIFY header from webhook
     * @param string $response_body Response body
     * @return bool Is valid
     */
    public function validate_webhook($x_verify, $response_body) {
        $string_to_hash = $response_body . $this->salt_key;
        $calculated_hash = hash('sha256', $string_to_hash) . '###' . $this->salt_index;
        
        return hash_equals($calculated_hash, $x_verify);
    }
    
    /**
     * Get payment status display
     * 
     * @param string $status PhonePe payment status
     * @return string Display status
     */
    public function get_status_display($status) {
        $status_map = [
            'COMPLETED' => __('Completed', 'waza-booking'),
            'PENDING' => __('Pending', 'waza-booking'),
            'FAILED' => __('Failed', 'waza-booking'),
            'CANCELLED' => __('Cancelled', 'waza-booking')
        ];
        
        return $status_map[$status] ?? $status;
    }
}
