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
                        window.location.reload();
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
        const username = form.find('#username').val().trim();
        const password = form.find('#password').val().trim();

        if (!username) {
            showMessage('Please enter your username or email.', 'error');
            return false;
        }

        if (!password) {
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

})(jQuery);