<?php
/**
 * Instructor Endpoint
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
 * Instructor Endpoint Class
 */
class InstructorEndpoint {
    
    /**
     * Register instructor routes
     */
    public function register_routes() {
        // Get instructor bookings
        register_rest_route('waza/v1', '/instructor/(?P<id>\d+)/bookings', [
            'methods' => 'GET',
            'callback' => [$this, 'get_instructor_bookings'],
            'permission_callback' => [$this, 'check_instructor_permission'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'Instructor ID'
                ]
            ]
        ]);
        
        // Create workshop
        register_rest_route('waza/v1', '/instructor/workshops', [
            'methods' => 'POST',
            'callback' => [$this, 'create_workshop'],
            'permission_callback' => [$this, 'check_instructor_permission']
        ]);
    }
    
    /**
     * Get instructor bookings
     */
    public function get_instructor_bookings(WP_REST_Request $request) {
        $instructor_id = $request->get_param('id');
        
        // Implementation for getting instructor bookings
        return new WP_REST_Response([
            'success' => true,
            'data' => []
        ]);
    }
    
    /**
     * Create workshop
     */
    public function create_workshop(WP_REST_Request $request) {
        // Implementation for creating workshops
        return new WP_REST_Response([
            'success' => true,
            'data' => []
        ]);
    }
    
    /**
     * Check instructor permission
     */
    public function check_instructor_permission() {
        return current_user_can('manage_waza_instructors') || current_user_can('edit_waza_slots');
    }
}