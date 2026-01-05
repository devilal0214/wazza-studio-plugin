/**
 * Waza Booking Payment Handler
 * 
 * Handles Razorpay and Stripe payment integration for booking forms
 */

(function($) {
    'use strict';
    
    var WazaPayment = {
        
        /**
         * Initialize payment functionality
         */
        init: function() {
            this.bindEvents();
            this.initializeGateways();
        },
        
        /**
         * Bind payment events
         */
        bindEvents: function() {
            $(document).on('click', '.waza-pay-button', this.handlePaymentClick);
            $(document).on('submit', '.waza-booking-form', this.handleBookingSubmit);
            $(document).on('change', 'input[name="payment_gateway"]', this.handleGatewayChange);
        },
        
        /**
         * Initialize payment gateways
         */
        initializeGateways: function() {
            // Initialize Stripe if available
            if (typeof Stripe !== 'undefined' && wazaPayment.stripe_public_key) {
                this.stripe = Stripe(wazaPayment.stripe_public_key);
                this.stripeElements = this.stripe.elements();
                this.setupStripeElements();
            }
            
            // Razorpay is initialized on demand
        },
        
        /**
         * Setup Stripe Elements
         */
        setupStripeElements: function() {
            var style = {
                base: {
                    color: '#32325d',
                    fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                    fontSmoothing: 'antialiased',
                    fontSize: '16px',
                    '::placeholder': {
                        color: '#aab7c4'
                    }
                },
                invalid: {
                    color: '#fa755a',
                    iconColor: '#fa755a'
                }
            };
            
            if ($('#stripe-card-element').length) {
                this.stripeCard = this.stripeElements.create('card', {style: style});
                this.stripeCard.mount('#stripe-card-element');
                
                // Handle real-time validation errors from the card Element
                this.stripeCard.on('change', function(event) {
                    var displayError = document.getElementById('stripe-card-errors');
                    if (event.error) {
                        displayError.textContent = event.error.message;
                    } else {
                        displayError.textContent = '';
                    }
                });
            }
        },
        
        /**
         * Handle payment gateway change
         */
        handleGatewayChange: function() {
            var gateway = $('input[name="payment_gateway"]:checked').val();
            
            $('.payment-method-details').hide();
            $('.payment-method-details[data-gateway="' + gateway + '"]').show();
        },
        
        /**
         * Handle booking form submission
         */
        handleBookingSubmit: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitButton = $form.find('.waza-submit-booking');
            
            // Validate form
            if (!WazaPayment.validateBookingForm($form)) {
                return false;
            }
            
            // Show loading state
            $submitButton.prop('disabled', true).text(wazaPayment.strings.processing);
            
            // Create booking first
            WazaPayment.createBooking($form);
        },
        
        /**
         * Handle direct payment button click
         */
        handlePaymentClick: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var bookingId = $button.data('booking-id');
            var amount = $button.data('amount');
            var gateway = $button.data('gateway');
            
            WazaPayment.processPayment(bookingId, amount, gateway, $button);
        },
        
        /**
         * Validate booking form
         */
        validateBookingForm: function($form) {
            var isValid = true;
            
            // Clear previous errors
            $form.find('.field-error').remove();
            $form.find('.error').removeClass('error');
            
            // Check required fields
            $form.find('input[required], select[required], textarea[required]').each(function() {
                var $field = $(this);
                var value = $field.val().trim();
                
                if (!value) {
                    WazaPayment.showFieldError($field, 'This field is required');
                    isValid = false;
                }
            });
            
            // Validate email
            var $email = $form.find('input[type="email"]');
            if ($email.length && $email.val()) {
                var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test($email.val())) {
                    WazaPayment.showFieldError($email, 'Please enter a valid email address');
                    isValid = false;
                }
            }
            
            // Validate phone number
            var $phone = $form.find('input[name="user_phone"]');
            if ($phone.length && $phone.val()) {
                var phonePattern = /^[\+]?[1-9][\d]{0,15}$/;
                if (!phonePattern.test($phone.val().replace(/\s/g, ''))) {
                    WazaPayment.showFieldError($phone, 'Please enter a valid phone number');
                    isValid = false;
                }
            }
            
            // Check payment gateway selection
            if (!$form.find('input[name="payment_gateway"]:checked').length) {
                WazaPayment.showFormError($form, 'Please select a payment method');
                isValid = false;
            }
            
            return isValid;
        },
        
        /**
         * Show field error
         */
        showFieldError: function($field, message) {
            $field.addClass('error');
            $field.after('<div class="field-error" style="color: #dc3232; font-size: 12px; margin-top: 5px;">' + message + '</div>');
        },
        
        /**
         * Show form error
         */
        showFormError: function($form, message) {
            var $errorDiv = $form.find('.form-errors');
            if (!$errorDiv.length) {
                $errorDiv = $('<div class="form-errors" style="color: #dc3232; margin-bottom: 15px; padding: 10px; border: 1px solid #dc3232; border-radius: 4px; background: #ffeaea;"></div>');
                $form.prepend($errorDiv);
            }
            $errorDiv.html(message).show();
        },
        
        /**
         * Create booking via AJAX
         */
        createBooking: function($form) {
            var formData = new FormData($form[0]);
            formData.append('action', 'waza_create_booking');
            formData.append('nonce', wazaPayment.nonce);
            
            $.ajax({
                url: wazaPayment.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        var booking = response.data;
                        var gateway = $form.find('input[name="payment_gateway"]:checked').val();
                        
                        // Proceed to payment
                        WazaPayment.processPayment(booking.id, booking.total_amount, gateway, $form.find('.waza-submit-booking'));
                    } else {
                        WazaPayment.showFormError($form, response.data.message || wazaPayment.strings.error);
                        $form.find('.waza-submit-booking').prop('disabled', false).text('Book Now');
                    }
                },
                error: function() {
                    WazaPayment.showFormError($form, wazaPayment.strings.error);
                    $form.find('.waza-submit-booking').prop('disabled', false).text('Book Now');
                }
            });
        },
        
        /**
         * Process payment
         */
        processPayment: function(bookingId, amount, gateway, $button) {
            // Create payment order
            $.ajax({
                url: wazaPayment.ajax_url,
                type: 'POST',
                data: {
                    action: 'waza_create_payment_order',
                    nonce: wazaPayment.nonce,
                    booking_id: bookingId,
                    amount: amount,
                    gateway: gateway
                },
                success: function(response) {
                    if (response.success) {
                        if (gateway === 'razorpay') {
                            WazaPayment.processRazorpayPayment(response.data, bookingId, $button);
                        } else if (gateway === 'stripe') {
                            WazaPayment.processStripePayment(response.data, bookingId, $button);
                        }
                    } else {
                        WazaPayment.handlePaymentError(response.data.message || wazaPayment.strings.error, $button);
                    }
                },
                error: function() {
                    WazaPayment.handlePaymentError(wazaPayment.strings.error, $button);
                }
            });
        },
        
        /**
         * Process Razorpay payment
         */
        processRazorpayPayment: function(orderData, bookingId, $button) {
            if (typeof Razorpay === 'undefined') {
                WazaPayment.handlePaymentError('Razorpay library not loaded', $button);
                return;
            }
            
            var options = {
                "key": orderData.key,
                "amount": orderData.amount,
                "currency": orderData.currency,
                "name": orderData.name,
                "description": orderData.description,
                "order_id": orderData.order_id,
                "handler": function(response) {
                    WazaPayment.verifyPayment('razorpay', response, bookingId, $button);
                },
                "prefill": {
                    "name": $('#user_name').val() || '',
                    "email": $('#user_email').val() || '',
                    "contact": $('#user_phone').val() || ''
                },
                "theme": {
                    "color": "#3399cc"
                },
                "modal": {
                    "ondismiss": function() {
                        WazaPayment.resetButton($button);
                    }
                }
            };
            
            var rzp = new Razorpay(options);
            rzp.open();
        },
        
        /**
         * Process Stripe payment
         */
        processStripePayment: function(intentData, bookingId, $button) {
            if (!WazaPayment.stripe) {
                WazaPayment.handlePaymentError('Stripe library not loaded', $button);
                return;
            }
            
            WazaPayment.stripe.confirmCardPayment(intentData.client_secret, {
                payment_method: {
                    card: WazaPayment.stripeCard,
                    billing_details: {
                        name: $('#user_name').val() || '',
                        email: $('#user_email').val() || '',
                        phone: $('#user_phone').val() || ''
                    }
                }
            }).then(function(result) {
                if (result.error) {
                    WazaPayment.handlePaymentError(result.error.message, $button);
                } else {
                    WazaPayment.verifyPayment('stripe', {
                        payment_intent_id: result.paymentIntent.id
                    }, bookingId, $button);
                }
            });
        },
        
        /**
         * Verify payment on server
         */
        verifyPayment: function(gateway, paymentData, bookingId, $button) {
            $.ajax({
                url: wazaPayment.ajax_url,
                type: 'POST',
                data: {
                    action: 'waza_verify_payment',
                    nonce: wazaPayment.nonce,
                    gateway: gateway,
                    payment_data: paymentData,
                    booking_id: bookingId
                },
                success: function(response) {
                    if (response.success) {
                        WazaPayment.handlePaymentSuccess(bookingId, $button);
                    } else {
                        WazaPayment.handlePaymentError(response.data.message || wazaPayment.strings.error, $button);
                    }
                },
                error: function() {
                    WazaPayment.handlePaymentError(wazaPayment.strings.error, $button);
                }
            });
        },
        
        /**
         * Handle payment success
         */
        handlePaymentSuccess: function(bookingId, $button) {
            // Show success message
            $button.removeClass('button-primary').addClass('button-disabled')
                   .prop('disabled', true)
                   .html('<span class="dashicons dashicons-yes" style="color: #46b450;"></span> ' + wazaPayment.strings.success);
            
            // Redirect to confirmation page if available
            var confirmationUrl = $('.waza-booking-form').data('confirmation-url');
            if (confirmationUrl) {
                setTimeout(function() {
                    window.location.href = confirmationUrl + '?booking_id=' + bookingId;
                }, 2000);
            }
            
            // Trigger success event
            $(document).trigger('waza_payment_success', [bookingId]);
        },
        
        /**
         * Handle payment error
         */
        handlePaymentError: function(message, $button) {
            alert(message);
            WazaPayment.resetButton($button);
            
            // Trigger error event
            $(document).trigger('waza_payment_error', [message]);
        },
        
        /**
         * Reset button to original state
         */
        resetButton: function($button) {
            var originalText = $button.data('original-text') || 'Pay Now';
            $button.prop('disabled', false).text(originalText);
        },
        
        /**
         * Format currency amount
         */
        formatCurrency: function(amount) {
            var currency = wazaPayment.currency;
            var symbols = {
                'INR': '₹',
                'USD': '$',
                'EUR': '€',
                'GBP': '£'
            };
            
            var symbol = symbols[currency] || currency;
            return symbol + parseFloat(amount).toFixed(2);
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        WazaPayment.init();
    });
    
    // Expose to global scope for external access
    window.WazaPayment = WazaPayment;
    
})(jQuery);