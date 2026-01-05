<?php
/**
 * QR Manager
 * 
 * @package WazaBooking\QR
 */

namespace WazaBooking\QR;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use WP_Error;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * QR Manager Class
 */
class QRManager {
    
    /**
     * Generate QR token for booking
     * 
     * @param int $booking_id
     * @param int $slot_id
     * @param string $type Options: 'single', 'multi', 'group', 'master'
     * @return string|false
     */
    public function generate_qr_token($booking_id, $slot_id, $type = 'single') {
        // Generate secure token
        $token = $this->generate_secure_token();
        $token_hash = hash('sha256', $token . get_option('waza_qr_secret', wp_salt()));
        
        // Get slot details from database
        global $wpdb;
        $slot = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_slots WHERE id = %d
        ", $slot_id));
        
        if (!$slot) {
            return false;
        }
        
        // Calculate expiry (slot end time + 2 hours)
        $expires_at = date('Y-m-d H:i:s', strtotime($slot->end_datetime . ' +2 hours'));
        
        // Set max uses based on type
        // single: 1 use, group: multiple uses for group members, multi: 999 uses, master: unlimited for instructors
        $max_uses_map = [
            'single' => 1,
            'group' => 50,
            'multi' => 999,
            'master' => 9999
        ];
        $max_uses = $max_uses_map[$type] ?? 1;
        
        global $wpdb;
        
        $token_data = [
            'token' => $token,
            'token_hash' => $token_hash,
            'booking_id' => $booking_id,
            'slot_id' => $slot_id,
            'token_type' => $type,
            'max_uses' => $max_uses,
            'expires_at' => $expires_at,
            'created_at' => current_time('mysql')
        ];
        
        $result = $wpdb->insert($wpdb->prefix . 'waza_qr_tokens', $token_data);
        
        if ($result) {
            // Update booking with QR token
            $wpdb->update(
                $wpdb->prefix . 'waza_bookings',
                ['qr_token' => $token],
                ['id' => $booking_id]
            );
            
            return $token;
        }
        
        return false;
    }
    
    /**
     * Generate QR code image
     * 
     * @param string $token
     * @param int $size
     * @return string Base64 encoded PNG
     */
    public function generate_qr_image($token, $size = 200) {
        try {
            $qr_code = new QrCode($token);
            $qr_code->setSize($size);
            
            $writer = new PngWriter();
            $result = $writer->write($qr_code);
            
            return 'data:image/png;base64,' . base64_encode($result->getString());
        } catch (\Exception $e) {
            error_log('QR Code generation failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify QR token
     * 
     * @param string $token
     * @param string $scanner_device
     * @return array|WP_Error
     */
    public function verify_token($token, $scanner_device = null) {
        global $wpdb;
        
        // Get token data with booking info
        $qr_data = $wpdb->get_row($wpdb->prepare("
            SELECT qt.*, b.user_name, b.user_email, b.booking_status, b.attendees_count,
                   p.post_title as slot_title
            FROM {$wpdb->prefix}waza_qr_tokens qt
            LEFT JOIN {$wpdb->prefix}waza_bookings b ON qt.booking_id = b.id
            LEFT JOIN {$wpdb->posts} p ON qt.slot_id = p.ID
            WHERE qt.token = %s AND qt.is_active = 1
        ", $token));
        
        if (!$qr_data) {
            return new WP_Error(
                'invalid_token',
                __('Invalid or expired QR code', 'waza-booking'),
                ['status' => 400]
            );
        }
        
        // Check if token is expired
        if (strtotime($qr_data->expires_at) < time()) {
            return new WP_Error(
                'token_expired',
                __('QR code has expired', 'waza-booking'),
                ['status' => 400]
            );
        }
        
        // Check if booking is valid
        if ($qr_data->booking_status !== 'confirmed') {
            return new WP_Error(
                'invalid_booking',
                __('Booking is not confirmed', 'waza-booking'),
                ['status' => 400]
            );
        }
        
        // Check usage limit
        if ($qr_data->used_count >= $qr_data->max_uses) {
            return new WP_Error(
                'token_used',
                __('QR code has already been used', 'waza-booking'),
                ['status' => 400]
            );
        }
        
        // Update token usage
        $wpdb->update(
            $wpdb->prefix . 'waza_qr_tokens',
            [
                'used_count' => $qr_data->used_count + 1,
                'last_used_at' => current_time('mysql'),
                'scanner_device' => $scanner_device
            ],
            ['id' => $qr_data->id]
        );
        
        // Mark attendance
        $this->mark_attendance($qr_data->booking_id, $qr_data->slot_id, $qr_data->id, $scanner_device);
        
        return [
            'booking_id' => $qr_data->booking_id,
            'user_name' => $qr_data->user_name,
            'user_email' => $qr_data->user_email,
            'slot_title' => $qr_data->slot_title,
            'attendees_count' => $qr_data->attendees_count,
            'remaining_uses' => $qr_data->max_uses - ($qr_data->used_count + 1),
            'verified_at' => current_time('mysql')
        ];
    }
    
    /**
     * Mark attendance
     * 
     * @param int $booking_id
     * @param int $slot_id
     * @param int $qr_token_id
     * @param string $scanner_device
     */
    private function mark_attendance($booking_id, $slot_id, $qr_token_id, $scanner_device) {
        global $wpdb;
        
        // Update booking attendance status
        $wpdb->update(
            $wpdb->prefix . 'waza_bookings',
            [
                'attended' => 1,
                'attended_at' => current_time('mysql')
            ],
            ['id' => $booking_id]
        );
        
        // Insert attendance record
        $attendance_data = [
            'booking_id' => $booking_id,
            'slot_id' => $slot_id,
            'qr_token_id' => $qr_token_id,
            'check_in_time' => current_time('mysql'),
            'scanner_device' => $scanner_device,
            'scanner_user_id' => get_current_user_id() ?: null,
            'ip_address' => $this->get_client_ip(),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '')
        ];
        
        $wpdb->insert($wpdb->prefix . 'waza_attendance', $attendance_data);
    }
    
    /**
     * Generate secure token
     * 
     * @return string
     */
    private function generate_secure_token() {
        return wp_generate_uuid4();
    }
    
    /**
     * Get client IP
     */
    private function get_client_ip() {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                return trim($ips[0]);
            }
        }
        
        return '';
    }
    
    /**
     * Cleanup expired tokens (scheduled task)
     */
    public function cleanup_expired_tokens() {
        global $wpdb;
        
        $deleted = $wpdb->query("
            DELETE FROM {$wpdb->prefix}waza_qr_tokens 
            WHERE expires_at < NOW() AND used_count >= max_uses
        ");
        
        if ($deleted) {
            error_log("Cleaned up {$deleted} expired QR tokens");
        }
    }

    /**
     * Get booking QR image URL (base64)
     * 
     * @param int $booking_id
     * @return string|false
     */
    public function get_booking_qr_url($booking_id) {
        global $wpdb;
        
        $token = $wpdb->get_var($wpdb->prepare("
            SELECT token FROM {$wpdb->prefix}waza_qr_tokens 
            WHERE booking_id = %d AND is_active = 1
            LIMIT 1
        ", $booking_id));
        
        if (!$token) {
            return false;
        }
        
        return $this->generate_qr_image($token);
    }
    
    /**
     * Generate group QR for choreographer with multiple students
     * 
     * @param int $booking_id Main choreographer booking
     * @param int $slot_id
     * @param int $num_students Number of students in group
     * @return array Array of individual QR tokens for students
     */
    public function generate_group_qr($booking_id, $slot_id, $num_students) {
        // Generate master group QR for choreographer
        $group_qr = $this->generate_qr_token($booking_id, $slot_id, 'group');
        
        global $wpdb;
        
        // Store group metadata
        $wpdb->insert($wpdb->prefix . 'waza_qr_groups', [
            'master_booking_id' => $booking_id,
            'master_qr_token' => $group_qr,
            'slot_id' => $slot_id,
            'total_members' => $num_students,
            'created_at' => current_time('mysql')
        ]);
        
        $group_id = $wpdb->insert_id;
        
        // Generate individual QRs for each student
        $student_qrs = [];
        for ($i = 1; $i <= $num_students; $i++) {
            // Create placeholder booking for student
            $student_booking_data = [
                'slot_id' => $slot_id,
                'user_id' => null,
                'user_name' => 'Group Member ' . $i,
                'user_email' => '',
                'attendees_count' => 1,
                'total_amount' => 0,
                'payment_status' => 'completed',
                'booking_status' => 'confirmed',
                'booking_type' => 'group_member',
                'created_at' => current_time('mysql')
            ];
            
            $wpdb->insert($wpdb->prefix . 'waza_bookings', $student_booking_data);
            $student_booking_id = $wpdb->insert_id;
            
            // Generate individual QR
            $student_qr = $this->generate_qr_token($student_booking_id, $slot_id, 'single');
            
            // Link to group
            $wpdb->insert($wpdb->prefix . 'waza_qr_group_members', [
                'group_id' => $group_id,
                'booking_id' => $student_booking_id,
                'qr_token' => $student_qr,
                'member_number' => $i
            ]);
            
            $student_qrs[] = [
                'booking_id' => $student_booking_id,
                'qr_token' => $student_qr,
                'member_number' => $i
            ];
        }
        
        return [
            'group_qr' => $group_qr,
            'group_id' => $group_id,
            'student_qrs' => $student_qrs
        ];
    }
    
    /**
     * Verify Master QR for instructors
     * 
     * @param string $token
     * @return array|WP_Error
     */
    public function verify_master_qr($token) {
        global $wpdb;
        
        $qr_data = $wpdb->get_row($wpdb->prepare("
            SELECT qt.*, b.user_name, b.booking_type, ws.workshop_title
            FROM {$wpdb->prefix}waza_qr_tokens qt
            LEFT JOIN {$wpdb->prefix}waza_bookings b ON qt.booking_id = b.id
            LEFT JOIN {$wpdb->prefix}waza_workshops ws ON b.id = ws.booking_id
            WHERE qt.token = %s AND qt.token_type = 'master' AND qt.is_active = 1
        ", $token));
        
        if (!$qr_data) {
            return new WP_Error(
                'invalid_master_qr',
                __('Invalid Master QR code', 'waza-booking')
            );
        }
        
        // Master QR grants instructor special access
        return [
            'valid' => true,
            'type' => 'master',
            'instructor_name' => $qr_data->user_name,
            'workshop_title' => $qr_data->workshop_title ?? '',
            'booking_id' => $qr_data->booking_id,
            'slot_id' => $qr_data->slot_id,
            'used_count' => $qr_data->used_count,
            'max_uses' => $qr_data->max_uses
        ];
    }
    
    /**
     * Get group QR details
     * 
     * @param int $group_id
     * @return array|false
     */
    public function get_group_qr_details($group_id) {
        global $wpdb;
        
        $group = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_qr_groups WHERE id = %d
        ", $group_id));
        
        if (!$group) {
            return false;
        }
        
        $members = $wpdb->get_results($wpdb->prepare("
            SELECT gm.*, b.user_name, b.attended, b.attended_at
            FROM {$wpdb->prefix}waza_qr_group_members gm
            LEFT JOIN {$wpdb->prefix}waza_bookings b ON gm.booking_id = b.id
            WHERE gm.group_id = %d
            ORDER BY gm.member_number
        ", $group_id));
        
        $attended_count = count(array_filter($members, function($m) {
            return $m->attended == 1;
        }));
        
        return [
            'group' => $group,
            'members' => $members,
            'total_members' => $group->total_members,
            'attended_count' => $attended_count,
            'attendance_percentage' => $group->total_members > 0 ? 
                round(($attended_count / $group->total_members) * 100, 2) : 0
        ];
    }
}