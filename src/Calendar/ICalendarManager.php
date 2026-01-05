<?php
/**
 * iCalendar Manager
 * 
 * Handles .ics file generation for Add-to-Calendar functionality
 * 
 * @package WazaBooking\Calendar
 */

namespace WazaBooking\Calendar;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ICalendar Manager Class
 */
class ICalendarManager {
    
    /**
     * Initialize calendar functionality
     */
    public function init() {
        add_action('init', [$this, 'register_download_endpoint']);
        add_action('template_redirect', [$this, 'handle_ics_download']);
        add_action('wp_ajax_waza_get_calendar_link', [$this, 'ajax_get_calendar_link']);
        add_action('wp_ajax_nopriv_waza_get_calendar_link', [$this, 'ajax_get_calendar_link']);
    }
    
    /**
     * Register ICS download endpoint
     */
    public function register_download_endpoint() {
        add_rewrite_rule(
            '^waza-calendar/download/([0-9]+)/?$',
            'index.php?waza_calendar_download=$matches[1]',
            'top'
        );
        
        add_filter('query_vars', function($vars) {
            $vars[] = 'waza_calendar_download';
            return $vars;
        });
    }
    
    /**
     * Handle .ics file download
     */
    public function handle_ics_download() {
        $booking_id = get_query_var('waza_calendar_download');
        
        if (!$booking_id) {
            return;
        }
        
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_bookings WHERE id = %d
        ", $booking_id));
        
        if (!$booking) {
            wp_die(__('Booking not found', 'waza-booking'));
        }
        
        $ics_content = $this->generate_ics($booking);
        
        // Output ICS file
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="booking-' . $booking_id . '.ics"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        
        echo $ics_content;
        exit;
    }
    
    /**
     * Generate ICS file content
     * 
     * @param object $booking
     * @return string
     */
    public function generate_ics($booking) {
        global $wpdb;
        
        // Get slot details
        $slot = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_slots WHERE id = %d
        ", $booking->slot_id));
        
        if (!$slot) {
            return '';
        }
        
        // Get activity details
        $activity = get_post($slot->activity_id);
        $activity_title = $activity ? $activity->post_title : __('Activity', 'waza-booking');
        $location = $slot->location ?: get_option('waza_booking_settings')['business_address'] ?? '';
        
        // Format dates for ICS (YYYYMMDDTHHmmss)
        $start_dt = new \DateTime($slot->start_datetime);
        $end_dt = new \DateTime($slot->end_datetime);
        $now = new \DateTime();
        
        $dtstamp = $now->format('Ymd\THis\Z');
        $dtstart = $start_dt->format('Ymd\THis');
        $dtend = $end_dt->format('Ymd\THis');
        
        $summary = $activity_title . ' - ' . __('Booking', 'waza-booking') . ' #' . $booking->id;
        $description = sprintf(
            __('Your booking for %s\n\nBooking ID: %s\nParticipants: %d\n\nPlease bring your QR code for check-in.', 'waza-booking'),
            $activity_title,
            'WB-' . str_pad($booking->id, 5, '0', STR_PAD_LEFT),
            $booking->attendees_count
        );
        
        $uid = 'waza-booking-' . $booking->id . '@' . parse_url(home_url(), PHP_URL_HOST);
        
        // Build ICS content
        $ics = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Waza Booking System//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:' . $uid,
            'DTSTAMP:' . $dtstamp,
            'DTSTART:' . $dtstart,
            'DTEND:' . $dtend,
            'SUMMARY:' . $this->escape_ics_string($summary),
            'DESCRIPTION:' . $this->escape_ics_string($description),
            'LOCATION:' . $this->escape_ics_string($location),
            'STATUS:CONFIRMED',
            'SEQUENCE:0',
            'BEGIN:VALARM',
            'TRIGGER:-PT1H',
            'ACTION:DISPLAY',
            'DESCRIPTION:' . $this->escape_ics_string(__('Reminder: Your activity starts in 1 hour', 'waza-booking')),
            'END:VALARM',
            'END:VEVENT',
            'END:VCALENDAR'
        ];
        
        return implode("\r\n", $ics);
    }
    
    /**
     * Escape string for ICS format
     * 
     * @param string $str
     * @return string
     */
    private function escape_ics_string($str) {
        $str = str_replace(["\r\n", "\n", "\r"], '\n', $str);
        $str = str_replace([',', ';'], ['\,', '\;'], $str);
        return $str;
    }
    
    /**
     * AJAX: Get calendar links for various services
     */
    public function ajax_get_calendar_link() {
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
        
        $activity = get_post($slot->activity_id);
        $activity_title = $activity ? $activity->post_title : __('Activity', 'waza-booking');
        
        $links = [
            'ics' => home_url('/waza-calendar/download/' . $booking_id),
            'google' => $this->get_google_calendar_link($booking, $slot, $activity_title),
            'outlook' => $this->get_outlook_calendar_link($booking, $slot, $activity_title),
            'apple' => home_url('/waza-calendar/download/' . $booking_id)
        ];
        
        wp_send_json_success($links);
    }
    
    /**
     * Generate Google Calendar link
     */
    private function get_google_calendar_link($booking, $slot, $title) {
        $start = new \DateTime($slot->start_datetime);
        $end = new \DateTime($slot->end_datetime);
        
        $params = [
            'action' => 'TEMPLATE',
            'text' => $title . ' - Booking #' . $booking->id,
            'dates' => $start->format('Ymd\THis') . '/' . $end->format('Ymd\THis'),
            'details' => 'Booking ID: WB-' . str_pad($booking->id, 5, '0', STR_PAD_LEFT),
            'location' => $slot->location ?: ''
        ];
        
        return 'https://calendar.google.com/calendar/render?' . http_build_query($params);
    }
    
    /**
     * Generate Outlook Calendar link
     */
    private function get_outlook_calendar_link($booking, $slot, $title) {
        $start = new \DateTime($slot->start_datetime);
        $end = new \DateTime($slot->end_datetime);
        
        $params = [
            'path' => '/calendar/action/compose',
            'rru' => 'addevent',
            'subject' => $title . ' - Booking #' . $booking->id,
            'startdt' => $start->format('Y-m-d\TH:i:s'),
            'enddt' => $end->format('Y-m-d\TH:i:s'),
            'body' => 'Booking ID: WB-' . str_pad($booking->id, 5, '0', STR_PAD_LEFT),
            'location' => $slot->location ?: ''
        ];
        
        return 'https://outlook.live.com/calendar/0/deeplink/compose?' . http_build_query($params);
    }
}
