<?php
/**
 * Booking Endpoint
 * 
 * @package WazaBooking\API\Endpoints
 */

namespace WazaBooking\API\Endpoints;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WazaBooking\Database\DatabaseManager;
use WazaBooking\Core\Plugin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Booking Endpoint Class
 */
class BookingEndpoint {
    
    /**
     * Database manager
     * 
     * @var DatabaseManager
     */
    private $db_manager;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db_manager = new DatabaseManager();
    }
    
    /**
     * Register booking routes
     */
    public function register_routes() {
        // Create booking
        register_rest_route('waza/v1', '/book', [
            'methods' => 'POST',
            'callback' => [$this, 'create_booking'],
            'permission_callback' => '__return_true',
            'args' => [
                'slot_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'Slot ID to book'
                ],
                'user_name' => [
                    'required' => true,
                    'type' => 'string',
                    'description' => 'User full name'
                ],
                'user_email' => [
                    'required' => true,
                    'type' => 'string',
                    'format' => 'email',
                    'description' => 'User email address'
                ],
                'user_phone' => [
                    'required' => false,
                    'type' => 'string',
                    'description' => 'User phone number'
                ],
                'attendees_count' => [
                    'required' => false,
                    'type' => 'integer',
                    'default' => 1,
                    'minimum' => 1,
                    'description' => 'Number of attendees'
                ],
                'coupon_code' => [
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Coupon code for discount'
                ],
                'special_requests' => [
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Special requests or notes'
                ]
            ]
        ]);
        
        // Get user bookings
        register_rest_route('waza/v1', '/bookings', [
            'methods' => 'GET',
            'callback' => [$this, 'get_user_bookings'],
            'permission_callback' => [$this, 'check_user_permission'],
            'args' => [
                'user_email' => [
                    'required' => false,
                    'type' => 'string',
                    'format' => 'email',
                    'description' => 'User email to filter bookings'
                ],
                'status' => [
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Booking status filter'
                ]
            ]
        ]);
        
        // Cancel booking
        register_rest_route('waza/v1', '/bookings/(?P<id>\d+)/cancel', [
            'methods' => 'POST',
            'callback' => [$this, 'cancel_booking'],
            'permission_callback' => [$this, 'check_booking_permission'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'Booking ID'
                ],
                'reason' => [
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Cancellation reason'
                ]
            ]
        ]);
        
        // Add to waitlist
        register_rest_route('waza/v1', '/waitlist', [
            'methods' => 'POST',
            'callback' => [$this, 'add_to_waitlist'],
            'permission_callback' => '__return_true',
            'args' => [
                'slot_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'Slot ID'
                ],
                'user_name' => [
                    'required' => true,
                    'type' => 'string',
                    'description' => 'User full name'
                ],
                'user_email' => [
                    'required' => true,
                    'type' => 'string',
                    'format' => 'email',
                    'description' => 'User email address'
                ],
                'user_phone' => [
                    'required' => false,
                    'type' => 'string',
                    'description' => 'User phone number'
                ],
                'requested_seats' => [
                    'required' => false,
                    'type' => 'integer',
                    'default' => 1,
                    'minimum' => 1,
                    'description' => 'Number of seats requested'
                ]
            ]
        ]);
    }
    
    /**
     * Create a booking
     */
    public function create_booking(WP_REST_Request $request) {
        $slot_id = $request->get_param('slot_id');
        $user_name = sanitize_text_field($request->get_param('user_name'));
        $user_email = sanitize_email($request->get_param('user_email'));
        $user_phone = sanitize_text_field($request->get_param('user_phone'));
        $attendees_count = (int) $request->get_param('attendees_count') ?: 1;
        $coupon_code = sanitize_text_field($request->get_param('coupon_code'));
        $special_requests = sanitize_textarea_field($request->get_param('special_requests'));
        
        // Validate required fields
        if (!$user_name || !$user_email) {
            return new WP_Error(
                'missing_required_fields',
                __('Name and email are required', 'waza-booking'),
                ['status' => 400]
            );
        }
        
        // Get slot with locking for concurrency control
        $slot = $this->db_manager->get_slot_for_booking($slot_id);
        
        if (!$slot) {
            $this->db_manager->rollback_booking();
            return new WP_Error(
                'slot_not_found',
                __('Slot not found or not available', 'waza-booking'),
                ['status' => 404]
            );
        }
        
        // Check availability
        $capacity = (int) $slot->capacity ?: 0;
        $booked_seats = (int) $slot->booked_seats ?: 0;
        $available_seats = $capacity - $booked_seats;
        
        if ($available_seats < $attendees_count) {
            $this->db_manager->rollback_booking();
            
            // Add to waitlist if no seats available
            if ($available_seats === 0) {
                $this->add_to_waitlist_internal($slot_id, $user_name, $user_email, $user_phone, $attendees_count);
                
                return new WP_Error(
                    'slot_full_waitlisted',
                    __('Slot is full. You have been added to the waitlist.', 'waza-booking'),
                    ['status' => 409, 'waitlisted' => true]
                );
            }
            
            return new WP_Error(
                'insufficient_seats',
                sprintf(__('Only %d seats available', 'waza-booking'), $available_seats),
                ['status' => 409]
            );
        }
        
        // Calculate price
        $base_price = (float) get_post_meta($slot_id, '_waza_price', true);
        $total_amount = $base_price * $attendees_count;
        $discount_amount = 0;
        
        // Apply coupon if provided
        if ($coupon_code) {
            $coupon_discount = $this->apply_coupon($coupon_code, $total_amount);
            if (!is_wp_error($coupon_discount)) {
                $discount_amount = $coupon_discount;
                $total_amount -= $discount_amount;
            }
        }
        
        // Create booking record
        $booking_data = [
            'slot_id' => $slot_id,
            'user_id' => get_current_user_id() ?: null,
            'user_name' => $user_name,
            'user_email' => $user_email,
            'user_phone' => $user_phone,
            'attendees_count' => $attendees_count,
            'total_amount' => $total_amount,
            'discount_amount' => $discount_amount,
            'coupon_code' => $coupon_code,
            'special_requests' => $special_requests,
            'payment_status' => 'pending',
            'booking_status' => 'confirmed'
        ];
        
        $booking_id = $this->db_manager->insert_booking($booking_data);
        
        if (!$booking_id) {
            $this->db_manager->rollback_booking();
            return new WP_Error(
                'booking_failed',
                __('Failed to create booking', 'waza-booking'),
                ['status' => 500]
            );
        }
        
        // Update booked seats count
        $new_booked_seats = $booked_seats + $attendees_count;
        update_post_meta($slot_id, '_waza_booked_seats', $new_booked_seats);
        
        // Commit the transaction
        $this->db_manager->commit_booking();
        
        // Generate QR code and payment link
        $qr_manager = Plugin::get_instance()->get_qr_manager();
        $qr_token = $qr_manager->generate_qr_token($booking_id, $slot_id);
        
        $payment_manager = Plugin::get_instance()->get_payment_manager();
        $payment_url = null;
        
        if ($total_amount > 0) {
            $payment_url = $payment_manager->create_payment_link($booking_id, $total_amount);
        }
        
        // Create booking post for admin reference
        $booking_post_id = wp_insert_post([
            'post_title' => sprintf('Booking #%d - %s', $booking_id, $user_name),
            'post_type' => 'waza_booking',
            'post_status' => 'publish',
            'meta_input' => [
                '_waza_booking_id' => $booking_id
            ]
        ]);
        
        // Send confirmation email
        $notification_manager = Plugin::get_instance()->get_notification_manager();
        $notification_manager->send_booking_confirmation($booking_id);
        
        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'booking_id' => $booking_id,
                'booking_post_id' => $booking_post_id,
                'total_amount' => $total_amount,
                'payment_url' => $payment_url,
                'qr_token' => $qr_token,
                'status' => 'confirmed'
            ]
        ]);
    }
    
    /**
     * Get user bookings
     */
    public function get_user_bookings(WP_REST_Request $request) {
        $user_email = $request->get_param('user_email');
        $status = $request->get_param('status');
        
        // If no email provided, get current user's bookings
        if (!$user_email && is_user_logged_in()) {
            $user = wp_get_current_user();
            $user_email = $user->user_email;
        }
        
        if (!$user_email) {
            return new WP_Error(
                'no_user_specified',
                __('User email required', 'waza-booking'),
                ['status' => 400]
            );
        }
        
        global $wpdb;
        
        $where = ['user_email = %s'];
        $values = [$user_email];
        
        if ($status) {
            $where[] = 'booking_status = %s';
            $values[] = $status;
        }
        
        $where_clause = implode(' AND ', $where);
        
        $bookings = $wpdb->get_results($wpdb->prepare("
            SELECT b.*, p.post_title as slot_title
            FROM {$wpdb->prefix}waza_bookings b
            LEFT JOIN {$wpdb->posts} p ON b.slot_id = p.ID
            WHERE {$where_clause}
            ORDER BY b.created_at DESC
        ", ...$values));
        
        $formatted_bookings = [];
        
        foreach ($bookings as $booking) {
            $slot_date = get_post_meta($booking->slot_id, '_waza_start_date', true);
            $slot_time = get_post_meta($booking->slot_id, '_waza_start_time', true);
            
            $formatted_bookings[] = [
                'id' => $booking->id,
                'slot_id' => $booking->slot_id,
                'slot_title' => $booking->slot_title,
                'slot_date' => $slot_date,
                'slot_time' => $slot_time,
                'attendees_count' => $booking->attendees_count,
                'total_amount' => $booking->total_amount,
                'payment_status' => $booking->payment_status,
                'booking_status' => $booking->booking_status,
                'attended' => (bool) $booking->attended,
                'created_at' => $booking->created_at
            ];
        }
        
        return new WP_REST_Response([
            'success' => true,
            'data' => $formatted_bookings
        ]);
    }
    
    /**
     * Cancel a booking
     */
    public function cancel_booking(WP_REST_Request $request) {
        $booking_id = $request->get_param('id');
        $reason = sanitize_textarea_field($request->get_param('reason'));
        
        $booking = $this->db_manager->get_booking($booking_id);
        
        if (!$booking) {
            return new WP_Error(
                'booking_not_found',
                __('Booking not found', 'waza-booking'),
                ['status' => 404]
            );
        }
        
        if ($booking->booking_status === 'cancelled') {
            return new WP_Error(
                'already_cancelled',
                __('Booking is already cancelled', 'waza-booking'),
                ['status' => 400]
            );
        }
        
        // Check cancellation policy
        $slot_date = get_post_meta($booking->slot_id, '_waza_start_date', true);
        $slot_time = get_post_meta($booking->slot_id, '_waza_start_time', true);
        $slot_datetime = strtotime($slot_date . ' ' . $slot_time);
        
        $cancellation_hours = get_option('waza_cancellation_hours', 24);
        $min_cancellation_time = $slot_datetime - ($cancellation_hours * 3600);
        
        if (time() > $min_cancellation_time && !current_user_can('manage_waza')) {
            return new WP_Error(
                'cancellation_too_late',
                sprintf(__('Bookings can only be cancelled at least %d hours in advance', 'waza-booking'), $cancellation_hours),
                ['status' => 400]
            );
        }
        
        // Update booking status
        $update_result = $this->db_manager->update_booking($booking_id, [
            'booking_status' => 'cancelled',
            'special_requests' => ($booking->special_requests ? $booking->special_requests . "\n\n" : '') . 'Cancelled: ' . $reason
        ]);
        
        if (!$update_result) {
            return new WP_Error(
                'cancellation_failed',
                __('Failed to cancel booking', 'waza-booking'),
                ['status' => 500]
            );
        }
        
        // Update seat count
        $current_booked_seats = (int) get_post_meta($booking->slot_id, '_waza_booked_seats', true);
        $new_booked_seats = max(0, $current_booked_seats - $booking->attendees_count);
        update_post_meta($booking->slot_id, '_waza_booked_seats', $new_booked_seats);
        
        // Process refund if applicable
        if ($booking->payment_status === 'paid' && $booking->total_amount > 0) {
            $payment_manager = Plugin::get_instance()->get_payment_manager();
            $refund_result = $payment_manager->process_refund($booking_id, $reason);
        }
        
        // Process waitlist
        $this->process_waitlist($booking->slot_id, $booking->attendees_count);
        
        // Send cancellation notification
        $notification_manager = Plugin::get_instance()->get_notification_manager();
        $notification_manager->send_cancellation_notice($booking_id, $reason);
        
        return new WP_REST_Response([
            'success' => true,
            'message' => __('Booking cancelled successfully', 'waza-booking')
        ]);
    }
    
    /**
     * Add to waitlist
     */
    public function add_to_waitlist(WP_REST_Request $request) {
        $slot_id = $request->get_param('slot_id');
        $user_name = sanitize_text_field($request->get_param('user_name'));
        $user_email = sanitize_email($request->get_param('user_email'));
        $user_phone = sanitize_text_field($request->get_param('user_phone'));
        $requested_seats = (int) $request->get_param('requested_seats') ?: 1;
        
        $result = $this->add_to_waitlist_internal($slot_id, $user_name, $user_email, $user_phone, $requested_seats);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return new WP_REST_Response([
            'success' => true,
            'data' => $result,
            'message' => __('Added to waitlist successfully', 'waza-booking')
        ]);
    }
    
    /**
     * Internal method to add to waitlist
     */
    private function add_to_waitlist_internal($slot_id, $user_name, $user_email, $user_phone, $requested_seats) {
        global $wpdb;
        
        // Check if already on waitlist
        $existing = $wpdb->get_row($wpdb->prepare("
            SELECT id FROM {$wpdb->prefix}waza_waitlist 
            WHERE slot_id = %d AND user_email = %s AND status = 'waiting'
        ", $slot_id, $user_email));
        
        if ($existing) {
            return new WP_Error(
                'already_waitlisted',
                __('Already on waitlist for this slot', 'waza-booking'),
                ['status' => 400]
            );
        }
        
        // Get next priority
        $max_priority = $wpdb->get_var($wpdb->prepare("
            SELECT COALESCE(MAX(priority), 0) FROM {$wpdb->prefix}waza_waitlist 
            WHERE slot_id = %d AND status = 'waiting'
        ", $slot_id));
        
        $waitlist_data = [
            'slot_id' => $slot_id,
            'user_id' => get_current_user_id() ?: null,
            'user_name' => $user_name,
            'user_email' => $user_email,
            'user_phone' => $user_phone,
            'requested_seats' => $requested_seats,
            'priority' => $max_priority + 1,
            'status' => 'waiting',
            'created_at' => current_time('mysql')
        ];
        
        $result = $wpdb->insert($wpdb->prefix . 'waza_waitlist', $waitlist_data);
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Process waitlist when seats become available
     */
    private function process_waitlist($slot_id, $available_seats) {
        global $wpdb;
        
        $waitlist_entries = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_waitlist 
            WHERE slot_id = %d AND status = 'waiting'
            ORDER BY priority ASC
        ", $slot_id));
        
        foreach ($waitlist_entries as $entry) {
            if ($available_seats >= $entry->requested_seats) {
                // Notify user and give them time to book
                $notification_manager = Plugin::get_instance()->get_notification_manager();
                $notification_manager->send_waitlist_notification($entry->id);
                
                // Update waitlist entry
                $wpdb->update(
                    $wpdb->prefix . 'waza_waitlist',
                    [
                        'status' => 'notified',
                        'notified_at' => current_time('mysql'),
                        'expires_at' => date('Y-m-d H:i:s', strtotime('+30 minutes'))
                    ],
                    ['id' => $entry->id]
                );
                
                $available_seats -= $entry->requested_seats;
                
                if ($available_seats <= 0) {
                    break;
                }
            }
        }
    }
    
    /**
     * Apply coupon code
     */
    private function apply_coupon($coupon_code, $total_amount) {
        // This would integrate with a coupon system
        // For now, return a simple percentage discount for demo codes
        
        $demo_coupons = [
            'SAVE10' => 0.10,
            'SAVE20' => 0.20,
            'FIRST' => 0.15
        ];
        
        if (isset($demo_coupons[$coupon_code])) {
            return $total_amount * $demo_coupons[$coupon_code];
        }
        
        return new WP_Error('invalid_coupon', __('Invalid coupon code', 'waza-booking'));
    }
    
    /**
     * Check user permission for booking operations
     */
    public function check_user_permission() {
        return current_user_can('read') || wp_verify_nonce($_REQUEST['_wpnonce'] ?? '', 'wp_rest');
    }
    
    /**
     * Check booking permission for specific booking operations
     */
    public function check_booking_permission(WP_REST_Request $request) {
        $booking_id = $request->get_param('id');
        
        if (current_user_can('manage_waza')) {
            return true;
        }
        
        // Check if user owns the booking
        $booking = $this->db_manager->get_booking($booking_id);
        
        if ($booking && is_user_logged_in()) {
            $user = wp_get_current_user();
            return $booking->user_email === $user->user_email || $booking->user_id == $user->ID;
        }
        
        return false;
    }
}