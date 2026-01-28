<?php
/**
 * Checkout Page Handler
 * 
 * Creates a checkout page for handling payments from activity bookings and rentals
 * 
 * @package WazaBooking
 */

namespace WazaBooking\Payment;

if (!defined('ABSPATH')) {
    exit;
}

class CheckoutPageHandler {
    
    public function __construct() {
        add_shortcode('waza_checkout', [$this, 'render_checkout_page']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_checkout_scripts']);
    }
    
    /**
     * Enqueue checkout scripts
     */
    public function enqueue_checkout_scripts() {
        if (is_page() && has_shortcode(get_post()->post_content, 'waza_checkout')) {
            wp_enqueue_script('jquery');
            
            // Localize script for AJAX
            wp_localize_script('jquery', 'waza_frontend', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('waza_frontend_nonce')
            ]);
        }
    }
    
    /**
     * Render checkout page
     */
    public function render_checkout_page($atts) {
        ob_start();
        
        // Get payment details from URL parameters
        $booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
        $rental_id = isset($_GET['rental_id']) ? intval($_GET['rental_id']) : 0;
        $temp_rental_id = isset($_GET['temp_rental_id']) ? sanitize_text_field($_GET['temp_rental_id']) : '';
        $amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 0;
        $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
        $customer_name = isset($_GET['customer_name']) ? sanitize_text_field($_GET['customer_name']) : '';
        $customer_email = isset($_GET['customer_email']) ? sanitize_email($_GET['customer_email']) : '';
        $customer_phone = isset($_GET['customer_phone']) ? sanitize_text_field($_GET['customer_phone']) : '';
        
        if (!$amount || (!$booking_id && !$rental_id && !$temp_rental_id)) {
            echo '<div class="waza-checkout-error">';
            echo '<h2>' . esc_html__('Invalid Checkout Session', 'waza-booking') . '</h2>';
            echo '<p>' . esc_html__('No payment information found. Please try booking again.', 'waza-booking') . '</p>';
            echo '<a href="' . esc_url(home_url('/')) . '" class="waza-btn waza-btn-primary">' . esc_html__('Go to Home', 'waza-booking') . '</a>';
            echo '</div>';
            return ob_get_clean();
        }
        
        // Get enabled payment methods
        $razorpay_enabled = \WazaBooking\Admin\SettingsManager::get_setting('razorpay_enabled') === '1';
        $stripe_enabled = \WazaBooking\Admin\SettingsManager::get_setting('stripe_enabled') === '1';
        $phonepe_enabled = \WazaBooking\Admin\SettingsManager::get_setting('phonepe_enabled') === '1';
        
        if (!$razorpay_enabled && !$stripe_enabled && !$phonepe_enabled) {
            echo '<div class="waza-checkout-error">';
            echo '<h2>' . esc_html__('Payment Gateway Not Configured', 'waza-booking') . '</h2>';
            echo '<p>' . esc_html__('No payment gateway is enabled. Please contact the administrator.', 'waza-booking') . '</p>';
            echo '</div>';
            return ob_get_clean();
        }
        
        ?>
        <div class="waza-checkout-container">
            <div class="waza-checkout-header">
                <h1><?php esc_html_e('Complete Your Payment', 'waza-booking'); ?></h1>
            </div>
            
            <div class="waza-checkout-content">
                <div class="waza-order-summary">
                    <h3><?php esc_html_e('Order Summary', 'waza-booking'); ?></h3>
                    <div class="summary-item">
                        <span class="label"><?php esc_html_e('Type:', 'waza-booking'); ?></span>
                        <span class="value"><?php echo esc_html(ucfirst($type)); ?></span>
                    </div>
                    <?php if ($customer_name) : ?>
                    <div class="summary-item">
                        <span class="label"><?php esc_html_e('Customer:', 'waza-booking'); ?></span>
                        <span class="value"><?php echo esc_html($customer_name); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($customer_email) : ?>
                    <div class="summary-item">
                        <span class="label"><?php esc_html_e('Email:', 'waza-booking'); ?></span>
                        <span class="value"><?php echo esc_html($customer_email); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="summary-item total">
                        <span class="label"><?php esc_html_e('Total Amount:', 'waza-booking'); ?></span>
                        <span class="value">₹<?php echo number_format($amount, 2); ?></span>
                    </div>
                </div>
                
                <div class="waza-payment-methods">
                    <h3><?php esc_html_e('Select Payment Method', 'waza-booking'); ?></h3>
                    
                    <?php if ($razorpay_enabled) : ?>
                    <div class="payment-method-card" data-method="razorpay">
                        <div class="payment-icon">💳</div>
                        <div class="payment-info">
                            <h4><?php esc_html_e('Razorpay', 'waza-booking'); ?></h4>
                            <p><?php esc_html_e('Pay with Cards, UPI, Wallets, Net Banking', 'waza-booking'); ?></p>
                        </div>
                        <button class="waza-btn waza-btn-primary pay-btn" data-gateway="razorpay">
                            <?php esc_html_e('Pay Now', 'waza-booking'); ?>
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($stripe_enabled) : ?>
                    <div class="payment-method-card" data-method="stripe">
                        <div class="payment-icon">💳</div>
                        <div class="payment-info">
                            <h4><?php esc_html_e('Stripe', 'waza-booking'); ?></h4>
                            <p><?php esc_html_e('Pay with Credit or Debit Card', 'waza-booking'); ?></p>
                        </div>
                        <button class="waza-btn waza-btn-primary pay-btn" data-gateway="stripe">
                            <?php esc_html_e('Pay Now', 'waza-booking'); ?>
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($phonepe_enabled) : ?>
                    <div class="payment-method-card" data-method="phonepe">
                        <div class="payment-icon">📱</div>
                        <div class="payment-info">
                            <h4><?php esc_html_e('PhonePe', 'waza-booking'); ?></h4>
                            <p><?php esc_html_e('Pay with PhonePe, UPI, Cards, Net Banking', 'waza-booking'); ?></p>
                        </div>
                        <button class="waza-btn waza-btn-primary pay-btn" data-gateway="phonepe">
                            <?php esc_html_e('Pay Now', 'waza-booking'); ?>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="waza-checkout-footer">
                    <p class="secure-notice">
                        🔒 <?php esc_html_e('Your payment is secure and encrypted', 'waza-booking'); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <style>
        .waza-checkout-container { max-width: 800px; margin: 40px auto; padding: 20px; }
        .waza-checkout-header { text-align: center; margin-bottom: 40px; }
        .waza-checkout-header h1 { font-size: 32px; color: #333; margin: 0; }
        .waza-checkout-content { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); padding: 40px; }
        .waza-order-summary { background: #f8f9fa; padding: 25px; border-radius: 8px; margin-bottom: 30px; }
        .waza-order-summary h3 { margin-top: 0; color: #333; font-size: 20px; }
        .summary-item { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #ddd; }
        .summary-item:last-child { border-bottom: none; }
        .summary-item.total { font-size: 24px; font-weight: 700; color: #2271b1; margin-top: 15px; padding-top: 15px; border-top: 2px solid #2271b1; }
        .waza-payment-methods h3 { margin-bottom: 20px; color: #333; font-size: 20px; }
        .payment-method-card { display: flex; align-items: center; gap: 20px; padding: 20px; border: 2px solid #ddd; border-radius: 8px; margin-bottom: 15px; transition: all 0.3s; }
        .payment-method-card:hover { border-color: #2271b1; box-shadow: 0 4px 15px rgba(34, 113, 177, 0.2); }
        .payment-icon { font-size: 36px; }
        .payment-info { flex: 1; }
        .payment-info h4 { margin: 0 0 5px 0; font-size: 18px; color: #333; }
        .payment-info p { margin: 0; color: #666; font-size: 14px; }
        .waza-btn { padding: 12px 30px; border: none; border-radius: 6px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .waza-btn-primary { background: #2271b1; color: white; }
        .waza-btn-primary:hover { background: #135e96; }
        .waza-checkout-footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; }
        .secure-notice { color: #666; font-size: 14px; }
        .waza-checkout-error { text-align: center; padding: 60px 20px; }
        .waza-checkout-error h2 { color: #d63638; margin-bottom: 15px; }
        </style>
        
        <!-- Razorpay Checkout Script -->
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
        
        <script>
        jQuery(document).ready(function($) {
            const paymentData = {
                booking_id: <?php echo intval($booking_id); ?>,
                rental_id: <?php echo intval($rental_id); ?>,
                temp_rental_id: '<?php echo esc_js($temp_rental_id); ?>',
                amount: <?php echo floatval($amount); ?>,
                type: '<?php echo esc_js($type); ?>',
                customer_name: '<?php echo esc_js($customer_name); ?>',
                customer_email: '<?php echo esc_js($customer_email); ?>',
                customer_phone: '<?php echo esc_js($customer_phone); ?>'
            };
            
            $('.pay-btn').on('click', function() {
                const gateway = $(this).data('gateway');
                initiatePayment(gateway, paymentData);
            });
            
            function initiatePayment(gateway, data) {
                // Create payment order via AJAX
                $.ajax({
                    url: waza_frontend.ajax_url,
                    type: 'POST',
                    dataType: 'json', // Force JSON parsing, ignoring HTML warnings
                    data: {
                        action: 'waza_create_payment_order',
                        nonce: '<?php echo wp_create_nonce('waza_payment_nonce'); ?>',
                        gateway: gateway,
                        amount: data.amount,
                        booking_id: data.booking_id,
                        rental_id: data.rental_id,
                        temp_rental_id: data.temp_rental_id,
                        type: data.type,
                        customer_name: data.customer_name,
                        customer_email: data.customer_email,
                        customer_phone: data.customer_phone
                    },
                    success: function(response) {
                        console.log('Payment order response:', response);
                        if (response.success) {
                            if (gateway === 'razorpay') {
                                openRazorpay(response.data);
                            } else if (gateway === 'stripe') {
                                openStripe(response.data);
                            } else if (gateway === 'phonepe') {
                                window.location.href = response.data.redirect_url;
                            }
                        } else {
                            alert('Payment failed: ' + (response.data.message || 'Unknown error'));
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Payment AJAX error:', status, error);
                        console.error('Response text:', xhr.responseText);
                        alert('Payment request failed: ' + error + '. Please try again.');
                    }
                });
            }
            
            function openRazorpay(orderData) {
                console.log('Opening Razorpay with:', orderData);
                
                // Validate Razorpay object exists
                if (typeof Razorpay === 'undefined') {
                    console.error('Razorpay SDK not loaded');
                    alert('Payment system not loaded. Please refresh the page and try again.');
                    return;
                }
                
                var options = {
                    key: orderData.key,
                    amount: orderData.amount,
                    currency: orderData.currency,
                    name: orderData.name,
                    description: orderData.description,
                    order_id: orderData.order_id,
                    handler: function(response) {
                        console.log('Razorpay payment response:', response);
                        
                        // Payment successful - verify on backend
                        $.ajax({
                            url: waza_frontend.ajax_url,
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'waza_verify_payment',
                                nonce: '<?php echo wp_create_nonce('waza_payment_nonce'); ?>',
                                gateway: 'razorpay',
                                payment_data: {
                                    razorpay_payment_id: response.razorpay_payment_id,
                                    razorpay_order_id: response.razorpay_order_id,
                                    razorpay_signature: response.razorpay_signature
                                },
                                booking_type: orderData.booking_type || 'activity',
                                booking_id: orderData.booking_id || 0,
                                temp_rental_id: orderData.temp_rental_id || ''
                            },
                            success: function(verifyResponse) {
                                console.log('Verification response:', verifyResponse);
                                if (verifyResponse.success) {
                                    // Redirect to confirmation page without alert
                                    window.location.href = verifyResponse.data.redirect_url || '<?php echo home_url('/booking-confirmation/'); ?>';
                                } else {
                                    alert('Payment verification failed: ' + (verifyResponse.data.message || 'Unknown error'));
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('AJAX Error:', status, error);
                                console.error('Response:', xhr.responseText);
                                alert('Payment verification request failed. Error: ' + error + '. Please contact support with payment ID: ' + response.razorpay_payment_id);
                            }
                        });
                    },
                    prefill: {
                        name: paymentData.customer_name,
                        email: paymentData.customer_email,
                        contact: paymentData.customer_phone
                    },
                    theme: {
                        color: '#2271b1'
                    },
                    modal: {
                        ondismiss: function() {
                            console.log('Razorpay checkout closed');
                        }
                    }
                };
                
                console.log('Creating Razorpay instance with options:', options);
                
                try {
                    var rzp = new Razorpay(options);
                    console.log('Razorpay instance created, opening modal...');
                    rzp.open();
                } catch (error) {
                    console.error('Error creating Razorpay instance:', error);
                    alert('Failed to open payment modal: ' + error.message);
                }
            }
            
            function openStripe(sessionData) {
                // Stripe integration would go here
                console.log('Opening Stripe with:', sessionData);
                alert('Stripe payment integration - implement in payment.js');
            }
        });
        </script>
        <?php
        
        return ob_get_clean();
    }
}
