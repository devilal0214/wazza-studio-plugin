<?php
/**
 * Notification Manager
 * 
 * @package WazaBooking\Notifications
 */

namespace WazaBooking\Notifications;

use WazaBooking\Email\EmailTemplateManager;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Notification Manager Class
 * Handles email and SMS notifications using Action Scheduler
 */
class NotificationManager {
    
    /**
     * Email template manager
     * 
     * @var EmailTemplateManager
     */
    private $email_template_manager;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Email template manager will be injected via setter
        $this->email_template_manager = null;
    }
    
    /**
     * Set email template manager
     * 
     * @param EmailTemplateManager $email_template_manager
     */
    public function set_email_template_manager(EmailTemplateManager $email_template_manager) {
        $this->email_template_manager = $email_template_manager;
    }
    
    /**
     * Send email using template manager with null check
     * 
     * @param string $template_type
     * @param string $to_email
     * @param array $template_data
     * @return bool
     */
    private function send_template_email($template_type, $to_email, $template_data = []) {
        if (!$this->email_template_manager) {
            error_log('Waza Booking: EmailTemplateManager not available for notifications');
            return false;
        }
        
        return $this->email_template_manager->send_email($template_type, $to_email, $template_data);
    }
    
    /**
     * Initialize notification system
     */
    public function init() {
        add_action('waza_send_booking_confirmation', [$this, 'send_booking_confirmation'], 10, 1);
        add_action('waza_send_reminder', [$this, 'send_reminder'], 10, 2);
        add_action('waza_send_cancellation_notice', [$this, 'send_cancellation_notice'], 10, 2);
        add_action('waza_send_waitlist_notification', [$this, 'send_waitlist_notification'], 10, 1);
        add_action('waza_send_thank_you_message', [$this, 'send_thank_you_message'], 10, 1);
        add_action('waza_send_welcome_email', [$this, 'send_welcome_email'], 10, 1);
        add_action('waza_send_instructor_notification', [$this, 'send_instructor_notification'], 10, 2);
    }
    
    /**
     * Send booking confirmation
     * 
     * @param int $booking_id
     */
    public function send_booking_confirmation($booking_id) {
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_bookings WHERE id = %d
        ", $booking_id));
        
        if (!$booking) {
            return;
        }
        
        // Get booking data for template variables
        $template_data = $this->prepare_booking_data($booking);
        
        // Send email using template
        $sent = $this->send_template_email(
            'booking_confirmation',
            $booking->user_email,
            $template_data
        );
        
        if ($sent) {
            // Schedule reminders
            $this->schedule_reminders($booking_id);
            
            // Schedule thank you message (after the activity)
            $this->schedule_thank_you_message($booking_id);
        }
        
        return $sent;
    }
    
    /**
     * Send reminder notification
     * 
     * @param int $booking_id
     * @param string $type
     */
    public function send_reminder($booking_id, $type = '24h') {
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_bookings WHERE id = %d
        ", $booking_id));
        
        if (!$booking || $booking->status !== 'confirmed') {
            return;
        }
        
        $template_data = $this->prepare_booking_data($booking);
        
        return $this->send_template_email(
            'booking_reminder',
            $booking->user_email,
            $template_data
        );
    }
    
    /**
     * Send cancellation notice
     * 
     * @param int $booking_id
     * @param string $reason
     */
    public function send_cancellation_notice($booking_id, $reason = '') {
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_bookings WHERE id = %d
        ", $booking_id));
        
        if (!$booking) {
            return;
        }
        
        $template_data = $this->prepare_booking_data($booking);
        $template_data['cancellation_reason'] = $reason;
        
        return $this->send_template_email(
            'booking_cancellation',
            $booking->user_email,
            $template_data
        );
    }
    
    /**
     * Send waitlist notification
     * 
     * @param int $waitlist_id
     */
    public function send_waitlist_notification($waitlist_id) {
        global $wpdb;
        
        $waitlist_entry = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_waitlist WHERE id = %d
        ", $waitlist_id));
        
        if (!$waitlist_entry) {
            return;
        }
        
        $template_data = $this->prepare_waitlist_data($waitlist_entry);
        
        return $this->send_template_email(
            'waitlist_notification',
            $waitlist_entry->user_email,
            $template_data
        );
    }
    
    /**
     * Send thank you message
     * 
     * @param int $booking_id
     */
    public function send_thank_you_message($booking_id) {
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_bookings WHERE id = %d
        ", $booking_id));
        
        if (!$booking || $booking->status !== 'confirmed') {
            return;
        }
        
        $template_data = $this->prepare_booking_data($booking);
        
        return $this->send_template_email(
            'thank_you_message',
            $booking->user_email,
            $template_data
        );
    }
    
    /**
     * Send welcome email
     * 
     * @param int $user_id
     */
    public function send_welcome_email($user_id) {
        $user = get_user_by('id', $user_id);
        
        if (!$user) {
            return;
        }
        
        $template_data = [
            'user_name' => $user->display_name,
            'user_email' => $user->user_email,
            'user_first_name' => get_user_meta($user_id, 'first_name', true),
            'user_last_name' => get_user_meta($user_id, 'last_name', true)
        ];
        
        return $this->send_template_email(
            'welcome_email',
            $user->user_email,
            $template_data
        );
    }
    
    /**
     * Send instructor notification
     * 
     * @param int $booking_id
     * @param string $type
     */
    public function send_instructor_notification($booking_id, $type = 'new_booking') {
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_bookings WHERE id = %d
        ", $booking_id));
        
        if (!$booking) {
            return;
        }
        
        // Get instructor email from activity
        $activity_id = get_post_meta($booking->slot_id, '_waza_activity_id', true);
        $instructor_id = get_post_meta($activity_id, '_waza_instructor_id', true);
        
        if (!$instructor_id) {
            return;
        }
        
        $instructor = get_user_by('id', $instructor_id);
        if (!$instructor) {
            return;
        }
        
        $template_data = $this->prepare_booking_data($booking);
        $template_data['instructor_name'] = $instructor->display_name;
        $template_data['instructor_email'] = $instructor->user_email;
        
        return $this->send_template_email(
            'instructor_notification',
            $instructor->user_email,
            $template_data
        );
    }
    
    /**
     * Schedule reminder notifications
     * 
     * @param int $booking_id
     */
    private function schedule_reminders($booking_id) {
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_bookings WHERE id = %d
        ", $booking_id));
        
        if (!$booking) {
            return;
        }
        
        $slot_date = get_post_meta($booking->slot_id, '_waza_start_date', true);
        $slot_time = get_post_meta($booking->slot_id, '_waza_start_time', true);
        $slot_datetime = strtotime($slot_date . ' ' . $slot_time);
        
        // Schedule 24-hour reminder
        $reminder_24h = $slot_datetime - (24 * 3600);
        if ($reminder_24h > time()) {
            as_schedule_single_action($reminder_24h, 'waza_send_reminder', [$booking_id, '24h']);
        }
        
        // Schedule 1-hour reminder  
        $reminder_1h = $slot_datetime - (1 * 3600);
        if ($reminder_1h > time()) {
            as_schedule_single_action($reminder_1h, 'waza_send_reminder', [$booking_id, '1h']);
        }
    }
    
    /**
     * Schedule thank you message
     * 
     * @param int $booking_id
     */
    private function schedule_thank_you_message($booking_id) {
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_bookings WHERE id = %d
        ", $booking_id));
        
        if (!$booking) {
            return;
        }
        
        $slot_date = get_post_meta($booking->slot_id, '_waza_start_date', true);
        $slot_time = get_post_meta($booking->slot_id, '_waza_start_time', true);
        $slot_duration = get_post_meta($booking->slot_id, '_waza_duration', true) ?: 60;
        
        $slot_datetime = strtotime($slot_date . ' ' . $slot_time);
        $thank_you_time = $slot_datetime + ($slot_duration * 60) + (2 * 3600); // 2 hours after activity ends
        
        if ($thank_you_time > time()) {
            as_schedule_single_action($thank_you_time, 'waza_send_thank_you_message', [$booking_id]);
        }
    }
    
    /**
     * Prepare booking data for email templates
     * 
     * @param object $booking
     * @return array
     */
    private function prepare_booking_data($booking) {
        $slot_id = $booking->slot_id;
        $activity_id = get_post_meta($slot_id, '_waza_activity_id', true);
        
        // Get slot details
        $slot_date = get_post_meta($slot_id, '_waza_start_date', true);
        $slot_time = get_post_meta($slot_id, '_waza_start_time', true);
        $slot_duration = get_post_meta($slot_id, '_waza_duration', true) ?: 60;
        
        // Format date and time
        $formatted_date = wp_date(get_option('date_format'), strtotime($slot_date));
        $formatted_time = wp_date(get_option('time_format'), strtotime($slot_time));
        
        // Calculate end time
        $end_time = date(get_option('time_format'), strtotime($slot_time) + ($slot_duration * 60));
        $time_range = $formatted_time . ' - ' . $end_time;
        
        // Get activity details
        $activity = get_post($activity_id);
        $activity_price = get_post_meta($activity_id, '_waza_price', true);
        $activity_location = get_post_meta($activity_id, '_waza_location', true);
        
        // Get instructor details
        $instructor_id = get_post_meta($activity_id, '_waza_instructor_id', true);
        $instructor = $instructor_id ? get_user_by('id', $instructor_id) : null;
        
        // Get user details
        $user_name_parts = explode(' ', $booking->user_name, 2);
        
        return [
            'user_name' => $booking->user_name,
            'user_email' => $booking->user_email,
            'user_phone' => $booking->user_phone ?: '',
            'user_first_name' => $user_name_parts[0] ?? '',
            'user_last_name' => $user_name_parts[1] ?? '',
            'booking_id' => 'WB-' . str_pad($booking->id, 5, '0', STR_PAD_LEFT),
            'booking_date' => $formatted_date,
            'booking_time' => $time_range,
            'booking_status' => ucfirst($booking->status),
            'booking_notes' => $booking->notes ?: '',
            'participants' => $booking->attendees_count,
            'activity_name' => $activity ? $activity->post_title : '',
            'activity_description' => $activity ? wp_strip_all_tags($activity->post_content) : '',
            'activity_duration' => $slot_duration . ' ' . __('minutes', 'waza-booking'),
            'activity_price' => $activity_price ? '$' . number_format($activity_price, 2) : '',
            'activity_category' => $this->get_activity_categories($activity_id),
            'activity_location' => $activity_location ?: '',
            'instructor_name' => $instructor ? $instructor->display_name : '',
            'instructor_email' => $instructor ? $instructor->user_email : '',
            'instructor_bio' => $instructor ? get_user_meta($instructor->ID, 'description', true) : '',
            'payment_amount' => '$' . number_format($booking->total_amount, 2),
            'payment_method' => $booking->payment_method ?: 'N/A',
            'payment_date' => $booking->payment_date ? wp_date(get_option('date_format'), strtotime($booking->payment_date)) : '',
            'transaction_id' => $booking->transaction_id ?: '',
            'qr_code' => \WazaBooking\Core\Plugin::instance()->get_manager('qr')->get_booking_qr_url($booking->id)
        ];
    }
    
    /**
     * Prepare waitlist data for email templates
     * 
     * @param object $waitlist_entry
     * @return array
     */
    private function prepare_waitlist_data($waitlist_entry) {
        $slot_id = $waitlist_entry->slot_id;
        $activity_id = get_post_meta($slot_id, '_waza_activity_id', true);
        
        $slot_date = get_post_meta($slot_id, '_waza_start_date', true);
        $slot_time = get_post_meta($slot_id, '_waza_start_time', true);
        
        $formatted_date = wp_date(get_option('date_format'), strtotime($slot_date));
        $formatted_time = wp_date(get_option('time_format'), strtotime($slot_time));
        
        $activity = get_post($activity_id);
        
        $user_name_parts = explode(' ', $waitlist_entry->user_name, 2);
        
        return [
            'user_name' => $waitlist_entry->user_name,
            'user_email' => $waitlist_entry->user_email,
            'user_first_name' => $user_name_parts[0] ?? '',
            'user_last_name' => $user_name_parts[1] ?? '',
            'activity_name' => $activity ? $activity->post_title : '',
            'booking_date' => $formatted_date,
            'booking_time' => $formatted_time,
            'waitlist_position' => $this->get_waitlist_position($waitlist_entry->id)
        ];
    }
    
    /**
     * Get activity categories as comma-separated string
     * 
     * @param int $activity_id
     * @return string
     */
    private function get_activity_categories($activity_id) {
        $categories = get_the_terms($activity_id, 'activity_category');
        if ($categories && !is_wp_error($categories)) {
            return implode(', ', wp_list_pluck($categories, 'name'));
        }
        return '';
    }
    
    /**
     * Get waitlist position
     * 
     * @param int $waitlist_id
     * @return int
     */
    private function get_waitlist_position($waitlist_id) {
        global $wpdb;
        
        $position = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) + 1 
            FROM {$wpdb->prefix}waza_waitlist w1 
            WHERE w1.slot_id = (
                SELECT slot_id FROM {$wpdb->prefix}waza_waitlist WHERE id = %d
            ) 
            AND w1.created_at < (
                SELECT created_at FROM {$wpdb->prefix}waza_waitlist WHERE id = %d
            )
        ", $waitlist_id, $waitlist_id));
        
        return (int) $position;
    }
    
    /**
     * Cancel scheduled notifications for a booking
     * 
     * @param int $booking_id
     */
    public function cancel_scheduled_notifications($booking_id) {
        // Cancel reminders
        as_unschedule_all_actions('waza_send_reminder', [$booking_id, '24h']);
        as_unschedule_all_actions('waza_send_reminder', [$booking_id, '1h']);
        
        // Cancel thank you message
        as_unschedule_all_actions('waza_send_thank_you_message', [$booking_id]);
    }
}