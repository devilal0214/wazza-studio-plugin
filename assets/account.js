/**
 * Waza Booking - User Account JavaScript
 */
(function ($) {
    'use strict';

    // Check if waza_account is defined (only on account pages)
    if (typeof waza_account === 'undefined') {
        return; // Exit if not on account page
    }

    // DOM Ready
    $(document).ready(function () {
        initWazaAccount();
    });

    /**
     * Initialize all account functionality
     */
    function initWazaAccount() {
        initAjax();
        initModals();
        initBookingActions();
        initFormValidation();
        initFilters();
        initQRGenerator();
        initProfileUpdate();
        initPasswordChange();
        loadMyBookings();
        initAttendanceView();
        initInstructorDashboard();
    }

    /**
     * Setup AJAX defaults
     */
    function initAjax() {
        // Set default AJAX settings
        $.ajaxSetup({
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', waza_account.nonce);
            }
        });

        // Global AJAX error handler
        $(document).ajaxError(function (event, xhr, settings) {
            if (xhr.status === 401) {
                showMessage('Session expired. Please refresh the page.', 'error');
            } else if (xhr.status >= 500) {
                showMessage('Server error. Please try again later.', 'error');
            }
        });
    }

    /**
     * Initialize modal functionality
     */
    function initModals() {
        // Close modal when clicking outside or on close button
        // But NOT for booking modals (they have waza-booking-modal-content class)
        $(document).on('click', '.waza-modal', function (e) {
            if (e.target === this && !$(this).find('.waza-booking-modal-content').length) {
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
     * Initialize booking action handlers
     */
    function initBookingActions() {
        // Cancel booking
        $(document).on('click', '.waza-cancel-booking', function (e) {
            e.preventDefault();

            const bookingId = $(this).data('booking-id');
            const bookingTitle = $(this).data('booking-title');

            if (confirm(`Are you sure you want to cancel "${bookingTitle}"?`)) {
                cancelBooking(bookingId);
            }
        });

        // Show QR Code
        $(document).on('click', '.waza-show-qr', function (e) {
            e.preventDefault();

            const bookingId = $(this).data('booking-id');
            showQRCode(bookingId);
        });

        // Refresh booking status
        $(document).on('click', '.waza-refresh-booking', function (e) {
            e.preventDefault();

            const bookingId = $(this).data('booking-id');
            refreshBookingStatus(bookingId);
        });
    }

    /**
     * Initialize form validation
     */
    function initFormValidation() {
        // Login form
        $('#waza-login-form').on('submit', function (e) {
            e.preventDefault();

            const form = $(this);
            const submitButton = form.find('button[type="submit"]');

            if (validateLoginForm(form)) {
                submitLoginForm(form, submitButton);
            }
        });

        // Registration form
        $('#waza-register-form').on('submit', function (e) {
            e.preventDefault();

            const form = $(this);
            const submitButton = form.find('button[type="submit"]');

            if (validateRegisterForm(form)) {
                submitRegisterForm(form, submitButton);
            }
        });

        // Profile update form
        $('#waza-profile-form').on('submit', function (e) {
            e.preventDefault();

            const form = $(this);
            const submitButton = form.find('button[type="submit"]');

            if (validateProfileForm(form)) {
                submitProfileForm(form, submitButton);
            }
        });

        // Password change form
        $('#waza-password-form').on('submit', function (e) {
            e.preventDefault();

            const form = $(this);
            const submitButton = form.find('button[type="submit"]');

            if (validatePasswordForm(form)) {
                submitPasswordForm(form, submitButton);
            }
        });

        // Instructor registration form
        $('#waza-instructor-register-form').on('submit', function (e) {
            e.preventDefault();

            const form = $(this);
            const submitButton = form.find('button[type="submit"]');

            if (validateInstructorForm(form)) {
                submitInstructorForm(form, submitButton);
            }
        });

        // View Roster in Instructor Dashboard
        $(document).on('click', '.waza-view-roster', function (e) {
            e.preventDefault();
            const slotId = $(this).data('slot-id');
            viewSlotRoster(slotId);
        });

        // View QR Code
        $(document).on('click', '.waza-btn-qr', function (e) {
            e.preventDefault();
            const bookingId = $(this).data('booking-id');
            viewBookingQR(bookingId);
        });
    }

    /**
     * Initialize filters
     */
    function initFilters() {
        // Status filter
        $('#waza-status-filter').on('change', function () {
            const status = $(this).val();
            filterBookings({ status: status });
        });

        // Date filter
        $('#waza-date-filter').on('change', function () {
            const dateRange = $(this).val();
            filterBookings({ date_range: dateRange });
        });

        // Activity filter
        $('#waza-activity-filter').on('change', function () {
            const activity = $(this).val();
            filterBookings({ activity: activity });
        });
    }

    /**
     * Initialize QR code generator
     */
    function initQRGenerator() {
        // Auto-refresh QR codes every 5 minutes for security
        setInterval(function () {
            $('.waza-qr-display img').each(function () {
                const bookingId = $(this).data('booking-id');
                if (bookingId) {
                    refreshQRCode(bookingId);
                }
            });
        }, 300000); // 5 minutes
    }

    /**
     * Initialize profile update functionality
     */
    function initProfileUpdate() {
        // Enable/disable profile editing
        $('#waza-edit-profile').on('click', function () {
            const form = $('#waza-profile-form');
            const inputs = form.find('input, select, textarea');

            if (inputs.prop('disabled')) {
                inputs.prop('disabled', false);
                $(this).text('Cancel');
                form.find('.waza-btn-primary').show();
            } else {
                inputs.prop('disabled', true);
                $(this).text('Edit Profile');
                form.find('.waza-btn-primary').hide();
                // Reset form
                form[0].reset();
            }
        });
    }

    /**
     * Initialize password change functionality
     */
    function initPasswordChange() {
        // Show/hide password change form
        $('#waza-change-password').on('click', function () {
            const form = $('#waza-password-form');
            form.toggle();

            if (form.is(':visible')) {
                $(this).text('Cancel');
                form.find('#current_password').focus();
            } else {
                $(this).text('Change Password');
                form[0].reset();
            }
        });

        // Password strength indicator
        $('#new_password').on('input', function () {
            const password = $(this).val();
            const strength = calculatePasswordStrength(password);
            updatePasswordStrength(strength);
        });
    }

    /**
     * Cancel a booking
     */
    function cancelBooking(bookingId) {
        showLoading('Cancelling booking...');

        $.ajax({
            url: waza_account.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_cancel_booking',
                booking_id: bookingId,
                nonce: waza_account.nonce
            },
            success: function (response) {
                hideLoading();

                if (response.success) {
                    showMessage('Booking cancelled successfully.', 'success');
                    // Remove booking from list or update status
                    updateBookingRow(bookingId, 'cancelled');
                } else {
                    showMessage(response.data || 'Failed to cancel booking.', 'error');
                }
            },
            error: function () {
                hideLoading();
                showMessage('Error cancelling booking. Please try again.', 'error');
            }
        });
    }

    /**
     * Show QR Code for booking
     */
    function showQRCode(bookingId) {
        showLoading('Generating QR code...');

        $.ajax({
            url: waza_account.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_get_booking_qr',
                booking_id: bookingId,
                nonce: waza_account.nonce
            },
            success: function (response) {
                hideLoading();

                if (response.success) {
                    displayQRModal(response.data);
                } else {
                    showMessage(response.data || 'Failed to generate QR code.', 'error');
                }
            },
            error: function () {
                hideLoading();
                showMessage('Error generating QR code. Please try again.', 'error');
            }
        });
    }

    /**
     * Display QR code in modal
     */
    function displayQRModal(data) {
        const modalHtml = `
            <div class="waza-modal">
                <div class="waza-modal-content">
                    <span class="waza-close">&times;</span>
                    <div class="waza-qr-display">
                        <h3>Your Booking QR Code</h3>
                        <div id="waza-qr-image">
                            <img src="${data.qr_code}" alt="QR Code" data-booking-id="${data.booking_id}">
                        </div>
                        <p class="waza-qr-instructions">
                            Show this QR code to the instructor for attendance verification.
                        </p>
                        <div class="waza-qr-actions">
                            <button class="waza-btn waza-btn-secondary" onclick="downloadQR('${data.qr_code}', '${data.booking_title}')">
                                Download QR Code
                            </button>
                            <button class="waza-btn waza-btn-outline" onclick="refreshQRCode(${data.booking_id})">
                                Refresh Code
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHtml);
    }

    /**
     * Refresh QR code
     */
    function refreshQRCode(bookingId) {
        $.ajax({
            url: waza_account.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_refresh_qr',
                booking_id: bookingId,
                nonce: waza_account.nonce
            },
            success: function (response) {
                if (response.success) {
                    $(`img[data-booking-id="${bookingId}"]`).attr('src', response.data.qr_code);
                    showMessage('QR code refreshed.', 'success');
                } else {
                    showMessage(response.data || 'Failed to refresh QR code.', 'error');
                }
            }
        });
    }

    /**
     * Download QR code
     */
    window.downloadQR = function (qrCodeUrl, bookingTitle) {
        const link = document.createElement('a');
        link.href = qrCodeUrl;
        link.download = `waza-booking-qr-${bookingTitle.replace(/[^a-z0-9]/gi, '-').toLowerCase()}.png`;
        link.click();
    };

    /**
     * Refresh booking status
     */
    function refreshBookingStatus(bookingId) {
        $.ajax({
            url: waza_account.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_refresh_booking_status',
                booking_id: bookingId,
                nonce: waza_account.nonce
            },
            success: function (response) {
                if (response.success) {
                    updateBookingRow(bookingId, response.data.status);
                    showMessage('Booking status updated.', 'info');
                } else {
                    showMessage(response.data || 'Failed to refresh status.', 'error');
                }
            }
        });
    }

    /**
     * Filter bookings
     */
    function filterBookings(filters) {
        showLoading('Filtering bookings...');

        $.ajax({
            url: waza_account.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_filter_bookings',
                filters: filters,
                nonce: waza_account.nonce
            },
            success: function (response) {
                hideLoading();

                if (response.success) {
                    $('#waza-bookings-container').html(response.data.html);
                } else {
                    showMessage(response.data || 'Failed to filter bookings.', 'error');
                }
            },
            error: function () {
                hideLoading();
                showMessage('Error filtering bookings. Please try again.', 'error');
            }
        });
    }

    /**
     * Submit login form
     */
    function submitLoginForm(form, button) {
        setButtonLoading(button, 'Signing in...');

        $.ajax({
            url: waza_account.ajax_url,
            type: 'POST',
            data: form.serialize() + '&action=waza_user_login&nonce=' + waza_account.nonce,
            success: function (response) {
                setButtonLoading(button, false);

                if (response.success) {
                    showMessage('Login successful! Redirecting...', 'success');
                    setTimeout(() => {
                        if (response.data && response.data.redirect) {
                            window.location.href = response.data.redirect;
                        } else {
                            window.location.reload();
                        }
                    }, 1500);
                } else {
                    showMessage(response.data || 'Login failed. Please try again.', 'error');
                }
            },
            error: function () {
                setButtonLoading(button, false);
                showMessage('Error during login. Please try again.', 'error');
            }
        });
    }

    /**
     * Submit registration form
     */
    function submitRegisterForm(form, button) {
        setButtonLoading(button, 'Creating account...');

        $.ajax({
            url: waza_account.ajax_url,
            type: 'POST',
            data: form.serialize() + '&action=waza_user_register&nonce=' + waza_account.nonce,
            success: function (response) {
                setButtonLoading(button, false);

                if (response.success) {
                    showMessage('Account created successfully! Please check your email.', 'success');
                    form[0].reset();
                } else {
                    showMessage(response.data || 'Registration failed. Please try again.', 'error');
                }
            },
            error: function () {
                setButtonLoading(button, false);
                showMessage('Error during registration. Please try again.', 'error');
            }
        });
    }

    /**
     * Submit profile form
     */
    function submitProfileForm(form, button) {
        setButtonLoading(button, 'Updating profile...');

        $.ajax({
            url: waza_account.ajax_url,
            type: 'POST',
            data: form.serialize() + '&action=waza_update_profile&nonce=' + waza_account.nonce,
            success: function (response) {
                setButtonLoading(button, false);

                if (response.success) {
                    showMessage('Profile updated successfully!', 'success');
                    // Disable form fields again
                    $('#waza-edit-profile').click();
                } else {
                    showMessage(response.data || 'Profile update failed.', 'error');
                }
            },
            error: function () {
                setButtonLoading(button, false);
                showMessage('Error updating profile. Please try again.', 'error');
            }
        });
    }

    /**
     * Submit password form
     */
    function submitPasswordForm(form, button) {
        setButtonLoading(button, 'Changing password...');

        $.ajax({
            url: waza_account.ajax_url,
            type: 'POST',
            data: form.serialize() + '&action=waza_change_password&nonce=' + waza_account.nonce,
            success: function (response) {
                setButtonLoading(button, false);

                if (response.success) {
                    showMessage('Password changed successfully!', 'success');
                    form[0].reset();
                    $('#waza-change-password').click(); // Hide form
                } else {
                    showMessage(response.data || 'Password change failed.', 'error');
                }
            },
            error: function () {
                setButtonLoading(button, false);
                showMessage('Error changing password. Please try again.', 'error');
            }
        });
    }

    /**
     * Form validation functions
     */
    function validateLoginForm(form) {
        const username = form.find('#user_login').val();
        const password = form.find('#user_password').val();

        if (!username || !username.trim()) {
            showMessage('Please enter your username or email.', 'error');
            return false;
        }

        if (!password || !password.trim()) {
            showMessage('Please enter your password.', 'error');
            return false;
        }

        return true;
    }

    function validateRegisterForm(form) {
        const username = form.find('#reg_username').val().trim();
        const email = form.find('#reg_email').val().trim();
        const password = form.find('#reg_password').val().trim();
        const confirmPassword = form.find('#reg_password_confirm').val().trim();

        if (!username) {
            showMessage('Please enter a username.', 'error');
            return false;
        }

        if (username.length < 3) {
            showMessage('Username must be at least 3 characters long.', 'error');
            return false;
        }

        if (!email) {
            showMessage('Please enter your email address.', 'error');
            return false;
        }

        if (!isValidEmail(email)) {
            showMessage('Please enter a valid email address.', 'error');
            return false;
        }

        if (!password) {
            showMessage('Please enter a password.', 'error');
            return false;
        }

        if (password.length < 8) {
            showMessage('Password must be at least 8 characters long.', 'error');
            return false;
        }

        if (password !== confirmPassword) {
            showMessage('Passwords do not match.', 'error');
            return false;
        }

        return true;
    }

    function validateProfileForm(form) {
        const email = form.find('#profile_email').val().trim();
        const firstName = form.find('#first_name').val().trim();
        const lastName = form.find('#last_name').val().trim();

        if (!email) {
            showMessage('Please enter your email address.', 'error');
            return false;
        }

        if (!isValidEmail(email)) {
            showMessage('Please enter a valid email address.', 'error');
            return false;
        }

        if (!firstName) {
            showMessage('Please enter your first name.', 'error');
            return false;
        }

        if (!lastName) {
            showMessage('Please enter your last name.', 'error');
            return false;
        }

        return true;
    }

    function validatePasswordForm(form) {
        const currentPassword = form.find('#current_password').val().trim();
        const newPassword = form.find('#new_password').val().trim();
        const confirmPassword = form.find('#confirm_password').val().trim();

        if (!currentPassword) {
            showMessage('Please enter your current password.', 'error');
            return false;
        }

        if (!newPassword) {
            showMessage('Please enter a new password.', 'error');
            return false;
        }

        if (newPassword.length < 8) {
            showMessage('New password must be at least 8 characters long.', 'error');
            return false;
        }

        if (newPassword !== confirmPassword) {
            showMessage('New passwords do not match.', 'error');
            return false;
        }

        if (currentPassword === newPassword) {
            showMessage('New password must be different from current password.', 'error');
            return false;
        }

        return true;
    }

    /**
     * Instructor form validation
     */
    function validateInstructorForm(form) {
        const name = form.find('#inst_name').val().trim();
        const email = form.find('#inst_email').val().trim();
        const phone = form.find('#inst_phone').val().trim();
        const password = form.find('#inst_pass').val().trim();
        const skills = form.find('#inst_skills').val().trim();
        const rating = form.find('input[name="rating"]:checked').val();

        if (!name || !email || !phone || !password || !skills || !rating) {
            showMessage('Please fill in all required fields.', 'error');
            return false;
        }

        if (!isValidEmail(email)) {
            showMessage('Please enter a valid email address.', 'error');
            return false;
        }

        if (password.length < 8) {
            showMessage('Password must be at least 8 characters long.', 'error');
            return false;
        }

        return true;
    }

    /**
     * Submit instructor registration form
     */
    function submitInstructorForm(form, button) {
        setButtonLoading(button, 'Submitting...');

        $.ajax({
            url: waza_account.ajax_url,
            type: 'POST',
            data: form.serialize() + '&action=waza_instructor_register&nonce=' + waza_account.nonce,
            success: function (response) {
                setButtonLoading(button, false);

                if (response.success) {
                    showMessage(response.data.message, 'success');
                    form[0].reset();
                } else {
                    showMessage(response.data.message || 'Registration failed.', 'error');
                }
            },
            error: function () {
                setButtonLoading(button, false);
                showMessage('Error during registration. Please try again.', 'error');
            }
        });
    }

    /**
     * Utility functions
     */
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function calculatePasswordStrength(password) {
        let strength = 0;

        if (password.length >= 8) strength += 1;
        if (password.match(/[a-z]/)) strength += 1;
        if (password.match(/[A-Z]/)) strength += 1;
        if (password.match(/[0-9]/)) strength += 1;
        if (password.match(/[^a-zA-Z0-9]/)) strength += 1;

        return strength;
    }

    function updatePasswordStrength(strength) {
        const strengthIndicator = $('#password-strength');
        const strengthText = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
        const strengthClass = ['very-weak', 'weak', 'fair', 'good', 'strong'];

        strengthIndicator
            .removeClass('very-weak weak fair good strong')
            .addClass(strengthClass[strength - 1] || 'very-weak')
            .text(strengthText[strength - 1] || 'Very Weak');
    }

    function updateBookingRow(bookingId, status) {
        const row = $(`[data-booking-id="${bookingId}"]`).closest('.waza-booking-item, tr');
        const statusElement = row.find('.waza-status');

        // Update status badge
        statusElement
            .removeClass('waza-status-confirmed waza-status-cancelled waza-status-pending')
            .addClass(`waza-status-${status}`)
            .text(status.charAt(0).toUpperCase() + status.slice(1));

        // Hide/show action buttons based on status
        if (status === 'cancelled') {
            row.find('.waza-cancel-booking').hide();
            row.find('.waza-show-qr').hide();
        }
    }

    function showMessage(message, type = 'info') {
        const messageHtml = `
            <div class="waza-message ${type}">
                ${message}
            </div>
        `;

        // Remove existing messages
        $('.waza-message').remove();

        // Add new message at the top
        $('.waza-user-dashboard, .waza-my-bookings, .waza-login-form-container, .waza-register-form-container')
            .first()
            .prepend(messageHtml);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            $('.waza-message').fadeOut(500, function () {
                $(this).remove();
            });
        }, 5000);

        // Scroll to top to show message
        $('html, body').animate({ scrollTop: 0 }, 300);
    }

    function showLoading(message = 'Loading...') {
        const loadingHtml = `
            <div id="waza-loading" class="waza-loading">
                ${message}
            </div>
        `;

        $('#waza-loading').remove();
        $('body').append(loadingHtml);
    }

    function hideLoading() {
        $('#waza-loading').remove();
    }

    function setButtonLoading(button, text) {
        if (text) {
            button.data('original-text', button.text());
            button.prop('disabled', true).text(text);
        } else {
            button.prop('disabled', false).text(button.data('original-text'));
        }
    }

    function closeModal() {
        $('.waza-modal').remove();
    }

    /**
     * View slot roster for instructors
     */
    function viewSlotRoster(slotId) {
        showLoading('Fetching roster...');

        $.ajax({
            url: waza_account.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_get_slot_roster',
                slot_id: slotId,
                nonce: waza_account.nonce
            },
            success: function (response) {
                hideLoading();
                if (response.success) {
                    $('#waza-roster-container').html(response.data.html).show();
                    $('html, body').animate({
                        scrollTop: $("#waza-roster-container").offset().top - 100
                    }, 500);
                } else {
                    showMessage(response.data.message || 'Failed to fetch roster.', 'error');
                }
            }
        });
    }

    /**
     * View booking QR code
     */
    function viewBookingQR(bookingId) {
        showLoading('Generating QR code...');

        $.ajax({
            url: waza_account.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_get_booking_qr',
                booking_id: bookingId,
                nonce: waza_account.nonce
            },
            success: function (response) {
                hideLoading();
                if (response.success) {
                    const qrUrl = response.data.qr_url;

                    const modalHtml = `
                        <div id="waza-qr-modal" class="waza-modal">
                            <div class="waza-modal-content waza-text-center">
                                <div class="waza-modal-header">
                                    <h3 class="waza-modal-title">Booking QR Code</h3>
                                    <button onclick="WazaAccount.closeModal()" class="waza-close">&times;</button>
                                </div>
                                <div class="waza-modal-body">
                                    <img src="${qrUrl}" alt="QR Code" style="max-width: 100%; height: auto; margin-bottom: 15px;">
                                    <p>Show this code at the venue to check in.</p>
                                    <a href="${qrUrl}" download="booking-qr-${bookingId}.png" class="waza-btn waza-btn-primary">Download QR Code</a>
                                </div>
                            </div>
                        </div>
                    `;

                    $('body').append(modalHtml);
                    $('#waza-qr-modal').show();
                } else {
                    showMessage(response.data.message || 'Failed to get QR code.', 'error');
                }
            },
            error: function () {
                hideLoading();
                showMessage('Error fetching QR code.', 'error');
            }
        });
    }

    // Global functions for external access
    window.WazaAccount = {
        showMessage: showMessage,
        showLoading: showLoading,
        hideLoading: hideLoading,
        closeModal: closeModal,
        refreshBookingStatus: refreshBookingStatus,
        filterBookings: filterBookings
    };

    /**
     * Load user's bookings
     */
    function loadMyBookings() {
        const bookingsList = $('#waza-bookings-list');
        
        if (bookingsList.length === 0) {
            return; // Not on bookings page
        }

        $.ajax({
            url: waza_account.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_get_my_bookings',
                nonce: waza_account.nonce
            },
            success: function (response) {
                if (response.success) {
                    displayBookings(response.data.bookings);
                } else {
                    bookingsList.html('<p class="waza-error">' + (response.data || 'Failed to load bookings') + '</p>');
                }
            },
            error: function () {
                bookingsList.html('<p class="waza-error">Error loading bookings. Please refresh the page.</p>');
            }
        });
    }

    /**
     * Display bookings list
     */
    function displayBookings(bookings) {
        const bookingsList = $('#waza-bookings-list');
        
        if (!bookings || bookings.length === 0) {
            bookingsList.html('<p class="waza-no-bookings">You haven\'t made any bookings yet.</p>');
            return;
        }

        let html = '<div class="waza-bookings-grid">';
        
        bookings.forEach(function (booking) {
            const statusClass = booking.booking_status === 'confirmed' ? 'confirmed' : 
                              booking.booking_status === 'cancelled' ? 'cancelled' : 'pending';
            const paymentClass = booking.payment_status === 'paid' || booking.payment_status === 'completed' ? 'paid' : 
                               booking.payment_status === 'pending' ? 'pending' : 'failed';
            const typeClass = booking.is_upcoming ? 'upcoming' : 'past';
            const isPending = booking.payment_status === 'pending';
            
            html += `
                <div class="waza-booking-card ${typeClass} ${isPending ? 'payment-pending' : ''}" data-booking-id="${booking.id}" data-type="${typeClass}">
                    ${isPending ? '<div class="waza-pending-ribbon">Payment Pending</div>' : ''}
                    <div class="waza-booking-image">
                        <img src="${booking.activity_image}" alt="${booking.activity_title}">
                        ${booking.is_upcoming ? '<span class="waza-badge upcoming">Upcoming</span>' : '<span class="waza-badge past">Past</span>'}
                    </div>
                    <div class="waza-booking-content">
                        <h4 class="waza-booking-title">${booking.activity_title}</h4>
                        ${isPending ? '<div class="waza-warning-message">⚠️ Complete payment to confirm your booking</div>' : ''}
                        <div class="waza-booking-details">
                            <div class="waza-booking-detail">
                                <span class="waza-icon">📅</span>
                                <span>${booking.formatted_date}</span>
                            </div>
                            <div class="waza-booking-detail">
                                <span class="waza-icon">🕐</span>
                                <span>${booking.formatted_time}</span>
                            </div>
                            <div class="waza-booking-detail">
                                <span class="waza-icon">👥</span>
                                <span>${booking.quantity} ${booking.quantity > 1 ? 'attendees' : 'attendee'}</span>
                            </div>
                            <div class="waza-booking-detail">
                                <span class="waza-icon">💰</span>
                                <span>₹${booking.total_amount}</span>
                            </div>
                        </div>
                        <div class="waza-booking-meta">
                            <span class="waza-status-badge ${statusClass}">${booking.booking_status}</span>
                            <span class="waza-status-badge ${paymentClass}">${booking.payment_status}</span>
                            ${booking.attended ? '<span class="waza-status-badge attended">Attended</span>' : ''}
                        </div>
                        <div class="waza-booking-actions">
                            ${(isPending && booking.is_upcoming) ? 
                                `<button class="waza-btn waza-btn-sm waza-btn-warning waza-complete-payment" data-booking-id="${booking.id}">
                                    <span class="waza-icon">💳</span> Complete Payment
                                </button>` : 
                                (booking.is_upcoming && booking.booking_status === 'confirmed' ? 
                                `<button class="waza-btn waza-btn-sm waza-btn-primary waza-download-qr" data-booking-id="${booking.id}">
                                    <span class="waza-icon">📱</span> Download QR
                                </button>` : '')
                            }
                            <button class="waza-btn waza-btn-sm waza-btn-secondary waza-view-booking-details" data-booking-id="${booking.id}">View Details</button>
                        </div>
                    </div>
                    <div class="waza-booking-footer">
                        <small>Booked on ${booking.created_at}</small>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        bookingsList.html(html);
        
        // Attach event handlers
        $('.waza-download-qr').on('click', function (e) {
            e.preventDefault();
            const bookingId = $(this).data('booking-id');
            downloadQRCode(bookingId);
        });
        
        $('.waza-complete-payment').on('click', function (e) {
            e.preventDefault();
            const bookingId = $(this).data('booking-id');
            completePayment(bookingId);
        });
        
        $('.waza-view-booking-details').on('click', function (e) {
            e.preventDefault();
            const bookingId = $(this).data('booking-id');
            viewBookingDetails(bookingId);
        });
        
        // Initialize filter buttons
        $('.waza-filter-btn').on('click', function () {
            $('.waza-filter-btn').removeClass('active');
            $(this).addClass('active');
            
            const filter = $(this).data('filter');
            filterBookingsByType(filter);
        });
    }

    /**
     * Filter bookings by type
     */
    function filterBookingsByType(filter) {
        const cards = $('.waza-booking-card');
        
        if (filter === 'all') {
            cards.show();
        } else {
            cards.hide();
            cards.filter(`.${filter}`).show();
        }
    }

    /**
     * Download QR code for booking
     */
    function downloadQRCode(bookingId) {
        $.ajax({
            url: waza_account.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_download_qr',
                booking_id: bookingId,
                nonce: waza_account.nonce
            },
            success: function (response) {
                if (response.success) {
                    // Open QR code in modal with multi-seat support
                    showQRModal(response.data.qr_codes, bookingId, response.data.is_multi_seat);
                } else {
                    showMessage(response.data || 'Failed to generate QR code', 'error');
                }
            },
            error: function () {
                showMessage('Error generating QR code', 'error');
            }
        });
    }

    /**
     * Complete payment for pending booking
     */
    function completePayment(bookingId) {
        // Show loading
        showMessage('Redirecting to payment...', 'info');
        
        // Get booking details first
        $.ajax({
            url: waza_account.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_get_booking_payment_details',
                booking_id: bookingId,
                nonce: waza_account.nonce
            },
            success: function (response) {
                if (response.success) {
                    // Open payment modal or redirect
                    showPaymentModal(response.data);
                } else {
                    showMessage(response.data.message || 'Failed to load payment details', 'error');
                }
            },
            error: function () {
                showMessage('Error loading payment details', 'error');
            }
        });
    }
    
    /**
     * Show payment modal
     */
    function showPaymentModal(paymentData) {
        // Remove existing modal
        $('#waza-payment-modal').remove();
        
        const modalHtml = `
            <div id="waza-payment-modal" class="waza-modal" style="display: block;">
                <div class="waza-modal-overlay"></div>
                <div class="waza-modal-dialog">
                    <div class="waza-modal-content">
                        <div class="waza-modal-header">
                            <h3>Complete Payment</h3>
                            <button type="button" class="waza-modal-close">&times;</button>
                        </div>
                        <div class="waza-modal-body" style="padding: 30px;">
                            <div class="waza-payment-summary">
                                <h4>Booking Summary</h4>
                                <div class="waza-summary-row">
                                    <span>Activity:</span>
                                    <strong>${paymentData.activity_title}</strong>
                                </div>
                                <div class="waza-summary-row">
                                    <span>Date & Time:</span>
                                    <strong>${paymentData.datetime}</strong>
                                </div>
                                <div class="waza-summary-row">
                                    <span>Quantity:</span>
                                    <strong>${paymentData.quantity} attendees</strong>
                                </div>
                                <div class="waza-summary-row" style="border-top: 2px solid #eee; margin-top: 10px; padding-top: 10px;">
                                    <span style="font-size: 1.2em;">Total Amount:</span>
                                    <strong style="font-size: 1.4em; color: #4F46E5;">₹${paymentData.total_amount}</strong>
                                </div>
                            </div>
                            <div class="waza-payment-message" style="margin-top: 20px; padding: 15px; background: #FEF3C7; border-radius: 8px; color: #92400E;">
                                ⚠️ Payment functionality will be integrated with your payment gateway.
                            </div>
                        </div>
                        <div class="waza-modal-footer">
                            <button type="button" class="waza-btn waza-btn-primary" id="waza-proceed-payment">Proceed to Pay</button>
                            <button type="button" class="waza-btn waza-btn-secondary waza-modal-close">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        
        // Close modal handlers
        $('.waza-modal-close, .waza-modal-overlay').on('click', function () {
            $('#waza-payment-modal').remove();
        });
        
        // Proceed to payment
        $('#waza-proceed-payment').on('click', function () {
            showMessage('Payment gateway integration required', 'info');
        });
    }
    
    /**
     * View booking details
     */
    function viewBookingDetails(bookingId) {
        // Show loading
        showMessage('Loading booking details...', 'info');
        
        $.ajax({
            url: waza_account.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_get_booking_details',
                booking_id: bookingId,
                nonce: waza_account.nonce
            },
            success: function (response) {
                if (response.success) {
                    showBookingDetailsModal(response.data);
                } else {
                    showMessage(response.data.message || 'Failed to load booking details', 'error');
                }
            },
            error: function () {
                showMessage('Error loading booking details', 'error');
            }
        });
    }
    
    /**
     * Show booking details modal
     */
    function showBookingDetailsModal(booking) {
        // Remove existing modal
        $('#waza-details-modal').remove();
        
        const statusBadge = booking.booking_status === 'confirmed' ? 
            '<span class="waza-status-badge confirmed">Confirmed</span>' : 
            '<span class="waza-status-badge pending">Pending</span>';
        
        const paymentBadge = booking.payment_status === 'completed' ? 
            '<span class="waza-status-badge completed">Completed</span>' : 
            '<span class="waza-status-badge pending-payment">Pending Payment</span>';
        
        const modalHtml = `
            <div id="waza-details-modal" class="waza-modal" style="display: block;">
                <div class="waza-modal-overlay"></div>
                <div class="waza-modal-dialog">
                    <div class="waza-modal-content">
                        <div class="waza-modal-header">
                            <h3>Booking Details #${booking.id}</h3>
                            <button type="button" class="waza-modal-close">&times;</button>
                        </div>
                        <div class="waza-modal-body" style="padding: 30px;">
                            <div class="waza-booking-details-content">
                                <div class="waza-detail-section">
                                    <h4>Activity Information</h4>
                                    <div class="waza-detail-row">
                                        <span class="label">Activity:</span>
                                        <strong>${booking.activity_title}</strong>
                                    </div>
                                    <div class="waza-detail-row">
                                        <span class="label">Date:</span>
                                        <strong>${booking.formatted_date}</strong>
                                    </div>
                                    <div class="waza-detail-row">
                                        <span class="label">Time:</span>
                                        <strong>${booking.formatted_time}</strong>
                                    </div>
                                </div>
                                
                                <div class="waza-detail-section">
                                    <h4>Booking Information</h4>
                                    <div class="waza-detail-row">
                                        <span class="label">Number of Attendees:</span>
                                        <strong>${booking.quantity}</strong>
                                    </div>
                                    <div class="waza-detail-row">
                                        <span class="label">Total Amount:</span>
                                        <strong style="color: #4F46E5;">₹${booking.total_amount}</strong>
                                    </div>
                                    <div class="waza-detail-row">
                                        <span class="label">Booking Status:</span>
                                        ${statusBadge}
                                    </div>
                                    <div class="waza-detail-row">
                                        <span class="label">Payment Status:</span>
                                        ${paymentBadge}
                                    </div>
                                    ${booking.payment_method ? `<div class="waza-detail-row">
                                        <span class="label">Payment Method:</span>
                                        <span>${booking.payment_method}</span>
                                    </div>` : ''}
                                    <div class="waza-detail-row">
                                        <span class="label">Booked On:</span>
                                        <span>${booking.created_at}</span>
                                    </div>
                                </div>
                                
                                <div class="waza-detail-section">
                                    <h4>Contact Information</h4>
                                    <div class="waza-detail-row">
                                        <span class="label">Name:</span>
                                        <strong>${booking.user_name || 'N/A'}</strong>
                                    </div>
                                    <div class="waza-detail-row">
                                        <span class="label">Email:</span>
                                        <span>${booking.user_email}</span>
                                    </div>
                                    <div class="waza-detail-row">
                                        <span class="label">Phone:</span>
                                        <span>${booking.user_phone}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="waza-modal-footer">
                            <button type="button" class="waza-btn waza-btn-secondary waza-modal-close">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        
        // Close modal handlers
        $('.waza-modal-close, .waza-modal-overlay').on('click', function () {
            $('#waza-details-modal').remove();
        });
    }
    
    /**
     * Show QR code modal
     */
    function showQRModal(qrCodes, bookingId, isMultiSeat) {
        // Remove existing modal if any
        $('#waza-qr-modal').remove();
        
        let qrContent = '';
        
        if (isMultiSeat) {
            qrContent = '<div class="waza-multi-qr-grid">';
            qrCodes.forEach((qr) => {
                qrContent += `
                    <div class="waza-qr-card">
                        <h4>Seat ${qr.seat_number}: ${qr.name}</h4>
                        <div class="waza-qr-image-wrapper">
                            <img src="${qr.qr_image}" alt="QR Code" class="waza-qr-image">
                        </div>
                        <p class="waza-qr-token"><small>${qr.token}</small></p>
                        <button class="waza-btn waza-btn-sm waza-btn-primary waza-download-single-qr" 
                                data-qr="${qr.qr_image}" 
                                data-name="${qr.name}" 
                                data-seat="${qr.seat_number}">
                            ⬇️ Download
                        </button>
                    </div>
                `;
            });
            qrContent += '</div>';
            qrContent += `<button class="waza-btn waza-btn-secondary waza-download-all-qr" style="margin-top: 20px;">📥 Download All QR Codes</button>`;
        } else {
            qrContent = `
                <div class="waza-single-qr-container" style="text-align: center;">
                    <div class="waza-qr-image-wrapper">
                        <img src="${qrCodes[0].qr_image}" alt="QR Code" class="waza-qr-image" style="max-width: 400px; margin: 0 auto;">
                    </div>
                    <p class="waza-qr-token"><small>${qrCodes[0].token}</small></p>
                </div>
            `;
        }
        
        const modalHtml = `
            <div id="waza-qr-modal" class="waza-modal" style="display: block;">
                <div class="waza-modal-overlay"></div>
                <div class="waza-modal-dialog">
                    <div class="waza-modal-content">
                        <div class="waza-modal-header">
                            <h3>${isMultiSeat ? 'Attendee QR Codes' : 'Your Booking QR Code'}</h3>
                            <button type="button" class="waza-modal-close">&times;</button>
                        </div>
                        <div class="waza-modal-body" style="padding: 30px;">
                            <p style="margin-bottom: 20px;">Show ${isMultiSeat ? 'these QR codes' : 'this QR code'} at the venue for check-in</p>
                            ${qrContent}
                            <p class="waza-qr-booking-id" style="margin-top: 20px;"><small>Booking ID: #${bookingId}</small></p>
                        </div>
                        <div class="waza-modal-footer">
                            ${!isMultiSeat ? `<button type="button" class="waza-btn waza-btn-primary waza-download-single-qr" data-qr="${qrCodes[0].qr_image}" data-name="Booking-${bookingId}" data-seat="1">⬇️ Download QR Code</button>` : ''}
                            <button type="button" class="waza-btn waza-btn-secondary waza-modal-close">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        
        // Download single QR handler
        $('#waza-qr-modal').on('click', '.waza-download-single-qr', function() {
            const qrData = $(this).data('qr');
            const name = $(this).data('name');
            const seat = $(this).data('seat');
            downloadImageData(qrData, `Waza-Booking-${bookingId}-Seat${seat}-${name}.png`);
        });
        
        // Download all QRs handler (for multi-seat)
        $('#waza-qr-modal').on('click', '.waza-download-all-qr', function() {
            qrCodes.forEach((qr, index) => {
                setTimeout(() => {
                    downloadImageData(qr.qr_image, `Waza-Booking-${bookingId}-Seat${qr.seat_number}-${qr.name}.png`);
                }, index * 300); // Stagger downloads by 300ms
            });
        });
        
        // Close modal handlers
        $('.waza-modal-close, .waza-modal-overlay').on('click', function () {
            $('#waza-qr-modal').remove();
        });
    }
    
    /**
     * Download image from base64 data
     */
    function downloadImageData(dataUrl, filename) {
        const link = document.createElement('a');
        link.href = dataUrl;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    /**
     * Initialize attendance view
     */
    function initAttendanceView() {
        if ($('.waza-my-attendance-container').length === 0) {
            return; // Not on attendance page
        }

        // Load attendance on page load
        loadMyAttendance('all');

        // Filter button handlers
        $(document).on('click', '.waza-my-attendance-container .waza-filter-btn', function () {
            $('.waza-my-attendance-container .waza-filter-btn').removeClass('active');
            $(this).addClass('active');

            const filter = $(this).data('filter');
            loadMyAttendance(filter);
        });
    }

    /**
     * Load user's attendance records
     */
    function loadMyAttendance(filter) {
        $('#waza-attendance-list').html('<div class="waza-loading">Loading attendance records...</div>');

        $.ajax({
            url: waza_account.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_get_my_attendance',
                filter: filter,
                nonce: waza_account.nonce
            },
            success: function (response) {
                if (response.success) {
                    displayAttendanceRecords(response.data.records);
                    updateAttendanceStats(response.data.stats);
                } else {
                    $('#waza-attendance-list').html('<div class="waza-error">' + (response.data.message || 'Failed to load attendance records') + '</div>');
                }
            },
            error: function () {
                $('#waza-attendance-list').html('<div class="waza-error">Error loading attendance records</div>');
            }
        });
    }

    /**
     * Display attendance records
     */
    function displayAttendanceRecords(records) {
        if (!records || records.length === 0) {
            $('#waza-attendance-list').html('<div class="waza-no-results">No attendance records found</div>');
            return;
        }

        let html = '<div class="waza-attendance-table-wrapper"><table class="waza-attendance-table">';
        html += '<thead><tr>';
        html += '<th>Date & Day</th>';
        html += '<th>Activity</th>';
        html += '<th>Slot Time</th>';
        html += '<th>Check In</th>';
        html += '<th>Check Out</th>';
        html += '<th>Duration</th>';
        html += '<th>Status</th>';
        html += '</tr></thead>';
        html += '<tbody>';

        records.forEach(function (record) {
            const statusClass = record.status === 'completed' ? 'status-completed' : 'status-checked-in';
            const entryBadge = record.entry_method === 'qr' ? '<span class="method-badge qr">QR</span>' : '<span class="method-badge manual">Manual</span>';
            const exitBadge = record.exit_method ? (record.exit_method === 'qr' ? '<span class="method-badge qr">QR</span>' : (record.exit_method === 'auto' ? '<span class="method-badge auto">Auto</span>' : '<span class="method-badge manual">Manual</span>')) : '—';

            html += '<tr>';
            html += '<td><div class="attendance-date"><strong>' + record.date + '</strong><br><small>' + record.day_of_week + '</small></div></td>';
            html += '<td><strong>' + record.activity_name + '</strong></td>';
            html += '<td>' + record.slot_time + '</td>';
            html += '<td>' + record.check_in_time + ' ' + entryBadge + '</td>';
            html += '<td>' + record.check_out_time + ' ' + exitBadge + '</td>';
            html += '<td><strong>' + record.duration + '</strong></td>';
            html += '<td><span class="attendance-status ' + statusClass + '">' + record.status_label + '</span></td>';
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        $('#waza-attendance-list').html(html);
    }

    /**
     * Update attendance statistics
     */
    function updateAttendanceStats(stats) {
        $('#waza-total-sessions').text(stats.total_sessions);
        $('#waza-completed-sessions').text(stats.completed_sessions);
        $('#waza-total-hours').text(stats.total_hours + 'h');
    }

    /**
     * Initialize instructor dashboard
     */
    function initInstructorDashboard() {
        if ($('.waza-instructor-dashboard').length === 0) {
            return; // Not on instructor dashboard
        }

        // Load overview stats on page load
        loadInstructorOverview();

        // Tab switching
        $(document).on('click', '.waza-tab-btn', function () {
            const tab = $(this).data('tab');
            
            // Update active tab button
            $('.waza-tab-btn').removeClass('active');
            $(this).addClass('active');
            
            // Show corresponding content
            $('.waza-tab-content').removeClass('active');
            $('[data-tab-content="' + tab + '"]').addClass('active');
            
            // Load data for the tab
            if (tab === 'overview') {
                loadInstructorOverview();
            } else if (tab === 'activities') {
                loadInstructorActivities();
            } else if (tab === 'schedule') {
                loadInstructorSchedule('upcoming');
            } else if (tab === 'students') {
                loadInstructorStudents();
            } else if (tab === 'attendance') {
                loadInstructorAttendance('today');
            }
        });

        // Schedule filter handlers
        $(document).on('click', '.waza-schedule-filter-btn', function () {
            $('.waza-schedule-filter-btn').removeClass('active');
            $(this).addClass('active');
            
            const filter = $(this).data('filter');
            loadInstructorSchedule(filter);
        });

        // Attendance filter handlers
        $(document).on('click', '.waza-attendance-filter-btn', function () {
            $('.waza-attendance-filter-btn').removeClass('active');
            $(this).addClass('active');
            
            const filter = $(this).data('filter');
            loadInstructorAttendance(filter);
        });
    }

    /**
     * Load instructor overview stats
     */
    function loadInstructorOverview() {
        $.ajax({
            url: waza_account.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_get_instructor_overview',
                nonce: waza_account.nonce
            },
            success: function (response) {
                if (response.success) {
                    $('#instructor-total-activities').text(response.data.total_activities);
                    $('#instructor-upcoming-slots').text(response.data.upcoming_slots);
                    $('#instructor-total-students').text(response.data.total_students);
                    $('#instructor-today-attendance').text(response.data.today_attendance);
                }
            }
        });
    }

    /**
     * Load instructor activities
     */
    function loadInstructorActivities() {
        $('#instructor-activities-list').html('<div class="waza-loading">Loading activities...</div>');

        $.ajax({
            url: waza_account.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_get_instructor_activities',
                nonce: waza_account.nonce
            },
            success: function (response) {
                if (response.success) {
                    displayInstructorActivities(response.data.activities);
                } else {
                    $('#instructor-activities-list').html('<div class="waza-error">' + (response.data.message || 'Failed to load activities') + '</div>');
                }
            },
            error: function () {
                $('#instructor-activities-list').html('<div class="waza-error">Error loading activities</div>');
            }
        });
    }

    /**
     * Display instructor activities
     */
    function displayInstructorActivities(activities) {
        if (!activities || activities.length === 0) {
            $('#instructor-activities-list').html('<div class="waza-no-results">No activities found</div>');
            return;
        }

        let html = '<div class="waza-activities-grid">';
        
        activities.forEach(function (activity) {
            html += '<div class="waza-activity-card">';
            html += '<div class="waza-activity-header">';
            html += '<h3>' + activity.title + '</h3>';
            html += '<a href="' + activity.edit_link + '" class="waza-btn waza-btn-sm">Edit</a>';
            html += '</div>';
            html += '<p>' + activity.description + '</p>';
            html += '<div class="waza-activity-meta">';
            html += '<span><strong>Price:</strong> ' + activity.price + '</span>';
            html += '<span><strong>Duration:</strong> ' + activity.duration + '</span>';
            html += '<span><strong>Capacity:</strong> ' + activity.capacity + '</span>';
            html += '</div>';
            html += '<div class="waza-activity-stats">';
            html += '<div class="stat"><span class="label">Total Slots</span><span class="value">' + activity.total_slots + '</span></div>';
            html += '<div class="stat"><span class="label">Total Bookings</span><span class="value">' + activity.total_bookings + '</span></div>';
            html += '</div>';
            html += '</div>';
        });
        
        html += '</div>';
        $('#instructor-activities-list').html(html);
    }

    /**
     * Load instructor schedule
     */
    function loadInstructorSchedule(filter) {
        $('#instructor-schedule-list').html('<div class="waza-loading">Loading schedule...</div>');

        $.ajax({
            url: waza_account.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_get_instructor_schedule',
                filter: filter,
                nonce: waza_account.nonce
            },
            success: function (response) {
                if (response.success) {
                    displayInstructorSchedule(response.data.slots);
                } else {
                    $('#instructor-schedule-list').html('<div class="waza-error">' + (response.data.message || 'Failed to load schedule') + '</div>');
                }
            },
            error: function () {
                $('#instructor-schedule-list').html('<div class="waza-error">Error loading schedule</div>');
            }
        });
    }

    /**
     * Display instructor schedule
     */
    function displayInstructorSchedule(slots) {
        if (!slots || slots.length === 0) {
            $('#instructor-schedule-list').html('<div class="waza-no-results">No slots found</div>');
            return;
        }

        let html = '<div class="waza-schedule-table-wrapper"><table class="waza-schedule-table">';
        html += '<thead><tr>';
        html += '<th>Date & Day</th>';
        html += '<th>Activity</th>';
        html += '<th>Time</th>';
        html += '<th>Capacity</th>';
        html += '<th>Booked</th>';
        html += '<th>Available</th>';
        html += '<th>Status</th>';
        html += '</tr></thead>';
        html += '<tbody>';

        slots.forEach(function (slot) {
            const statusClass = slot.status === 'past' ? 'status-past' : 'status-upcoming';
            const availableClass = slot.available === 0 ? 'text-danger' : (slot.available < 5 ? 'text-warning' : 'text-success');
            
            html += '<tr>';
            html += '<td><div class="schedule-date"><strong>' + slot.date + '</strong><br><small>' + slot.day + '</small></div></td>';
            html += '<td><strong>' + slot.activity_name + '</strong></td>';
            html += '<td>' + slot.time + '</td>';
            html += '<td>' + slot.capacity + '</td>';
            html += '<td><strong>' + slot.booked + '</strong></td>';
            html += '<td class="' + availableClass + '"><strong>' + slot.available + '</strong></td>';
            html += '<td><span class="schedule-status ' + statusClass + '">' + slot.status + '</span></td>';
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        $('#instructor-schedule-list').html(html);
    }

    /**
     * Load instructor students
     */
    function loadInstructorStudents() {
        $('#instructor-students-list').html('<div class="waza-loading">Loading students...</div>');

        $.ajax({
            url: waza_account.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_get_instructor_students',
                nonce: waza_account.nonce
            },
            success: function (response) {
                if (response.success) {
                    displayInstructorStudents(response.data.students);
                } else {
                    $('#instructor-students-list').html('<div class="waza-error">' + (response.data.message || 'Failed to load students') + '</div>');
                }
            },
            error: function () {
                $('#instructor-students-list').html('<div class="waza-error">Error loading students</div>');
            }
        });
    }

    /**
     * Display instructor students
     */
    function displayInstructorStudents(students) {
        if (!students || students.length === 0) {
            $('#instructor-students-list').html('<div class="waza-no-results">No students found</div>');
            return;
        }

        let html = '<div class="waza-students-table-wrapper"><table class="waza-students-table">';
        html += '<thead><tr>';
        html += '<th>Student Name</th>';
        html += '<th>Email</th>';
        html += '<th>Total Bookings</th>';
        html += '<th>Attendance</th>';
        html += '<th>Last Activity</th>';
        html += '</tr></thead>';
        html += '<tbody>';

        students.forEach(function (student) {
            const attendanceRate = student.total_bookings > 0 ? Math.round((student.total_attendance / student.total_bookings) * 100) : 0;
            const rateClass = attendanceRate >= 80 ? 'rate-high' : (attendanceRate >= 50 ? 'rate-medium' : 'rate-low');
            
            html += '<tr>';
            html += '<td><strong>' + student.name + '</strong></td>';
            html += '<td>' + student.email + '</td>';
            html += '<td>' + student.total_bookings + '</td>';
            html += '<td><span class="attendance-rate ' + rateClass + '">' + student.total_attendance + ' (' + attendanceRate + '%)</span></td>';
            html += '<td>' + student.last_activity + '</td>';
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        $('#instructor-students-list').html(html);
    }

    /**
     * Load instructor attendance records
     */
    function loadInstructorAttendance(filter) {
        $('#instructor-attendance-list').html('<div class="waza-loading">Loading attendance records...</div>');

        $.ajax({
            url: waza_account.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_get_instructor_attendance',
                filter: filter,
                nonce: waza_account.nonce
            },
            success: function (response) {
                if (response.success) {
                    displayInstructorAttendance(response.data.records);
                } else {
                    $('#instructor-attendance-list').html('<div class="waza-error">' + (response.data.message || 'Failed to load attendance') + '</div>');
                }
            },
            error: function () {
                $('#instructor-attendance-list').html('<div class="waza-error">Error loading attendance</div>');
            }
        });
    }

    /**
     * Display instructor attendance records
     */
    function displayInstructorAttendance(records) {
        if (!records || records.length === 0) {
            $('#instructor-attendance-list').html('<div class="waza-no-results">No attendance records found</div>');
            return;
        }

        let html = '<div class="waza-attendance-table-wrapper"><table class="waza-attendance-table">';
        html += '<thead><tr>';
        html += '<th>Student</th>';
        html += '<th>Activity</th>';
        html += '<th>Date</th>';
        html += '<th>Slot Time</th>';
        html += '<th>Check In</th>';
        html += '<th>Check Out</th>';
        html += '<th>Duration</th>';
        html += '<th>Status</th>';
        html += '</tr></thead>';
        html += '<tbody>';

        records.forEach(function (record) {
            const statusClass = record.status === 'completed' ? 'status-completed' : 'status-checked-in';
            const entryBadge = record.entry_method === 'qr' ? '<span class="method-badge qr">QR</span>' : '<span class="method-badge manual">Manual</span>';
            const exitBadge = record.exit_method ? (record.exit_method === 'qr' ? '<span class="method-badge qr">QR</span>' : (record.exit_method === 'auto' ? '<span class="method-badge auto">Auto</span>' : '<span class="method-badge manual">Manual</span>')) : '—';

            html += '<tr>';
            html += '<td><strong>' + record.student_name + '</strong></td>';
            html += '<td>' + record.activity_name + '</td>';
            html += '<td>' + record.date + '</td>';
            html += '<td>' + record.slot_time + '</td>';
            html += '<td>' + record.check_in + ' ' + entryBadge + '</td>';
            html += '<td>' + record.check_out + ' ' + exitBadge + '</td>';
            html += '<td><strong>' + record.duration + '</strong></td>';
            html += '<td><span class="attendance-status ' + statusClass + '">' + record.status + '</span></td>';
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        $('#instructor-attendance-list').html(html);
    }


    /**
     * Announcements Notification System
     */
    let readAnnouncementIds = JSON.parse(localStorage.getItem('waza_read_announcements') || '[]');

    // Initialize announcements bell
    function initAnnouncementsBell() {
        console.log('Initializing announcements bell...');
        console.log('Found bells:', $('.waza-announcements-bell').length);
        
        // Use event delegation for better compatibility
        $(document).on('click', '.waza-announcements-bell', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Bell clicked!');
            const target = $(this).data('target');
            console.log('Target:', target);
            loadAnnouncementsModal(target);
        });

        // Load and check for new announcements
        $('.waza-announcements-bell').each(function() {
            const target = $(this).data('target');
            checkNewAnnouncements(target, $(this));
        });

        // Close modal - use specific classes to avoid conflicts
        $(document).on('click', '.waza-announcements-close', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Announcements close button clicked');
            $('#waza-announcements-modal').fadeOut(200);
        });
        
        // Close on backdrop click
        $(document).on('click', '.waza-announcements-modal-overlay', function(e) {
            if (e.target === this || $(e.target).hasClass('waza-announcements-modal-overlay')) {
                console.log('Announcements backdrop clicked');
                $('#waza-announcements-modal').fadeOut(200);
            }
        });
    }

    function checkNewAnnouncements(target, bellButton) {
        console.log('Checking announcements for target:', target);
        $.ajax({
            url: waza_account.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_get_active_announcements',
                nonce: waza_account.nonce,
                target: target
            },
            success: function(response) {
                console.log('Announcements response:', response);
                if (response.success && response.data) {
                    // Handle both old format (array) and new format (object with announcements)
                    const announcements = Array.isArray(response.data) ? response.data : response.data.announcements;
                    const totalCount = response.data.count || announcements.length;
                    const userType = response.data.user_type || target;
                    
                    console.log('Announcements count:', totalCount, 'for user type:', userType);
                    
                    const newCount = announcements.filter(a => !readAnnouncementIds.includes(a.id)).length;
                    console.log('New (unread) announcements count:', newCount);
                    
                    if (newCount > 0) {
                        bellButton.addClass('has-new');
                        bellButton.find('.waza-announcements-count')
                            .text(newCount)
                            .show();
                    } else if (totalCount > 0) {
                        // Show total count even if all are read
                        bellButton.find('.waza-announcements-count')
                            .text(totalCount)
                            .show()
                            .css('opacity', '0.7'); // Dimmed for read announcements
                    } else {
                        bellButton.find('.waza-announcements-count').hide();
                    }
                } else {
                    console.error('Failed to load announcements:', response);
                    bellButton.find('.waza-announcements-count').hide();
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error loading announcements:', error, xhr.responseText);
            }
        });
    }

    function loadAnnouncementsModal(target) {
        console.log('Opening announcements modal for target:', target);
        $('#waza-announcements-modal').css('display', 'flex').hide().fadeIn(200);
        $('#waza-announcements-list').html('<div class="waza-loading"><div class="spinner"></div><p>Loading announcements...</p></div>');

        $.ajax({
            url: waza_account.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_get_active_announcements',
                nonce: waza_account.nonce,
                target: target
            },
            success: function(response) {
                console.log('Modal announcements response:', response);
                if (response.success) {
                    if (response.data && response.data.length > 0) {
                        console.log('Rendering', response.data.length, 'announcements');
                        renderAnnouncementsList(response.data);
                        
                        // Mark all as read
                        response.data.forEach(function(announcement) {
                            if (!readAnnouncementIds.includes(announcement.id)) {
                                readAnnouncementIds.push(announcement.id);
                            }
                        });
                        localStorage.setItem('waza_read_announcements', JSON.stringify(readAnnouncementIds));
                        
                        // Update bell
                        $('.waza-announcements-bell').removeClass('has-new');
                        $('.waza-announcements-count').hide();
                    } else {
                        console.log('No announcements available');
                        $('#waza-announcements-list').html('<div class="waza-empty-announcements"><h4>No announcements</h4><p>Check back later for updates</p></div>');
                    }
                } else {
                    console.error('Error response:', response);
                    $('#waza-announcements-list').html('<div class="waza-empty-announcements"><h4>Error</h4><p>' + (response.data && response.data.message ? response.data.message : 'Failed to load announcements') + '</p></div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error, status, xhr.responseText);
                $('#waza-announcements-list').html('<div class="waza-empty-announcements"><h4>Error loading announcements</h4><p>Please check console for details</p></div>');
            }
        });
    }

    function renderAnnouncementsList(announcements) {
        if (!announcements || announcements.length === 0) {
            $('#waza-announcements-list').html('<div class="waza-empty-announcements"><h4>No announcements</h4><p>Check back later for updates</p></div>');
            return;
        }

        let html = '';
        announcements.forEach(function(announcement) {
            const isUnread = !readAnnouncementIds.includes(announcement.id);
            const typeIcons = {
                'general': '',
                'urgent': '',
                'event': '',
                'maintenance': ''
            };
            const icon = typeIcons[announcement.announcement_type] || '';
            
            html += '<div class="waza-announcement-item ' + (isUnread ? 'unread' : '') + '">';
            html += '  <div class="waza-announcement-header">';
            html += '    <h4 class="waza-announcement-title">';
            html += '      <span class="waza-announcement-icon">' + icon + '</span>';
            html += '      ' + announcement.title;
            html += '    </h4>';
            html += '    <span class="waza-announcement-badge ' + announcement.announcement_type + '">' + announcement.announcement_type + '</span>';
            html += '  </div>';
            html += '  <div class="waza-announcement-message">' + announcement.message + '</div>';
            html += '  <div class="waza-announcement-meta">';
            html += '    <span class="waza-announcement-date"> ' + formatAnnouncementDate(announcement.created_at) + '</span>';
            if (announcement.expires_at && announcement.expires_at !== '0000-00-00 00:00:00') {
                html += '    <span> Expires: ' + formatAnnouncementDate(announcement.expires_at) + '</span>';
            }
            html += '  </div>';
            html += '</div>';
        });

        $('#waza-announcements-list').html(html);
    }

    function formatAnnouncementDate(dateString) {
        if (!dateString || dateString === '0000-00-00 00:00:00') return '';
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);

        if (minutes < 60) return minutes + ' minutes ago';
        if (hours < 24) return hours + ' hours ago';
        if (days < 7) return days + ' days ago';
        
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    // Initialize on document ready - multiple ways to ensure it runs
    $(document).ready(function() {
        console.log('Document ready - checking for announcements bell');
        if ($('.waza-announcements-bell').length) {
            console.log('Announcements bell found, initializing...');
            initAnnouncementsBell();
        } else {
            console.log('No announcements bell found on page');
        }
    });
    
    // Also try on window load as backup
    $(window).on('load', function() {
        if ($('.waza-announcements-bell').length && !$('.waza-announcements-bell').hasClass('initialized')) {
            console.log('Window loaded - initializing announcements (backup)');
            $('.waza-announcements-bell').addClass('initialized');
            initAnnouncementsBell();
        }
    });


})(jQuery);

