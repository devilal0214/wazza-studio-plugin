<?php
/**
 * Studio Rental Manager
 * 
 * Handles studio rental bookings, pricing, and availability checking
 * 
 * @package WazaBooking\Rental
 */

namespace WazaBooking\Rental;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class RentalManager {
    
    /**
     * Get rental pricing from settings
     */
    public function get_pricing() {
        $settings = \WazaBooking\Admin\RentalSettingsManager::get_settings();
        return $settings['pricing'] ?? [];
    }
    
    /**
     * Get enabled rental types
     */
    public function get_rental_types() {
        $settings = \WazaBooking\Admin\RentalSettingsManager::get_settings();
        $enabled_types = [];
        
        foreach ($settings['rental_types'] as $key => $type) {
            if ($type['enabled']) {
                $enabled_types[$key] = $type;
            }
        }
        
        return $enabled_types;
    }
    
    /**
     * Get enabled durations
     */
    public function get_durations() {
        $settings = \WazaBooking\Admin\RentalSettingsManager::get_settings();
        $enabled_durations = [];
        
        foreach ($settings['durations'] as $key => $duration) {
            if ($duration['enabled']) {
                $enabled_durations[$key] = $duration;
            }
        }
        
        return $enabled_durations;
    }
    
    /**
     * Get rental settings
     */
    public function get_rental_settings() {
        return \WazaBooking\Admin\RentalSettingsManager::get_settings();
    }
    
    public function __construct() {
        // Shortcodes
        add_shortcode('waza_studio_rental', [$this, 'rental_form_shortcode']);
        
        // AJAX handlers
        add_action('wp_ajax_waza_check_rental_availability', [$this, 'check_availability']);
        add_action('wp_ajax_nopriv_waza_check_rental_availability', [$this, 'check_availability']);
        add_action('wp_ajax_waza_submit_rental_booking', [$this, 'submit_rental_booking']);
        add_action('wp_ajax_nopriv_waza_submit_rental_booking', [$this, 'submit_rental_booking']);
        
        // Create database table on init
        add_action('init', [$this, 'create_rental_table']);
    }
    
    /**
     * Create rental bookings table
     */
    public function create_rental_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'waza_rentals';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            customer_name varchar(255) NOT NULL,
            customer_email varchar(255) NOT NULL,
            customer_phone varchar(50) NOT NULL,
            rental_type varchar(50) NOT NULL,
            duration_type varchar(50) NOT NULL,
            rental_date date NOT NULL,
            start_time time NOT NULL,
            end_time time NOT NULL,
            total_amount decimal(10,2) NOT NULL,
            payment_status varchar(50) DEFAULT 'pending',
            booking_status varchar(50) DEFAULT 'pending',
            qr_code_path varchar(255) DEFAULT NULL,
            special_requirements text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY rental_date (rental_date),
            KEY booking_status (booking_status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Studio rental form shortcode
     */
    public function rental_form_shortcode($atts) {
        ob_start();
        include WAZA_BOOKING_PLUGIN_DIR . 'templates/studio-rental.php';
        return ob_get_clean();
    }
    
    /**
     * Check rental availability
     */
    public function check_availability() {
        check_ajax_referer('waza_rental_nonce', 'nonce');
        
        $date = sanitize_text_field($_POST['date']);
        $start_time = sanitize_text_field($_POST['start_time']);
        $end_time = sanitize_text_field($_POST['end_time']);
        
        global $wpdb;
        
        // Check slots table for conflicts (only active slots)
        $slots_conflict = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}waza_slots
            WHERE status = 'active'
            AND (
                start_datetime BETWEEN %s AND %s
                OR end_datetime BETWEEN %s AND %s
                OR (start_datetime <= %s AND end_datetime >= %s)
            )
        ", 
            "$date $start_time", 
            "$date $end_time",
            "$date $start_time",
            "$date $end_time",
            "$date $start_time",
            "$date $end_time"
        ));
        
        // Check rentals table for conflicts
        $rentals_conflict = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}waza_rentals
            WHERE rental_date = %s
            AND booking_status != 'cancelled'
            AND (
                (start_time BETWEEN %s AND %s)
                OR (end_time BETWEEN %s AND %s)
                OR (start_time <= %s AND end_time >= %s)
            )
        ", $date, $start_time, $end_time, $start_time, $end_time, $start_time, $end_time));
        
        if ($slots_conflict > 0 || $rentals_conflict > 0) {
            wp_send_json_error([
                'message' => __('Studio is not available for the selected time slot. Please choose a different time.', 'waza-booking')
            ]);
        }
        
        wp_send_json_success([
            'message' => __('Studio is available!', 'waza-booking')
        ]);
    }
    
    /**
     * Submit rental booking
     * Saves to session and redirects to payment. Data is saved to DB only after successful payment.
     */
    public function submit_rental_booking() {
        check_ajax_referer('waza_rental_nonce', 'nonce');
        
        // Validate required fields
        $required = ['customer_name', 'customer_email', 'customer_phone', 'rental_type', 'duration_type', 'rental_date', 'start_time', 'end_time'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(['message' => __('Please fill in all required fields', 'waza-booking')]);
            }
        }
        
        $rental_type = sanitize_text_field($_POST['rental_type']);
        $duration_type = sanitize_text_field($_POST['duration_type']);
        $rental_date = sanitize_text_field($_POST['rental_date']);
        $start_time = sanitize_text_field($_POST['start_time']);
        $end_time = sanitize_text_field($_POST['end_time']);
        
        // Calculate amount based on actual time duration
        $amount = $this->calculate_rental_amount_by_time($rental_type, $duration_type, $start_time, $end_time);
        
        if (!$amount) {
            wp_send_json_error(['message' => __('Invalid rental type or duration', 'waza-booking')]);
        }
        
        // Store rental data in session (NOT in database yet)
        $rental_data = [
            'user_id' => get_current_user_id() ?: null,
            'customer_name' => sanitize_text_field($_POST['customer_name']),
            'customer_email' => sanitize_email($_POST['customer_email']),
            'customer_phone' => sanitize_text_field($_POST['customer_phone']),
            'rental_type' => $rental_type,
            'duration_type' => $duration_type,
            'rental_date' => $rental_date,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'total_amount' => $amount,
            'special_requirements' => sanitize_textarea_field($_POST['special_requirements'] ?? '')
        ];
        
        // Generate temporary ID for session storage
        $temp_id = 'rental_' . time() . '_' . wp_generate_password(8, false);
        
        // Store in transient (expires in 1 hour) instead of database
        error_log('Creating transient: waza_pending_rental_' . $temp_id);
        error_log('Rental data: ' . print_r($rental_data, true));
        $saved = set_transient('waza_pending_rental_' . $temp_id, $rental_data, HOUR_IN_SECONDS);
        error_log('Transient saved: ' . ($saved ? 'YES' : 'NO'));
        
        // Return success with payment redirect (no DB insert yet)
        wp_send_json_success([
            'message' => __('Redirecting to payment...', 'waza-booking'),
            'temp_id' => $temp_id,
            'amount' => $amount,
            'redirect' => add_query_arg([
                'temp_rental_id' => $temp_id,
                'amount' => $amount,
                'type' => 'rental',
                'customer_name' => sanitize_text_field($_POST['customer_name']),
                'customer_email' => sanitize_email($_POST['customer_email'])
            ], home_url('/checkout/'))
        ]);
    }
    
    /**
     * Complete rental booking after successful payment
     * This is called by payment callback
     */
    public function complete_rental_booking($temp_id, $payment_data = []) {
        // Retrieve rental data from transient
        error_log('Attempting to retrieve transient: waza_pending_rental_' . $temp_id);
        $rental_data = get_transient('waza_pending_rental_' . $temp_id);
        error_log('Transient data: ' . print_r($rental_data, true));
        
        if (!$rental_data) {
            error_log('ERROR: Rental data not found for temp_id: ' . $temp_id);
            return new \WP_Error('rental_not_found', __('Rental booking data not found or expired', 'waza-booking'));
        }
        
        global $wpdb;
        
        // Now insert rental booking into database
        $inserted = $wpdb->insert(
            $wpdb->prefix . 'waza_rentals',
            array_merge($rental_data, [
                'payment_status' => 'completed',
                'booking_status' => 'confirmed'
            ]),
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s']
        );
        
        if (!$inserted) {
            return new \WP_Error('rental_save_failed', __('Failed to save rental booking', 'waza-booking'));
        }
        
        $rental_id = $wpdb->insert_id;
        
        // Clear transient immediately to prevent double-processing
        delete_transient('waza_pending_rental_' . $temp_id);
        
        // Send confirmation emails
        $this->send_rental_confirmation($rental_id);
        $this->notify_admin_rental($rental_id);
        
        return $rental_id;
    }
    
    /**
     * Calculate rental amount based on actual time duration
     * 
     * @param string $rental_type - Type of rental (full_studio, half_studio, etc.)
     * @param string $duration_type - Duration type (hourly, daily, monthly)
     * @param string $start_time - Start time (HH:MM format)
     * @param string $end_time - End time (HH:MM format)
     * @return float|false - Calculated amount or false on error
     */
    private function calculate_rental_amount_by_time($rental_type, $duration_type, $start_time, $end_time) {
        $settings = \WazaBooking\Admin\RentalSettingsManager::get_settings();
        $pricing = $settings['pricing'] ?? [];
        
        if (!isset($pricing[$rental_type]) || !isset($pricing[$rental_type][$duration_type])) {
            return false;
        }
        
        $base_rate = $pricing[$rental_type][$duration_type];
        
        // For non-hourly durations, use fixed pricing
        if ($duration_type !== 'hourly') {
            // Apply tax if configured
            $tax_percentage = $settings['tax_percentage'] ?? 0;
            $tax_amount = ($base_rate * $tax_percentage) / 100;
            return $base_rate + $tax_amount;
        }
        
        // For hourly rentals, calculate based on actual duration
        try {
            $start = new \DateTime($start_time);
            $end = new \DateTime($end_time);
            
            // If end time is before start time, assume it's next day
            if ($end < $start) {
                $end->modify('+1 day');
            }
            
            $interval = $start->diff($end);
            $hours = $interval->h + ($interval->days * 24);
            $minutes = $interval->i;
            
            // Calculate total hours (round up to nearest 0.5 hour for billing)
            $total_hours = $hours + ($minutes / 60);
            
            // Round up to nearest 0.5 hour (30 minutes minimum billing increment)
            $billable_hours = ceil($total_hours * 2) / 2;
            
            // Calculate amount
            $amount = $billable_hours * $base_rate;
            
            // Apply tax if configured
            $tax_percentage = $settings['tax_percentage'] ?? 0;
            $tax_amount = ($amount * $tax_percentage) / 100;
            
            return $amount + $tax_amount;
            
            error_log(sprintf(
                'Waza Rental: %s to %s = %.2f hours (billable: %.2f hours) @ ₹%s/hr = ₹%.2f',
                $start_time, $end_time, $total_hours, $billable_hours, $hourly_rate, $amount
            ));
            
            return $amount;
            
        } catch (\Exception $e) {
            error_log('Waza Rental: Time calculation error - ' . $e->getMessage());
            // Fallback to hourly rate
            return $hourly_rate;
        }
    }
    
    /**
     * Generate QR code for rental
     */
    private function generate_rental_qr($rental_id) {
        try {
            $qr_manager = \WazaBooking\Core\Plugin::get_instance()->get_manager('qr');
            
            if ($qr_manager) {
                $qr_data = [
                    'type' => 'rental',
                    'rental_id' => $rental_id,
                    'timestamp' => time()
                ];
                
                $qr_path = $qr_manager->generate_qr_code(json_encode($qr_data), 'rental-' . $rental_id);
                
                global $wpdb;
                $wpdb->update(
                    $wpdb->prefix . 'waza_rentals',
                    ['qr_code_path' => $qr_path],
                    ['id' => $rental_id],
                    ['%s'],
                    ['%d']
                );
            }
        } catch (\Exception $e) {
            error_log('QR generation failed for rental: ' . $e->getMessage());
        }
    }
    
    /**
     * Send rental confirmation email to customer
     */
    private function send_rental_confirmation($rental_id) {
        global $wpdb;
        
        $rental = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}waza_rentals WHERE id = %d",
            $rental_id
        ));
        
        if (!$rental) {
            return;
        }
        
        $settings = \WazaBooking\Admin\RentalSettingsManager::get_settings();
        $currency = $settings['currency_symbol'] ?? '₹';
        
        $to = $rental->customer_email;
        $subject = sprintf(__('[%s] Studio Rental Confirmation', 'waza-booking'), get_bloginfo('name'));
        
        $message = sprintf(
            __("Dear %s,\n\nThank you for booking our studio! Your rental has been confirmed.\n\nRental Details:\n━━━━━━━━━━━━━━━━\nRental ID: #%d\nType: %s\nDuration: %s\nDate: %s\nTime: %s - %s\nAmount Paid: %s%s\n\nSpecial Requirements: %s\n\nIf you have any questions, please contact us.\n\nBest regards,\n%s", 'waza-booking'),
            $rental->customer_name,
            $rental_id,
            ucwords(str_replace('_', ' ', $rental->rental_type)),
            ucwords(str_replace('_', ' ', $rental->duration_type)),
            date_i18n('F j, Y', strtotime($rental->rental_date)),
            date_i18n('g:i A', strtotime($rental->start_time)),
            date_i18n('g:i A', strtotime($rental->end_time)),
            $currency,
            number_format($rental->total_amount, 2),
            $rental->special_requirements ?: 'None',
            get_bloginfo('name')
        );
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Notify admin about new rental
     */
    private function notify_admin_rental($rental_id) {
        global $wpdb;
        
        $rental = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}waza_rentals WHERE id = %d",
            $rental_id
        ));
        
        if (!$rental) {
            return;
        }
        
        $admin_email = get_option('admin_email');
        $subject = sprintf(__('[%s] New Studio Rental Booking', 'waza-booking'), get_bloginfo('name'));
        
        $message = sprintf(
            __('New studio rental booking received:

Customer: %s
Email: %s
Phone: %s
Rental Type: %s
Duration: %s
Date: %s
Time: %s - %s
Amount: ₹%s

Review and approve: %s', 'waza-booking'),
            $rental->customer_name,
            $rental->customer_email,
            $rental->customer_phone,
            ucwords(str_replace('_', ' ', $rental->rental_type)),
            ucwords(str_replace('_', ' ', $rental->duration_type)),
            date('M j, Y', strtotime($rental->rental_date)),
            date('g:i A', strtotime($rental->start_time)),
            date('g:i A', strtotime($rental->end_time)),
            number_format($rental->total_amount, 2),
            admin_url('admin.php?page=waza-rentals')
        );
        
        wp_mail($admin_email, $subject, $message);
    }
}
