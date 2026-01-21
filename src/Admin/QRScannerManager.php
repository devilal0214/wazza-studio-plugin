<?php
/**
 * QR Scanner Manager
 * 
 * Handles QR code scanning for booking verification and attendance marking
 * 
 * @package WazaBooking\Admin
 */

namespace WazaBooking\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class QRScannerManager {
    
    /**
     * Initialize scanner
     */
    public function init() {
        // Add shortcode for QR scanner
        add_shortcode('waza_qr_scanner', [$this, 'scanner_shortcode']);
        
        // AJAX handlers
        add_action('wp_ajax_waza_verify_qr', [$this, 'verify_qr_code']);
        add_action('wp_ajax_waza_mark_attendance', [$this, 'mark_attendance']);
        add_action('wp_ajax_waza_get_scanner_stats', [$this, 'get_scanner_stats']);
    }
    
    /**
     * QR Scanner shortcode - Updated at 13:00
     */
    public function scanner_shortcode($atts) {
        error_log('=== QR SCANNER SHORTCODE CALLED AT ' . date('H:i:s') . ' ===');
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            error_log('Not logged in');
            return '<div class="waza-error">Please log in to access the QR scanner.</div>';
        }
        
        error_log('User is logged in - ID: ' . get_current_user_id());
        
        // Use WordPress capability check - administrators have manage_options capability
        $has_access = current_user_can('manage_options');
        
        error_log('Has manage_options capability: ' . ($has_access ? 'YES' : 'NO'));
        
        // Also check for instructor role if not admin
        if (!$has_access) {
            $current_user = wp_get_current_user();
            $has_access = in_array('waza_instructor', (array)$current_user->roles);
            error_log('Checking instructor role: ' . ($has_access ? 'YES' : 'NO'));
        }
        
        if (!$has_access) {
            error_log('ACCESS DENIED - User does not have required permissions');
            return '<div class="waza-error">Access denied. Administrator or instructor role required. [Code: 403]</div>';
        }
        
        error_log('ACCESS GRANTED - Rendering scanner interface');
        
        ob_start();
        ?>
        <div class="waza-qr-scanner-container">
            <h2><?php _e('Scan QR Code for Verification', 'waza-booking'); ?></h2>
            
            <!-- Today's Stats -->
            <div class="scanner-stats">
                <div class="stat-card">
                    <h3 id="total-checkins">0</h3>
                    <p>Total Check-ins</p>
                </div>
                <div class="stat-card">
                    <h3 id="total-checkouts">0</h3>
                    <p>Total Check-outs</p>
                </div>
                <div class="stat-card">
                    <h3 id="currently-active">0</h3>
                    <p>Currently Active</p>
                </div>
            </div>
            
            <div class="scanner-section">
                <div id="qr-reader" style="width: 100%; max-width: 600px; margin: 0 auto;"></div>
                
                <div class="manual-entry">
                    <h3><?php _e('Or Enter Booking Code Manually', 'waza-booking'); ?></h3>
                    <input type="text" id="manual-booking-code" placeholder="WB-00042">
                    <button onclick="verifyManualCode()" class="waza-button waza-button-primary">
                        <?php _e('Verify', 'waza-booking'); ?>
                    </button>
                </div>
            </div>
            
            <div id="scan-result" style="margin-top: 30px; display: none;">
                <div id="booking-details"></div>
                <div id="scan-actions"></div>
            </div>
        </div>
        
        <style>
        .waza-qr-scanner-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .scanner-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            font-size: 36px;
            margin: 0 0 10px 0;
            font-weight: 700;
        }
        
        .stat-card p {
            margin: 0;
            font-size: 14px;
            opacity: 0.9;
        }
        
        .scanner-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .manual-entry {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #f0f0f0;
            text-align: center;
        }
        
        #manual-booking-code {
            padding: 12px 20px;
            font-size: 16px;
            border: 2px solid #ddd;
            border-radius: 8px;
            width: 200px;
            text-align: center;
            text-transform: uppercase;
        }
        
        .booking-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin: 20px 0;
        }
        
        .booking-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .booking-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .info-item {
            padding: 15px;
            background: white;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        
        .info-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-attended {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 25px;
            justify-content: center;
        }
        
        .waza-button {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .waza-button-primary {
            background: #667eea;
            color: white;
        }
        
        .waza-button-success {
            background: #48bb78;
            color: white;
        }
        
        .waza-button-danger {
            background: #f56565;
            color: white;
        }
        
        .waza-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .waza-qr-scanner-container {
                margin: 20px 10px;
                padding: 15px;
            }
            
            .scanner-section {
                padding: 20px 15px;
            }
            
            #qr-reader {
                max-width: 100% !important;
            }
            
            .booking-info {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .info-item {
                padding: 12px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 10px;
            }
            
            .waza-button {
                width: 100%;
                padding: 14px 20px;
                font-size: 16px;
            }
            
            .booking-header {
                padding: 15px;
            }
            
            .booking-header h2 {
                font-size: 18px;
            }
        }
        </style>
        
        <!-- Include HTML5 QR Code Scanner Library -->
        <script src="https://unpkg.com/html5-qrcode"></script>
        
        <script>
        let html5QrCode;
        
        // Initialize QR Scanner
        function onScanSuccess(decodedText, decodedResult) {
            console.log('QR Code scanned:', decodedText);
            
            // Don't stop scanner - keep it running
            // html5QrCode.stop();
            
            // Parse QR data
            try {
                const qrData = JSON.parse(decodedText);
                verifyBooking(qrData);
            } catch (e) {
                // If not JSON, treat as booking code
                verifyBookingCode(decodedText);
            }
        }
        
        function onScanError(errorMessage) {
            // Ignore scan errors
        }
        
        // Start scanner
        html5QrCode = new Html5Qrcode("qr-reader");
        html5QrCode.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: 250 },
            onScanSuccess,
            onScanError
        ).catch(err => {
            console.error('Error starting QR scanner:', err);
            // If camera fails, show manual entry only
            document.querySelector('.manual-entry').style.display = 'block';
        });
        
        // Verify booking from QR data
        function verifyBooking(qrData) {
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'waza_verify_qr',
                    qr_data: JSON.stringify(qrData),
                    nonce: '<?php echo wp_create_nonce('waza_qr_verify'); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayBookingDetails(data.data);
                } else {
                    alert('Verification failed: ' + data.data.message);
                    restartScanner();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Verification error. Please try again.');
                restartScanner();
            });
        }
        
        // Verify booking from code
        function verifyBookingCode(code) {
            const bookingId = code.replace('WB-', '').replace(/^0+/, '');
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'waza_verify_qr',
                    booking_id: bookingId,
                    nonce: '<?php echo wp_create_nonce('waza_qr_verify'); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayBookingDetails(data.data);
                } else {
                    alert('Booking not found');
                    restartScanner();
                }
            });
        }
        
        // Manual verification
        function verifyManualCode() {
            const code = document.getElementById('manual-booking-code').value.trim();
            if (!code) {
                alert('Please enter a booking code');
                return;
            }
            verifyBookingCode(code);
        }
        
        // Display booking details
        function displayBookingDetails(booking) {
            // Insert result after the scanner div, not in separate result area
            const scannerSection = document.querySelector('.scanner-section');
            let resultDiv = document.getElementById('scan-result');
            
            // If result div doesn't exist after scanner, create it
            if (!resultDiv || !scannerSection.nextElementSibling || scannerSection.nextElementSibling.id !== 'scan-result') {
                resultDiv = document.createElement('div');
                resultDiv.id = 'scan-result';
                resultDiv.style.marginTop = '30px';
                scannerSection.insertAdjacentElement('afterend', resultDiv);
            }
            
            const detailsDiv = document.createElement('div');
            detailsDiv.id = 'booking-details';
            const actionsDiv = document.createElement('div');
            actionsDiv.id = 'scan-actions';
            
            detailsDiv.innerHTML = `
                <div class="booking-card">
                    <div class="booking-header">
                        <h2>Booking ${booking.booking_code}</h2>
                        <p>${booking.activity_name}</p>
                    </div>
                    
                    <div class="booking-info">
                        <div class="info-item">
                            <div class="info-label">Customer Name</div>
                            <div class="info-value">${booking.user_name}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value">${booking.user_email}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Phone</div>
                            <div class="info-value">${booking.user_phone && booking.user_phone !== 'N/A' ? booking.user_phone : 'Not provided'}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Date</div>
                            <div class="info-value">${booking.date}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Time</div>
                            <div class="info-value">${booking.time}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Participants</div>
                            <div class="info-value">${booking.quantity}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Booking Status</div>
                            <div class="info-value">
                                <span class="status-badge status-${booking.status}">${booking.status}</span>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Payment</div>
                            <div class="info-value">
                                <span class="status-badge status-${booking.payment_status}">${booking.payment_status}</span>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Amount Paid</div>
                            <div class="info-value">₹${parseFloat(booking.total_amount || 0).toFixed(2)}</div>
                        </div>
                    </div>
                    
                    ${booking.validation_message ? `
                    <div class="validation-notice" style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin-top: 20px; border-radius: 4px;">
                        <p style="margin: 0; color: #856404; font-weight: 500;">${booking.validation_message}</p>
                    </div>
                    ` : ''}
                </div>
            `;
            
            actionsDiv.innerHTML = `
                <div class="action-buttons">
                    <button class="waza-button waza-button-success" onclick="markAttendance(${booking.id})">
                        ✓ Mark Attendance
                    </button>
                    <button class="waza-button waza-button-primary" onclick="clearResult()">
                        Scan Another
                    </button>
                </div>
            `;
            
            resultDiv.innerHTML = '';
            resultDiv.appendChild(detailsDiv);
            resultDiv.appendChild(actionsDiv);
            resultDiv.style.display = 'block';
        }
        
        // Mark attendance
        function markAttendance(bookingId) {
            if (!confirm('Mark this booking as attended?')) {
                return;
            }
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'waza_mark_attendance',
                    booking_id: bookingId,
                    nonce: '<?php echo wp_create_nonce('waza_mark_attendance'); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Attendance marked successfully!');
                    restartScanner();
                } else {
                    alert('Error marking attendance: ' + data.data.message);
                }
            });
        }
        
        // Restart scanner
        function restartScanner() {
            document.getElementById('scan-result').style.display = 'none';
            document.getElementById('manual-booking-code').value = '';
            
            html5QrCode.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: 250 },
                onScanSuccess,
                onScanError
            );
        }
        
        // Clear result but keep scanner running
        function clearResult() {
            const resultDiv = document.getElementById('scan-result');
            if (resultDiv) {
                resultDiv.style.display = 'none';
                resultDiv.innerHTML = '';
            }
            document.getElementById('manual-booking-code').value = '';
        }
        
        // Load today's stats
        function loadStats() {
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'waza_get_scanner_stats',
                    nonce: '<?php echo wp_create_nonce('waza_qr_verify'); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('total-checkins').textContent = data.data.total_checkins || 0;
                    document.getElementById('total-checkouts').textContent = data.data.total_checkouts || 0;
                    document.getElementById('currently-active').textContent = data.data.currently_active || 0;
                }
            })
            .catch(error => console.error('Stats error:', error));
        }
        
        // Load stats on page load and refresh every 30 seconds
        loadStats();
        setInterval(loadStats, 30000);
        </script>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * AJAX: Verify QR code
     */
    public function verify_qr_code() {
        check_ajax_referer('waza_qr_verify', 'nonce');
        
        if (!current_user_can('manage_options') && !current_user_can('waza_instructor')) {
            wp_send_json_error(['message' => 'Access denied']);
        }
        
        global $wpdb;
        
        // Get booking ID from QR data or direct input
        $booking_id = 0;
        
        if (isset($_POST['qr_data'])) {
            $qr_data = json_decode(stripslashes($_POST['qr_data']), true);
            $booking_id = intval($qr_data['booking_id'] ?? 0);
        } elseif (isset($_POST['booking_id'])) {
            $booking_id = intval($_POST['booking_id']);
        }
        
        if (!$booking_id) {
            wp_send_json_error(['message' => 'Invalid booking ID']);
        }
        
        // Get booking details
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT b.*, s.start_datetime, s.end_datetime, s.activity_id,
                   p.post_title as activity_name
            FROM {$wpdb->prefix}waza_bookings b
            LEFT JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
            LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
            WHERE b.id = %d
        ", $booking_id));
        
        if (!$booking) {
            wp_send_json_error(['message' => 'Booking not found']);
        }
        
        // Convert UTC datetime to site timezone (Asia/Kolkata)
        $start_dt = new \DateTime($booking->start_datetime, new \DateTimeZone('UTC'));
        $start_dt->setTimezone(new \DateTimeZone(wp_timezone_string()));
        $end_dt = new \DateTime($booking->end_datetime, new \DateTimeZone('UTC'));
        $end_dt->setTimezone(new \DateTimeZone(wp_timezone_string()));
        
        // Check if slot is today
        $slot_date = $start_dt->format('Y-m-d');
        $today = current_time('Y-m-d');
        
        // Add validation message if not today
        $validation_message = '';
        if ($slot_date < $today) {
            $validation_message = '\u23f0 This booking is for ' . $start_dt->format('F j, Y') . ' (past date).';
        } elseif ($slot_date > $today) {
            $validation_message = '\u23f0 This booking is for ' . $start_dt->format('F j, Y') . ' (future date). Slot starts at: ' . $start_dt->format('g:i A');
        }
        
        wp_send_json_success([
            'id' => $booking->id,
            'booking_code' => 'WB-' . str_pad($booking->id, 5, '0', STR_PAD_LEFT),
            'user_name' => $booking->user_name ?: 'Guest',
            'user_email' => $booking->user_email ?: 'No email',
            'user_phone' => $booking->user_phone ?: '',
            'activity_name' => $booking->activity_name,
            'date' => $start_dt->format('F j, Y'),
            'time' => $start_dt->format('g:i A') . ' - ' . $end_dt->format('g:i A'),
            'quantity' => $booking->quantity ?: 1,
            'status' => $booking->booking_status,
            'payment_status' => $booking->payment_status,
            'total_amount' => $booking->total_amount ?: 0,
            'validation_message' => $validation_message,
            'is_today' => $slot_date === $today
        ]);
    }
    
    /**
     * AJAX: Mark attendance
     */
    public function mark_attendance() {
        check_ajax_referer('waza_mark_attendance', 'nonce');
        
        if (!current_user_can('manage_options') && !current_user_can('waza_instructor')) {
            wp_send_json_error(['message' => 'Access denied']);
        }
        
        $booking_id = intval($_POST['booking_id'] ?? 0);
        
        if (!$booking_id) {
            wp_send_json_error(['message' => 'Invalid booking ID']);
        }
        
        global $wpdb;
        
        // Update booking status to attended
        $updated = $wpdb->update(
            $wpdb->prefix . 'waza_bookings',
            [
                'booking_status' => 'attended',
                'attended_at' => current_time('mysql')
            ],
            ['id' => $booking_id],
            ['%s', '%s'],
            ['%d']
        );
        
        if ($updated === false) {
            wp_send_json_error(['message' => 'Database error']);
        }
        
        wp_send_json_success(['message' => 'Attendance marked successfully']);
    }
    
    /**
     * AJAX: Get today's scanner stats
     */
    public function get_scanner_stats() {
        check_ajax_referer('waza_qr_verify', 'nonce');
        
        if (!current_user_can('manage_options') && !current_user_can('waza_instructor')) {
            wp_send_json_error(['message' => 'Access denied']);
        }
        
        global $wpdb;
        $today = current_time('Y-m-d');
        
        // Total check-ins today
        $total_checkins = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}waza_attendance
            WHERE DATE(check_in_time) = %s
            AND attendance_status = 'present'
        ", $today));
        
        // Total check-outs today
        $total_checkouts = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}waza_attendance
            WHERE DATE(check_out_time) = %s
            AND check_out_time IS NOT NULL
        ", $today));
        
        // Currently active (checked in but not checked out)
        $currently_active = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}waza_attendance
            WHERE DATE(check_in_time) = %s
            AND attendance_status = 'present'
            AND (check_out_time IS NULL OR DATE(check_out_time) != %s)
        ", $today, $today));
        
        wp_send_json_success([
            'total_checkins' => intval($total_checkins),
            'total_checkouts' => intval($total_checkouts),
            'currently_active' => intval($currently_active)
        ]);
    }
}
