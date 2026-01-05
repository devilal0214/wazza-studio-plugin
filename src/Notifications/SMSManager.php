<?php
/**
 * SMS Manager
 * 
 * Handles SMS notifications via Twilio or other SMS gateways
 * 
 * @package WazaBooking\Notifications
 */

namespace WazaBooking\Notifications;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SMS Manager Class
 */
class SMSManager {
    
    /**
     * SMS Gateway instance
     */
    private $gateway;
    
    /**
     * Initialize SMS functionality
     */
    public function init() {
        add_action('waza_send_sms_confirmation', [$this, 'send_booking_confirmation_sms'], 10, 1);
        add_action('waza_send_sms_reminder', [$this, 'send_reminder_sms'], 10, 2);
        add_action('waza_send_sms_cancellation', [$this, 'send_cancellation_sms'], 10, 1);
        
        $this->initialize_gateway();
    }
    
    /**
     * Initialize SMS gateway
     */
    private function initialize_gateway() {
        $settings = get_option('waza_booking_settings', []);
        $sms_enabled = $settings['sms_enabled'] ?? '0';
        
        if ($sms_enabled !== '1') {
            return;
        }
        
        $provider = $settings['sms_provider'] ?? 'twilio';
        
        switch ($provider) {
            case 'twilio':
                $this->gateway = new TwilioGateway(
                    $settings['twilio_account_sid'] ?? '',
                    $settings['twilio_auth_token'] ?? '',
                    $settings['twilio_phone_number'] ?? ''
                );
                break;
                
            case 'textlocal':
                $this->gateway = new TextLocalGateway(
                    $settings['textlocal_api_key'] ?? '',
                    $settings['textlocal_sender'] ?? ''
                );
                break;
                
            default:
                $this->gateway = new MockSMSGateway();
        }
    }
    
    /**
     * Send booking confirmation SMS
     */
    public function send_booking_confirmation_sms($booking_id) {
        if (!$this->gateway) {
            return false;
        }
        
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_bookings WHERE id = %d
        ", $booking_id));
        
        if (!$booking || !$booking->user_phone) {
            return false;
        }
        
        $slot = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_slots WHERE id = %d
        ", $booking->slot_id));
        
        $activity = get_post($slot->activity_id);
        $activity_title = $activity ? $activity->post_title : __('Activity', 'waza-booking');
        
        $date = wp_date('M d, Y', strtotime($slot->start_datetime));
        $time = wp_date('g:i A', strtotime($slot->start_datetime));
        
        $message = sprintf(
            __('Booking confirmed! %s on %s at %s. Booking ID: WB-%s. Show QR code at check-in.', 'waza-booking'),
            $activity_title,
            $date,
            $time,
            str_pad($booking->id, 5, '0', STR_PAD_LEFT)
        );
        
        return $this->gateway->send_sms($booking->user_phone, $message);
    }
    
    /**
     * Send reminder SMS
     */
    public function send_reminder_sms($booking_id, $type = '24h') {
        if (!$this->gateway) {
            return false;
        }
        
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_bookings WHERE id = %d
        ", $booking_id));
        
        if (!$booking || !$booking->user_phone || $booking->booking_status !== 'confirmed') {
            return false;
        }
        
        $slot = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_slots WHERE id = %d
        ", $booking->slot_id));
        
        $activity = get_post($slot->activity_id);
        $activity_title = $activity ? $activity->post_title : __('Activity', 'waza-booking');
        
        $time = wp_date('g:i A', strtotime($slot->start_datetime));
        
        if ($type === '24h') {
            $message = sprintf(
                __('Reminder: Your %s session is tomorrow at %s. Location: %s', 'waza-booking'),
                $activity_title,
                $time,
                $slot->location ?: 'Studio'
            );
        } else {
            $message = sprintf(
                __('Your %s session starts in 1 hour at %s. Please arrive 10 minutes early.', 'waza-booking'),
                $activity_title,
                $time
            );
        }
        
        return $this->gateway->send_sms($booking->user_phone, $message);
    }
    
    /**
     * Send cancellation SMS
     */
    public function send_cancellation_sms($booking_id) {
        if (!$this->gateway) {
            return false;
        }
        
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_bookings WHERE id = %d
        ", $booking_id));
        
        if (!$booking || !$booking->user_phone) {
            return false;
        }
        
        $slot = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_slots WHERE id = %d
        ", $booking->slot_id));
        
        $activity = get_post($slot->activity_id);
        $activity_title = $activity ? $activity->post_title : __('Activity', 'waza-booking');
        
        $message = sprintf(
            __('Your booking for %s has been cancelled. Booking ID: WB-%s. Contact us for any queries.', 'waza-booking'),
            $activity_title,
            str_pad($booking->id, 5, '0', STR_PAD_LEFT)
        );
        
        return $this->gateway->send_sms($booking->user_phone, $message);
    }
}

/**
 * Twilio Gateway
 */
class TwilioGateway {
    private $account_sid;
    private $auth_token;
    private $from_number;
    
    public function __construct($account_sid, $auth_token, $from_number) {
        $this->account_sid = $account_sid;
        $this->auth_token = $auth_token;
        $this->from_number = $from_number;
    }
    
    public function send_sms($to, $message) {
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->account_sid}/Messages.json";
        
        $data = [
            'From' => $this->from_number,
            'To' => $to,
            'Body' => $message
        ];
        
        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->account_sid . ':' . $this->auth_token)
            ],
            'body' => $data
        ]);
        
        if (is_wp_error($response)) {
            error_log('Twilio SMS Error: ' . $response->get_error_message());
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        return isset($body['sid']);
    }
}

/**
 * TextLocal Gateway (India)
 */
class TextLocalGateway {
    private $api_key;
    private $sender;
    
    public function __construct($api_key, $sender) {
        $this->api_key = $api_key;
        $this->sender = $sender;
    }
    
    public function send_sms($to, $message) {
        $url = 'https://api.textlocal.in/send/';
        
        $data = [
            'apikey' => $this->api_key,
            'numbers' => $to,
            'message' => $message,
            'sender' => $this->sender
        ];
        
        $response = wp_remote_post($url, ['body' => $data]);
        
        if (is_wp_error($response)) {
            error_log('TextLocal SMS Error: ' . $response->get_error_message());
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        return $body['status'] === 'success';
    }
}

/**
 * Mock SMS Gateway for testing
 */
class MockSMSGateway {
    public function send_sms($to, $message) {
        error_log("Mock SMS to {$to}: {$message}");
        return true;
    }
}
