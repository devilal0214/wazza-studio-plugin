/**
 * Waza Booking - Frontend JavaScript
 */
(function ($) {
    'use strict';

    // Define utilities first (used throughout the code)
    window.WazaUtils = {
        isValidEmail: function(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },
        isValidPhone: function(phone) {
            const phoneRegex = /^[+]?[\d\s\-\(\)]{10,}$/;
            return phoneRegex.test(phone);
        },
        formatPrice: function (amount) {
            return '₹' + parseFloat(amount).toFixed(2);
        },
        formatDate: function (dateStr) {
            return new Date(dateStr).toLocaleDateString('en-IN', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        },
        formatTime: function (timeStr) {
            return new Date('1970-01-01T' + timeStr).toLocaleTimeString('en-IN', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        }
    };

    // Global variables
    let selectedSlot = null;
    let currentMonth = new Date();
    let bookingInProgress = false;

    // Initialize when document is ready
    $(document).ready(function () {
        initWazaBooking();
    });

    /**
     * Initialize all booking functionality
     */
    function initWazaBooking() {
        initCalendar();
        initSlotSelection();
        initBookingForm();
        initPaymentMethods();
        initModals();
        initActivityFilters();
        initMyBookings();
        setupAjax();
    }

    /**
     * Setup AJAX defaults
     */
    function setupAjax() {
        $.ajaxSetup({
            beforeSend: function (xhr) {
                if (typeof waza_frontend !== 'undefined' && waza_frontend.nonce) {
                    xhr.setRequestHeader('X-WP-Nonce', waza_frontend.nonce);
                }
            }
        });

        // Global AJAX error handler
        $(document).ajaxError(function (event, xhr, settings) {
            hideLoading();

            if (xhr.status === 401) {
                showAlert('Session expired. Please refresh the page.', 'error');
            } else if (xhr.status >= 500) {
                showAlert('Server error. Please try again later.', 'error');
            }
        });
    }

    /**
     * Initialize calendar functionality
     */
    function initCalendar() {
        // Check if calendar exists on page
        if ($('.waza-calendar-grid').length === 0) {
            return;
        }

        // Month navigation
        $(document).on('click', '.waza-nav-button.prev', function (e) {
            e.preventDefault();
            currentMonth.setMonth(currentMonth.getMonth() - 1);
            loadCalendarMonth();
        });

        $(document).on('click', '.waza-nav-button.next', function (e) {
            e.preventDefault();
            currentMonth.setMonth(currentMonth.getMonth() + 1);
            loadCalendarMonth();
        });

        // View switcher
        $(document).on('change', '.waza-calendar-view', function () {
            const view = $(this).val();
            // TODO: Implement week and day views
            if (view === 'month') {
                loadCalendarMonth();
            } else {
                showAlert('Week and Day views coming soon!', 'info');
            }
        });

        // Day selection
        $(document).on('click', '.waza-calendar-day:not(.other-month):not(.disabled)', function (e) {
            // If clicking on a slot directly, let the slot handler handle it
            if ($(e.target).closest('.waza-slot-indicator').length) {
                return;
            }

            const date = $(this).data('date');

            // Highlight
            selectCalendarDay(date);

            // Store selected date globally
            selectedDate = date;

            // Open booking modal with slots as step 1
            openBookingModalWithSlots(date);
        });

        // Load current month on init
        setTimeout(function() {
            loadCalendarMonth();
        }, 100);
    }

    /**
     * Initialize slot selection
     */
    function initSlotSelection() {
        // Update to use button click and load form steps
        $(document).on('click', '.waza-select-slot', function (e) {
            e.stopPropagation();
            const slotId = $(this).data('slot-id');
            
            // Store selected slot
            selectedSlot = slotId;
            
            // Show loader and load form steps
            showLoading('Loading booking form...');
            
            // Load booking form steps (2-5)
            $.ajax({
                url: getAjaxUrl(),
                type: 'POST',
                data: {
                    action: 'waza_load_booking_form',
                    slot_id: slotId,
                    nonce: getNonce()
                },
                success: function (response) {
                    hideLoading();
                    
                    if (response.success) {
                        // Replace step 1 with steps 2-5
                        $('.waza-step-section[data-step="1"]').removeClass('active').hide();
                        $('#waza-booking-steps-container').html(response.data.form);
                        
                        // Update progress bar
                        $('.waza-progress-bar-segment[data-step="1"]').addClass('completed').removeClass('active');
                        $('.waza-progress-bar-segment[data-step="2"]').addClass('active');
                        
                        // Initialize form components
                        updateBookingTotal();
                    } else {
                        showAlert(response.data || 'Failed to load booking form.', 'error');
                    }
                },
                error: function () {
                    hideLoading();
                    showAlert('Error loading booking form. Please try again.', 'error');
                }
            });
        });

        $(document).on('click', '.waza-slot-indicator', function (e) {
            e.stopPropagation();
            if ($(this).closest('.waza-calendar-day').hasClass('disabled')) {
                return;
            }
            const date = $(this).closest('.waza-calendar-day').data('date');

            selectCalendarDay(date);
            
            // Open booking modal with all slots for this date
            openBookingModalWithSlots(date);
        });
    }

    /**
     * Initialize booking form
     */
    function initBookingForm() {
        // Use event delegation for dynamically loaded forms
        $(document).on('submit', '#waza-booking-form', function (e) {
            e.preventDefault();

            if (bookingInProgress) return;

            if (validateBookingForm()) {
                processBooking();
            }
        });

        // Step navigation: Next button
        $(document).on('click', '.waza-next-step-btn', function () {
            const currentStep = parseInt($('input[name="current_step"]').val());
            const nextStep = currentStep + 1;
            const totalSteps = 5; // Fixed: 5 steps total
            
            // Validate current step before proceeding
            if (currentStep === 3 && !validateStep3()) {
                return; // Step 3: User Info validation
            }
            
            if (nextStep <= totalSteps) {
                showBookingStep(nextStep);
            }
        });

        // Step navigation: Previous button
        $(document).on('click', '.waza-prev-step-btn', function () {
            const currentStep = parseInt($('input[name="current_step"]').val());
            const prevStep = currentStep - 1;
            
            // Allow going back to step 1 (slots) from step 2
            if (prevStep === 1) {
                // Show step 1 (slots) again
                $('.waza-step-section[data-step="1"]').addClass('active').show();
                $('#waza-booking-steps-container').hide();
                
                // Update progress
                $('.waza-progress-bar-segment, .waza-progress-step').removeClass('active completed');
                $('.waza-progress-bar-segment[data-step="1"], .waza-progress-step[data-step="1"]').addClass('active');
                
                // Update buttons
                $('.waza-prev-step-btn, .waza-next-step-btn, .waza-submit-booking').hide();
                
                $('input[name="current_step"]').val(1);
            } else if (prevStep >= 2) {
                showBookingStep(prevStep);
            }
        });

        // Update total when quantity changes
        $(document).on('change', '.waza-quantity-input', function () {
            const quantity = parseInt($(this).val()) || 1;
            const slotId = $('input[name="slot_id"]').val();
            
            // Update displayed total if price is shown
            if ($('.waza-slot-price').length) {
                const basePrice = parseFloat($('.waza-slot-price').data('price')) || 0;
                const total = quantity * basePrice;
                $('.waza-total-display').html('<strong>Total: </strong>₹' + total.toFixed(2));
            }
        });

        // Apply discount code
        $(document).on('click', '#waza-apply-discount', function () {
            applyDiscountCode();
        });

        // Account creation toggle
        $(document).on('change', '#create-account', function () {
            if ($(this).is(':checked')) {
                $('.waza-password-options').slideDown();
            } else {
                $('.waza-password-options').slideUp();
                $('.waza-manual-password').slideUp();
            }
        });
        
        // Password option toggle
        $(document).on('change', 'input[name=\"password_option\"]', function () {
            if ($(this).val() === 'manual') {
                $('.waza-manual-password').slideDown();
                $('#customer_password').attr('required', true);
            } else {
                $('.waza-manual-password').slideUp();
                $('#customer_password').attr('required', false);
            }
        });
    }

    /**
     * Initialize payment methods
     */
    function initPaymentMethods() {
        $(document).on('click', '.waza-payment-method', function () {
            $('.waza-payment-method').removeClass('selected');
            $(this).addClass('selected');

            const method = $(this).data('method');
            updatePaymentMethod(method);
        });
    }

    /**
     * Initialize modals
     */
    function initModals() {
        // Close modal when clicking outside or on close button
        $(document).on('click', '.waza-modal', function (e) {
            if (e.target === this) {
                closeModal();
            }
        });

        $(document).on('click', '.waza-close', function () {
            closeModal();
        });

        // ESC key to close modal
        $(document).keyup(function (e) {
            if (e.keyCode === 27) {
                closeModal();
            }
        });
    }

    /**
     * Initialize activity filters
     */
    function initActivityFilters() {
        $('#waza-activity-filter').on('change', function () {
            const category = $(this).val();
            filterActivities(category);
        });

        $('#waza-instructor-filter').on('change', function () {
            const instructor = $(this).val();
            filterByInstructor(instructor);
        });

        $('.waza-search-activities').on('input', function () {
            const query = $(this).val();
            searchActivities(query);
        });
    }

    /**
     * Load calendar month
     */
    function loadCalendarMonth() {
        const $calendar = $('.waza-calendar-grid');
        if ($calendar.length === 0) {
            console.warn('Waza: Calendar grid not found');
            return;
        }

        showLoading('Loading calendar...');

        const year = currentMonth.getFullYear();
        const month = currentMonth.getMonth() + 1;
        const activityId = getCurrentActivityId();

        $.ajax({
            url: getAjaxUrl(),
            type: 'POST',
            data: {
                action: 'waza_load_calendar',
                year: year,
                month: month,
                activity_id: activityId,
                nonce: getNonce()
            },
            success: function (response) {
                hideLoading();

                if (response.success) {
                    $calendar.html(response.data.calendar);
                    $('.waza-current-month').text(response.data.month_name);
                } else {
                    const errorMsg = response.data && response.data.message 
                        ? response.data.message 
                        : 'Failed to load calendar.';
                    showAlert(errorMsg, 'error');
                    console.error('Waza calendar error:', response);
                }
            },
            error: function (xhr, status, error) {
                hideLoading();
                showAlert('Error loading calendar. Please try again.', 'error');
                console.error('Waza AJAX error:', {xhr, status, error});
            }
        });
    }

    /**
     * Open booking modal with slots as step 1
     */
    function openBookingModalWithSlots(date) {
        showLoading('Loading available times...');

        $.ajax({
            url: getAjaxUrl(),
            type: 'POST',
            data: {
                action: 'waza_load_day_slots',
                date: date,
                activity_id: getCurrentActivityId(),
                nonce: getNonce()
            },
            success: function (response) {
                hideLoading();

                if (response.success) {
                    showBookingModalStep1(date, response.data.slots);
                } else {
                    showAlert('No available times for this date.', 'info');
                }
            },
            error: function () {
                hideLoading();
                showAlert('Error loading time slots. Please try again.', 'error');
            }
        });
    }

    /**
     * Show booking modal at step 1 (slot selection)
     */
    function showBookingModalStep1(date, slotsHtml) {
        // Remove any existing modal first
        $('#waza-booking-modal').remove();
        
        const dateStr = WazaUtils.formatDate(date);
        const stepLabels = (waza_frontend.appearance && waza_frontend.appearance.step_labels) 
            ? waza_frontend.appearance.step_labels.split(',') 
            : ['Select Time', 'Details', 'Your Info', 'Payment', 'Done'];
        
        const modalHtml = `
            <div id="waza-booking-modal" class="waza-modal">
                <div class="waza-modal-content waza-booking-modal-content">
                    <div class="waza-modal-header">
                        <h3 class="waza-modal-title">Complete Booking - ${dateStr}</h3>
                        <button class="waza-close">&times;</button>
                    </div>
                    <div class="waza-modal-body">
                        <div class="waza-booking-form-wrapper">
                            <!-- Progress Bar -->
                            <div class="waza-progress-bar-container">
                                <div class="waza-progress-bar-segment active" data-step="1">
                                    <div class="waza-progress-bar-label">${stepLabels[0]}</div>
                                    <div class="waza-progress-bar-fill"></div>
                                </div>
                                <div class="waza-progress-bar-segment" data-step="2">
                                    <div class="waza-progress-bar-label">${stepLabels[1]}</div>
                                    <div class="waza-progress-bar-fill"></div>
                                </div>
                                <div class="waza-progress-bar-segment" data-step="3">
                                    <div class="waza-progress-bar-label">${stepLabels[2]}</div>
                                    <div class="waza-progress-bar-fill"></div>
                                </div>
                                <div class="waza-progress-bar-segment" data-step="4">
                                    <div class="waza-progress-bar-label">${stepLabels[3]}</div>
                                    <div class="waza-progress-bar-fill"></div>
                                </div>
                                <div class="waza-progress-bar-segment" data-step="5">
                                    <div class="waza-progress-bar-label">${stepLabels[4]}</div>
                                    <div class="waza-progress-bar-fill"></div>
                                </div>
                            </div>
                            
                            <!-- Step 1: Slot Selection -->
                            <div class="waza-step-section active" data-step="1">
                                <h4>Select a time slot</h4>
                                ${slotsHtml}
                            </div>
                            
                            <!-- Steps 2-5 will be loaded dynamically -->
                            <div id="waza-booking-steps-container"></div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHtml);
        $('#waza-booking-modal').show();
    }

    /**
     * Select calendar day
     */
    function selectCalendarDay(date) {
        $('.waza-calendar-day').removeClass('selected');
        $(`.waza-calendar-day[data-date="${date}"]`).addClass('selected');
    }

    /**
     * Load day slots
     */
    function loadDaySlots(date) {
        showLoading('Loading available times...');

        $.ajax({
            url: getAjaxUrl(),
            type: 'POST',
            data: {
                action: 'waza_load_day_slots',
                date: date,
                activity_id: getCurrentActivityId(),
                nonce: getNonce()
            },
            success: function (response) {
                hideLoading();

                if (response.success) {
                    // Check if we are navigating back from Booking Modal
                    const $bookingModal = $('#waza-booking-modal');

                    if ($bookingModal.length) {
                        // REVERT ID & Content (Strict Flow Backwards)
                        $bookingModal.attr('id', 'waza-slots-modal');
                        $bookingModal.find('.waza-booking-modal-content').removeClass('waza-booking-modal-content');

                        // Smooth transition
                        const $modalContent = $bookingModal.find('.waza-modal-content');
                        $modalContent.css('opacity', '0.5');

                        setTimeout(() => {
                            $bookingModal.find('.waza-modal-body').html(response.data.slots);
                            $bookingModal.find('.waza-modal-title').text(WazaUtils.formatDate(date));
                            $modalContent.css('opacity', '1');
                        }, 200);

                    } else {
                        // Standard Open
                        showSlotsModal(date, response.data.slots);
                    }
                } else {
                    showAlert('No available times for this date.', 'info');
                }
            },
            error: function () {
                hideLoading();
                showAlert('Error loading time slots. Please try again.', 'error');
            }
        });
    }

    /**
     * Show slots in a modal
     */
    function showSlotsModal(date, slotsHtml) {
        const dateStr = WazaUtils.formatDate(date);
        const modalHtml = `
            <div id="waza-slots-modal" class="waza-modal">
                <div class="waza-modal-content">
                    <div class="waza-modal-header">
                        <h3 class="waza-modal-title">${dateStr}</h3>
                        <button class="waza-close">&times;</button>
                    </div>
                    <div class="waza-modal-body">
                        ${slotsHtml}
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHtml);
        $('#waza-slots-modal').show();
    }

    /**
     * Load booking form (DEPRECATED - kept for backward compatibility)
     * Now handled by initSlotSelection() with unified modal
     */
    function loadBookingForm() {
        if (!selectedSlot) return;

        showLoading('Loading booking form...');

        $.ajax({
            url: getAjaxUrl(),
            type: 'POST',
            data: {
                action: 'waza_load_booking_form',
                slot_id: selectedSlot,
                nonce: getNonce()
            },
            success: function (response) {
                hideLoading();

                if (response.success) {
                    // Check if slots modal is open
                    const $slotsModal = $('#waza-slots-modal');

                    if ($slotsModal.length) {
                        // REUSE EXISTING MODAL (Strict Flow)
                        // Transition ID
                        $slotsModal.attr('id', 'waza-booking-modal');
                        $slotsModal.find('.waza-booking-modal-content').addClass('waza-booking-modal-content');

                        // Update Content
                        const $modalContent = $slotsModal.find('.waza-modal-content');

                        // Smooth transition
                        $modalContent.css('opacity', '0.5');
                        setTimeout(() => {
                            $slotsModal.find('.waza-modal-body').html(response.data.form);
                            // Update header title if present, or just keep close button
                            $slotsModal.find('.waza-modal-title').text('Complete Booking');
                            $modalContent.css('opacity', '1');
                            
                            // Mark step 1 as completed (slot selection already done)
                            $('.waza-progress-bar-segment[data-step="1"]').addClass('completed');
                            $('.waza-progress-step[data-step="1"]').addClass('completed');
                            
                            // Set step 2 as active
                            $('.waza-progress-bar-segment[data-step="2"]').addClass('active');
                            $('.waza-progress-step[data-step="2"]').addClass('active');
                            
                            updateBookingTotal();
                        }, 200);

                    } else {
                        // Standard Create (Fallback)
                        $('#waza-booking-modal').remove();

                        const modalHtml = `
                            <div id="waza-booking-modal" class="waza-modal">
                                <div class="waza-modal-content waza-booking-modal-content">
                                    <div class="waza-modal-header">
                                        <h3 class="waza-modal-title">Complete Booking</h3>
                                        <button class="waza-close">&times;</button>
                                    </div>
                                    <div class="waza-modal-body">
                                        ${response.data.form}
                                    </div>
                                </div>
                            </div>
                        `;

                        $('body').append(modalHtml);
                        $('#waza-booking-modal').show();

                        // Mark step 1 as completed
                        $('.waza-progress-bar-segment[data-step="1"]').addClass('completed');
                        $('.waza-progress-step[data-step="1"]').addClass('completed');
                        $('.waza-progress-bar-segment[data-step="2"]').addClass('active');
                        $('.waza-progress-step[data-step="2"]').addClass('active');

                        // Initialize form components
                        updateBookingTotal();
                    }
                } else {
                    showAlert(response.data || 'Failed to load booking form.', 'error');
                }
            },
            error: function () {
                hideLoading();
                showAlert('Error loading booking form. Please try again.', 'error');
            }
        });
    }

    /**
     * Validate booking form
     */
    function validateBookingForm() {
        let isValid = true;
        const form = $('#waza-booking-form');

        // Clear previous errors
        $('.waza-form-error').remove();

        // Check required fields
        form.find('[required]').each(function () {
            const field = $(this);
            const value = field.val().trim();

            if (!value) {
                showFieldError(field, 'This field is required.');
                isValid = false;
            }
        });

        // Validate email
        const email = form.find('input[type="email"]');
        if (email.length && email.val() && !isValidEmail(email.val())) {
            showFieldError(email, 'Please enter a valid email address.');
            isValid = false;
        }

        // Validate phone
        const phone = form.find('input[name="phone"]');
        if (phone.length && phone.val() && !isValidPhone(phone.val())) {
            showFieldError(phone, 'Please enter a valid phone number.');
            isValid = false;
        }

        // Check payment method selection
        if (!$('.waza-payment-method.selected').length) {
            showAlert('Please select a payment method.', 'error');
            isValid = false;
        }

        // Check terms acceptance
        const terms = form.find('#accept-terms');
        if (terms.length && !terms.is(':checked')) {
            showFieldError(terms.closest('.waza-form-group'), 'Please accept the terms and conditions.');
            isValid = false;
        }

        return isValid;
    }

    /**
     * Process booking
     */
    function processBooking() {
        bookingInProgress = true;
        showLoading('Processing booking...');

        const form = $('#waza-booking-form');
        const formData = form.serialize();

        $.ajax({
            url: getAjaxUrl(),
            type: 'POST',
            data: formData + '&action=waza_process_booking&nonce=' + getNonce(),
            success: function (response) {
                bookingInProgress = false;
                hideLoading();

                if (response.success) {
                    // Handle successful booking
                    if (response.data.payment_required) {
                        // Redirect to payment gateway
                        initiatePayment(response.data.payment_data);
                    } else {
                        // Show success message
                        showBookingSuccess(response.data);
                    }
                } else {
                    // Check if waitlisted
                    if (response.data && response.data.waitlisted) {
                        showAlert('Slot is full. ' + response.data.message, 'success');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showAlert(response.data || 'Booking failed. Please try again.', 'error');
                    }
                }
            },
            error: function (xhr) {
                bookingInProgress = false;
                hideLoading();

                // Handle REST API errors
                if (xhr.responseJSON) {
                    const data = xhr.responseJSON;
                    if (data.code === 'slot_full_waitlisted') {
                        showAlert(data.message, 'success');
                        setTimeout(() => location.reload(), 2000);
                        return;
                    }
                    showAlert(data.message || 'Error processing booking.', 'error');
                } else {
                    showAlert('Error processing booking. Please try again.', 'error');
                }
            }
        });
    }

    /**
     * Initiate payment
     */
    function initiatePayment(paymentData) {
        const method = $('.waza-payment-method.selected').data('method');

        if (method === 'phonepe') {
            initiatePhonePePayment(paymentData);
        } else if (method === 'razorpay') {
            initiateRazorpayPayment(paymentData);
        } else if (method === 'stripe') {
            initiateStripePayment(paymentData);
        } else {
            showAlert('Invalid payment method selected.', 'error');
        }
    }

    /**
     * Initiate Razorpay payment
     */
    /**
     * Initiate PhonePe payment
     */
    function initiatePhonePePayment(paymentData) {
        // Since we don't have a real PhonePe integration in this environment,
        // we will simulate the user being redirected to PhonePe and coming back.

        // 1. Show redirection message
        showLoading('Redirecting to PhonePe Secure Payment...');

        // 2. Simulate delay (as if user is paying)
        setTimeout(function () {
            // 3. Simulate success callback
            // In a real scenario, PhonePe would redirect the browser to paymentData.redirectUrl
            // For this test, we will just call the success handler directly.

            const mockResponse = {
                booking_id: paymentData.booking_id,
                transaction_id: paymentData.transactionId,
                status: 'SUCCESS'
            };

            handlePaymentSuccess(mockResponse, 'phonepe');

        }, 2000);
    }

    /**
     * Initiate Razorpay payment
     */
    function initiateRazorpayPayment(paymentData) {
        if (typeof Razorpay === 'undefined') {
            showAlert('Razorpay is not loaded. Please refresh the page.', 'error');
            return;
        }

        const options = {
            key: paymentData.key,
            amount: paymentData.amount,
            currency: paymentData.currency,
            name: paymentData.name,
            description: paymentData.description,
            order_id: paymentData.order_id,
            handler: function (response) {
                handlePaymentSuccess(response, 'razorpay');
            },
            prefill: {
                name: paymentData.customer_name,
                email: paymentData.customer_email,
                contact: paymentData.customer_phone
            },
            theme: {
                color: '#3498db'
            },
            modal: {
                ondismiss: function () {
                    handlePaymentCancel();
                }
            }
        };

        const rzp = new Razorpay(options);
        rzp.open();
    }

    /**
     * Initiate Stripe payment
     */
    function initiateStripePayment(paymentData) {
        if (typeof Stripe === 'undefined') {
            showAlert('Stripe is not loaded. Please refresh the page.', 'error');
            return;
        }

        const stripe = Stripe(paymentData.publishable_key);

        stripe.redirectToCheckout({
            sessionId: paymentData.session_id
        }).then(function (result) {
            if (result.error) {
                showAlert(result.error.message, 'error');
            }
        });
    }

    /**
     * Handle payment success
     */
    function handlePaymentSuccess(response, method) {
        showLoading('Confirming payment...');

        $.ajax({
            url: getAjaxUrl(),
            type: 'POST',
            data: {
                action: 'waza_confirm_payment',
                payment_response: response,
                payment_method: method,
                nonce: getNonce()
            },
            success: function (confirmResponse) {
                hideLoading();

                if (confirmResponse.success) {
                    showBookingSuccess(confirmResponse.data);
                } else {
                    showAlert(confirmResponse.data || 'Payment confirmation failed.', 'error');
                }
            },
            error: function () {
                hideLoading();
                showAlert('Error confirming payment. Please contact support.', 'error');
            }
        });
    }

    /**
     * Handle payment cancellation
     */
    function handlePaymentCancel() {
        showAlert('Payment was cancelled. You can try again.', 'warning');
    }

    /**
     * Show booking success
     */
    function showBookingSuccess(data) {
        // Update step 5 with booking details
        const successHtml = `
            <div class="waza-success-message">
                <div class="waza-success-icon">✓</div>
                <h3>Booking Confirmed!</h3>
                <p>Thank you for your booking. Confirmation email sent to your address.</p>
                <div class="waza-booking-details" style="margin-top: 2rem; text-align: left; background: var(--waza-bg); padding: 1.5rem; border-radius: var(--waza-radius);">
                    <p><strong>Booking ID:</strong> ${data.booking_id}</p>
                    <p><strong>Activity:</strong> ${data.activity_title}</p>
                    <p><strong>Date & Time:</strong> ${data.datetime}</p>
                    ${data.location ? `<p><strong>Location:</strong> ${data.location}</p>` : ''}
                </div>
                ${data.qr_code ? `<div class="waza-qr-code" style="margin-top: 1.5rem;"><img src="${data.qr_code}" alt="QR Code" style="max-width: 200px;"></div>` : ''}
                <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: center;">
                    <button class="waza-btn waza-btn-primary" onclick="location.reload()">Book Another</button>
                    ${data.dashboard_url ? `<a href="${data.dashboard_url}" class="waza-btn waza-btn-secondary">View My Bookings</a>` : ''}
                </div>
            </div>
        `;
        
        // Show step 5
        $('.waza-step-section[data-step="5"]').html(successHtml).show().addClass('active');
        $('.waza-step-section[data-step="4"]').removeClass('active').hide();
        
        // Update progress to step 5
        $('.waza-progress-bar-segment[data-step="4"]').addClass('completed').removeClass('active');
        $('.waza-progress-bar-segment[data-step="5"]').addClass('active');
        
        // Hide all buttons
        $('.waza-form-actions').hide();
    }

    /**
     * Update booking total
     */
    function updateBookingTotal() {
        const quantity = parseInt($('.waza-quantity-input').val()) || 1;
        const basePrice = parseFloat($('#base-price').text()) || 0;
        const discount = parseFloat($('#discount-amount').text()) || 0;

        const subtotal = quantity * basePrice;
        const total = Math.max(0, subtotal - discount);

        $('#subtotal-amount').text(subtotal.toFixed(2));
        $('#total-amount').text(total.toFixed(2));

        // Update payment button text
        $('.waza-payment-button').text(`Pay ₹${total.toFixed(2)}`);
    }

    /**
     * Apply discount code
     */
    function applyDiscountCode() {
        const code = $('#discount-code').val().trim();

        if (!code) {
            showAlert('Please enter a discount code.', 'error');
            return;
        }

        showLoading('Applying discount...');

        $.ajax({
            url: getAjaxUrl(),
            type: 'POST',
            data: {
                action: 'waza_apply_discount',
                code: code,
                slot_id: selectedSlot,
                nonce: getNonce()
            },
            success: function (response) {
                hideLoading();

                if (response.success) {
                    $('#discount-amount').text(response.data.discount_amount);
                    $('.waza-discount-info').html(`
                        <span class="discount-code">${code}</span>
                        <span class="discount-percentage">${response.data.discount_percentage}% off</span>
                    `).show();
                    updateBookingTotal();
                    showAlert('Discount applied successfully!', 'success');
                } else {
                    showAlert(response.data || 'Invalid discount code.', 'error');
                }
            },
            error: function () {
                hideLoading();
                showAlert('Error applying discount. Please try again.', 'error');
            }
        });
    }

    /**
     * Toggle guest checkout fields
     */
    function toggleGuestFields(isGuest) {
        const guestFields = $('.waza-guest-fields');

        if (isGuest) {
            guestFields.show();
        } else {
            guestFields.hide();
        }
    }

    /**
     * Filter activities
     */
    function filterActivities(category) {
        if (category === 'all') {
            $('.waza-activity-card').show();
        } else {
            $('.waza-activity-card').hide();
            $(`.waza-activity-card[data-category="${category}"]`).show();
        }
    }

    /**
     * Filter by instructor
     */
    function filterByInstructor(instructor) {
        if (instructor === 'all') {
            $('.waza-activity-card').show();
        } else {
            $('.waza-activity-card').hide();
            $(`.waza-activity-card[data-instructor="${instructor}"]`).show();
        }
    }

    /**
     * Search activities
     */
    function searchActivities(query) {
        const searchTerm = query.toLowerCase();

        $('.waza-activity-card').each(function () {
            const title = $(this).find('.waza-activity-title').text().toLowerCase();
            const description = $(this).find('.waza-activity-description').text().toLowerCase();
            const instructor = $(this).find('.waza-activity-instructor').text().toLowerCase();

            if (title.includes(searchTerm) || description.includes(searchTerm) || instructor.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }

    /**
 * Initialize My Bookings
 */
    function initMyBookings() {
        if (!$('.waza-my-bookings-container').length) return;

        // Load initial bookings
        loadUserBookings('confirmed');

        // Tab click handler
        $('.waza-tab-btn').on('click', function () {
            $('.waza-tab-btn').removeClass('active');
            $(this).addClass('active');

            const status = $(this).data('status');
            loadUserBookings(status);
        });

        // Cancel booking handler
        $(document).on('click', '.waza-cancel-booking', function () {
            const bookingId = $(this).data('booking-id');
            if (confirm(waza_frontend.strings.confirm_cancel)) {
                cancelBooking(bookingId);
            }
        });
    }

    /**
     * Load user bookings
     */
    function loadUserBookings(status) {
        $('#waza-user-bookings-list').html('<div class="waza-loading-spinner">Loading...</div>');

        $.ajax({
            url: getApiUrl() + 'bookings',
            type: 'GET',
            data: {
                status: status,
                timestamp: new Date().getTime()
            },
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', waza_frontend.nonce);
            },
            success: function (response) {
                if (response.success && response.data.length > 0) {
                    renderBookingsList(response.data, status);
                } else {
                    $('#waza-user-bookings-list').html('<p class="waza-no-data">No bookings found.</p>');
                }
            },
            error: function () {
                $('#waza-user-bookings-list').html('<p class="waza-error">Failed to load bookings.</p>');
            }
        });
    }

    /**
     * Render bookings list
     */
    function renderBookingsList(bookings, status) {
        let html = '<div class="waza-bookings-grid">';

        bookings.forEach(function (booking) {
            const date = WazaUtils.formatDate(booking.slot_date);
            const canCancel = booking.booking_status !== 'cancelled' && status === 'confirmed';

            html += `
                <div class="waza-booking-card ${booking.booking_status}">
                    <div class="waza-booking-header">
                        <span class="waza-booking-id">#${booking.id}</span>
                        <span class="waza-booking-status ${booking.booking_status}">${booking.booking_status}</span>
                    </div>
                    <div class="waza-booking-body">
                        <h4>${booking.slot_title}</h4>
                        <p><i class="dashicons dashicons-calendar"></i> ${date} at ${booking.slot_time}</p>
                        <p><i class="dashicons dashicons-money"></i> ₹${booking.total_amount}</p>
                    </div>
                    ${canCancel ? `
                    <div class="waza-booking-footer">
                        <button class="waza-btn waza-btn-sm waza-btn-danger waza-cancel-booking" data-booking-id="${booking.id}">
                            Cancel
                        </button>
                    </div>
                    ` : ''}
                </div>
            `;
        });

        html += '</div>';
        $('#waza-user-bookings-list').html(html);
    }

    /**
     * Cancel booking
     */
    function cancelBooking(bookingId) {
        showLoading('Cancelling booking...');

        $.ajax({
            url: getApiUrl() + 'bookings/' + bookingId + '/cancel',
            type: 'POST',
            data: {
                reason: 'User cancelled via My Bookings'
            },
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', waza_frontend.nonce);
            },
            success: function (response) {
                hideLoading();
                showAlert('Booking cancelled successfully', 'success');
                loadUserBookings('confirmed'); // Refresh list
            },
            error: function (xhr) {
                hideLoading();
                const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to cancel booking';
                showAlert(msg, 'error');
            }
        });
    }

    /**
         * Utility functions
         */
    function showFieldError(field, message) {
        field.after(`<span class="waza-form-error">${message}</span>`);
        field.addClass('error');
    }

    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function isValidPhone(phone) {
        const phoneRegex = /^[+]?[\d\s\-\(\)]{10,}$/;
        return phoneRegex.test(phone);
    }

    /**
     * Show specific booking step
     */
    function showBookingStep(stepNumber) {
        // Get settings
        const buttonNext = waza_frontend.appearance.button_next || 'NEXT';
        const buttonBack = waza_frontend.appearance.button_back || 'BACK';
        const progressStyle = waza_frontend.appearance.progress_style || 'bar';
        const totalSteps = 5; // Fixed: Select Time, Details, Your Info, Payment, Done
        
        // Update hidden field
        $('input[name="current_step"]').val(stepNumber);
        
        // Update step sections
        $('.waza-step-section').removeClass('active');
        $(`.waza-step-section[data-step="${stepNumber}"]`).addClass('active');
        
        // If showing step 4 (payment), update the review totals
        if (stepNumber === 4) {
            const quantity = parseInt($('#booking_quantity').val()) || 1;
            const basePrice = parseFloat($('.waza-slot-price').data('price')) || 0;
            const total = quantity * basePrice;
            
            $('.waza-review-quantity').text(quantity);
            $('.waza-review-total-amount').text('₹' + total.toFixed(2));
        }
        
        // Update progress indicator based on style
        if (progressStyle === 'bar') {
            $('.waza-progress-bar-segment').removeClass('active');
            $(`.waza-progress-bar-segment[data-step="${stepNumber}"]`).addClass('active');
            $('.waza-progress-bar-segment').each(function() {
                const step = parseInt($(this).data('step'));
                if (step < stepNumber) {
                    $(this).addClass('completed');
                } else {
                    $(this).removeClass('completed');
                }
            });
        } else {
            $('.waza-progress-step').removeClass('active');
            $(`.waza-progress-step[data-step="${stepNumber}"]`).addClass('active');
            $('.waza-progress-step').each(function() {
                const step = parseInt($(this).data('step'));
                if (step < stepNumber) {
                    $(this).addClass('completed');
                } else {
                    $(this).removeClass('completed');
                }
            });
        }
        
        // Update button visibility and text based on 5-step flow
        if (stepNumber === 1) {
            // Step 1: Slot Selection (this is handled separately, but keep for reference)
            $('.waza-prev-step-btn').hide();
            $('.waza-back-to-slots-btn').hide();
            $('.waza-next-step-btn').hide();
            $('.waza-submit-booking').hide();
        } else if (stepNumber === 2) {
            // Step 2: Slot Details Confirmation
            $('.waza-prev-step-btn').show();
            $('.waza-back-to-slots-btn').hide();
            $('.waza-next-step-btn').show().text(buttonNext);
            $('.waza-submit-booking').hide();
        } else if (stepNumber === 3) {
            // Step 3: User Information (Name, Email, Phone)
            $('.waza-prev-step-btn').show();
            $('.waza-back-to-slots-btn').hide();
            $('.waza-next-step-btn').show().text(buttonNext);
            $('.waza-submit-booking').hide();
        } else if (stepNumber === 4) {
            // Step 4: Payment
            $('.waza-prev-step-btn').show();
            $('.waza-back-to-slots-btn').hide();
            $('.waza-next-step-btn').hide();
            $('.waza-submit-booking').show();
        } else if (stepNumber === 5) {
            // Step 5: Success/Done
            $('.waza-prev-step-btn').hide();
            $('.waza-back-to-slots-btn').hide();
            $('.waza-next-step-btn').hide();
            $('.waza-submit-booking').hide();
        }
        
        // Smooth scroll to top of modal content
        const $modalBody = $('.waza-modal-body');
        if ($modalBody.length) {
            $modalBody.animate({ scrollTop: 0 }, 300);
        }
    }
    
    /**
     * Validate step 3 (user details)
     */
    function validateStep3() {
        let isValid = true;
        
        // Clear previous errors
        $('.waza-step-section[data-step="3"] .waza-form-error').remove();
        $('.waza-step-section[data-step="3"] .error').removeClass('error');
        
        // Validate name
        const name = $('#customer_name');
        if (!name.val().trim()) {
            showFieldError(name, 'Name is required');
            isValid = false;
        }
        
        // Validate phone
        const phone = $('#customer_phone');
        if (!phone.val().trim()) {
            showFieldError(phone, 'Phone number is required');
            isValid = false;
        } else if (!isValidPhone(phone.val())) {
            showFieldError(phone, 'Please enter a valid phone number');
            isValid = false;
        }
        
        // Validate email
        const email = $('#customer_email');
        if (!email.val().trim()) {
            showFieldError(email, 'Email is required');
            isValid = false;
        } else if (!isValidEmail(email.val())) {
            showFieldError(email, 'Please enter a valid email address');
            isValid = false;
        }
        
        // Validate terms if shown
        const termsCheckbox = $('#accept_terms');
        if (termsCheckbox.length && !termsCheckbox.is(':checked')) {
            showFieldError(termsCheckbox.parent(), 'You must agree to the terms of service');
            isValid = false;
        }
        
        return isValid;
    }

    function formatDate(dateString) {
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', options);
    }

    function getCurrentActivityId() {
        return $('[data-activity-id]').first().data('activity-id') || '';
    }

    function getAjaxUrl() {
        return (typeof waza_frontend !== 'undefined' && waza_frontend.ajax_url)
            ? waza_frontend.ajax_url
            : '/wp-admin/admin-ajax.php';
    }

    function getNonce() {
        return (typeof waza_frontend !== 'undefined' && waza_frontend.nonce)
            ? waza_frontend.nonce
            : '';
    }

    function showAlert(message, type = 'info') {
        const alertHtml = `
            <div class="waza-alert waza-alert-${type}">
                ${message}
            </div>
        `;

        // Remove existing alerts
        $('.waza-alert').remove();

        // Check if a modal is open
        const openModal = $('.waza-modal:visible');

        if (openModal.length) {
            // Add alert inside the modal body
            openModal.find('.waza-modal-body').first().prepend(alertHtml);

            // Scroll modal to top
            openModal.find('.waza-modal-content').animate({ scrollTop: 0 }, 300);
        } else {
            // Add new alert at the top of the frontend container
            $('.waza-booking-frontend').first().prepend(alertHtml);

            // Scroll to top to show alert
            $('html, body').animate({ scrollTop: 0 }, 300);
        }

        // Auto-remove after 5 seconds
        setTimeout(() => {
            $('.waza-alert').fadeOut(500, function () {
                $(this).remove();
            });
        }, 5000);
    }

    function showLoading(message = 'Loading...') {
        const loadingHtml = `
            <div id="waza-loading" class="waza-loading">
                <div class="waza-spinner"></div>
                ${message}
            </div>
        `;

        $('#waza-loading').remove();
        $('body').append(loadingHtml);
    }

    function hideLoading() {
        $('#waza-loading').remove();
    }

    function closeModal() {
        $('.waza-modal').remove();
    }

    function updatePaymentMethod(method) {
        // Update hidden field
        $('input[name="payment_method"]').val(method);

        // Show/hide method-specific fields
        $('.waza-payment-fields').hide();
        $(`.waza-payment-fields[data-method="${method}"]`).show();
    }

    // Global functions for external access
    window.WazaBooking = {
        showAlert: showAlert,
        showLoading: showLoading,
        hideLoading: hideLoading,
        closeModal: closeModal,
        updateBookingTotal: updateBookingTotal
    };

})(jQuery);