/**
 * Enhanced Attendance Scanner JavaScript
 * 
 * Handles QR code scanning, manual search, and attendance marking
 */

(function($) {
    'use strict';
    
    let html5QrCode = null;
    let lastScannedCode = null;
    let scanCooldown = false;
    
    $(document).ready(function() {
        initializeScanner();
        loadTodayStats();
        
        // Tab switching
        $('.waza-tab-btn').on('click', function() {
            const tab = $(this).data('tab');
            switchTab(tab);
        });
        
        // Start scanner button
        $('#start-scanner').on('click', startScanning);
        $('#stop-scanner').on('click', stopScanning);
        
        // Manual search
        $('#search-booking-btn').on('click', performManualSearch);
        $('#booking-search-input').on('keypress', function(e) {
            if (e.which === 13) {
                performManualSearch();
            }
        });
        
        // Refresh stats every 30 seconds
        setInterval(loadTodayStats, 30000);
    });
    
    /**
     * Initialize scanner
     */
    function initializeScanner() {
        if (typeof Html5Qrcode === 'undefined') {
            console.error('HTML5 QR Code library not loaded');
            return;
        }
        
        html5QrCode = new Html5Qrcode("qr-reader");
    }
    
    /**
     * Start QR code scanning
     */
    function startScanning() {
        if (!html5QrCode) {
            showNotification('Scanner not initialized', 'error');
            return;
        }
        
        const config = {
            fps: 10,
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0
        };
        
        html5QrCode.start(
            { facingMode: "environment" },
            config,
            onScanSuccess,
            onScanError
        ).then(() => {
            $('#start-scanner').hide();
            $('#stop-scanner').show();
            $('#qr-reader-status p').text('Scanner active - Ready to scan');
        }).catch(err => {
            console.error('Failed to start scanner:', err);
            showNotification('Failed to start camera: ' + err, 'error');
        });
    }
    
    /**
     * Stop QR code scanning
     */
    function stopScanning() {
        if (!html5QrCode) return;
        
        html5QrCode.stop().then(() => {
            $('#start-scanner').show();
            $('#stop-scanner').hide();
            $('#qr-reader-status p').text('Scanner stopped');
        }).catch(err => {
            console.error('Failed to stop scanner:', err);
        });
    }
    
    /**
     * QR code scan success callback
     */
    function onScanSuccess(decodedText, decodedResult) {
        // Prevent duplicate scans
        if (scanCooldown || decodedText === lastScannedCode) {
            return;
        }
        
        lastScannedCode = decodedText;
        scanCooldown = true;
        
        // Play success sound (optional)
        playBeep();
        
        // Process the scanned code
        processQRCode(decodedText);
        
        // Reset cooldown after 3 seconds
        setTimeout(() => {
            scanCooldown = false;
            lastScannedCode = null;
        }, 3000);
    }
    
    /**
     * QR code scan error callback
     */
    function onScanError(errorMessage) {
        // Ignore errors - they happen continuously while scanning
    }
    
    /**
     * Process scanned QR code
     */
    function processQRCode(qrData) {
        $('#qr-reader-status p').html('<span class="spinner is-active"></span> Processing...');
        
        $.ajax({
            url: wazaScannerData.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_scan_qr_code',
                nonce: wazaScannerData.nonce,
                qr_data: qrData
            },
            success: function(response) {
                if (response.success) {
                    displayValidBooking(response.data);
                } else {
                    displayError(response.data);
                }
                $('#qr-reader-status p').text('Ready to scan next QR code');
            },
            error: function() {
                showNotification('Failed to process QR code', 'error');
                $('#qr-reader-status p').text('Error - Ready to try again');
            }
        });
    }
    
    /**
     * Perform manual booking search
     */
    function performManualSearch() {
        const searchTerm = $('#booking-search-input').val().trim();
        
        if (!searchTerm) {
            showNotification('Please enter a booking ID or QR token', 'warning');
            return;
        }
        
        $('#search-booking-btn').prop('disabled', true).html('<span class="spinner is-active"></span> Searching...');
        
        $.ajax({
            url: wazaScannerData.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_search_booking',
                nonce: wazaScannerData.nonce,
                search_term: searchTerm
            },
            success: function(response) {
                if (response.success) {
                    displayValidBooking(response.data);
                } else {
                    displayError(response.data);
                }
            },
            error: function() {
                showNotification('Search failed', 'error');
            },
            complete: function() {
                $('#search-booking-btn').prop('disabled', false).html('<span class="dashicons dashicons-search"></span> Search');
            }
        });
    }
    
    /**
     * Display valid booking details
     */
    function displayValidBooking(data) {
        const isGroupBooking = data.attendance_info.is_group_booking;
        const remaining = data.attendance_info.remaining;
        
        let html = `
            <div class="waza-scan-result success">
                <div class="result-header">
                    <span class="result-icon">✅</span>
                    <h2>${data.message}</h2>
                </div>
                
                <div class="result-content">
                    <div class="student-info">
                        <h3>Student Information</h3>
                        <table class="widefat">
                            <tr>
                                <th>Name:</th>
                                <td><strong>${data.user.display_name}</strong></td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td>${data.user.email}</td>
                            </tr>
                            <tr>
                                <th>Phone:</th>
                                <td>${data.user.phone || 'N/A'}</td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="booking-info">
                        <h3>Booking Details</h3>
                        <table class="widefat">
                            <tr>
                                <th>Booking ID:</th>
                                <td>#${data.booking.id}</td>
                            </tr>
                            <tr>
                                <th>Activity:</th>
                                <td><strong>${data.booking.activity}</strong></td>
                            </tr>
                            <tr>
                                <th>Date:</th>
                                <td>${data.booking.slot_date}</td>
                            </tr>
                            <tr>
                                <th>Time:</th>
                                <td>${data.booking.slot_time}</td>
                            </tr>
                            <tr>
                                <th>Payment:</th>
                                <td><span class="status-badge success">${data.booking.payment_status}</span></td>
                            </tr>
                            <tr>
                                <th>Amount:</th>
                                <td>₹${data.booking.total_amount}</td>
                            </tr>
                        </table>
                    </div>
                    
                    ${isGroupBooking ? `
                    <div class="attendance-progress">
                        <h3>Group Booking Progress</h3>
                        <div class="progress-info">
                            <p class="progress-message">${data.attendance_info.progress_message}</p>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: ${(data.attendance_info.already_marked / data.attendance_info.total_attendees) * 100}%"></div>
                            </div>
                            <p class="progress-text">
                                <strong>${data.attendance_info.already_marked}</strong> of 
                                <strong>${data.attendance_info.total_attendees}</strong> attendees checked in
                                <br>
                                <span class="remaining-count">${remaining} remaining</span>
                            </p>
                        </div>
                    </div>
                    ` : ''}
                </div>
                
                <div class="result-actions">
                    <button class="button button-primary button-large mark-attendance-btn" 
                            data-booking-id="${data.booking.id}"
                            data-slot-id="${data.slot_id}">
                        <span class="dashicons dashicons-yes"></span>
                        Mark Attendance (${remaining} remaining)
                    </button>
                    <button class="button button-large cancel-btn">
                        <span class="dashicons dashicons-no"></span>
                        Cancel
                    </button>
                </div>
            </div>
        `;
        
        $('#scan-result-area').html(html).slideDown();
        
        // Bind mark attendance button
        $('.mark-attendance-btn').on('click', function() {
            const bookingId = $(this).data('booking-id');
            const slotId = $(this).data('slot-id');
            markAttendance(bookingId, slotId);
        });
        
        // Bind cancel button
        $('.cancel-btn').on('click', function() {
            $('#scan-result-area').slideUp();
        });
    }
    
    /**
     * Display error message
     */
    function displayError(data) {
        let html = `
            <div class="waza-scan-result error">
                <div class="result-header">
                    <span class="result-icon">${getIconForErrorType(data.type)}</span>
                    <h2>${data.message}</h2>
                </div>
                
                <div class="result-content">
                    <div class="error-details">
                        <p class="details-text">${data.details}</p>
                        ${data.help_text ? `<p class="help-text"><em>${data.help_text}</em></p>` : ''}
                    </div>
                    
                    ${getErrorSpecificInfo(data)}
                </div>
                
                <div class="result-actions">
                    <button class="button button-large close-error-btn">
                        <span class="dashicons dashicons-dismiss"></span>
                        Close
                    </button>
                </div>
            </div>
        `;
        
        $('#scan-result-area').html(html).slideDown();
        
        $('.close-error-btn').on('click', function() {
            $('#scan-result-area').slideUp();
        });
    }
    
    /**
     * Get icon for error type
     */
    function getIconForErrorType(type) {
        const icons = {
            'not_found': '❌',
            'payment_pending': '💳',
            'not_confirmed': '⚠️',
            'past_slot': '⏰',
            'future_slot': '📅',
            'already_attended': '✅'
        };
        return icons[type] || '❌';
    }
    
    /**
     * Get error-specific additional information
     */
    function getErrorSpecificInfo(data) {
        switch(data.type) {
            case 'past_slot':
            case 'future_slot':
                return `
                    <div class="slot-info">
                        <table class="widefat">
                            <tr>
                                <th>Activity:</th>
                                <td>${data.activity}</td>
                            </tr>
                            <tr>
                                <th>Scheduled Date:</th>
                                <td>${data.slot_date}</td>
                            </tr>
                            <tr>
                                <th>Scheduled Time:</th>
                                <td>${data.slot_time}</td>
                            </tr>
                            ${data.days_remaining ? `
                            <tr>
                                <th>Days Remaining:</th>
                                <td><strong>${data.days_remaining}</strong></td>
                            </tr>
                            ` : ''}
                        </table>
                    </div>
                `;
            
            case 'payment_pending':
                return `
                    <div class="payment-info">
                        <table class="widefat">
                            <tr>
                                <th>Booking ID:</th>
                                <td>#${data.booking_id}</td>
                            </tr>
                            <tr>
                                <th>Payment Status:</th>
                                <td><span class="status-badge error">${data.payment_status}</span></td>
                            </tr>
                            <tr>
                                <th>Amount Due:</th>
                                <td><strong>₹${data.amount}</strong></td>
                            </tr>
                        </table>
                    </div>
                `;
            
            case 'already_attended':
                return `
                    <div class="attendance-info">
                        <table class="widefat">
                            <tr>
                                <th>Booking ID:</th>
                                <td>#${data.booking_id}</td>
                            </tr>
                            <tr>
                                <th>Attended Count:</th>
                                <td>${data.attended_count} of ${data.total_count}</td>
                            </tr>
                            <tr>
                                <th>First Check-in:</th>
                                <td>${data.first_check_in || 'N/A'}</td>
                            </tr>
                        </table>
                    </div>
                `;
            
            default:
                return '';
        }
    }
    
    /**
     * Mark attendance
     */
    function markAttendance(bookingId, slotId) {
        $('.mark-attendance-btn').prop('disabled', true).html('<span class="spinner is-active"></span> Marking...');
        
        $.ajax({
            url: wazaScannerData.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_mark_single_attendance',
                nonce: wazaScannerData.nonce,
                booking_id: bookingId,
                slot_id: slotId
            },
            success: function(response) {
                if (response.success) {
                    displayAttendanceSuccess(response.data);
                    loadTodayStats();
                } else {
                    showNotification(response.data.message || 'Failed to mark attendance', 'error');
                }
            },
            error: function() {
                showNotification('Failed to mark attendance', 'error');
            },
            complete: function() {
                $('.mark-attendance-btn').prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Mark Attendance');
            }
        });
    }
    
    /**
     * Display attendance success
     */
    function displayAttendanceSuccess(data) {
        let html = `
            <div class="waza-scan-result success-complete">
                <div class="result-header">
                    <span class="result-icon success-checkmark">✅</span>
                    <h2>Attendance Marked Successfully!</h2>
                </div>
                
                <div class="result-content">
                    <div class="success-info">
                        <p class="student-name"><strong>${data.student_name}</strong></p>
                        <p class="check-in-time">Checked in at <strong>${data.check_in_time}</strong></p>
                        
                        ${data.attendance_progress.total > 1 ? `
                        <div class="group-progress">
                            <p class="progress-update">${data.attendance_progress.progress_message}</p>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: ${(data.attendance_progress.marked / data.attendance_progress.total) * 100}%"></div>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                </div>
                
                <div class="result-actions">
                    <button class="button button-primary button-large continue-btn">
                        <span class="dashicons dashicons-arrow-right-alt"></span>
                        Scan Next Student
                    </button>
                </div>
            </div>
        `;
        
        $('#scan-result-area').html(html);
        
        $('.continue-btn').on('click', function() {
            $('#scan-result-area').slideUp();
            $('#booking-search-input').val('');
        });
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            $('#scan-result-area').slideUp();
        }, 5000);
    }
    
    /**
     * Load today's statistics
     */
    function loadTodayStats() {
        $.ajax({
            url: wazaScannerData.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_get_today_stats',
                nonce: wazaScannerData.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateStatsDisplay(response.data.stats);
                    updateRecentScans(response.data.recent_scans);
                }
            }
        });
    }
    
    /**
     * Update stats display
     */
    function updateStatsDisplay(stats) {
        $('#total-checked-in').text(stats.total_checked_in);
        $('#total-expected').text(stats.total_expected);
        $('#attendance-rate').text(stats.attendance_rate + '%');
        $('#active-slots').text(stats.active_slots);
    }
    
    /**
     * Update recent scans list
     */
    function updateRecentScans(scans) {
        if (!scans || scans.length === 0) {
            $('#recent-scans-list').html('<p>No scans yet today</p>');
            return;
        }
        
        let html = '<table class="widefat striped"><thead><tr>' +
                   '<th>Student</th><th>Activity</th><th>Check-in Time</th><th>Time Ago</th>' +
                   '</tr></thead><tbody>';
        
        scans.forEach(scan => {
            html += `<tr>
                <td><strong>${scan.student_name}</strong></td>
                <td>${scan.activity}</td>
                <td>${scan.check_in_time}</td>
                <td>${scan.time_ago}</td>
            </tr>`;
        });
        
        html += '</tbody></table>';
        $('#recent-scans-list').html(html);
    }
    
    /**
     * Switch tabs
     */
    function switchTab(tab) {
        $('.waza-tab-btn').removeClass('active');
        $('[data-tab="' + tab + '"].waza-tab-btn').addClass('active');
        
        $('.waza-tab-content').removeClass('active');
        $('[data-tab="' + tab + '"].waza-tab-content').addClass('active');
        
        if (tab === 'recent-scans') {
            loadTodayStats();
        }
    }
    
    /**
     * Show notification
     */
    function showNotification(message, type) {
        // You can implement a toast notification system here
        alert(message);
    }
    
    /**
     * Play beep sound on successful scan
     */
    function playBeep() {
        // Optional: Add audio beep
        const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBTGH0fPTgjMGHm7A7+OZSA0PVqzn77BdGAg+ltryxnMpBSl+zPLaizsIGGS57OihUBELTKXh8bllHAU2jdXyzn0vBSF1xe/glEILElyx6OywWxkIOZPY88p2KwUme8rx3I4+CRdqvu7mnkwPC1Ck4/K2YhwGOI/X88x6LQUjdsXv45NCDBFZR24');
        audio.volume = 0.3;
        audio.play().catch(e => {/* Ignore */});
    }
    
})(jQuery);
