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
        
        // Get attendee details (for multi-seat bookings)
        $attendee_names = isset($_POST['attendee_name']) ? array_map('sanitize_text_field', $_POST['attendee_name']) : [];
        $attendee_emails = isset($_POST['attendee_email']) ? array_map('sanitize_email', $_POST['attendee_email']) : [];
        $attendee_phones = isset($_POST['attendee_phone']) ? array_map('sanitize_text_field', $_POST['attendee_phone']) : [];
        
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
                 WHERE s.id = %d AND s.status IN ('active', 'available')",
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
            
            // For guest users, handle account creation preference
            $user_id = get_current_user_id();
            $needs_account_creation = false;
            $new_user_password = '';
            
            if (!$user_id && $create_account) {
                // Check if email already exists - if so, skip account creation
                if (email_exists($customer_email)) {
                    // Email exists - user can still book, just won't create duplicate account
                    $needs_account_creation = false;
                } else {
                    // Mark for account creation after payment
                    $needs_account_creation = true;
                    
                    // Generate password now (we'll create the account after payment)
                    if ($password_option === 'manual' && !empty($customer_password)) {
                        if (strlen($customer_password) < 8) {
                            wp_send_json_error(__('Password must be at least 8 characters long.', 'waza-booking'));
                        }
                        $new_user_password = $customer_password;
                    } else {
                        $new_user_password = wp_generate_password(12, false);
                    }
                }
            }
            
            // Create booking record (with pending status)
            $booking_data = [
                'user_id' => $user_id ?: null,
                'activity_id' => $activity_id,  // Add activity_id
                'slot_id' => $slot_id,
                'quantity' => $quantity,  // Add quantity
                'attendees_count' => $quantity,
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
            
            $inserted = $wpdb->insert(
                $wpdb->prefix . 'waza_bookings',
                $booking_data
                // Formats autodetected
            );
            
            if (!$inserted) {
                wp_send_json_error(__('Failed to create booking.', 'waza-booking'));
            }
            
            // Get the actual booking ID
            $booking_id = $wpdb->insert_id;
            
            // Log booking creation activity
            do_action('waza_log_activity', 'booking_created', 'booking', $booking_id, [
                'description' => sprintf('New booking created for %s', $booking_data['user_name']),
                'user_email' => $booking_data['user_email'],
                'activity_name' => $activity->post_title ?? 'Unknown',
                'quantity' => $booking_data['quantity'],
                'total_amount' => $total_amount
            ]);
            
            // Save attendee details if provided (for multi-seat bookings)
            if (!empty($attendee_names) && count($attendee_names) > 0) {
                $this->save_booking_attendees($booking_id, $attendee_names, $attendee_emails, $attendee_phones);
            }
            
            // Store account creation info for later (using options since post doesn't exist yet)
            if ($needs_account_creation) {
                update_option("waza_pending_account_{$booking_id}", [
                    'password' => $new_user_password,
                    'password_option' => $password_option,
                    'email' => sanitize_email($_POST['user_email']),
                    'name' => sanitize_text_field($_POST['user_name'])
                ], false);
            }
            
            // Handle payment
            if ($total_amount > 0 && $payment_method) {
                // Return booking info for payment processing
                // Payment will be handled by PaymentManager via AJAX
                wp_send_json_success([
                    'payment_required' => true,
                    'booking_id' => $booking_id,
                    'total_amount' => $total_amount,
                    'payment_method' => $payment_method,
                    'customer_name' => $customer_name,
                    'customer_email' => $customer_email,
                    'activity_name' => $activity->post_title ?? 'Activity Booking'
                ]);
            } else {
                // Free booking or payment later - create account immediately
                if ($needs_account_creation) {
                    $password = get_post_meta($booking_id, '_pending_account_password', true);
                    $password_option = get_post_meta($booking_id, '_pending_account_password_option', true);
                    
                    // Create user account
                    $new_user_id = wp_create_user($customer_email, $password, $customer_email);
                    
                    if (!is_wp_error($new_user_id)) {
                        wp_update_user([
                            'ID' => $new_user_id,
                            'display_name' => $customer_name,
                            'role' => 'waza_student'
                        ]);
                        update_user_meta($new_user_id, 'phone', $customer_phone);
                        
                        // Update booking with user_id
                        $wpdb->update(
                            $wpdb->prefix . 'waza_bookings',
                            ['user_id' => $new_user_id],
                            ['id' => $booking_id],
                            ['%d'],
                            ['%d']
                        );
                        
                        // Send credentials email if auto-generated
                        if ($password_option === 'auto') {
                            $this->send_account_credentials($customer_email, $customer_name, $password);
                        }
                        
                        // Clean up meta
                        delete_post_meta($booking_id, '_pending_account_creation');
                        delete_post_meta($booking_id, '_pending_account_password');
                        delete_post_meta($booking_id, '_pending_account_password_option');
                    }
                }
                
                $wpdb->update(
                    $wpdb->prefix . 'waza_bookings',
                    ['booking_status' => 'confirmed', 'payment_status' => 'completed'],
                    ['id' => $booking_id],
                    ['%s', '%s'],
                    ['%d']
                );
                
                // Update slot booked count
                $this->update_slot_booked_count($slot_id, $quantity);
                
                // Create booking post for admin panel
                $this->create_booking_post($booking_id);
                
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
            
            // Get booking to update slot count and create user if needed
            $booking = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}waza_bookings WHERE id = %d",
                $booking_id
            ));
            
            if ($booking) {
                // Create user account now (after successful payment)
                if (!$booking->user_id) {
                    $pending_account = get_option("waza_pending_account_{$booking_id}");
                    
                    if ($pending_account && is_array($pending_account)) {
                        $password = $pending_account['password'];
                        $password_option = $pending_account['password_option'];
                        
                        // Create user account
                        $user_id = wp_create_user($booking->user_email, $password, $booking->user_email);
                        
                        if (!is_wp_error($user_id)) {
                            // Update user meta
                            wp_update_user([
                                'ID' => $user_id,
                                'display_name' => $booking->user_name,
                                'role' => 'waza_student'
                            ]);
                            update_user_meta($user_id, 'phone', $booking->user_phone);
                            
                            // Update booking with user_id
                            $wpdb->update(
                                $wpdb->prefix . 'waza_bookings',
                                ['user_id' => $user_id],
                                ['id' => $booking_id],
                                ['%d'],
                                ['%d']
                            );
                            
                            // Send credentials email if auto-generated
                            if ($password_option === 'auto') {
                                $this->send_account_credentials($booking->user_email, $booking->user_name, $password);
                            }
                            
                            // Clean up option
                            delete_option("waza_pending_account_{$booking_id}");
                        }
                    }
                }
                
                // Update slot booked count
                $this->update_slot_booked_count($booking->slot_id, $booking->quantity);
                
                // Create booking post for admin panel
                $this->create_booking_post($booking_id);
            }
            
            // Generate QR code
            $qr_code = $this->generate_booking_qr($booking_id);
            
            // Send confirmation email
            $this->send_booking_confirmation($booking_id);
            
            // Get booking details for response
            $booking = $wpdb->get_row($wpdb->prepare(
                "SELECT b.*, s.start_datetime, s.end_datetime, p.post_title as activity_title
                 FROM {$wpdb->prefix}waza_bookings b
                 LEFT JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
                 LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
                 WHERE b.id = %d",
                $booking_id
            ));
            
            wp_send_json_success([
                'booking_id' => $booking_id,
                'activity_title' => $booking->activity_title,
                'datetime' => date('l, F j, Y', strtotime($booking->start_datetime)) . ' at ' . date('g:i A', strtotime($booking->start_datetime)),
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
             LEFT JOIN {$wpdb->posts} i ON s.instructor_id = i.ID AND i.post_type = 'waza_instructor'
             WHERE DATE(s.start_datetime) = %s
             AND s.status IN ('active', 'available')",
            $date
        );
        
        if ($activity_id) {
            $sql .= $wpdb->prepare(' AND s.activity_id = %d', $activity_id);
        }
        
        $sql .= ' ORDER BY s.start_datetime ASC';
        
        $results = $wpdb->get_results($sql);
        $slots = [];
        
        if ($results) {
            foreach ($results as $row) {
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
            
            // Book Now Button (only show for available slots, not for past or full)
            if (!$unavailable) {
                $html .= '<button class="waza-btn waza-btn-primary waza-select-slot" data-slot-id="' . $slot['id'] . '">' . 
                         esc_html__('Book Now', 'waza-booking') . 
                         '</button>';
            } elseif (!$is_past) {
                // Only show "Fully Booked" button for full slots (not past slots)
                $html .= '<button class="waza-btn waza-btn-disabled" disabled>' . 
                         esc_html__('Fully Booked', 'waza-booking') . 
                         '</button>';
            }
            // Past slots: no button, only "Expired" badge above
            
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
             WHERE s.id = %d AND s.status IN ('active', 'available')",
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
            'available_spots' => max(0, (int)$slot_data->capacity - (int)$slot_data->booked_count),
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
                    <input type="number" name="quantity" id="booking_quantity" class="waza-quantity-input" 
                           min="1" max="<?php echo min(10, (int)$slot->available_spots); ?>" 
                           value="1" required>
                    <p class="waza-field-help">Available seats: <?php echo (int)$slot->available_spots; ?></p>
                </div>
                
                <!-- Attendee Details (shown when quantity > 1) -->
                <div id="waza-attendees-container" class="waza-attendees-container" style="display: none; margin-top: 1.5rem;">
                    <h4 style="margin-bottom: 1rem;">Attendee Details</h4>
                    <p class="waza-field-help" style="margin-bottom: 1rem;">Please provide details for each attendee. Each will receive their own QR code.</p>
                    <div id="waza-attendee-fields"></div>
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
                        <div class="waza-phone-wrapper">
                            <div class="waza-phone-input" style="display: flex; gap: 8px;">
                                <select name="customer_phone_country" class="waza-country-select" style="width: 90px; flex-shrink: 0;">
                                    <option value="+91" selected>🇮🇳 +91</option>
                                    <option value="+1">🇺🇸 +1</option>
                                    <option value="+44">🇬🇧 +44</option>
                                </select>
                                <input type="tel" name="customer_phone" id="customer_phone" required
                                       style="flex: 1;"
                                       value="<?php echo esc_attr($user_info['phone']); ?>"
                                       placeholder="9876543210">
                            </div>
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
                     <?php
                     // Get enabled payment gateways
                     $razorpay_enabled = \WazaBooking\Admin\SettingsManager::get_setting('razorpay_enabled') === '1';
                     $stripe_enabled = \WazaBooking\Admin\SettingsManager::get_setting('stripe_enabled') === '1';
                     $phonepe_enabled = \WazaBooking\Admin\SettingsManager::get_setting('phonepe_enabled') === '1';
                     
                     if ($razorpay_enabled):
                     ?>
                     <div class="waza-payment-method selected" data-method="razorpay">
                         <span class="waza-payment-icon">💳</span>
                         <span class="waza-payment-label">Razorpay (Cards, UPI, Wallets, Net Banking)</span>
                     </div>
                     <input type="hidden" name="payment_method" value="razorpay">
                     <?php elseif ($stripe_enabled): ?>
                     <div class="waza-payment-method selected" data-method="stripe">
                         <span class="waza-payment-icon">💳</span>
                         <span class="waza-payment-label">Credit / Debit Card</span>
                     </div>
                     <input type="hidden" name="payment_method" value="stripe">
                     <?php elseif ($phonepe_enabled): ?>
                     <div class="waza-payment-method selected" data-method="phonepe">
                         <span class="waza-payment-icon">💳</span>
                         <span class="waza-payment-label">PhonePe / UPI / Cards / Netbanking</span>
                     </div>
                     <input type="hidden" name="payment_method" value="phonepe">
                     <?php else: ?>
                     <p style="color: #d63638;">No payment gateway is enabled. Please contact administrator.</p>
                     <?php endif; ?>
                 </div>
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
            "SELECT capacity, booked_count FROM {$wpdb->prefix}waza_slots WHERE id = %d AND status IN ('active', 'available')",
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
    
    private function create_booking_post($booking_id) {
        global $wpdb;
        
        // Get booking details
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT b.*, s.start_datetime, s.end_datetime, p.post_title as activity_title
             FROM {$wpdb->prefix}waza_bookings b
             LEFT JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
             LEFT JOIN {$wpdb->posts} p ON b.activity_id = p.ID
             WHERE b.id = %d",
            $booking_id
        ));
        
        if (!$booking) {
            return false;
        }
        
        // Create post for admin panel visibility
        $post_data = [
            'post_title' => sprintf(
                'Booking #%d - %s - %s',
                $booking_id,
                $booking->user_name,
                $booking->activity_title
            ),
            'post_type' => 'waza_booking',
            'post_status' => 'publish',
            'post_author' => $booking->user_id ?: 1
        ];
        
        $post_id = wp_insert_post($post_data);
        
        if ($post_id) {
            // Link post to booking table record
            update_post_meta($post_id, '_waza_booking_id', $booking_id);
            update_post_meta($post_id, '_waza_slot_id', $booking->slot_id);
            update_post_meta($post_id, '_waza_activity_id', $booking->activity_id);
            update_post_meta($post_id, '_waza_user_email', $booking->user_email);
            update_post_meta($post_id, '_waza_user_phone', $booking->user_phone);
            update_post_meta($post_id, '_waza_total_amount', $booking->total_amount);
            update_post_meta($post_id, '_waza_payment_status', $booking->payment_status);
            update_post_meta($post_id, '_waza_booking_status', $booking->booking_status);
            
            // If there's pending account data in options, save it to post meta
            $pending_account = get_option("waza_pending_account_{$booking_id}");
            if ($pending_account && is_array($pending_account)) {
                update_post_meta($post_id, '_pending_account_password', $pending_account['password']);
                update_post_meta($post_id, '_pending_account_password_option', $pending_account['password_option']);
            }
        }
        
        return $post_id;
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
    
    /**
     * Save booking attendees
     */
    private function save_booking_attendees($booking_id, $names, $emails, $phones) {
        global $wpdb;
        
        $count = count($names);
        
        for ($i = 0; $i < $count; $i++) {
            $name = isset($names[$i]) ? sanitize_text_field($names[$i]) : '';
            $email = isset($emails[$i]) ? sanitize_email($emails[$i]) : '';
            $phone = isset($phones[$i]) ? sanitize_text_field($phones[$i]) : '';
            
            // Skip if name is empty
            if (empty($name)) {
                continue;
            }
            
            // Generate unique QR token for each attendee
            $qr_token = 'WAZA-ATT-' . $booking_id . '-' . ($i + 1) . '-' . wp_generate_password(12, false);
            
            $wpdb->insert(
                $wpdb->prefix . 'waza_booking_attendees',
                [
                    'booking_id' => $booking_id,
                    'attendee_name' => $name,
                    'attendee_email' => $email,
                    'attendee_phone' => $phone,
                    'qr_token' => $qr_token,
                    'seat_number' => ($i + 1),
                    'created_at' => current_time('mysql')
                ],
                ['%d', '%s', '%s', '%s', '%s', '%d', '%s']
            );
        }
    }
    
    /**
     * Get attendees for a booking
     */
    private function get_booking_attendees($booking_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}waza_booking_attendees 
             WHERE booking_id = %d 
             ORDER BY seat_number ASC",
            $booking_id
        ));
    }
}