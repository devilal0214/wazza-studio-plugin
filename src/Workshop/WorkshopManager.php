<?php
/**
 * Workshop Manager
 * 
 * Handles instructor workshop creation, invite links, and student enrollment
 * 
 * @package WazaBooking\Workshop
 */

namespace WazaBooking\Workshop;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Workshop Manager Class
 */
class WorkshopManager {
    
    /**
     * Initialize workshop functionality
     */
    public function init() {
        add_action('admin_menu', [$this, 'add_admin_menu'], 20);
        add_action('wp_ajax_waza_create_workshop', [$this, 'ajax_create_workshop']);
        add_action('wp_ajax_waza_get_workshop_roster', [$this, 'ajax_get_workshop_roster']);
        add_action('wp_ajax_waza_join_workshop', [$this, 'ajax_join_workshop']);
        add_action('wp_ajax_nopriv_waza_join_workshop', [$this, 'ajax_join_workshop']);
        add_action('template_redirect', [$this, 'handle_workshop_invite']);
        add_shortcode('waza_workshop_invite', [$this, 'workshop_invite_shortcode']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'waza-booking',
            __('Workshops', 'waza-booking'),
            __('Workshops', 'waza-booking'),
            'edit_waza_slots',
            'waza-workshops',
            [$this, 'admin_page']
        );
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        global $wpdb;
        
        $workshops = $wpdb->get_results("
            SELECT w.*, a.post_title as activity_name, s.start_datetime, u.display_name as instructor_name
            FROM {$wpdb->prefix}waza_workshops w
            LEFT JOIN {$wpdb->prefix}waza_bookings b ON w.booking_id = b.id
            LEFT JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
            LEFT JOIN {$wpdb->posts} a ON s.activity_id = a.ID AND a.post_type = 'waza_activity'
            LEFT JOIN {$wpdb->users} u ON w.instructor_id = u.ID
            ORDER BY w.created_at DESC
            LIMIT 100
        ");
        ?>
        <div class=\"wrap\">
            <h1><?php esc_html_e('Workshops', 'waza-booking'); ?></h1>
            <p><?php esc_html_e('Manage instructor-led workshops and view student rosters.', 'waza-booking'); ?></p>
            
            <table class=\"wp-list-table widefat fixed striped\">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Workshop Title', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Activity', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Instructor', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Date/Time', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Max Students', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Price', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Invite Link', 'waza-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($workshops)): ?>
                        <tr>
                            <td colspan=\"7\"><?php esc_html_e('No workshops found.', 'waza-booking'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($workshops as $workshop): ?>
                            <tr>
                                <td><?php echo esc_html($workshop->workshop_title); ?></td>
                                <td><?php echo esc_html($workshop->activity_name ?? 'N/A'); ?></td>
                                <td><?php echo esc_html($workshop->instructor_name ?? 'N/A'); ?></td>
                                <td><?php echo esc_html($workshop->start_datetime ?? 'N/A'); ?></td>
                                <td><?php echo esc_html($workshop->max_students); ?></td>
                                <td><?php echo $workshop->is_paid ? '₹' . number_format($workshop->price, 2) : __('Free', 'waza-booking'); ?></td>
                                <td>
                                    <input type=\"text\" value=\"<?php echo esc_attr($workshop->invite_link); ?>\" readonly style=\"width: 100%; font-size: 11px;\" />
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <br>
            <p class=\"description\">
                <strong><?php esc_html_e('Note:', 'waza-booking'); ?></strong>
                <?php esc_html_e('Instructors create workshops from the frontend. Use shortcodes [waza_workshop_invite] to display workshop creation forms.', 'waza-booking'); ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Create workshop booking for instructor
     */
    public function ajax_create_workshop() {
        check_ajax_referer('waza_workshop_nonce', 'nonce');
        
        if (!current_user_can('edit_waza_slots')) {
            wp_send_json_error(__('Permission denied', 'waza-booking'));
        }
        
        $slot_id = intval($_POST['slot_id'] ?? 0);
        $workshop_title = sanitize_text_field($_POST['workshop_title'] ?? '');
        $workshop_description = sanitize_textarea_field($_POST['workshop_description'] ?? '');
        $max_students = intval($_POST['max_students'] ?? 0);
        $is_paid = (bool) ($_POST['is_paid'] ?? false);
        $price = $is_paid ? floatval($_POST['price'] ?? 0) : 0;
        
        if (!$slot_id || !$workshop_title) {
            wp_send_json_error(__('Missing required fields', 'waza-booking'));
        }
        
        global $wpdb;
        
        // Verify instructor owns this slot or has permission
        $instructor_id = get_current_user_id();
        
        // Create workshop booking
        $booking_data = [
            'slot_id' => $slot_id,
            'user_id' => $instructor_id,
            'user_name' => wp_get_current_user()->display_name,
            'user_email' => wp_get_current_user()->user_email,
            'attendees_count' => $max_students > 0 ? $max_students : 50,
            'total_amount' => 0,
            'payment_status' => 'completed',
            'booking_status' => 'confirmed',
            'booking_type' => 'workshop',
            'special_requests' => $workshop_description,
            'created_at' => current_time('mysql')
        ];
        
        $result = $wpdb->insert($wpdb->prefix . 'waza_bookings', $booking_data);
        
        if (!$result) {
            wp_send_json_error(__('Failed to create workshop', 'waza-booking'));
        }
        
        $booking_id = $wpdb->insert_id;
        
        // Generate unique invite link
        $invite_token = $this->generate_workshop_token($booking_id);
        
        // Store workshop metadata
        $workshop_meta = [
            'workshop_title' => $workshop_title,
            'workshop_description' => $workshop_description,
            'instructor_id' => $instructor_id,
            'max_students' => $max_students,
            'is_paid' => $is_paid,
            'price' => $price,
            'invite_token' => $invite_token,
            'invite_link' => home_url('/workshop/' . $invite_token),
            'created_at' => current_time('mysql')
        ];
        
        $wpdb->insert($wpdb->prefix . 'waza_workshops', array_merge(['booking_id' => $booking_id], $workshop_meta));
        
        // Generate Master QR for instructor
        $qr_manager = \WazaBooking\Core\Plugin::get_instance()->get_manager('qr');
        $master_qr_token = $qr_manager->generate_qr_token($booking_id, $slot_id, 'master');
        
        wp_send_json_success([
            'booking_id' => $booking_id,
            'invite_link' => $workshop_meta['invite_link'],
            'master_qr_token' => $master_qr_token,
            'message' => __('Workshop created successfully!', 'waza-booking')
        ]);
    }
    
    /**
     * Get workshop roster (attending students)
     */
    public function ajax_get_workshop_roster() {
        check_ajax_referer('waza_workshop_nonce', 'nonce');
        
        $workshop_id = intval($_POST['workshop_id'] ?? 0);
        
        if (!$workshop_id) {
            wp_send_json_error(__('Invalid workshop ID', 'waza-booking'));
        }
        
        global $wpdb;
        
        // Verify user is instructor of this workshop
        $workshop = $wpdb->get_row($wpdb->prepare("
            SELECT w.*, b.user_id as instructor_id
            FROM {$wpdb->prefix}waza_workshops w
            JOIN {$wpdb->prefix}waza_bookings b ON w.booking_id = b.id
            WHERE w.id = %d
        ", $workshop_id));
        
        if (!$workshop || $workshop->instructor_id != get_current_user_id()) {
            wp_send_json_error(__('Permission denied', 'waza-booking'));
        }
        
        // Get all students enrolled in workshop
        $students = $wpdb->get_results($wpdb->prepare("
            SELECT ws.*, b.user_name, b.user_email, b.user_phone, 
                   b.payment_status, b.attended, b.attended_at, b.qr_token
            FROM {$wpdb->prefix}waza_workshop_students ws
            JOIN {$wpdb->prefix}waza_bookings b ON ws.booking_id = b.id
            WHERE ws.workshop_id = %d
            ORDER BY ws.joined_at DESC
        ", $workshop_id));
        
        wp_send_json_success([
            'workshop' => $workshop,
            'students' => $students,
            'total_students' => count($students)
        ]);
    }
    
    /**
     * Join workshop via invite link
     */
    public function ajax_join_workshop() {
        check_ajax_referer('waza_workshop_nonce', 'nonce');
        
        $invite_token = sanitize_text_field($_POST['invite_token'] ?? '');
        $user_name = sanitize_text_field($_POST['user_name'] ?? '');
        $user_email = sanitize_email($_POST['user_email'] ?? '');
        $user_phone = sanitize_text_field($_POST['user_phone'] ?? '');
        
        if (!$invite_token || !$user_name || !$user_email) {
            wp_send_json_error(__('Missing required fields', 'waza-booking'));
        }
        
        global $wpdb;
        
        // Get workshop details
        $workshop = $wpdb->get_row($wpdb->prepare("
            SELECT w.*, b.slot_id
            FROM {$wpdb->prefix}waza_workshops w
            JOIN {$wpdb->prefix}waza_bookings b ON w.booking_id = b.id
            WHERE w.invite_token = %s
        ", $invite_token));
        
        if (!$workshop) {
            wp_send_json_error(__('Invalid workshop invite link', 'waza-booking'));
        }
        
        // Check if workshop is full
        $current_students = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}waza_workshop_students
            WHERE workshop_id = %d
        ", $workshop->id));
        
        if ($workshop->max_students > 0 && $current_students >= $workshop->max_students) {
            wp_send_json_error(__('Workshop is full', 'waza-booking'));
        }
        
        // Check if already enrolled
        $existing = $wpdb->get_var($wpdb->prepare("
            SELECT id FROM {$wpdb->prefix}waza_workshop_students
            WHERE workshop_id = %d AND user_email = %s
        ", $workshop->id, $user_email));
        
        if ($existing) {
            wp_send_json_error(__('You are already enrolled in this workshop', 'waza-booking'));
        }
        
        // Create booking for student
        $user_id = get_current_user_id();
        
        $booking_data = [
            'slot_id' => $workshop->slot_id,
            'user_id' => $user_id > 0 ? $user_id : null,
            'user_name' => $user_name,
            'user_email' => $user_email,
            'user_phone' => $user_phone,
            'attendees_count' => 1,
            'total_amount' => $workshop->is_paid ? $workshop->price : 0,
            'payment_status' => $workshop->is_paid ? 'pending' : 'completed',
            'booking_status' => 'confirmed',
            'booking_type' => 'workshop_student',
            'created_at' => current_time('mysql')
        ];
        
        $result = $wpdb->insert($wpdb->prefix . 'waza_bookings', $booking_data);
        
        if (!$result) {
            wp_send_json_error(__('Failed to join workshop', 'waza-booking'));
        }
        
        $booking_id = $wpdb->insert_id;
        
        // Add to workshop students
        $wpdb->insert($wpdb->prefix . 'waza_workshop_students', [
            'workshop_id' => $workshop->id,
            'booking_id' => $booking_id,
            'user_email' => $user_email,
            'joined_at' => current_time('mysql')
        ]);
        
        // Generate individual QR for student
        $qr_manager = \WazaBooking\Core\Plugin::get_instance()->get_manager('qr');
        $qr_token = $qr_manager->generate_qr_token($booking_id, $workshop->slot_id, 'single');
        
        // Send confirmation email
        do_action('waza_workshop_student_enrolled', $booking_id, $workshop->id);
        
        $response = [
            'booking_id' => $booking_id,
            'qr_token' => $qr_token,
            'message' => __('Successfully enrolled in workshop!', 'waza-booking')
        ];
        
        // If paid workshop, initiate payment
        if ($workshop->is_paid) {
            $response['requires_payment'] = true;
            $response['amount'] = $workshop->price;
        }
        
        wp_send_json_success($response);
    }
    
    /**
     * Handle workshop invite page redirect
     */
    public function handle_workshop_invite() {
        if (preg_match('/^\/workshop\/([a-zA-Z0-9]+)\/?$/', $_SERVER['REQUEST_URI'], $matches)) {
            $invite_token = $matches[1];
            
            // Load workshop invite template
            include WAZA_BOOKING_PLUGIN_DIR . 'templates/workshop-invite.php';
            exit;
        }
    }
    
    /**
     * Workshop invite shortcode
     */
    public function workshop_invite_shortcode($atts) {
        $atts = shortcode_atts(['token' => ''], $atts);
        
        if (empty($atts['token'])) {
            return '<p>' . __('Invalid workshop invite', 'waza-booking') . '</p>';
        }
        
        global $wpdb;
        
        $workshop = $wpdb->get_row($wpdb->prepare("
            SELECT w.*, b.slot_id, u.display_name as instructor_name
            FROM {$wpdb->prefix}waza_workshops w
            JOIN {$wpdb->prefix}waza_bookings b ON w.booking_id = b.id
            JOIN {$wpdb->users} u ON w.instructor_id = u.ID
            WHERE w.invite_token = %s
        ", $atts['token']));
        
        if (!$workshop) {
            return '<p>' . __('Workshop not found', 'waza-booking') . '</p>';
        }
        
        ob_start();
        include WAZA_BOOKING_PLUGIN_DIR . 'templates/workshop-invite-form.php';
        return ob_get_clean();
    }
    
    /**
     * Generate unique workshop invite token
     */
    private function generate_workshop_token($booking_id) {
        return bin2hex(random_bytes(16)) . '-' . $booking_id;
    }
}
