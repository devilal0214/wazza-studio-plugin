<?php
/**
 * Booking Confirmation Template
 * 
 * Displays booking confirmation with QR code and details after successful payment
 * 
 * @package WazaBooking
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get booking ID or rental ID from URL (with verification hash for security)
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
$rental_id = isset($_GET['rental_id']) ? intval($_GET['rental_id']) : 0;
$verify_hash = isset($_GET['verify']) ? sanitize_text_field($_GET['verify']) : '';

error_log('Booking Confirmation Template - booking_id: ' . $booking_id . ', rental_id: ' . $rental_id . ', verify: ' . $verify_hash);

if (!$booking_id && !$rental_id) {
    error_log('ERROR: Neither booking_id nor rental_id found');
    echo '<div class="waza-error-message">';
    echo '<h2>' . esc_html__('Invalid Request', 'waza-booking') . '</h2>';
    echo '<p>' . esc_html__('No booking or rental information found.', 'waza-booking') . '</p>';
    echo '<a href="' . esc_url(home_url('/')) . '" class="waza-btn waza-btn-primary">' . esc_html__('Go to Home', 'waza-booking') . '</a>';
    echo '</div>';
    return;
}

global $wpdb;

// Handle rental confirmation
if ($rental_id) {
    error_log('Querying rental with ID: ' . $rental_id);
    $rental = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}waza_rentals WHERE id = %d",
        $rental_id
    ));
    
    error_log('Rental query result: ' . print_r($rental, true));
    
    if (!$rental) {
        error_log('ERROR: Rental not found in database for ID: ' . $rental_id);
        echo '<div class="waza-error-message">';
        echo '<h2>' . esc_html__('Rental Not Found', 'waza-booking') . '</h2>';
        echo '<p>' . esc_html__('The rental booking you are looking for does not exist.', 'waza-booking') . '</p>';
        echo '<a href="' . esc_url(home_url('/')) . '" class="waza-btn waza-btn-primary">' . esc_html__('Go to Home', 'waza-booking') . '</a>';
        echo '</div>';
        return;
    }
    
    // Security verification for rental
    if ($verify_hash) {
        $expected_hash = md5($rental_id . $rental->customer_email . wp_salt());
        error_log('Verify hash check - Expected: ' . $expected_hash . ', Got: ' . $verify_hash);
        if ($verify_hash !== $expected_hash) {
            error_log('ERROR: Hash mismatch!');
            echo '<div class="waza-error-message">';
            echo '<h2>' . esc_html__('Access Denied', 'waza-booking') . '</h2>';
            echo '<p>' . esc_html__('Invalid verification code.', 'waza-booking') . '</p>';
            echo '</div>';
            return;
        }
    }
    
    error_log('Loading rental confirmation display template');
    // Display rental confirmation
    include plugin_dir_path(__FILE__) . 'rental-confirmation-display.php';
    return;
}

// Get booking details with payment information
$booking = $wpdb->get_row($wpdb->prepare(
    "SELECT b.*, s.start_datetime, s.end_datetime, s.activity_id, s.instructor_id,
            p.post_title as activity_title, p.post_content as activity_description,
            u.display_name as instructor_name,
            pay.transaction_id, pay.payment_method as payment_gateway, pay.amount as paid_amount,
            pay.created_at as payment_date
     FROM {$wpdb->prefix}waza_bookings b
     LEFT JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
     LEFT JOIN {$wpdb->prefix}waza_posts p ON s.activity_id = p.ID
     LEFT JOIN {$wpdb->users} u ON s.instructor_id = u.ID
     LEFT JOIN {$wpdb->prefix}waza_payments pay ON b.id = pay.booking_id
     WHERE b.id = %d
     ORDER BY pay.created_at DESC
     LIMIT 1",
    $booking_id
));

// Debug logging
error_log('Waza Confirmation: Booking ID ' . $booking_id);
if ($booking) {
    error_log('Waza Confirmation: Payment Status = ' . ($booking->payment_status ?? 'NULL'));
    error_log('Waza Confirmation: Booking Status = ' . ($booking->booking_status ?? 'NULL'));
    error_log('Waza Confirmation: Transaction ID = ' . ($booking->transaction_id ?? 'NULL'));
    error_log('Waza Confirmation: Payment Gateway = ' . ($booking->payment_gateway ?? 'NULL'));
}

if (!$booking) {
    echo '<div class="waza-error-message">';
    echo '<h2>' . esc_html__('Booking Not Found', 'waza-booking') . '</h2>';
    echo '<p>' . esc_html__('The booking you are looking for does not exist.', 'waza-booking') . '</p>';
    echo '<a href="' . esc_url(home_url('/my-bookings/')) . '" class="waza-btn waza-btn-primary">' . esc_html__('View My Bookings', 'waza-booking') . '</a>';
    echo '</div>';
    return;
}

// Security verification - validate hash if provided
if ($verify_hash) {
    $expected_hash = md5($booking_id . $booking->user_email . wp_salt());
    if ($verify_hash !== $expected_hash) {
        echo '<div class="waza-error-message">';
        echo '<h2>' . esc_html__('Access Denied', 'waza-booking') . '</h2>';
        echo '<p>' . esc_html__('Invalid verification code.', 'waza-booking') . '</p>';
        echo '</div>';
        return;
    }
}

// Get QR code
$qr_code_url = '';
try {
    $plugin = \WazaBooking\Core\Plugin::get_instance();
    if ($plugin && method_exists($plugin, 'get_manager')) {
        $qr_manager = $plugin->get_manager('qr');
        if ($qr_manager && $booking) {
            // Create QR data string
            $qr_data_string = json_encode([
                'booking_id' => $booking_id,
                'user_email' => $booking->user_email,
                'activity_id' => $booking->activity_id,
                'slot_id' => $booking->slot_id,
                'timestamp' => time()
            ]);
            
            error_log('Waza: Generating QR code for booking ' . $booking_id);
            error_log('Waza: QR data string: ' . $qr_data_string);
            
            // Generate QR code image (returns base64 data URL)
            $qr_code_result = $qr_manager->generate_qr_image($qr_data_string, 300);
            
            if ($qr_code_result && $qr_code_result !== false) {
                $qr_code_url = $qr_code_result;
                error_log('Waza: QR code generated successfully');
            } else {
                error_log('Waza: QR code generation returned false');
            }
        } else {
            error_log('Waza: QR Manager not available');
        }
    }
} catch (\Exception $e) {
    error_log('QR Code generation error on confirmation page: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
}

$activity_location = get_post_meta($booking->activity_id, 'waza_activity_location', true) ?: __('To be announced', 'waza-booking');
?>

<div class="waza-booking-confirmation">
    <!-- Success Header -->
    <div class="confirmation-header">
        <div class="success-icon">
            <svg width="80" height="80" viewBox="0 0 80 80" fill="none">
                <circle cx="40" cy="40" r="40" fill="#4CAF50"/>
                <path d="M25 40L35 50L55 30" stroke="white" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <h1><?php esc_html_e('Booking Confirmed!', 'waza-booking'); ?></h1>
        <p class="confirmation-subtitle"><?php esc_html_e('Thank you for your booking. Your payment has been processed successfully.', 'waza-booking'); ?></p>
    </div>

    <!-- Booking Details Card -->
    <div class="confirmation-content">
        <div class="booking-details-card">
            <h2><?php esc_html_e('Booking Details', 'waza-booking'); ?></h2>
            
            <div class="detail-row">
                <span class="label"><?php esc_html_e('Booking ID:', 'waza-booking'); ?></span>
                <span class="value booking-id">#<?php echo esc_html($booking_id); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="label"><?php esc_html_e('Activity:', 'waza-booking'); ?></span>
                <span class="value"><?php echo esc_html($booking->activity_title); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="label"><?php esc_html_e('Date & Time:', 'waza-booking'); ?></span>
                <span class="value">
                    <?php echo date_i18n('l, F j, Y', strtotime($booking->start_datetime)); ?><br>
                    <?php echo date_i18n('g:i A', strtotime($booking->start_datetime)); ?> - 
                    <?php echo date_i18n('g:i A', strtotime($booking->end_datetime)); ?>
                </span>
            </div>
            
            <?php if ($booking->instructor_name) : ?>
            <div class="detail-row">
                <span class="label"><?php esc_html_e('Instructor:', 'waza-booking'); ?></span>
                <span class="value"><?php echo esc_html($booking->instructor_name); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="detail-row">
                <span class="label"><?php esc_html_e('Location:', 'waza-booking'); ?></span>
                <span class="value"><?php echo esc_html($activity_location); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="label"><?php esc_html_e('Participants:', 'waza-booking'); ?></span>
                <span class="value"><?php echo esc_html($booking->quantity); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="label"><?php esc_html_e('Payment Status:', 'waza-booking'); ?></span>
                <span class="value status-paid">
                    <?php 
                    $payment_status = !empty($booking->payment_status) ? ucfirst($booking->payment_status) : 'Completed';
                    echo esc_html($payment_status); 
                    ?>
                </span>
            </div>
            
            <?php if (!empty($booking->transaction_id)) : ?>
            <div class="detail-row">
                <span class="label"><?php esc_html_e('Transaction ID:', 'waza-booking'); ?></span>
                <span class="value transaction-id"><?php echo esc_html($booking->transaction_id); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($booking->payment_gateway)) : ?>
            <div class="detail-row">
                <span class="label"><?php esc_html_e('Payment Method:', 'waza-booking'); ?></span>
                <span class="value"><?php echo esc_html(ucfirst($booking->payment_gateway)); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="detail-row total-amount">
                <span class="label"><?php esc_html_e('Total Amount Paid:', 'waza-booking'); ?></span>
                <span class="value">₹<?php echo number_format($booking->total_amount, 2); ?></span>
            </div>
        </div>

        <!-- QR Code Card -->
        <div class="qr-code-card">
            <h2><?php esc_html_e('Your Check-in QR Code', 'waza-booking'); ?></h2>
            <p><?php esc_html_e('Please present this QR code at the studio entrance for check-in.', 'waza-booking'); ?></p>
            <?php if ($qr_code_url && $qr_code_url !== false) : ?>
            <div class="qr-code-container">
                <img src="<?php echo $qr_code_url; ?>" alt="Booking QR Code" id="booking-qr-code" style="max-width: 300px; height: auto;">
            </div>
            <?php else : ?>
            <div class="qr-code-error">
                <p style="color: #666; font-style: italic;">
                    <?php esc_html_e('QR code will be generated shortly. Please check your email or return to this page.', 'waza-booking'); ?>
                </p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Customer Details -->
        <div class="customer-details-card">
            <h2><?php esc_html_e('Customer Information', 'waza-booking'); ?></h2>
            
            <div class="detail-row">
                <span class="label"><?php esc_html_e('Name:', 'waza-booking'); ?></span>
                <span class="value"><?php echo esc_html($booking->user_name); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="label"><?php esc_html_e('Email:', 'waza-booking'); ?></span>
                <span class="value"><?php echo esc_html($booking->user_email); ?></span>
            </div>
            
            <?php if ($booking->user_phone) : ?>
            <div class="detail-row">
                <span class="label"><?php esc_html_e('Phone:', 'waza-booking'); ?></span>
                <span class="value"><?php echo esc_html($booking->user_phone); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="detail-row">
                <span class="label"><?php esc_html_e('Booking Status:', 'waza-booking'); ?></span>
                <span class="value status-confirmed"><?php echo esc_html(ucfirst($booking->booking_status ?: 'Pending')); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="label"><?php esc_html_e('Payment Status:', 'waza-booking'); ?></span>
                <span class="value status-paid"><?php echo esc_html(ucfirst($booking->payment_status ?: 'Pending')); ?></span>
            </div>
            
            <?php if (!empty($booking->transaction_id)) : ?>
            <div class="detail-row">
                <span class="label"><?php esc_html_e('Transaction ID:', 'waza-booking'); ?></span>
                <span class="value transaction-id"><?php echo esc_html($booking->transaction_id); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($booking->payment_gateway)) : ?>
            <div class="detail-row">
                <span class="label"><?php esc_html_e('Payment Method:', 'waza-booking'); ?></span>
                <span class="value"><?php echo esc_html(ucfirst($booking->payment_gateway)); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="confirmation-actions">
        <button onclick="addToCalendar()" class="waza-btn waza-btn-primary" id="add-calendar-btn">
            <svg width="16" height="16" fill="currentColor" style="margin-right: 8px; vertical-align: middle;">
                <path d="M11 3V1H9v2H5V1H3v2H1v12h14V3h-4zM3 5h10v8H3V5z"/>
            </svg>
            <?php esc_html_e('Add to Calendar', 'waza-booking'); ?>
        </button>
        <a href="<?php echo esc_url(home_url('/my-bookings/')); ?>" class="waza-btn waza-btn-secondary">
            <?php esc_html_e('View My Bookings', 'waza-booking'); ?>
        </a>
        <button onclick="downloadQRCode()" class="waza-btn waza-btn-outline" id="download-qr-btn">
            <?php esc_html_e('Download QR Code', 'waza-booking'); ?>
        </button>
    </div>

    <!-- Important Information -->
    <div class="important-info">
        <h3><?php esc_html_e('Important Information', 'waza-booking'); ?></h3>
        <ul>
            <li><?php esc_html_e('Please arrive 10 minutes before the scheduled time', 'waza-booking'); ?></li>
            <li><?php esc_html_e('Bring your QR code for quick check-in', 'waza-booking'); ?></li>
            <li><?php esc_html_e('A confirmation email has been sent to your registered email address', 'waza-booking'); ?></li>
            <li><?php esc_html_e('For any queries, please contact our support team', 'waza-booking'); ?></li>
        </ul>
    </div>
</div>

<style>
.waza-booking-confirmation {
    max-width: 900px;
    margin: 40px auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, sans-serif;
}

.confirmation-header {
    text-align: center;
    padding: 40px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    color: white;
    margin-bottom: 30px;
}

.success-icon {
    margin-bottom: 20px;
    animation: scaleIn 0.5s ease-out;
}

@keyframes scaleIn {
    from { transform: scale(0); }
    to { transform: scale(1); }
}

.confirmation-header h1 {
    margin: 0 0 10px 0;
    font-size: 32px;
    font-weight: 700;
}

.confirmation-subtitle {
    margin: 0;
    font-size: 16px;
    opacity: 0.95;
}

.confirmation-content {
    display: grid;
    gap: 20px;
    margin-bottom: 30px;
}

.booking-details-card,
.qr-code-card,
.customer-details-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.booking-details-card h2,
.qr-code-card h2,
.customer-details-card h2 {
    margin: 0 0 20px 0;
    font-size: 20px;
    color: #333;
    border-bottom: 2px solid #667eea;
    padding-bottom: 10px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #f5f5f5;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-row .label {
    font-weight: 600;
    color: #666;
}

.detail-row .value {
    text-align: right;
    color: #333;
    word-break: break-word;
}

.booking-id {
    font-size: 18px;
    font-weight: 700;
    color: #667eea;
}

.transaction-id {
    font-size: 12px;
    font-family: monospace;
    background: #f5f5f5;
    padding: 4px 8px;
    border-radius: 4px;
}

.total-amount {
    background: #f8f9fa;
    padding: 15px !important;
    margin-top: 10px;
    border-radius: 8px;
}

.total-amount .value {
    font-size: 24px;
    font-weight: 700;
    color: #4CAF50;
}

.qr-code-card {
    text-align: center;
}

.qr-code-container {
    background: white;
    padding: 20px;
    border: 2px dashed #ddd;
    border-radius: 8px;
    display: inline-block;
    margin: 20px 0;
}

.qr-code-container img {
    max-width: 250px;
    height: auto;
}

.status-confirmed,
.status-paid {
    background: #4CAF50;
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
}

.confirmation-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
    margin: 30px 0;
}

.waza-btn {
    padding: 12px 30px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 16px;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-block;
}

.waza-btn-primary {
    background: #667eea;
    color: white;
}

.waza-btn-primary:hover {
    background: #5568d3;
}

.waza-btn-secondary {
    background: #48bb78;
    color: white;
}

.waza-btn-secondary:hover {
    background: #38a169;
}

.waza-btn-outline {
    background: white;
    color: #667eea;
    border: 2px solid #667eea;
}

.waza-btn-outline:hover {
    background: #667eea;
    color: white;
}

.important-info {
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 8px;
    padding: 20px;
    margin-top: 30px;
}

.important-info h3 {
    margin: 0 0 15px 0;
    color: #856404;
    font-size: 18px;
}

.important-info ul {
    margin: 0;
    padding-left: 20px;
    color: #856404;
}

.important-info li {
    margin-bottom: 8px;
}

@media print {
    .confirmation-actions,
    .important-info {
        display: none;
    }
}

@media (max-width: 768px) {
    .confirmation-header h1 {
        font-size: 24px;
    }
    
    .detail-row {
        flex-direction: column;
        gap: 5px;
    }
    
    .detail-row .value {
        text-align: left;
    }
    
    .confirmation-actions {
        flex-direction: column;
    }
    
    .waza-btn {
        width: 100%;
    }
}
</style>

<script>
// Download QR Code
function downloadQRCode() {
    console.log('Download QR Code button clicked');
    const qrImage = document.getElementById('booking-qr-code');
    console.log('QR Image element:', qrImage);
    
    if (!qrImage) {
        console.error('QR code image not found');
        alert('<?php esc_html_e('QR Code not available. Please try refreshing the page.', 'waza-booking'); ?>');
        return;
    }
    
    console.log('QR Image src:', qrImage.src);
    
    try {
        const link = document.createElement('a');
        link.href = qrImage.src;
        link.download = 'booking-<?php echo esc_js($booking_id); ?>-qr-code.png';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        console.log('QR Code download initiated');
    } catch (error) {
        console.error('Error downloading QR code:', error);
        alert('Error downloading QR code. Please try again.');
    }
}

// Add to Calendar
function addToCalendar() {
    console.log('Add to Calendar button clicked');
    
    try {
        // Create ICS file content
        const startDate = '<?php echo date('Ymd\THis', strtotime($booking->start_datetime)); ?>';
        const endDate = '<?php echo date('Ymd\THis', strtotime($booking->end_datetime)); ?>';
        const title = '<?php echo addslashes($booking->activity_title); ?>';
        const description = 'Booking ID: <?php echo $booking_id; ?>\\nActivity: <?php echo addslashes($booking->activity_title); ?>';
        const location = '<?php echo addslashes(get_post_meta($booking->activity_id, 'waza_activity_location', true) ?: ''); ?>';
        
        console.log('Calendar data:', { startDate, endDate, title });
        
        const icsContent = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Waza Booking//EN',
            'BEGIN:VEVENT',
            'UID:booking-<?php echo $booking_id; ?>@' + window.location.hostname,
            'DTSTAMP:' + startDate,
            'DTSTART:' + startDate,
            'DTEND:' + endDate,
            'SUMMARY:' + title,
            'DESCRIPTION:' + description,
            'LOCATION:' + location,
            'STATUS:CONFIRMED',
            'END:VEVENT',
            'END:VCALENDAR'
        ].join('\r\n');
        
        console.log('ICS Content length:', icsContent.length);
        
        // Create blob and download
        const blob = new Blob([icsContent], { type: 'text/calendar;charset=utf-8' });
        const link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);
        link.download = 'booking-<?php echo $booking_id; ?>.ics';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(link.href);
        console.log('Calendar file download initiated');
    } catch (error) {
        console.error('Error creating calendar event:', error);
        alert('Error creating calendar event. Please try again.');
    }
}

// Debug on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Booking confirmation page loaded');
    console.log('Booking ID: <?php echo $booking_id; ?>');
    console.log('QR Code URL exists:', <?php echo $qr_code_url ? 'true' : 'false'; ?>);
    
    const qrImage = document.getElementById('booking-qr-code');
    if (qrImage) {
        console.log('QR Image found, src:', qrImage.src);
    } else {
        console.log('QR Image NOT found');
    }
});
</script>
