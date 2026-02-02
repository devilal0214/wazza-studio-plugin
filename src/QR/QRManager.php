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
     * Constructor - Register hooks
     */
    public function __construct() {
        add_action('template_redirect', [$this, 'handle_qr_code_download']);
    }
    
    /**
     * Handle QR code download page
     */
    public function handle_qr_code_download() {
        // Get the requested path
        $request_uri = $_SERVER['REQUEST_URI'];
        
        // Check if this is a QR code download request
        // Handle both /qr-code/ and /qr-code? patterns
        if (strpos($request_uri, '/qr-code/') === false && 
            strpos($request_uri, '/qr-code?') === false) {
            return;
        }
        
        $token = sanitize_text_field($_GET['token'] ?? '');
        
        if (empty($token)) {
            wp_die(__('Invalid QR code request. Please use the link from your booking confirmation email.', 'waza-booking'), __('Error', 'waza-booking'), ['response' => 400]);
        }
        
        global $wpdb;
        
        // Get booking details from token
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT b.*, qt.token, qt.token_type, qt.expires_at,
                   s.activity_id, s.start_datetime, s.end_datetime,
                   p.post_title as activity_title
            FROM {$wpdb->prefix}waza_qr_tokens qt
            LEFT JOIN {$wpdb->prefix}waza_bookings b ON qt.booking_id = b.id
            LEFT JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
            LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
            WHERE qt.token = %s AND qt.is_active = 1
        ", $token));
        
        if (!$booking) {
            wp_die(__('QR code not found or expired. Please contact support if you believe this is an error.', 'waza-booking'), __('Error', 'waza-booking'), ['response' => 404]);
        }
        
        // Check if token is expired
        if (strtotime($booking->expires_at) < time()) {
            wp_die(__('This QR code has expired. QR codes are valid until 2 hours after the activity ends.', 'waza-booking'), __('Expired', 'waza-booking'), ['response' => 410]);
        }
        
        // Generate QR code image
        $qr_image = $this->generate_qr_image($token, 300);
        
        if (!$qr_image) {
            wp_die(__('Failed to generate QR code. Please try again or contact support.', 'waza-booking'), __('Error', 'waza-booking'), ['response' => 500]);
        }
        
        // Display QR code page
        $this->display_qr_code_page($booking, $qr_image, $token);
        exit;
    }
    
    /**
     * Display QR code download page
     * 
     * @param object $booking
     * @param string $qr_image Base64 encoded image
     * @param string $token
     */
    private function display_qr_code_page($booking, $qr_image, $token) {
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php _e('Download QR Code', 'waza-booking'); ?> - <?php bloginfo('name'); ?></title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                }
                .qr-container {
                    background: white;
                    border-radius: 20px;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                    max-width: 500px;
                    width: 100%;
                    padding: 40px;
                    text-align: center;
                }
                .qr-header {
                    margin-bottom: 30px;
                }
                .qr-header h1 {
                    color: #333;
                    font-size: 28px;
                    margin-bottom: 10px;
                }
                .qr-header p {
                    color: #666;
                    font-size: 14px;
                }
                .qr-image-wrapper {
                    background: #f8f9fa;
                    border-radius: 15px;
                    padding: 30px;
                    margin-bottom: 30px;
                }
                .qr-image {
                    width: 100%;
                    max-width: 300px;
                    height: auto;
                    border: 4px solid white;
                    border-radius: 10px;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
                }
                .booking-details {
                    background: #f8f9fa;
                    border-radius: 10px;
                    padding: 20px;
                    margin-bottom: 30px;
                    text-align: left;
                }
                .booking-details h3 {
                    color: #333;
                    font-size: 18px;
                    margin-bottom: 15px;
                    text-align: center;
                }
                .detail-row {
                    display: flex;
                    justify-content: space-between;
                    padding: 10px 0;
                    border-bottom: 1px solid #e0e0e0;
                }
                .detail-row:last-child {
                    border-bottom: none;
                }
                .detail-label {
                    color: #666;
                    font-size: 14px;
                    font-weight: 500;
                }
                .detail-value {
                    color: #333;
                    font-size: 14px;
                    font-weight: 600;
                }
                .action-buttons {
                    display: flex;
                    gap: 15px;
                    margin-top: 20px;
                }
                .btn {
                    flex: 1;
                    padding: 15px 30px;
                    border: none;
                    border-radius: 10px;
                    font-size: 16px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    text-decoration: none;
                    display: inline-block;
                }
                .btn-primary {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                }
                .btn-primary:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
                }
                .btn-secondary {
                    background: white;
                    color: #667eea;
                    border: 2px solid #667eea;
                }
                .btn-secondary:hover {
                    background: #667eea;
                    color: white;
                }
                .info-text {
                    color: #888;
                    font-size: 12px;
                    margin-top: 20px;
                    line-height: 1.6;
                }
                @media print {
                    body {
                        background: white;
                    }
                    .action-buttons,
                    .info-text {
                        display: none;
                    }
                }
            </style>
        </head>
        <body>
            <div class="qr-container">
                <div class="qr-header">
                    <h1><?php _e('Your QR Code', 'waza-booking'); ?></h1>
                    <p><?php _e('Show this code at the venue to check in', 'waza-booking'); ?></p>
                </div>
                
                <div class="qr-image-wrapper">
                    <img src="<?php echo esc_attr($qr_image); ?>" alt="QR Code" class="qr-image" id="qr-image">
                </div>
                
                <div class="booking-details">
                    <h3><?php _e('Booking Details', 'waza-booking'); ?></h3>
                    <div class="detail-row">
                        <span class="detail-label"><?php _e('Booking ID', 'waza-booking'); ?>:</span>
                        <span class="detail-value">#<?php echo esc_html($booking->id); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label"><?php _e('Activity', 'waza-booking'); ?>:</span>
                        <span class="detail-value"><?php echo esc_html($booking->activity_title); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label"><?php _e('Name', 'waza-booking'); ?>:</span>
                        <span class="detail-value"><?php echo esc_html($booking->user_name); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label"><?php _e('Date', 'waza-booking'); ?>:</span>
                        <span class="detail-value"><?php echo esc_html(date('M d, Y', strtotime($booking->start_datetime))); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label"><?php _e('Time', 'waza-booking'); ?>:</span>
                        <span class="detail-value"><?php echo esc_html(date('g:i A', strtotime($booking->start_datetime))); ?></span>
                    </div>
                    <?php if ($booking->attendees_count > 1): ?>
                    <div class="detail-row">
                        <span class="detail-label"><?php _e('Attendees', 'waza-booking'); ?>:</span>
                        <span class="detail-value"><?php echo esc_html($booking->attendees_count); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="downloadQR()"><?php _e('Download', 'waza-booking'); ?></button>
                    <button class="btn btn-secondary" onclick="window.print()"><?php _e('Print', 'waza-booking'); ?></button>
                </div>
                
                <p class="info-text">
                    <?php _e('This QR code is valid until', 'waza-booking'); ?>: <strong><?php echo esc_html(date('M d, Y g:i A', strtotime($booking->expires_at))); ?></strong><br>
                    <?php _e('Keep this code safe and do not share it with others.', 'waza-booking'); ?>
                </p>
            </div>
            
            <script>
                function downloadQR() {
                    const img = document.getElementById('qr-image');
                    const link = document.createElement('a');
                    link.href = img.src;
                    link.download = 'waza-booking-<?php echo esc_js($booking->id); ?>-qr-code.png';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            </script>
        </body>
        </html>
        <?php
    }
    
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
            // Validate token
            if (empty($token)) {
                error_log('QR Code generation failed: Empty token provided');
                return false;
            }
            
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