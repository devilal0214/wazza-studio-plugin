<?php
/**
 * Reschedule Manager
 * 
 * Handles booking rescheduling functionality
 * 
 * @package WazaBooking\Booking
 */

namespace WazaBooking\Booking;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Reschedule Manager Class
 */
class RescheduleManager {
    
    /**
     * Initialize reschedule functionality
     */
    public function init() {
        add_action('wp_ajax_waza_check_reschedule_eligibility', [$this, 'ajax_check_eligibility']);
        add_action('wp_ajax_waza_get_available_slots', [$this, 'ajax_get_available_slots']);
        add_action('wp_ajax_waza_process_reschedule', [$this, 'ajax_process_reschedule']);
        add_action('wp_ajax_nopriv_waza_check_reschedule_eligibility', [$this, 'ajax_check_eligibility']);
        add_action('wp_ajax_nopriv_waza_get_available_slots', [$this, 'ajax_get_available_slots']);
        add_action('wp_ajax_nopriv_waza_process_reschedule', [$this, 'ajax_process_reschedule']);
    }
    
    /**
     * Check if booking is eligible for rescheduling
     */
    public function ajax_check_eligibility() {
        check_ajax_referer('waza_frontend_nonce', 'nonce');
        
        $booking_id = intval($_POST['booking_id'] ?? 0);
        
        if (!$booking_id) {
            wp_send_json_error(__('Invalid booking ID', 'waza-booking'));
        }
        
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_bookings WHERE id = %d
        ", $booking_id));
        
        if (!$booking) {
            wp_send_json_error(__('Booking not found', 'waza-booking'));
        }
        
        $slot = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_slots WHERE id = %d
        ", $booking->slot_id));
        
        $eligibility = $this->check_eligibility($booking, $slot);
        
        wp_send_json_success($eligibility);
    }
    
    /**
     * Get available slots for rescheduling
     */
    public function ajax_get_available_slots() {
        check_ajax_referer('waza_frontend_nonce', 'nonce');
        
        $booking_id = intval($_POST['booking_id'] ?? 0);
        $activity_id = intval($_POST['activity_id'] ?? 0);
        
        if (!$booking_id || !$activity_id) {
            wp_send_json_error(__('Missing required parameters', 'waza-booking'));
        }
        
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_bookings WHERE id = %d
        ", $booking_id));
        
        // Get available future slots for same activity
        $slots = $wpdb->get_results($wpdb->prepare("
            SELECT 
                s.*,
                (s.capacity - s.booked_count) as available_seats
            FROM {$wpdb->prefix}waza_slots s
            WHERE s.activity_id = %d 
                AND s.start_datetime > NOW()
                AND s.status = 'available'
                AND (s.capacity - s.booked_count) >= %d
            ORDER BY s.start_datetime ASC
            LIMIT 20
        ", $activity_id, $booking->attendees_count));
        
        $formatted_slots = array_map(function($slot) {
            return [
                'id' => $slot->id,
                'start_datetime' => $slot->start_datetime,
                'end_datetime' => $slot->end_datetime,
                'available_seats' => $slot->available_seats,
                'location' => $slot->location,
                'formatted_date' => wp_date('F j, Y', strtotime($slot->start_datetime)),
                'formatted_time' => wp_date('g:i A', strtotime($slot->start_datetime)) . ' - ' . wp_date('g:i A', strtotime($slot->end_datetime))
            ];
        }, $slots);
        
        wp_send_json_success(['slots' => $formatted_slots]);
    }
    
    /**
     * Process reschedule request
     */
    public function ajax_process_reschedule() {
        check_ajax_referer('waza_frontend_nonce', 'nonce');
        
        $booking_id = intval($_POST['booking_id'] ?? 0);
        $new_slot_id = intval($_POST['new_slot_id'] ?? 0);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');
        
        if (!$booking_id || !$new_slot_id) {
            wp_send_json_error(__('Missing required parameters', 'waza-booking'));
        }
        
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_bookings WHERE id = %d
        ", $booking_id));
        
        if (!$booking) {
            wp_send_json_error(__('Booking not found', 'waza-booking'));
        }
        
        // Verify user owns this booking
        $current_user_id = get_current_user_id();
        if ($current_user_id && $booking->user_id != $current_user_id) {
            if (!current_user_can('manage_waza')) {
                wp_send_json_error(__('Permission denied', 'waza-booking'));
            }
        }
        
        $old_slot = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_slots WHERE id = %d
        ", $booking->slot_id));
        
        $new_slot = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_slots WHERE id = %d
        ", $new_slot_id));
        
        if (!$new_slot) {
            wp_send_json_error(__('New slot not found', 'waza-booking'));
        }
        
        // Check eligibility
        $eligibility = $this->check_eligibility($booking, $old_slot);
        if (!$eligibility['eligible']) {
            wp_send_json_error($eligibility['reason']);
        }
        
        // Check availability
        $available = ($new_slot->capacity - $new_slot->booked_count);
        if ($available < $booking->attendees_count) {
            wp_send_json_error(__('Not enough seats available in the new slot', 'waza-booking'));
        }
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Release seats from old slot
            $wpdb->query($wpdb->prepare("
                UPDATE {$wpdb->prefix}waza_slots 
                SET booked_count = booked_count - %d
                WHERE id = %d
            ", $booking->attendees_count, $old_slot->id));
            
            // Book seats in new slot
            $wpdb->query($wpdb->prepare("
                UPDATE {$wpdb->prefix}waza_slots 
                SET booked_count = booked_count + %d
                WHERE id = %d
            ", $booking->attendees_count, $new_slot_id));
            
            // Update booking
            $wpdb->update(
                $wpdb->prefix . 'waza_bookings',
                [
                    'slot_id' => $new_slot_id,
                    'updated_at' => current_time('mysql')
                ],
                ['id' => $booking_id]
            );
            
            // Log reschedule activity
            $wpdb->insert($wpdb->prefix . 'waza_activity_logs', [
                'user_id' => $current_user_id ?: $booking->user_id,
                'action_type' => 'booking_rescheduled',
                'object_type' => 'booking',
                'object_id' => $booking_id,
                'description' => sprintf(
                    __('Booking rescheduled from %s to %s', 'waza-booking'),
                    wp_date('F j, Y g:i A', strtotime($old_slot->start_datetime)),
                    wp_date('F j, Y g:i A', strtotime($new_slot->start_datetime))
                ),
                'metadata' => json_encode([
                    'old_slot_id' => $old_slot->id,
                    'new_slot_id' => $new_slot_id,
                    'reason' => $reason
                ]),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'created_at' => current_time('mysql')
            ]);
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            // Send reschedule notification
            do_action('waza_booking_rescheduled', $booking_id, $old_slot->id, $new_slot_id);
            
            wp_send_json_success([
                'message' => __('Booking rescheduled successfully', 'waza-booking'),
                'new_slot' => [
                    'id' => $new_slot->id,
                    'date' => wp_date('F j, Y', strtotime($new_slot->start_datetime)),
                    'time' => wp_date('g:i A', strtotime($new_slot->start_datetime))
                ]
            ]);
            
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(__('Failed to reschedule booking', 'waza-booking'));
        }
    }
    
    /**
     * Check reschedule eligibility
     */
    private function check_eligibility($booking, $slot) {
        $settings = get_option('waza_booking_settings', []);
        
        // Check booking status
        if ($booking->booking_status === 'cancelled' || $booking->booking_status === 'refunded') {
            return [
                'eligible' => false,
                'reason' => __('Cannot reschedule cancelled or refunded booking', 'waza-booking')
            ];
        }
        
        // Check if already attended
        if ($booking->attended) {
            return [
                'eligible' => false,
                'reason' => __('Cannot reschedule a booking that was already attended', 'waza-booking')
            ];
        }
        
        // Check reschedule deadline
        $reschedule_hours = intval($settings['reschedule_deadline_hours'] ?? 24);
        $slot_time = strtotime($slot->start_datetime);
        $current_time = time();
        $hours_until_slot = ($slot_time - $current_time) / 3600;
        
        if ($hours_until_slot < $reschedule_hours) {
            return [
                'eligible' => false,
                'reason' => sprintf(
                    __('Rescheduling must be done at least %d hours before the activity', 'waza-booking'),
                    $reschedule_hours
                )
            ];
        }
        
        if ($hours_until_slot < 0) {
            return [
                'eligible' => false,
                'reason' => __('Activity has already occurred', 'waza-booking')
            ];
        }
        
        // Check reschedule limit
        global $wpdb;
        $reschedule_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}waza_activity_logs
            WHERE object_type = 'booking' 
                AND object_id = %d 
                AND action_type = 'booking_rescheduled'
        ", $booking->id));
        
        $max_reschedules = intval($settings['max_reschedules_per_booking'] ?? 2);
        
        if ($reschedule_count >= $max_reschedules) {
            return [
                'eligible' => false,
                'reason' => sprintf(
                    __('Maximum reschedule limit (%d) reached for this booking', 'waza-booking'),
                    $max_reschedules
                )
            ];
        }
        
        return [
            'eligible' => true,
            'reason' => '',
            'reschedules_remaining' => $max_reschedules - $reschedule_count
        ];
    }
}
