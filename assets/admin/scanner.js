/**
 * Waza Booking - QR Scanner JavaScript
 */
(function ($) {
    'use strict';

    console.log('=== Scanner.js loaded ===');
    console.log('wazaScanner object:', typeof wazaScanner !== 'undefined' ? wazaScanner : 'UNDEFINED');

    let html5QrcodeScanner = null;
    let currentBookingId = null;

    $(document).ready(function () {
        console.log('Document ready, scanner container exists:', $('.waza-scanner-container').length > 0);
        if ($('.waza-scanner-container').length) {
            init();
        }
    });

    function init() {
        console.log('Initializing scanner...');
        initTabs();
        initScanner();
        initManualEntry();
        loadTodayStats();
    }

    function initTabs() {
        $('.waza-tab-btn').on('click', function () {
            const tab = $(this).data('tab');
            
            $('.waza-tab-btn').removeClass('active');
            $(this).addClass('active');
            
            $('.waza-tab-content').removeClass('active');
            $(`.waza-tab-content[data-tab="${tab}"]`).addClass('active');
            
            if (tab === 'scanner' && !html5QrcodeScanner) {
                startScanner();
            } else if (tab === 'stats') {
                loadTodayStats();
            }
        });
    }

    function initScanner() {
        setTimeout(() => {
            startScanner();
        }, 500);
    }

    function startScanner() {
        if (html5QrcodeScanner) {
            return;
        }

        html5QrcodeScanner = new Html5Qrcode("qr-reader");
        
        const config = {
            fps: 10,
            qrbox: { width: 250, height: 250 }
        };
        
        html5QrcodeScanner.start(
            { facingMode: "environment" },
            config,
            onScanSuccess,
            onScanFailure
        ).catch(err => {
            console.error('Failed to start scanner:', err);
            $('#qr-reader-status').html('<p class="error">Failed to start camera. Please check permissions.</p>');
        });
    }

    function onScanSuccess(decodedText, decodedResult) {
        if (html5QrcodeScanner) {
            html5QrcodeScanner.pause(true);
        }
        verifyQRCode(decodedText);
    }

    function onScanFailure(error) {
        // Ignore
    }

    function verifyQRCode(qrData) {
        console.log('=== Verifying QR Code ===');
        console.log('QR Data:', qrData);
        console.log('Ajax URL:', typeof wazaScanner !== 'undefined' ? wazaScanner.ajax_url : 'wazaScanner undefined');
        console.log('Nonce:', typeof wazaScanner !== 'undefined' ? wazaScanner.nonce : 'wazaScanner undefined');
        
        $.ajax({
            url: wazaScanner.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_verify_qr',
                nonce: wazaScanner.nonce,
                qr_data: qrData
            },
            beforeSend: function() {
                console.log('Sending AJAX request...');
            },
            success: function (response) {
                console.log('AJAX Success:', response);
                if (response.success) {
                    showStudentDetails(response.data);
                } else {
                    showError(response.data || 'Invalid QR code');
                    if (html5QrcodeScanner) {
                        setTimeout(() => html5QrcodeScanner.resume(), 2000);
                    }
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', xhr, status, error);
                console.error('Response Text:', xhr.responseText);
                showError('Error verifying QR code');
                if (html5QrcodeScanner) {
                    setTimeout(() => html5QrcodeScanner.resume(), 2000);
                }
            }
        });
    }

    function showStudentDetails(data) {
        const booking = data.booking;
        const user = data.user;
        const attendance = data.attendance;
        const canMarkAttendance = data.can_mark_attendance;
        const timeMessage = data.time_message;
        
        currentBookingId = booking.id;
        
        // Clear previous results
        $('#booking-details-container').empty();
        
        let html = `
            <div class="booking-detail-card" style="background: #fff; border: 2px solid #28a745; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #e0e0e0; padding-bottom: 15px;">
                    <h3 style="margin: 0; color: #28a745;">✓ Booking Verified</h3>
                    <button onclick="clearBookingDetails()" style="background: #dc3545; color: white; border: none; padding: 5px 15px; border-radius: 4px; cursor: pointer; font-size: 20px;">×</button>
                </div>
                
                <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 6px;">
                    <div style="width: 60px; height: 60px; background: #007bff; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: bold;">
                        ${user.display_name.charAt(0).toUpperCase()}
                    </div>
                    <div>
                        <h4 style="margin: 0 0 5px 0; font-size: 18px;">${user.display_name}</h4>
                        <p style="margin: 0; color: #666; font-size: 14px;"><strong>Email:</strong> ${user.email}</p>
                        <p style="margin: 0; color: #666; font-size: 14px;"><strong>Phone:</strong> ${user.phone || 'N/A'}</p>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 20px;">
                    <div style="padding: 10px; background: #e7f3ff; border-left: 3px solid #007bff; border-radius: 4px;">
                        <div style="font-size: 12px; color: #666; margin-bottom: 3px;">Activity</div>
                        <div style="font-weight: bold; color: #333;">${booking.activity_title}</div>
                    </div>
                    <div style="padding: 10px; background: #fff3cd; border-left: 3px solid #ffc107; border-radius: 4px;">
                        <div style="font-size: 12px; color: #666; margin-bottom: 3px;">Booking Code</div>
                        <div style="font-weight: bold; color: #333;">${booking.booking_code || 'WB-' + String(booking.id).padStart(5, '0')}</div>
                    </div>
                    <div style="padding: 10px; background: #d4edda; border-left: 3px solid #28a745; border-radius: 4px;">
                        <div style="font-size: 12px; color: #666; margin-bottom: 3px;">Slot Time</div>
                        <div style="font-weight: bold; color: #333;">${formatTime(booking.start_datetime)} - ${formatTime(booking.end_datetime)}</div>
                    </div>
                    <div style="padding: 10px; background: #f8d7da; border-left: 3px solid #dc3545; border-radius: 4px;">
                        <div style="font-size: 12px; color: #666; margin-bottom: 3px;">Participants</div>
                        <div style="font-weight: bold; color: #333;">${booking.attendees_count || 1}</div>
                    </div>
                    <div style="padding: 10px; background: #d1ecf1; border-left: 3px solid #17a2b8; border-radius: 4px;">
                        <div style="font-size: 12px; color: #666; margin-bottom: 3px;">Amount Paid</div>
                        <div style="font-weight: bold; color: #333;">₹${parseFloat(booking.total_amount).toFixed(2)}</div>
                    </div>
                    <div style="padding: 10px; background: #e2e3e5; border-left: 3px solid #6c757d; border-radius: 4px;">
                        <div style="font-size: 12px; color: #666; margin-bottom: 3px;">Status</div>
                        <div style="font-weight: bold; color: #333; text-transform: uppercase;">${booking.booking_status}</div>
                    </div>
                </div>
                
                <div style="padding: 15px; background: ${data.has_checked_in ? '#d4edda' : (canMarkAttendance ? '#fff3cd' : '#f8d7da')}; border-radius: 6px; text-align: center;">
                    ${data.has_checked_in ? `
                        <div style="color: #155724; font-size: 16px; font-weight: bold;">
                            <span style="font-size: 24px;">✓</span> Attendance Already Marked
                        </div>
                        <div style="color: #155724; margin-top: 5px;">Check-in: ${formatTime(attendance.check_in_time)}</div>
                    ` : canMarkAttendance ? `
                        <button onclick="markAttendanceInline(${booking.id})" style="background: #28a745; color: white; border: none; padding: 12px 30px; border-radius: 6px; font-size: 16px; font-weight: bold; cursor: pointer; width: 100%;">
                            <span style="font-size: 20px;">✓</span> Mark Attendance Now
                        </button>
                    ` : `
                        <div style="color: #856404; font-size: 16px; font-weight: bold;">
                            <span style="font-size: 24px;">⏰</span> ${timeMessage}
                        </div>
                        ${data.slot_start_time ? `<div style="color: #856404; margin-top: 5px; font-size: 14px;">Slot starts at: ${data.slot_start_time}</div>` : ''}
                    `}
                </div>
            </div>
        `;
        
        $('#booking-details-container').html(html).show();
        
        // Scroll to details
        $('html, body').animate({
            scrollTop: $('#booking-details-container').offset().top - 100
        }, 500);
    }
    
    window.clearBookingDetails = function() {
        $('#booking-details-container').empty().hide();
        currentBookingId = null;
        $('#manual-token').val('');
    };
    
    window.markAttendanceInline = function(bookingId) {
        if (!confirm('Mark attendance for this booking?')) {
            return;
        }
        
        $.ajax({
            url: wazaScanner.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_mark_attendance',
                nonce: wazaScanner.nonce,
                booking_id: bookingId,
                action_type: 'entry',
                method: 'manual'
            },
            beforeSend: function() {
                $('button[onclick*="markAttendanceInline"]').prop('disabled', true).html('Processing...');
            },
            success: function(response) {
                if (response.success) {
                    showSuccess('Attendance marked successfully!');
                    // Refresh the details
                    setTimeout(() => {
                        $('#manual-token').val(bookingId);
                        $('#verify-manual-btn').click();
                    }, 1000);
                } else {
                    showError(response.data || 'Failed to mark attendance');
                    $('button[onclick*="markAttendanceInline"]').prop('disabled', false).html('<span style="font-size: 20px;">✓</span> Mark Attendance Now');
                }
            },
            error: function() {
                showError('Error marking attendance');
                $('button[onclick*="markAttendanceInline"]').prop('disabled', false).html('<span style="font-size: 20px;">✓</span> Mark Attendance Now');
            }
        });
    };

    window.markAttendance = function(bookingId, actionType) {
        // Deprecated - use markAttendanceInline instead
        markAttendanceInline(bookingId);
    };

    window.closeStudentModal = function() {
        $('#student-details-modal').fadeOut(300);
        currentBookingId = null;
        
        if (html5QrcodeScanner) {
            setTimeout(() => {
                try {
                    html5QrcodeScanner.resume();
                } catch (e) {}
            }, 500);
        }
    };

    function initManualEntry() {
        $('#verify-manual-btn').on('click', function () {
            const token = $('#manual-token').val().trim();
            if (!token) {
                showError('Please enter a booking ID or QR token');
                return;
            }
            verifyQRCode(token);
        });
        
        $('#manual-token').on('keypress', function (e) {
            if (e.which === 13) {
                $('#verify-manual-btn').click();
            }
        });
    }

    function loadTodayStats() {
        $('#attendance-stats').html('<p class="loading">Loading statistics...</p>');
        
        $.ajax({
            url: wazaScanner.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_get_attendance_stats',
                nonce: wazaScanner.nonce
            },
            success: function (response) {
                if (response.success) {
                    displayStats(response.data);
                } else {
                    $('#attendance-stats').html('<p class="error">Failed to load statistics</p>');
                }
            },
            error: function () {
                $('#attendance-stats').html('<p class="error">Error loading statistics</p>');
            }
        });
    }

    function displayStats(data) {
        const stats = data.stats;
        const slots = data.slots;
        
        let html = `
            <div class="stats-overview">
                <div class="stat-card">
                    <div class="stat-value">${stats.total_checked_in}</div>
                    <div class="stat-label">Total Check-ins</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">${stats.total_checked_out}</div>
                    <div class="stat-label">Total Check-outs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">${stats.currently_active}</div>
                    <div class="stat-label">Currently Active</div>
                </div>
            </div>
            
            <div class="slots-table">
                <h4>Today's Slots</h4>
                <table class="waza-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Activity</th>
                            <th>Bookings</th>
                            <th>Attendees</th>
                            <th>Checked In</th>
                            <th>%</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        if (slots.length === 0) {
            html += '<tr><td colspan="6" class="text-center">No slots scheduled for today</td></tr>';
        } else {
            slots.forEach(slot => {
                const percentage = slot.total_attendees > 0 ? 
                    Math.round((slot.checked_in / slot.total_attendees) * 100) : 0;
                
                html += `
                    <tr>
                        <td>${formatTime(slot.start_datetime)}</td>
                        <td>${slot.activity_title}</td>
                        <td>${slot.total_bookings || 0}</td>
                        <td>${slot.total_attendees || 0}</td>
                        <td>${slot.checked_in || 0}</td>
                        <td><span class="percentage">${percentage}%</span></td>
                    </tr>
                `;
            });
        }
        
        html += '</tbody></table></div>';
        $('#attendance-stats').html(html);
    }

    function formatTime(datetime) {
        if (!datetime) return '';
        const date = new Date(datetime);
        return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
    }

    function showSuccess(message) {
        const toast = $(`
            <div class="waza-toast success">
                <span class="toast-icon">✓</span>
                <span class="toast-message">${message}</span>
            </div>
        `);
        
        $('body').append(toast);
        setTimeout(() => toast.addClass('show'), 100);
        setTimeout(() => {
            toast.removeClass('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    function showError(message) {
        const toast = $(`
            <div class="waza-toast error">
                <span class="toast-icon">✗</span>
                <span class="toast-message">${message}</span>
            </div>
        `);
        
        $('body').append(toast);
        setTimeout(() => toast.addClass('show'), 100);
        setTimeout(() => {
            toast.removeClass('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    $('.waza-modal-close, .waza-modal-overlay').on('click', function () {
        closeStudentModal();
    });

})(jQuery);
