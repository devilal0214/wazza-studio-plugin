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
        $booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
        
        if (!$booking_id) {
            return '<div class="waza-message waza-error">' . __('No booking information found.', 'waza-booking') . '</div>';
        }
        
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_bookings WHERE id = %d
        ", $booking_id));
        
        if (!$booking) {
            return '<div class="waza-message waza-error">' . __('Booking not found.', 'waza-booking') . '</div>';
        }
        
        // Get slot details
        $slot = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_slots WHERE id = %d
        ", $booking->slot_id));
        
        // Get activity details
        $activity = get_post($slot->activity_id);
        
        // Get QR code
        $qr_manager = \WazaBooking\Core\Plugin::get_instance()->get_manager('qr');
        $qr_image = $qr_manager->generate_qr_image($booking->qr_token, 300);
        
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
                        <span class="waza-detail-value"><?php echo esc_html($activity->post_title); ?></span>
                    </div>
                    
                    <div class="waza-detail-row">
                        <span class="waza-detail-label"><?php _e('Date:', 'waza-booking'); ?></span>
                        <span class="waza-detail-value"><?php echo wp_date(get_option('date_format'), strtotime($slot->start_datetime)); ?></span>
                    </div>
                    
                    <div class="waza-detail-row">
                        <span class="waza-detail-label"><?php _e('Time:', 'waza-booking'); ?></span>
                        <span class="waza-detail-value">
                            <?php 
                            echo wp_date(get_option('time_format'), strtotime($slot->start_datetime));
                            echo ' - ';
                            echo wp_date(get_option('time_format'), strtotime($slot->end_datetime));
                            ?>
                        </span>
                    </div>
                    
                    <?php if ($slot->location): ?>
                    <div class="waza-detail-row">
                        <span class="waza-detail-label"><?php _e('Location:', 'waza-booking'); ?></span>
                        <span class="waza-detail-value"><?php echo esc_html($slot->location); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="waza-detail-row">
                        <span class="waza-detail-label"><?php _e('Participants:', 'waza-booking'); ?></span>
                        <span class="waza-detail-value"><?php echo intval($booking->attendees_count); ?></span>
                    </div>
                    
                    <div class="waza-detail-row">
                        <span class="waza-detail-label"><?php _e('Total Amount:', 'waza-booking'); ?></span>
                        <span class="waza-detail-value waza-amount">
                            <?php echo $this->format_currency($booking->total_amount); ?>
                        </span>
                    </div>
                    
                    <div class="waza-detail-row">
                        <span class="waza-detail-label"><?php _e('Payment Status:', 'waza-booking'); ?></span>
                        <span class="waza-detail-value">
                            <span class="waza-badge waza-badge-<?php echo esc_attr($booking->payment_status); ?>">
                                <?php echo ucfirst($booking->payment_status); ?>
                            </span>
                        </span>
                    </div>
                </div>
                
                <div class="waza-confirmation-qr">
                    <h2><?php _e('Your QR Code', 'waza-booking'); ?></h2>
                    <p class="waza-qr-instructions">
                        <?php _e('Please present this QR code at the studio entrance for check-in.', 'waza-booking'); ?>
                    </p>
                    <div class="waza-qr-code">
                        <?php if ($qr_image): ?>
                            <img src="<?php echo esc_url($qr_image); ?>" alt="QR Code" />
                        <?php endif; ?>
                    </div>
                    <p class="waza-qr-token">
                        <small><?php echo esc_html($booking->qr_token); ?></small>
                    </p>
                </div>
            </div>
            
            <div class="waza-confirmation-actions">
                <h3><?php _e('What\'s Next?', 'waza-booking'); ?></h3>
                
                <div class="waza-action-buttons">
                    <button class="waza-button waza-button-primary waza-add-to-calendar" data-booking-id="<?php echo esc_attr($booking->id); ?>">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/>
                        </svg>
                        <?php _e('Add to Calendar', 'waza-booking'); ?>
                    </button>
                    
                    <a href="<?php echo esc_url(home_url('/my-bookings')); ?>" class="waza-button waza-button-secondary">
                        <?php _e('View My Bookings', 'waza-booking'); ?>
                    </a>
                    
                    <button class="waza-button waza-button-outline waza-download-qr" data-booking-id="<?php echo esc_attr($booking->id); ?>">
                        <?php _e('Download QR Code', 'waza-booking'); ?>
                    </button>
                </div>
                
                <div class="waza-calendar-dropdown" style="display:none;">
                    <a href="#" class="waza-calendar-option" data-type="google">
                        <img src="<?php echo WAZA_BOOKING_PLUGIN_URL; ?>assets/images/google-calendar.svg" alt="Google Calendar" />
                        <?php _e('Google Calendar', 'waza-booking'); ?>
                    </a>
                    <a href="#" class="waza-calendar-option" data-type="outlook">
                        <img src="<?php echo WAZA_BOOKING_PLUGIN_URL; ?>assets/images/outlook.svg" alt="Outlook" />
                        <?php _e('Outlook', 'waza-booking'); ?>
                    </a>
                    <a href="#" class="waza-calendar-option" data-type="apple">
                        <img src="<?php echo WAZA_BOOKING_PLUGIN_URL; ?>assets/images/apple.svg" alt="Apple Calendar" />
                        <?php _e('Apple Calendar', 'waza-booking'); ?>
                    </a>
                    <a href="#" class="waza-calendar-option" data-type="ics">
                        <?php _e('Download .ics File', 'waza-booking'); ?>
                    </a>
                </div>
            </div>
            
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
