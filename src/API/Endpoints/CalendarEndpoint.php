<?php
/**
 * Calendar Endpoint
 * 
 * @package WazaBooking\API\Endpoints
 */

namespace WazaBooking\API\Endpoints;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Calendar Endpoint Class
 */
class CalendarEndpoint {
    
    /**
     * Register calendar routes
     */
    public function register_routes() {
        // Get calendar data
        register_rest_route('waza/v1', '/calendar', [
            'methods' => 'GET',
            'callback' => [$this, 'get_calendar'],
            'permission_callback' => '__return_true',
            'args' => [
                'from' => [
                    'required' => false,
                    'type' => 'string',
                    'format' => 'date',
                    'description' => 'Start date (YYYY-MM-DD)'
                ],
                'to' => [
                    'required' => false,
                    'type' => 'string',
                    'format' => 'date',
                    'description' => 'End date (YYYY-MM-DD)'
                ],
                'activity' => [
                    'required' => false,
                    'type' => 'integer',
                    'description' => 'Activity ID to filter'
                ],
                'instructor' => [
                    'required' => false,
                    'type' => 'integer',
                    'description' => 'Instructor ID to filter'
                ]
            ]
        ]);
        
        // Get single slot details
        register_rest_route('waza/v1', '/slots/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_slot'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'Slot ID'
                ]
            ]
        ]);
    }
    
    /**
     * Get calendar data
     */
    public function get_calendar(WP_REST_Request $request) {
        $from = $request->get_param('from') ?: date('Y-m-d');
        $to = $request->get_param('to') ?: date('Y-m-d', strtotime('+30 days'));
        $activity_id = $request->get_param('activity');
        $instructor_id = $request->get_param('instructor');
        
        // Validate date range
        if (strtotime($from) > strtotime($to)) {
            return new WP_Error(
                'invalid_date_range',
                __('Start date must be before end date', 'waza-booking'),
                ['status' => 400]
            );
        }
        
        // Build query args
        $args = [
            'post_type' => 'waza_slot',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_waza_start_date',
                    'value' => [$from, $to],
                    'compare' => 'BETWEEN',
                    'type' => 'DATE'
                ]
            ]
        ];
        
        // Add activity filter
        if ($activity_id) {
            $args['meta_query'][] = [
                'key' => '_waza_activity_id',
                'value' => $activity_id,
                'compare' => '='
            ];
        }
        
        // Add instructor filter
        if ($instructor_id) {
            $args['meta_query'][] = [
                'key' => '_waza_instructor_id',
                'value' => $instructor_id,
                'compare' => '='
            ];
        }
        
        $slots = get_posts($args);
        $calendar_data = [];
        
        foreach ($slots as $slot) {
            $slot_data = $this->format_slot_data($slot);
            if ($slot_data) {
                $calendar_data[] = $slot_data;
            }
        }
        
        return new WP_REST_Response([
            'success' => true,
            'data' => $calendar_data,
            'meta' => [
                'from' => $from,
                'to' => $to,
                'total' => count($calendar_data)
            ]
        ]);
    }
    
    /**
     * Get single slot details
     */
    public function get_slot(WP_REST_Request $request) {
        $slot_id = $request->get_param('id');
        
        $slot = get_post($slot_id);
        
        if (!$slot || $slot->post_type !== 'waza_slot') {
            return new WP_Error(
                'slot_not_found',
                __('Slot not found', 'waza-booking'),
                ['status' => 404]
            );
        }
        
        $slot_data = $this->format_slot_data($slot, true);
        
        if (!$slot_data) {
            return new WP_Error(
                'slot_unavailable',
                __('Slot is not available', 'waza-booking'),
                ['status' => 400]
            );
        }
        
        return new WP_REST_Response([
            'success' => true,
            'data' => $slot_data
        ]);
    }
    
    /**
     * Format slot data for API response
     */
    private function format_slot_data($slot, $detailed = false) {
        $activity_id = get_post_meta($slot->ID, '_waza_activity_id', true);
        $instructor_id = get_post_meta($slot->ID, '_waza_instructor_id', true);
        $start_date = get_post_meta($slot->ID, '_waza_start_date', true);
        $start_time = get_post_meta($slot->ID, '_waza_start_time', true);
        $end_time = get_post_meta($slot->ID, '_waza_end_time', true);
        $capacity = (int) get_post_meta($slot->ID, '_waza_capacity', true);
        $booked_seats = (int) get_post_meta($slot->ID, '_waza_booked_seats', true);
        $price = (float) get_post_meta($slot->ID, '_waza_price', true);
        $room = get_post_meta($slot->ID, '_waza_room', true);
        
        // Skip if missing required data
        if (!$start_date || !$start_time || !$capacity) {
            return null;
        }
        
        // Calculate available seats
        $seats_left = max(0, $capacity - $booked_seats);
        
        // Get activity details
        $activity = null;
        if ($activity_id) {
            $activity_post = get_post($activity_id);
            if ($activity_post) {
                $activity = [
                    'id' => $activity_post->ID,
                    'title' => $activity_post->post_title,
                    'slug' => $activity_post->post_name
                ];
                
                if ($detailed) {
                    $activity['description'] = $activity_post->post_content;
                    $activity['excerpt'] = $activity_post->post_excerpt;
                    $activity['duration'] = get_post_meta($activity_id, '_waza_duration', true);
                    $activity['skill_level'] = get_post_meta($activity_id, '_waza_skill_level', true);
                    $activity['equipment_required'] = get_post_meta($activity_id, '_waza_equipment_required', true);
                }
            }
        }
        
        // Get instructor details
        $instructor = null;
        if ($instructor_id) {
            $instructor_post = get_post($instructor_id);
            if ($instructor_post) {
                $instructor = [
                    'id' => $instructor_post->ID,
                    'name' => $instructor_post->post_title,
                    'slug' => $instructor_post->post_name
                ];
                
                if ($detailed) {
                    $instructor['bio'] = get_post_meta($instructor_id, '_waza_bio', true);
                    $instructor['experience'] = get_post_meta($instructor_id, '_waza_experience', true);
                    $instructor['certifications'] = get_post_meta($instructor_id, '_waza_certifications', true);
                }
            }
        }
        
        // Build datetime strings
        $start_datetime = $start_date . ' ' . $start_time;
        $end_datetime = $start_date . ' ' . $end_time;
        
        // Basic slot data
        $data = [
            'id' => $slot->ID,
            'title' => $slot->post_title,
            'start' => $start_datetime,
            'end' => $end_datetime,
            'start_date' => $start_date,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'activity' => $activity,
            'instructor' => $instructor,
            'capacity' => $capacity,
            'booked_seats' => $booked_seats,
            'seats_left' => $seats_left,
            'price' => $price,
            'room' => $room,
            'status' => $seats_left > 0 ? 'available' : 'full',
            'is_bookable' => $seats_left > 0 && strtotime($start_datetime) > time()
        ];
        
        // Add detailed information if requested
        if ($detailed) {
            $data['description'] = $slot->post_content;
            
            // Get recent bookings for admin users
            if (current_user_can('view_waza_bookings')) {
                global $wpdb;
                
                $recent_bookings = $wpdb->get_results($wpdb->prepare("
                    SELECT user_name, user_email, attendees_count, booking_status, created_at
                    FROM {$wpdb->prefix}waza_bookings 
                    WHERE slot_id = %d 
                    ORDER BY created_at DESC 
                    LIMIT 10
                ", $slot->ID));
                
                $data['recent_bookings'] = $recent_bookings;
            }
        }
        
        return $data;
    }
}