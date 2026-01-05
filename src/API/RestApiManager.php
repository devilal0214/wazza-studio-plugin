<?php
/**
 * REST API Manager
 * 
 * @package WazaBooking\API
 */

namespace WazaBooking\API;

use WazaBooking\API\Endpoints\CalendarEndpoint;
use WazaBooking\API\Endpoints\BookingEndpoint;
use WazaBooking\API\Endpoints\QREndpoint;
use WazaBooking\API\Endpoints\InstructorEndpoint;
use WazaBooking\API\Endpoints\PaymentEndpoint;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST API Manager Class
 */
class RestApiManager {
    
    /**
     * API namespace
     */
    const NAMESPACE = 'waza/v1';
    
    /**
     * Initialize REST API
     */
    public function init() {
        add_action('rest_api_init', [$this, 'register_routes']);
        add_filter('rest_pre_serve_request', [$this, 'cors_headers'], 10, 4);
    }
    
    /**
     * Register all REST API routes
     */
    public function register_routes() {
        // Calendar endpoints
        $calendar_endpoint = new CalendarEndpoint();
        $calendar_endpoint->register_routes();
        
        // Booking endpoints
        $booking_endpoint = new BookingEndpoint();
        $booking_endpoint->register_routes();
        
        // QR endpoints
        $qr_endpoint = new QREndpoint();
        $qr_endpoint->register_routes();
        
        // Instructor endpoints
        $instructor_endpoint = new InstructorEndpoint();
        $instructor_endpoint->register_routes();
        
        // Payment endpoints
        $payment_endpoint = new PaymentEndpoint();
        $payment_endpoint->register_routes();
    }
    
    /**
     * Add CORS headers for API requests
     */
    public function cors_headers($served, $result, $request, $server) {
        $origin = get_http_origin();
        
        if ($origin) {
            header("Access-Control-Allow-Origin: {$origin}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce');
        }
        
        if ($request->get_method() === 'OPTIONS') {
            exit();
        }
        
        return $served;
    }
}