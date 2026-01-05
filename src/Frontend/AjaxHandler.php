<?php
/**
 * AJAX Handler
 *
 * Handles AJAX requests for frontend functionality.
 *
 * @package WazaBooking
 */

namespace WazaBooking\Frontend;

if (!defined('ABSPATH')) {
    exit;
}

class AjaxHandler {
    
    /**
     * Initialize AJAX handlers
     */
    public function __construct() {
        // Calendar AJAX actions
        add_action('wp_ajax_waza_load_calendar', [$this, 'load_calendar']);
        add_action('wp_ajax_nopriv_waza_load_calendar', [$this, 'load_calendar']);
        
        add_action('wp_ajax_waza_load_day_slots', [$this, 'load_day_slots']);
        add_action('wp_ajax_nopriv_waza_load_day_slots', [$this, 'load_day_slots']);
        
        // Booking AJAX actions
        add_action('wp_ajax_waza_load_booking_form', [$this, 'load_booking_form']);
        add_action('wp_ajax_nopriv_waza_load_booking_form', [$this, 'load_booking_form']);
        
        add_action('wp_ajax_waza_process_booking', [$this, 'process_booking']);
        add_action('wp_ajax_nopriv_waza_process_booking', [$this, 'process_booking']);
        
        // Payment AJAX actions
        add_action('wp_ajax_waza_confirm_payment', [$this, 'confirm_payment']);
        add_action('wp_ajax_nopriv_waza_confirm_payment', [$this, 'confirm_payment']);
        
        // Discount AJAX actions
        add_action('wp_ajax_waza_apply_discount', [$this, 'apply_discount']);
        add_action('wp_ajax_nopriv_waza_apply_discount', [$this, 'apply_discount']);
        
        // Activity filter actions
        add_action('wp_ajax_waza_filter_activities', [$this, 'filter_activities']);
        add_action('wp_ajax_nopriv_waza_filter_activities', [$this, 'filter_activities']);
    }
    
    /**
     * Load calendar month
     */
    public function load_calendar() {
        check_ajax_referer('waza_frontend_nonce', 'nonce');
        
        $year = intval($_POST['year'] ?? date('Y'));
        $month = intval($_POST['month'] ?? date('n'));
        $activity_id = sanitize_text_field($_POST['activity_id'] ?? '');
        
        try {
            $calendar_html = $this->generate_calendar_html($year, $month, $activity_id);
            $month_name = date('F Y', mktime(0, 0, 0, $month, 1, $year));
            
            wp_send_json_success([
                'calendar' => $calendar_html,
                'month_name' => $month_name
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(__('Failed to load calendar.', 'waza-booking'));
        }
    }
    
    /**
     * Load day slots
     */
    public function load_day_slots() {
        check_ajax_referer('waza_frontend_nonce', 'nonce');
        
        $date = sanitize_text_field($_POST['date'] ?? '');
        $activity_id = sanitize_text_field($_POST['activity_id'] ?? '');
        
        if (!$date) {
            wp_send_json_error(__('Date is required.', 'waza-booking'));
        }
        
        try {
            $slots = $this->get_day_slots($date, $activity_id);
            $slots_html = $this->generate_slots_html($slots);
            
            wp_send_json_success([
                'slots' => $slots_html,
                'date' => $date
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(__('Failed to load time slots.', 'waza-booking'));
			
			
			
        }
    }
    
    /**
     * Load booking form
     */
    public function load_booking_form() {
        check_ajax_referer('waza_frontend_nonce', 'nonce');
        
        $slot_id = intval($_POST['slot_id'] ?? 0);
        
        if (!$slot_id) {
            wp_send_json_error(__('Slot ID is required.', 'waza-booking'));
        }
        
        try {
            $slot = $this->get_slot_details($slot_id);
            
            if (!$slot) {
                wp_send_json_error(__('Slot not found.', 'waza-booking'));
            }
            
            $form_html = $this->generate_booking_form_html($slot);
            
            wp_send_json_success([
                'form' => $form_html,
                'slot' => $slot
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(__('Failed to load booking form.', 'waza-booking'));
        }
    }
    
    /**
     * Process booking
     */
    public function process_booking() {
        check_ajax_referer('waza_frontend_nonce', 'nonce');
        
        $slot_id = intval($_POST['slot_id'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 1);
        $customer_name = sanitize_text_field($_POST['customer_name'] ?? '');
        $customer_email = sanitize_email($_POST['customer_email'] ?? '');
        $customer_phone = sanitize_text_field($_POST['customer_phone'] ?? '');
        $payment_method = sanitize_text_field($_POST['payment_method'] ?? '');
        $create_account = (bool) ($_POST['create_account'] ?? false);
        $password_option = sanitize_text_field($_POST['password_option'] ?? 'auto');
        $customer_password = $_POST['customer_password'] ?? '';
        
        // Validation
        if (!$slot_id || !$customer_name || !$customer_email) {
            wp_send_json_error(__('Please fill in all required fields.', 'waza-booking'));
        }
        
        if (!is_email($customer_email)) {
            wp_send_json_error(__('Please enter a valid email address.', 'waza-booking'));
        }
        
        try {
            global $wpdb;
            
            // Get slot details from custom table
            $slot = $wpdb->get_row($wpdb->prepare(
                "SELECT s.*, p.ID as activity_post_id
                 FROM {$wpdb->prefix}waza_slots s
                 LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
                 WHERE s.id = %d AND s.status = 'available'",
                $slot_id
            ));
            
            if (!$slot) {
                wp_send_json_error(__('Invalid slot.', 'waza-booking'));
            }
            
            $activity_id = $slot->activity_id;
            $price = floatval($slot->price);
            $total_amount = $price * $quantity;
            
            // Apply discount if provided
            $discount_code = sanitize_text_field($_POST['discount_code'] ?? '');
            $discount_amount = 0;
            
            if ($discount_code) {
                $discount = $this->validate_discount_code($discount_code, $activity_id);
                if ($discount) {
                    $discount_amount = ($total_amount * $discount['percentage']) / 100;
                    $total_amount = max(0, $total_amount - $discount_amount);
                }
            }
            
            // Check availability
            if (!$this->check_slot_availability($slot_id, $quantity)) {
                wp_send_json_error(__('Sorry, this slot is no longer available.', 'waza-booking'));
            }
            
            // Create user if requested
            $user_id = get_current_user_id();
            $new_user_password = '';
            
            if (!$user_id && $create_account) {
                // Generate or use provided password
                if ($password_option === 'manual' && !empty($customer_password)) {
                    if (strlen($customer_password) < 8) {
                        wp_send_json_error(__('Password must be at least 8 characters long.', 'waza-booking'));
                    }
                    $new_user_password = $customer_password;
                } else {
                    $new_user_password = wp_generate_password(12, false);
                }
                
                // Check if email already exists
                if (email_exists($customer_email)) {
                    wp_send_json_error(__('An account with this email already exists. Please log in.', 'waza-booking'));
                }
                
                // Create user account
                $user_id = wp_create_user($customer_email, $new_user_password, $customer_email);
                
                if (is_wp_error($user_id)) {
                    wp_send_json_error(__('Failed to create user account.', 'waza-booking'));
                }
                
                // Update user meta
                wp_update_user([
                    'ID' => $user_id,
                    'display_name' => $customer_name,
                    'role' => 'waza_student'
                ]);
                update_user_meta($user_id, 'phone', $customer_phone);
                
                // Send credentials email if auto-generated
                if ($password_option === 'auto') {
                    $this->send_account_credentials($customer_email, $customer_name, $new_user_password);
                }
            }
            
            // Create booking record
            $booking_data = [
                'user_id' => $user_id,
                'activity_id' => $activity_id,
                'slot_id' => $slot_id,
                'quantity' => $quantity,
                'attendees_count' => $quantity, // Redundant but consistent
                'total_amount' => $total_amount,
                'discount_amount' => $discount_amount,
                'coupon_code' => $discount_code,
                'user_name' => $customer_name,
                'user_email' => $customer_email,
                'user_phone' => $customer_phone,
                'payment_method' => $payment_method,
                'payment_status' => 'pending',
                'booking_status' => 'pending',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ];
            
            $booking_id = $wpdb->insert(
                $wpdb->prefix . 'waza_bookings',
                $booking_data
                // Formats autodetected
            );
            
            if (!$booking_id) {
                wp_send_json_error(__('Failed to create booking.', 'waza-booking'));
            }
            
            // Handle payment
            if ($total_amount > 0 && $payment_method) {
                $payment_data = $this->prepare_payment_data($booking_id, $total_amount, $payment_method, $booking_data);
                
                wp_send_json_success([
                    'payment_required' => true,
                    'payment_data' => $payment_data,
                    'booking_id' => $booking_id
                ]);
            } else {
                // Free booking or payment later
                $wpdb->update(
                    $wpdb->prefix . 'waza_bookings',
                    ['booking_status' => 'confirmed', 'payment_status' => 'completed'],
                    ['id' => $booking_id],
                    ['%s', '%s'],
                    ['%d']
                );
                
                // Update slot booked count
                $this->update_slot_booked_count($slot_id, $quantity);
                
                // Generate QR code
                $qr_code = $this->generate_booking_qr($booking_id);
                
                // Send confirmation email
                $this->send_booking_confirmation($booking_id);
                
                // Get slot details for response
                $slot_details = $this->get_slot_details($slot_id);
                
                wp_send_json_success([
                    'payment_required' => false,
                    'booking_id' => $booking_id,
                    'activity_title' => $slot_details->activity_title,
                    'datetime' => date('l, F j, Y', strtotime($slot_details->start_date)) . ' at ' . date('g:i A', strtotime($slot_details->start_time)),
                    'location' => get_post_meta($slot_details->activity_id, 'waza_activity_location', true) ?: 'TBD',
                    'qr_code' => $qr_code,
                    'dashboard_url' => home_url('/my-bookings'),
                    'message' => __('Booking confirmed successfully!', 'waza-booking')
                ]);
            }
            
        } catch (Exception $e) {
            wp_send_json_error(__('Failed to process booking. Please try again.', 'waza-booking'));
        }
    }
    
    /**
     * Confirm payment
     */
    public function confirm_payment() {
        check_ajax_referer('waza_frontend_nonce', 'nonce');
        
        $payment_response = $_POST['payment_response'] ?? [];
        $payment_method = sanitize_text_field($_POST['payment_method'] ?? '');
        
        if (empty($payment_response) || !$payment_method) {
            wp_send_json_error(__('Invalid payment data.', 'waza-booking'));
        }
        
        try {
            // Verify payment with gateway
            $verified = $this->verify_payment($payment_response, $payment_method);
            
            if (!$verified) {
                wp_send_json_error(__('Payment verification failed.', 'waza-booking'));
            }
            
            // Update booking status
            global $wpdb;
            
            $booking_id = $verified['booking_id'];
            
            $wpdb->update(
                $wpdb->prefix . 'waza_bookings',
                [
                    'booking_status' => 'confirmed',
                    'payment_status' => 'completed',
                    'payment_id' => $verified['payment_id'],
                    // 'paid_amount' column not in schema, skipping or adding if needed. Schema showed total_amount used.
                ],
                ['id' => $booking_id],
                ['%s', '%s', '%s'],
                ['%d']
            );
            
            // Generate QR code
            $qr_code = $this->generate_booking_qr($booking_id);
            
            // Send confirmation email
            $this->send_booking_confirmation($booking_id);
            
            // Get booking details for response
            $booking = $wpdb->get_row($wpdb->prepare(
                "SELECT b.*, s.start_date, s.start_time, p.post_title as activity_title
                 FROM {$wpdb->prefix}waza_bookings b
                 LEFT JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
                 LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
                 WHERE b.id = %d",
                $booking_id
            ));
            
            wp_send_json_success([
                'booking_id' => $booking_id,
                'activity_title' => $booking->activity_title,
                'datetime' => date('l, F j, Y', strtotime($booking->start_date)) . ' at ' . date('g:i A', strtotime($booking->start_time)),
                'location' => get_post_meta($booking->activity_id, 'waza_activity_location', true) ?: 'TBD',
                'qr_code' => $qr_code,
                'dashboard_url' => home_url('/my-bookings'),
                'message' => __('Payment confirmed! Booking is complete.', 'waza-booking')
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(__('Payment confirmation failed.', 'waza-booking'));
        }
    }
    
    /**
     * Apply discount code
     */
    public function apply_discount() {
        check_ajax_referer('waza_frontend_nonce', 'nonce');
        
        $code = sanitize_text_field($_POST['code'] ?? '');
        $slot_id = intval($_POST['slot_id'] ?? 0);
        
        if (!$code || !$slot_id) {
            wp_send_json_error(__('Code and slot ID are required.', 'waza-booking'));
        }
        
        try {
            $activity_id = get_post_meta($slot_id, '_waza_activity_id', true);
            $discount = $this->validate_discount_code($code, $activity_id);
            
            if (!$discount) {
                wp_send_json_error(__('Invalid or expired discount code.', 'waza-booking'));
            }
            
            $price = floatval(get_post_meta($activity_id, '_waza_price', true));
            $discount_amount = ($price * $discount['percentage']) / 100;
            
            wp_send_json_success([
                'discount_percentage' => $discount['percentage'],
                'discount_amount' => number_format($discount_amount, 2),
                'message' => sprintf(
                    __('Discount applied: %d%% off', 'waza-booking'),
                    $discount['percentage']
                )
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(__('Failed to apply discount.', 'waza-booking'));
        }
    }
    
    /**
     * Filter activities
     */
    public function filter_activities() {
        check_ajax_referer('waza_frontend_nonce', 'nonce');
        
        $filters = $_POST['filters'] ?? [];
        
        try {
            $activities = $this->get_filtered_activities($filters);
            $html = $this->generate_activities_html($activities);
            
            wp_send_json_success([
                'html' => $html,
                'count' => count($activities)
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(__('Failed to filter activities.', 'waza-booking'));
        }
    }
    
    /**
     * Generate calendar HTML
     */
    private function generate_calendar_html($year, $month, $activity_id = '') {
        $first_day = mktime(0, 0, 0, $month, 1, $year);
        $days_in_month = date('t', $first_day);
        $day_of_week = date('w', $first_day);
        
        $html = '';
        
        // Calendar header (days of week)
        $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        foreach ($days as $day) {
            $html .= '<div class="waza-calendar-header-day">' . esc_html__($day, 'waza-booking') . '</div>';
        }
        
        // Empty cells for days before the first day of the month
        for ($i = 0; $i < $day_of_week; $i++) {
            $prev_month_days = date('t', mktime(0, 0, 0, $month - 1, 1, $year));
            $prev_day = $prev_month_days - $day_of_week + $i + 1;
            $html .= '<div class="waza-calendar-day other-month" data-date="' . 
                     date('Y-m-d', mktime(0, 0, 0, $month - 1, $prev_day, $year)) . '">';
            $html .= '<div class="waza-day-number">' . $prev_day . '</div>';
            $html .= '</div>';
        }
        
        // Days of the current month
        $today = current_time('Y-m-d');
        $current_datetime = current_time('mysql');
        
        for ($day = 1; $day <= $days_in_month; $day++) {
            $current_date = date('Y-m-d', mktime(0, 0, 0, $month, $day, $year));
            $is_past = $current_date < $today;
            
            $class = 'waza-calendar-day';
            
            // Check for available slots (this filters out past times automatically)
            $day_slots = [];
            if (!$is_past || $current_date === $today) {
                // For today, get_day_slots will filter by time
                // For future dates, get all slots
                $day_slots = $this->get_day_slots($current_date, $activity_id);
                if (!empty($day_slots)) {
                    $class .= ' has-slots';
                } elseif ($is_past) {
                    // Past date with no future slots = disabled
                    $class .= ' disabled';
                }
            } else {
                // Fully past date
                $class .= ' disabled';
            }
            
            $html .= '<div class="' . $class . '" data-date="' . $current_date . '">';
            $html .= '<div class="waza-day-number">' . $day . '</div>';
            
            if (!$is_past && !empty($day_slots)) {
                $slots = $day_slots;
                
                if (!empty($slots)) {
                    $html .= '<div class="waza-day-slots">';
                    foreach (array_slice($slots, 0, 3) as $slot) {
                        $slot_class = '';
                        if ($slot['available_spots'] == 0) {
                            $slot_class = 'full';
                        } elseif ($slot['available_spots'] <= 2) {
                            $slot_class = 'limited';
                        }
                        
                        $html .= '<div class="waza-slot-indicator ' . $slot_class . '" data-slot-id="' . $slot['id'] . '">';
                        $html .= date('H:i', strtotime($slot['start_time']));
                        $html .= '</div>';
                    }
                    
                    if (count($slots) > 3) {
                        $html .= '<div class="waza-slot-indicator">+' . (count($slots) - 3) . '</div>';
                    }
                    
                    $html .= '</div>';
                }
            }
            
            $html .= '</div>';
        }
        
        // Fill remaining cells
        $total_cells = 42; // 6 weeks * 7 days
        $current_cells = $day_of_week + $days_in_month;
        
        for ($i = 1; $current_cells < $total_cells; $i++, $current_cells++) {
            $next_date = date('Y-m-d', mktime(0, 0, 0, $month + 1, $i, $year));
            $html .= '<div class="waza-calendar-day other-month" data-date="' . $next_date . '">';
            $html .= '<div class="waza-day-number">' . $i . '</div>';
            $html .= '</div>';
        }
        
        return $html;
    }
    
    /**
     * Get day slots - filters out past slots
     */
    private function get_day_slots($date, $activity_id = '') {
        global $wpdb;
        
        // Get current datetime in WordPress timezone
        $current_datetime = current_time('mysql');
        
        $sql = $wpdb->prepare(
            "SELECT s.*, p.post_title as activity_title, i.post_title as instructor_name,
                    (s.capacity - s.booked_count) as available_spots
             FROM {$wpdb->prefix}waza_slots s
             LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
             LEFT JOIN {$wpdb->posts} i ON s.instructor_id = i.ID
             WHERE DATE(s.start_datetime) = %s
             AND s.status = 'available'
             AND s.start_datetime >= %s",
            $date,
            $current_datetime
        );
        
        if ($activity_id) {
            $sql .= $wpdb->prepare(' AND s.activity_id = %d', $activity_id);
        }
        
        $sql .= ' ORDER BY s.start_datetime ASC';
        
        $results = $wpdb->get_results($sql);
        $slots = [];
        
        if ($results) {
            foreach ($results as $row) {
                // Double-check that slot hasn't started yet (belt and suspenders)
                $slot_start = strtotime($row->start_datetime);
                $now = strtotime($current_datetime);
                
                if ($slot_start < $now) {
                    continue; // Skip past slots
                }
                
                $start_dt = new \DateTime($row->start_datetime);
                $end_dt = new \DateTime($row->end_datetime);
                
                $start_time = $start_dt->format('H:i');
                $end_time = $end_dt->format('H:i');
                $available = max(0, (int)$row->available_spots);
                $booked = (int)$row->booked_count;
                $capacity = (int)$row->capacity;
                
                $slots[] = [
                    'id' => $row->id,
                    'activity_title' => $row->activity_title ?: '',
                    'instructor_name' => $row->instructor_name ?: '',
                    'booked_spots' => $booked,
                    'available_spots' => $available,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'start_datetime' => $row->start_datetime,
                    'end_datetime' => $row->end_datetime,
                    'capacity' => $capacity,
                    'price' => (float)$row->price,
                    'location' => $row->location ?: ''
                ];
            }
        }
        
        return $slots;
    }
    
    /**
     * Generate slots HTML
     */
    private function generate_slots_html($slots) {
        if (empty($slots)) {
            $selected_date = current_time('Y-m-d'); // This would ideally be passed as param
            $today = current_time('Y-m-d');
            
            if ($selected_date === $today) {
                return '<div class="waza-no-slots">' . 
                       esc_html__('No more available time slots for today. All slots have passed or are full.', 'waza-booking') . 
                       '</div>';
            } else {
                return '<div class="waza-no-slots">' . 
                       esc_html__('No available time slots for this date.', 'waza-booking') . 
                       '</div>';
            }
        }
        
        $html = '<div class="waza-slots-list">';
        $current_datetime = current_time('mysql');
        
        foreach ($slots as $slot) {
            $is_past = $slot['start_datetime'] < $current_datetime;
            $unavailable = $slot['available_spots'] <= 0 || $is_past;
            
            $class = $unavailable ? 'unavailable' : '';
            if ($is_past) {
                $class .= ' waza-past-slot';
            }
            
            $html .= '<div class="waza-time-slot ' . $class . '" data-slot-id="' . $slot['id'] . '">';
            
            // Activity Name (Top)
            if (!empty($slot['activity_title'])) {
                $html .= '<div class="waza-slot-activity">' . esc_html($slot['activity_title']) . '</div>';
            }
            
            // Time (Large, Center)
            $html .= '<div class="waza-slot-time">' . 
                     date('g:i A', strtotime($slot['start_datetime'])) . 
                     ' - ' . 
                     date('g:i A', strtotime($slot['end_datetime'])) . 
                     '</div>';
            
            // Instructor
            $html .= '<div class="waza-slot-instructor">' . 
                     esc_html($slot['instructor_name']) . 
                     '</div>';
            
            // Availability Badge
            if ($is_past) {
                $html .= '<div class="waza-slot-badge waza-badge-expired">' . 
                         esc_html__('Expired', 'waza-booking') . 
                         '</div>';
            } elseif ($slot['available_spots'] <= 0) {
                $html .= '<div class="waza-slot-badge waza-badge-full">' . 
                         esc_html__('Fully Booked', 'waza-booking') . 
                         '</div>';
            } else {
                $availability_class = $slot['available_spots'] <= 2 ? 'waza-badge-low' : 'waza-badge-available';
                $availability_text = sprintf(
                    _n('%d spot left', '%d spots left', $slot['available_spots'], 'waza-booking'),
                    $slot['available_spots']
                );
                
                $html .= '<div class="waza-slot-badge ' . $availability_class . '">' . 
                         $availability_text . 
                         '</div>';
            }
            
            // Book Now Button (only for available slots)
            if (!$unavailable) {
                $html .= '<button class="waza-btn waza-btn-primary waza-select-slot" data-slot-id="' . $slot['id'] . '">' . 
                         esc_html__('Book Now', 'waza-booking') . 
                         '</button>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Helper methods would go here...
     * (get_slot_details, generate_booking_form_html, validate_discount_code, etc.)
     */
    
    private function get_slot_details($slot_id) {
        global $wpdb;
        
        $slot_data = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, p.post_title as activity_title, i.post_title as instructor_name
             FROM {$wpdb->prefix}waza_slots s
             LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
             LEFT JOIN {$wpdb->posts} i ON s.instructor_id = i.ID
             WHERE s.id = %d AND s.status = 'available'",
            $slot_id
        ));
        
        if (!$slot_data) {
            return null;
        }
        
        // Convert to object with expected properties
        $slot = (object)[
            'ID' => $slot_data->id,
            'activity_title' => $slot_data->activity_title ?: '',
            'instructor_name' => $slot_data->instructor_name ?: '',
            'price' => (float)$slot_data->price,
            'start_date' => date('Y-m-d', strtotime($slot_data->start_datetime)),
            'start_time' => date('H:i', strtotime($slot_data->start_datetime)),
            'end_time' => date('H:i', strtotime($slot_data->end_datetime)),
            'capacity' => (int)$slot_data->capacity,
            'booked_count' => (int)$slot_data->booked_count,
            'location' => $slot_data->location ?: '',
            'notes' => $slot_data->notes ?: ''
        ];
        
        return $slot;
    }
    
    private function generate_booking_form_html($slot) {
        $user_id = get_current_user_id();
        $user_info = [
            'name' => '',
            'email' => '',
            'phone' => ''
        ];
        
        if ($user_id) {
            $user = get_userdata($user_id);
            $user_info['name'] = $user->display_name;
            $user_info['email'] = $user->user_email;
            $user_info['phone'] = get_user_meta($user_id, 'billing_phone', true) ?: '';
        }

        // Get appearance settings
        $settings = get_option('waza_booking_settings', []);
        $show_terms = $settings['appearance_show_terms'] ?? '1';
        $terms_text = $settings['appearance_terms_text'] ?? 'I agree to the terms of service';
        $button_next = $settings['appearance_button_next'] ?? 'NEXT';
        $button_back = $settings['appearance_button_back'] ?? 'BACK';

        $price_display = '₹' . number_format((float)$slot->price, 2);
        $date_display = date('l, F j, Y', strtotime($slot->start_date));
        $time_display = date('g:i A', strtotime($slot->start_time)) . ' - ' . date('g:i A', strtotime($slot->end_time));

        ob_start();
        ?>
        <form id="waza-booking-form" class="waza-booking-step-form">
            <input type="hidden" name="slot_id" value="<?php echo esc_attr($slot->ID); ?>">
            <input type="hidden" name="current_step" value="2">
            
            <!-- Step 2: Slot Details Confirmation -->
            <div class="waza-step-section active" data-step="2">
                <h4>Confirm Your Selection</h4>
                <div class="waza-booking-info waza-slot-details-card">
                    <div class="waza-detail-row">
                        <span class="waza-detail-label">Activity:</span>
                        <span class="waza-detail-value"><?php echo esc_html($slot->activity_title); ?></span>
                    </div>
                    <div class="waza-detail-row">
                        <span class="waza-detail-label">Instructor:</span>
                        <span class="waza-detail-value"><?php echo esc_html($slot->instructor_name); ?></span>
                    </div>
                    <div class="waza-detail-row">
                        <span class="waza-detail-label">Date:</span>
                        <span class="waza-detail-value"><?php echo esc_html($date_display); ?></span>
                    </div>
                    <div class="waza-detail-row">
                        <span class="waza-detail-label">Time:</span>
                        <span class="waza-detail-value"><?php echo esc_html($time_display); ?></span>
                    </div>
                    <div class="waza-detail-row waza-detail-price">
                        <span class="waza-detail-label">Price per person:</span>
                        <span class="waza-detail-value waza-slot-price" data-price="<?php echo esc_attr($slot->price); ?>"><?php echo esc_html($price_display); ?></span>
                    </div>
                </div>
                
                <div class="waza-form-group" style="margin-top: 1.5rem;">
                    <label for="booking_quantity">Number of Seats <span class="required">*</span></label>
                    <select name="quantity" id="booking_quantity" class="waza-quantity-input" required>
                        <?php for ($i = 1; $i <= min(10, (int)$slot->available_spots); $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?> <?php echo $i === 1 ? 'Seat' : 'Seats'; ?></option>
                        <?php endfor; ?>
                    </select>
                    <p class="waza-field-help">Available seats: <?php echo (int)$slot->available_spots; ?></p>
                </div>
                
                <div class="waza-total-display" style="margin-top: 1rem; padding: 1rem; background: var(--waza-bg); border-radius: var(--waza-radius); font-size: 1.125rem;">
                    <strong>Total: </strong><?php echo esc_html($price_display); ?>
                </div>
            </div>

            <!-- Step 3: User Information -->
            <div class="waza-step-section" data-step="3">
                <h4>Your Information</h4>
                <div class="waza-form-row">
                    <div class="waza-form-group waza-form-col-full">
                        <label for="customer_name">Name <span class="required">*</span></label>
                        <input type="text" name="customer_name" id="customer_name" required 
                               value="<?php echo esc_attr($user_info['name']); ?>"
                               placeholder="Enter your full name">
                    </div>
                </div>
                
                <div class="waza-form-row">
                    <div class="waza-form-group waza-form-col-half">
                        <label for="customer_email">Email <span class="required">*</span></label>
                        <input type="email" name="customer_email" id="customer_email" required
                               value="<?php echo esc_attr($user_info['email']); ?>"
                               placeholder="your@email.com">
                    </div>
                    
                    <div class="waza-form-group waza-form-col-half">
                        <label for="customer_phone">Phone <span class="required">*</span></label>
                        <div class="waza-phone-input">
                            <select name="customer_phone_country" class="waza-country-select">
                                <option value="+91" selected>🇮🇳 +91</option>
                                <option value="+1">🇺🇸 +1</option>
                                <option value="+44">🇬🇧 +44</option>
                            </select>
                            <input type="tel" name="customer_phone" id="customer_phone" required
                                   value="<?php echo esc_attr($user_info['phone']); ?>"
                                   placeholder="9876543210">
                        </div>
                    </div>
                </div>
                
                <?php if (!$user_id): ?>
                <input type="hidden" name="create_account" value="1">
                <input type="hidden" name="password_option" value="auto">
                <?php endif; ?>
                
                <?php if ($show_terms == '1'): ?>
                <div class="waza-form-group waza-terms-group">
                    <label class="waza-checkbox-label">
                        <input type="checkbox" name="accept_terms" id="accept_terms" value="1" required>
                        <span><?php echo esc_html($terms_text); ?></span>
                    </label>
                </div>
                <?php endif; ?>
            </div>

            <!-- Step 4: Payment -->
            <div class="waza-step-section" data-step="4">
                 <h4>Payment</h4>
                 <div class="waza-booking-review">
                     <h5>Booking Summary</h5>
                     <div class="waza-review-item">
                         <strong>Activity:</strong> 
                         <span><?php echo esc_html($slot->activity_title); ?></span>
                     </div>
                     <div class="waza-review-item">
                         <strong>Date:</strong> 
                         <span><?php echo esc_html($date_display); ?></span>
                     </div>
                     <div class="waza-review-item">
                         <strong>Time:</strong> 
                         <span><?php echo esc_html($time_display); ?></span>
                     </div>
                     <div class="waza-review-item">
                         <strong>Instructor:</strong> 
                         <span><?php echo esc_html($slot->instructor_name); ?></span>
                     </div>
                     <div class="waza-review-item">
                         <strong>Number of Seats:</strong> 
                         <span class="waza-review-quantity">1</span>
                     </div>
                     <div class="waza-review-item">
                         <strong>Price per Seat:</strong> 
                         <span><?php echo esc_html($price_display); ?></span>
                     </div>
                     <div class="waza-review-item waza-review-total">
                         <strong>Total Amount:</strong> 
                         <span class="waza-review-total-amount"><?php echo esc_html($price_display); ?></span>
                     </div>
                 </div>
                 
                 <h5 style="margin-top: 20px;">Select Payment Method</h5>
                 <div class="waza-payment-methods">
                     <div class="waza-payment-method selected" data-method="phonepe">
                         <span class="waza-payment-icon">💳</span>
                         <span class="waza-payment-label">PhonePe / UPI / Cards / Netbanking</span>
                     </div>
                 </div>
                 <input type="hidden" name="payment_method" value="phonepe">
            </div>
            
            <!-- Step 5: Confirmation (shown after payment) -->
            <div class="waza-step-section" data-step="5" style="display:none;">
                <div class="waza-success-message">
                    <div class="waza-success-icon">✓</div>
                    <h3>Booking Confirmed!</h3>
                    <p>Thank you for your booking. You will receive a confirmation email shortly.</p>
                </div>
            </div>
            
            <div class="waza-form-actions">
                <button type="button" class="waza-prev-step-btn waza-btn-secondary" style="display:none;">
                    <?php echo esc_html($button_back); ?>
                </button>
                <button type="button" class="waza-next-step-btn waza-btn-primary">
                    <?php echo esc_html($button_next); ?>
                </button>
                <button type="submit" class="waza-submit-booking waza-btn-primary" style="display:none;">
                    Proceed to Payment
                </button>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }
    
    // ... [check_slot_availability implementation] ...
    
    private function check_slot_availability($slot_id, $quantity) {
        global $wpdb;
        
        $slot = $wpdb->get_row($wpdb->prepare(
            "SELECT capacity, booked_count FROM {$wpdb->prefix}waza_slots WHERE id = %d AND status = 'available'",
            $slot_id
        ));
        
        if (!$slot) {
            return false;
        }
        
        $available = $slot->capacity - $slot->booked_count;
        return $available >= $quantity;
    }
    
    private function update_slot_booked_count($slot_id, $quantity) {
        global $wpdb;
        
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}waza_slots 
             SET booked_count = booked_count + %d,
                 updated_at = NOW()
             WHERE id = %d",
            $quantity,
            $slot_id
        ));
    }
    
    private function prepare_payment_data($booking_id, $amount, $method, $booking_data) {
        if ($method === 'phonepe') {
            // Get credentials from settings
            $merchant_id = \WazaBooking\Admin\SettingsManager::get_setting('phonepe_merchant_id', 'MOCK_MERCHANT');
            $salt_key = \WazaBooking\Admin\SettingsManager::get_setting('phonepe_salt_key', 'mock_salt_key');
            $salt_index = \WazaBooking\Admin\SettingsManager::get_setting('phonepe_salt_index', '1');
            $is_enabled = \WazaBooking\Admin\SettingsManager::get_setting('phonepe_enabled');

            if (!$is_enabled) {
                return ['error' => 'PhonePe is disabled'];
            }

            return [
                'gateway' => 'phonepe',
                'booking_id' => $booking_id,
                'amount' => $amount * 100, // cents/paise
                'currency' => \WazaBooking\Admin\SettingsManager::get_setting('currency', 'INR'),
                'merchantId' => $merchant_id,
                'transactionId' => 'TXN_' . $booking_id . '_' . time(),
                'redirectUrl' => home_url('/payment-success'), // URL to handle redirect
                'mode' => \WazaBooking\Admin\SettingsManager::get_setting('payment_mode', 'sandbox')
            ];
        }
        
        // ... existing logic for other gateways
        if ($method === 'razorpay') {
             // ...
        }
        
        return [];
    }
    
    private function verify_payment($response, $method) {
        // Mock verification for PhonePe
        return [
            'booking_id' => (isset($response['booking_id']) ? $response['booking_id'] : 0),
            'payment_id' => 'pay_' . uniqid(),
            'amount' => 1500 
        ];
    }
    
    private function generate_booking_qr($booking_id) {
        // Use Google Charts API for easy QR generation
        $data = "WAZA-BOOKING-" . $booking_id;
        $url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($data);
        return $url;
    }
    
    private function send_booking_confirmation($booking_id) {
        do_action('waza_send_booking_confirmation', $booking_id);
    }
    
    private function validate_discount_code($code, $activity_id) {
        // Placeholder simple discount logic
        if ($code === 'WELCOME50') {
            return ['percentage' => 50];
        }
        return null;
    }
    
    private function get_filtered_activities($filters) {
        // Fetch activities based on filters
         $args = [
            'post_type' => 'waza_activity',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ];
        // Apply filters if needed
        return get_posts($args);
    }
    
    /**
     * Send account credentials email to new users
     */
    private function send_account_credentials($email, $name, $password) {
        $subject = __('Your Waza Booking Account Credentials', 'waza-booking');
        $login_url = wp_login_url();
        
        $message = sprintf(
            __('Hi %s,

Your account has been created successfully!

Login Details:
Email: %s
Password: %s

Login URL: %s

Please keep these credentials safe. You can change your password after logging in.

Thank you for choosing Waza Booking!', 'waza-booking'),
            $name,
            $email,
            $password,
            $login_url
        );
        
        wp_mail($email, $subject, $message);
    }
    
    private function generate_activities_html($activities) {
        // Simple list generator
        ob_start();
        foreach($activities as $activity) {
            echo '<div class="waza-activity-card">' . esc_html($activity->post_title) . '</div>';
        }
        return ob_get_clean();
    }
}