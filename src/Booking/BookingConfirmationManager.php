<?php
/**
 * Booking Confirmation Manager
 * 
 * Handles booking confirmation page and display
 * 
 * @package WazaBooking\Booking
 */

namespace WazaBooking\Booking;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Booking Confirmation Manager Class
 */
class BookingConfirmationManager {
    
    /**
     * Initialize confirmation functionality
     */
    public function init() {
        add_action('init', [$this, 'create_confirmation_page']);
        add_shortcode('waza_booking_confirmation', [$this, 'confirmation_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }
    
    /**
     * Create booking confirmation page
     */
    public function create_confirmation_page() {
        $page_id = get_option('waza_confirmation_page_id');
        
        if (!$page_id || !get_post($page_id)) {
            $page_id = wp_insert_post([
                'post_title' => __('Booking Confirmation', 'waza-booking'),
                'post_content' => '[waza_booking_confirmation]',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_name' => 'booking-confirmation'
            ]);
            
            if (!is_wp_error($page_id)) {
                update_option('waza_confirmation_page_id', $page_id);
            }
        }
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        if (is_page(get_option('waza_confirmation_page_id'))) {
            wp_enqueue_style('waza-frontend', WAZA_BOOKING_PLUGIN_URL . 'assets/frontend.css', [], WAZA_BOOKING_VERSION);
            wp_enqueue_script('waza-frontend', WAZA_BOOKING_PLUGIN_URL . 'assets/frontend.js', ['jquery'], WAZA_BOOKING_VERSION, true);
            
            wp_localize_script('waza-frontend', 'waza_confirmation', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('waza_frontend_nonce')
            ]);
        }
    }
    
    /**
     * Booking confirmation shortcode
     */
    public function confirmation_shortcode($atts) {
        error_log('=== CONFIRMATION SHORTCODE CALLED ===');
        
        $booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
        $rental_id = isset($_GET['rental_id']) ? intval($_GET['rental_id']) : 0;
        
        error_log('Booking ID: ' . $booking_id . ', Rental ID: ' . $rental_id);
        
        if (!$booking_id && !$rental_id) {
            error_log('NO BOOKING OR RENTAL ID - Returning error');
            return '<div class="waza-message waza-error">' . __('No booking information found.', 'waza-booking') . '</div>';
        }
        
        // If rental_id is present, load the rental confirmation template
        if ($rental_id) {
            error_log('Loading rental confirmation template');
            ob_start();
            include plugin_dir_path(dirname(__FILE__)) . '../templates/booking-confirmation.php';
            return ob_get_clean();
        }
        
        // Otherwise proceed with booking confirmation
        global $wpdb;
        
        // Get booking with payment details
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT b.*, s.start_datetime, s.end_datetime, s.activity_id, s.instructor_id,
                   p.post_title as activity_title,
                   u.display_name as instructor_name
            FROM {$wpdb->prefix}waza_bookings b
            LEFT JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
            LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
            LEFT JOIN {$wpdb->users} u ON s.instructor_id = u.ID
            WHERE b.id = %d
            LIMIT 1
        ", $booking_id));
        
        // Get payment details separately
        $payment = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_payments 
            WHERE booking_id = %d 
            ORDER BY created_at DESC 
            LIMIT 1
        ", $booking_id));
        
        // Merge payment data into booking object
        if ($payment) {
            $booking->transaction_id = $payment->payment_id ?? '';
            $booking->payment_gateway = $payment->payment_method ?? '';
        }
        
        if (!$booking) {
            return '<div class="waza-message waza-error">' . __('Booking not found.', 'waza-booking') . '</div>';
        }
        
        // Get slot details
        $slot = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_slots WHERE id = %d
        ", $booking->slot_id));
        
        // Get activity details
        $activity = get_post($slot->activity_id);
        // Get location from slot first, fallback to activity meta, then default
        $activity_location = !empty($slot->location) ? $slot->location : (get_post_meta($slot->activity_id, 'waza_activity_location', true) ?: __('To be announced', 'waza-booking'));
        
        // Get QR code
        $qr_manager = \WazaBooking\Core\Plugin::get_instance()->get_manager('qr');
        $qr_image = false;
        
        // Convert UTC to site timezone for QR data
        $qr_start_dt = new \DateTime($slot->start_datetime, new \DateTimeZone('UTC'));
        $qr_start_dt->setTimezone(new \DateTimeZone(wp_timezone_string()));
        $qr_end_dt = new \DateTime($slot->end_datetime, new \DateTimeZone('UTC'));
        $qr_end_dt->setTimezone(new \DateTimeZone(wp_timezone_string()));
        
        // Generate comprehensive QR code data for admin verification and attendance
        $qr_data = [
            'type' => 'booking',
            'booking_id' => $booking->id,
            'booking_code' => 'WB-' . str_pad($booking->id, 5, '0', STR_PAD_LEFT),
            'user_name' => $booking->user_name,
            'user_email' => $booking->user_email,
            'user_phone' => $booking->user_phone,
            'activity_id' => $slot->activity_id,
            'activity_name' => $booking->activity_title,
            'slot_id' => $booking->slot_id,
            'date' => $qr_start_dt->format('Y-m-d'),
            'time' => $qr_start_dt->format('H:i') . '-' . $qr_end_dt->format('H:i'),
            'quantity' => $booking->quantity,
            'status' => $booking->booking_status,
            'payment_status' => $booking->payment_status,
            'verified' => false,
            'attended' => false,
            'generated_at' => time()
        ];
        
        $qr_data_string = json_encode($qr_data);
        
        error_log('=== QR CODE GENERATION ===');
        error_log('Booking ID: ' . $booking->id);
        error_log('QR Manager exists: ' . ($qr_manager ? 'YES' : 'NO'));
        error_log('QR Data: ' . $qr_data_string);
        error_log('QR Data length: ' . strlen($qr_data_string));
        
        if ($qr_manager) {
            $qr_image = $qr_manager->generate_qr_image($qr_data_string, 300);
            error_log('QR Image generated: ' . ($qr_image ? 'YES (length: ' . strlen($qr_image) . ')' : 'NO/FALSE'));
            if ($qr_image) {
                error_log('QR Image starts with: ' . substr($qr_image, 0, 50));
            }
        }
        
        ob_start();
        ?>
        <div class="waza-booking-confirmation">
            <div class="waza-confirmation-header">
                <div class="waza-success-icon">
                    <svg width="80" height="80" viewBox="0 0 80 80" fill="none">
                        <circle cx="40" cy="40" r="38" fill="#10b981" stroke="#059669" stroke-width="2"/>
                        <path d="M25 40 L35 50 L55 30" stroke="white" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h1><?php _e('Booking Confirmed!', 'waza-booking'); ?></h1>
                <p class="waza-confirmation-subtitle">
                    <?php printf(__('Your booking ID is: %s', 'waza-booking'), '<strong>WB-' . str_pad($booking->id, 5, '0', STR_PAD_LEFT) . '</strong>'); ?>
                </p>
            </div>
            
            <div class="waza-confirmation-content">
                <div class="waza-confirmation-details">
                    <h2><?php _e('Booking Details', 'waza-booking'); ?></h2>
                    
                    <div class="waza-detail-row">
                        <span class="waza-detail-label"><?php _e('Activity:', 'waza-booking'); ?></span>
                        <span class="waza-detail-value"><?php echo esc_html($booking->activity_title); ?></span>
                    </div>
                    
                    <div class="waza-detail-row">
                        <span class="waza-detail-label"><?php _e('Date:', 'waza-booking'); ?></span>
                        <span class="waza-detail-value">
                            <?php 
                            // Convert UTC to site timezone
                            $start_dt = new \DateTime($slot->start_datetime, new \DateTimeZone('UTC'));
                            $start_dt->setTimezone(new \DateTimeZone(wp_timezone_string()));
                            echo $start_dt->format('F j, Y'); 
                            ?>
                        </span>
                    </div>
                    
                    <div class="waza-detail-row">
                        <span class="waza-detail-label"><?php _e('Time:', 'waza-booking'); ?></span>
                        <span class="waza-detail-value">
                            <?php 
                            // Convert UTC to site timezone
                            $end_dt = new \DateTime($slot->end_datetime, new \DateTimeZone('UTC'));
                            $end_dt->setTimezone(new \DateTimeZone(wp_timezone_string()));
                            echo $start_dt->format('g:i A');
                            echo ' – ';
                            echo $end_dt->format('g:i A');
                            ?>
                        </span>
                    </div>
                    
                    <div class="waza-detail-row">
                        <span class="waza-detail-label"><?php _e('Location:', 'waza-booking'); ?></span>
                        <span class="waza-detail-value"><?php echo esc_html($activity_location); ?></span>
                    </div>
                    
                    <div class="waza-detail-row">
                        <span class="waza-detail-label"><?php _e('Participants:', 'waza-booking'); ?></span>
                        <span class="waza-detail-value"><?php echo intval($booking->quantity); ?></span>
                    </div>
                    
                    <div class="waza-detail-row">
                        <span class="waza-detail-label"><?php _e('Total Amount:', 'waza-booking'); ?></span>
                        <span class="waza-detail-value waza-amount">
                            ₹<?php echo number_format($booking->total_amount, 2); ?>
                        </span>
                    </div>
                    
                    <div class="waza-detail-row">
                        <span class="waza-detail-label"><?php _e('Payment Status:', 'waza-booking'); ?></span>
                        <span class="waza-detail-value">
                            <span class="waza-badge waza-badge-<?php echo esc_attr($booking->payment_status); ?>">
                                <?php echo ucfirst($booking->payment_status ?: 'Completed'); ?>
                            </span>
                        </span>
                    </div>
                    
                    <?php if (!empty($booking->transaction_id)): ?>
                    <div class="waza-detail-row">
                        <span class="waza-detail-label"><?php _e('Transaction ID:', 'waza-booking'); ?></span>
                        <span class="waza-detail-value" style="font-family: monospace; font-size: 12px;">
                            <?php echo esc_html($booking->transaction_id); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($booking->payment_gateway)): ?>
                    <div class="waza-detail-row">
                        <span class="waza-detail-label"><?php _e('Payment Method:', 'waza-booking'); ?></span>
                        <span class="waza-detail-value">
                            <?php echo ucfirst($booking->payment_gateway); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="waza-confirmation-qr">
                    <h2><?php _e('Your QR Code', 'waza-booking'); ?></h2>
                    <p class="waza-qr-instructions">
                        <?php _e('Please present this QR code at the studio entrance for check-in.', 'waza-booking'); ?>
                    </p>
                    <div class="waza-qr-code">
                        <?php if ($qr_image): ?>
                            <img src="<?php echo $qr_image; ?>" alt="QR Code" id="booking-qr-image" style="max-width: 300px; height: auto;" />
                        <?php else: ?>
                            <p style="color: #666;"><?php _e('QR code will be generated shortly.', 'waza-booking'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="waza-confirmation-actions">
                <h3><?php _e('What\'s Next?', 'waza-booking'); ?></h3>
                
                <div class="waza-action-buttons">
                    <button class="waza-button waza-button-primary" onclick="addToCalendarConfirmation()">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/>
                        </svg>
                        <?php _e('Add to Calendar', 'waza-booking'); ?>
                    </button>
                    
                    <a href="<?php echo esc_url(home_url('/my-bookings/')); ?>" class="waza-button waza-button-secondary">
                        <?php _e('View My Bookings', 'waza-booking'); ?>
                    </a>
                    
                    <button class="waza-button waza-button-outline" onclick="downloadConfirmationQR()">
                        <?php _e('Download QR Code', 'waza-booking'); ?>
                    </button>
                </div>
            </div>
            
            <script>
            // Download QR Code function
            function downloadConfirmationQR() {
                console.log('=== Download Confirmation QR Called ===');
                const qrImage = document.getElementById('booking-qr-image');
                console.log('QR Image element:', qrImage);
                
                if (!qrImage || !qrImage.src) {
                    console.error('QR Image not found or no src');
                    alert('<?php _e('QR Code not available. Please refresh the page.', 'waza-booking'); ?>');
                    return;
                }
                
                console.log('QR Image src length:', qrImage.src.length);
                console.log('QR Image src starts with:', qrImage.src.substring(0, 50));
                
                // For base64 data URLs, we need to convert to blob
                if (qrImage.src.startsWith('data:')) {
                    try {
                        const base64Data = qrImage.src.split(',')[1];
                        const byteCharacters = atob(base64Data);
                        const byteNumbers = new Array(byteCharacters.length);
                        for (let i = 0; i < byteCharacters.length; i++) {
                            byteNumbers[i] = byteCharacters.charCodeAt(i);
                        }
                        const byteArray = new Uint8Array(byteNumbers);
                        const blob = new Blob([byteArray], { type: 'image/png' });
                        const url = window.URL.createObjectURL(blob);
                        
                        const link = document.createElement('a');
                        link.href = url;
                        link.download = 'booking-WB-<?php echo str_pad($booking->id, 5, '0', STR_PAD_LEFT); ?>-qr.png';
                        console.log('Download filename:', link.download);
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        window.URL.revokeObjectURL(url);
                        console.log('Download completed successfully');
                    } catch (error) {
                        console.error('Error downloading QR:', error);
                        alert('Error downloading QR code: ' + error.message);
                    }
                } else {
                    const link = document.createElement('a');
                    link.href = qrImage.src;
                    link.download = 'booking-WB-<?php echo str_pad($booking->id, 5, '0', STR_PAD_LEFT); ?>-qr.png';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    console.log('Download completed successfully');
                }
            }
            
            function addToCalendarConfirmation() {
                const startDate = '<?php echo date('Ymd\THis', strtotime($slot->start_datetime)); ?>';
                const endDate = '<?php echo date('Ymd\THis', strtotime($slot->end_datetime)); ?>';
                const title = '<?php echo addslashes($booking->activity_title); ?>';
                const description = 'Booking ID: WB-<?php echo str_pad($booking->id, 5, '0', STR_PAD_LEFT); ?>\\nActivity: <?php echo addslashes($booking->activity_title); ?>';
                const location = '<?php echo addslashes($activity_location); ?>';
                
                const icsContent = [
                    'BEGIN:VCALENDAR',
                    'VERSION:2.0',
                    'PRODID:-//Waza Booking//EN',
                    'BEGIN:VEVENT',
                    'UID:booking-<?php echo $booking->id; ?>@' + window.location.hostname,
                    'DTSTAMP:' + startDate,
                    'DTSTART:' + startDate,
                    'DTEND:' + endDate,
                    'SUMMARY:' + title,
                    'DESCRIPTION:' + description,
                    'LOCATION:' + location,
                    'STATUS:CONFIRMED',
                    'END:VEVENT',
                    'END:VCALENDAR'
                ].join('\\r\\n');
                
                const blob = new Blob([icsContent], { type: 'text/calendar;charset=utf-8' });
                const link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = 'booking-<?php echo $booking->id; ?>.ics';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(link.href);
            }
            </script>
            
            <div class="waza-confirmation-notice">
                <p><strong><?php _e('Important:', 'waza-booking'); ?></strong></p>
                <ul>
                    <li><?php _e('A confirmation email has been sent to your email address.', 'waza-booking'); ?></li>
                    <li><?php _e('You will receive a reminder 24 hours before your activity.', 'waza-booking'); ?></li>
                    <li><?php _e('Please arrive 10 minutes early for check-in.', 'waza-booking'); ?></li>
                    <li><?php _e('Save or screenshot your QR code for easy access.', 'waza-booking'); ?></li>
                </ul>
            </div>
        </div>
        
        <style>
        .waza-booking-confirmation {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
        }
        .waza-confirmation-header {
            text-align: center;
            padding: 40px 20px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        .waza-success-icon {
            margin-bottom: 20px;
        }
        .waza-confirmation-header h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
        }
        .waza-confirmation-subtitle {
            font-size: 18px;
            opacity: 0.95;
        }
        .waza-confirmation-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        .waza-confirmation-details,
        .waza-confirmation-qr {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .waza-confirmation-details h2,
        .waza-confirmation-qr h2 {
            margin-top: 0;
            font-size: 20px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .waza-detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .waza-detail-label {
            font-weight: 600;
            color: #6b7280;
        }
        .waza-detail-value {
            text-align: right;
            color: #111827;
        }
        .waza-amount {
            font-size: 20px;
            font-weight: 700;
            color: #10b981;
        }
        .waza-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .waza-badge-completed {
            background: #d1fae5;
            color: #065f46;
        }
        .waza-badge-pending {
            background: #fef3c7;
            color: #92400e;
        }
        .waza-qr-code {
            text-align: center;
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
            margin: 20px 0;
        }
        .waza-qr-code img {
            max-width: 300px;
            height: auto;
        }
        .waza-qr-instructions {
            color: #6b7280;
            font-size: 14px;
        }
        .waza-qr-token {
            text-align: center;
            color: #9ca3af;
            font-family: monospace;
        }
        .waza-confirmation-actions {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .waza-action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .waza-button {
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s;
        }
        .waza-button-primary {
            background: #3b82f6;
            color: white;
        }
        .waza-button-primary:hover {
            background: #2563eb;
        }
        .waza-button-secondary {
            background: #6b7280;
            color: white;
        }
        .waza-button-outline {
            background: white;
            color: #3b82f6;
            border: 2px solid #3b82f6;
        }
        .waza-confirmation-notice {
            background: #fffbeb;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #f59e0b;
        }
        .waza-confirmation-notice ul {
            margin: 10px 0 0 0;
            padding-left: 20px;
        }
        .waza-confirmation-notice li {
            margin: 8px 0;
        }
        @media (max-width: 768px) {
            .waza-confirmation-content {
                grid-template-columns: 1fr;
            }
            .waza-action-buttons {
                flex-direction: column;
            }
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Format currency
     */
    private function format_currency($amount) {
        $settings = get_option('waza_booking_settings', []);
        $currency = $settings['currency'] ?? 'USD';
        
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'INR' => '₹'
        ];
        
        $symbol = $symbols[$currency] ?? '$';
        
        return $symbol . number_format($amount, 2);
    }
}
