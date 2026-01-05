<?php
/**
 * Auto Logout Manager
 * 
 * Handles automatic logout for students after slot timeout
 * 
 * @package WazaBooking\User
 */

namespace WazaBooking\User;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Auto Logout Manager Class
 */
class AutoLogoutManager {
    
    /**
     * Session timeout in seconds (default: 1 hour after last slot)
     */
    private $timeout_seconds = 3600;
    
    /**
     * Initialize auto-logout functionality
     */
    public function init() {
        // Check for auto-logout on every page load for logged-in students
        add_action('init', [$this, 'check_auto_logout'], 1);
        
        // Display auto-logout notice
        add_action('wp_footer', [$this, 'display_logout_notice']);
        
        // Add login notice for auto-logout
        add_action('login_message', [$this, 'login_auto_logout_message']);
    }
    
    /**
     * Check if user should be auto-logged-out
     */
    public function check_auto_logout() {
        // Only for logged-in users
        if (!is_user_logged_in()) {
            return;
        }
        
        // Skip admin pages
        if (is_admin()) {
            return;
        }
        
        $user = wp_get_current_user();
        
        // Only auto-logout students (not admins or instructors)
        if (!in_array('waza_student', $user->roles) && !in_array('subscriber', $user->roles)) {
            return;
        }
        
        global $wpdb;
        
        // Get user's next upcoming slot
        $next_slot = $wpdb->get_var($wpdb->prepare("
            SELECT MIN(s.end_datetime)
            FROM {$wpdb->prefix}waza_bookings b
            LEFT JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
            WHERE b.user_id = %d 
            AND b.booking_status = 'confirmed'
            AND s.end_datetime >= NOW()
        ", $user->ID));
        
        // If user has upcoming bookings, don't logout
        if ($next_slot) {
            // Update last activity timestamp
            update_user_meta($user->ID, 'waza_last_slot_time', strtotime($next_slot));
            return;
        }
        
        // Get the last slot time
        $last_slot = $wpdb->get_var($wpdb->prepare("
            SELECT MAX(s.end_datetime)
            FROM {$wpdb->prefix}waza_bookings b
            LEFT JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
            WHERE b.user_id = %d
        ", $user->ID));
        
        // If no bookings at all, don't auto-logout
        if (!$last_slot) {
            return;
        }
        
        $last_slot_time = strtotime($last_slot);
        $current_time = current_time('timestamp');
        
        // Check if timeout period has passed since last slot
        if (($current_time - $last_slot_time) > $this->timeout_seconds) {
            // Store logout reason
            set_transient('waza_auto_logout_' . $user->ID, [
                'reason' => 'slot_timeout',
                'last_slot' => $last_slot,
                'message' => __('You have been automatically logged out because your booking session has expired.', 'waza-booking')
            ], 300); // 5 minutes
            
            // Log the activity
            do_action('waza_log_activity', 'user_auto_logout', 'user', $user->ID, [
                'description' => 'User auto-logged out due to slot timeout',
                'last_slot' => $last_slot
            ]);
            
            // Logout user
            wp_logout();
            
            // Redirect to login page with auto-logout flag
            $login_url = home_url('/student-login');
            if (!$login_url) {
                $login_url = wp_login_url();
            }
            
            wp_redirect(add_query_arg('auto_logout', '1', $login_url));
            exit;
        }
    }
    
    /**
     * Display auto-logout countdown notice
     */
    public function display_logout_notice() {
        if (!is_user_logged_in() || is_admin()) {
            return;
        }
        
        $user = wp_get_current_user();
        
        // Only for students
        if (!in_array('waza_student', $user->roles) && !in_array('subscriber', $user->roles)) {
            return;
        }
        
        global $wpdb;
        
        // Get last slot end time
        $last_slot = $wpdb->get_var($wpdb->prepare("
            SELECT MAX(s.end_datetime)
            FROM {$wpdb->prefix}waza_bookings b
            LEFT JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
            WHERE b.user_id = %d
            AND s.end_datetime < NOW()
        ", $user->ID));
        
        if (!$last_slot) {
            return;
        }
        
        $last_slot_time = strtotime($last_slot);
        $current_time = current_time('timestamp');
        $time_since_last = $current_time - $last_slot_time;
        
        // Show notice if within 30 minutes of timeout
        if ($time_since_last > ($this->timeout_seconds - 1800) && $time_since_last < $this->timeout_seconds) {
            $minutes_remaining = ceil(($this->timeout_seconds - $time_since_last) / 60);
            ?>
            <div id="waza-auto-logout-notice" style="
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                background: #fff3cd;
                color: #856404;
                padding: 15px;
                text-align: center;
                border-bottom: 2px solid #ffc107;
                z-index: 999999;
                font-size: 14px;
            ">
                <strong><?php esc_html_e('Notice:', 'waza-booking'); ?></strong>
                <?php 
                printf(
                    esc_html__('You will be automatically logged out in %d minutes due to session timeout.', 'waza-booking'),
                    $minutes_remaining
                );
                ?>
                <a href="<?php echo esc_url(home_url('/activities')); ?>" style="margin-left: 10px; text-decoration: underline;">
                    <?php esc_html_e('Book another slot to extend session', 'waza-booking'); ?>
                </a>
            </div>
            <script>
                // Auto-refresh to update countdown
                setTimeout(function() {
                    location.reload();
                }, 60000); // Refresh every minute
            </script>
            <?php
        }
    }
    
    /**
     * Display auto-logout message on login page
     */
    public function login_auto_logout_message($message) {
        if (isset($_GET['auto_logout']) && $_GET['auto_logout'] == '1') {
            $user_id = get_current_user_id();
            if (!$user_id && isset($_COOKIE['waza_auto_logout_user'])) {
                $user_id = intval($_COOKIE['waza_auto_logout_user']);
            }
            
            $logout_data = get_transient('waza_auto_logout_' . $user_id);
            
            $auto_logout_msg = '<div class="message" style="background: #fff3cd; color: #856404; border-left: 4px solid #ffc107; padding: 12px;">';
            $auto_logout_msg .= '<p><strong>' . esc_html__('Session Expired', 'waza-booking') . '</strong></p>';
            
            if ($logout_data && isset($logout_data['message'])) {
                $auto_logout_msg .= '<p>' . esc_html($logout_data['message']) . '</p>';
                if (isset($logout_data['last_slot'])) {
                    $auto_logout_msg .= '<p><small>' . sprintf(
                        esc_html__('Your last booking slot ended at: %s', 'waza-booking'),
                        date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($logout_data['last_slot']))
                    ) . '</small></p>';
                }
            } else {
                $auto_logout_msg .= '<p>' . esc_html__('You have been automatically logged out because your booking session has expired.', 'waza-booking') . '</p>';
            }
            
            $auto_logout_msg .= '<p><small>' . esc_html__('Please log in again to book new activities.', 'waza-booking') . '</small></p>';
            $auto_logout_msg .= '</div>';
            
            $message .= $auto_logout_msg;
        }
        
        return $message;
    }
    
    /**
     * Get timeout duration in seconds
     */
    public function get_timeout_seconds() {
        return apply_filters('waza_auto_logout_timeout', $this->timeout_seconds);
    }
    
    /**
     * Set timeout duration
     */
    public function set_timeout_seconds($seconds) {
        $this->timeout_seconds = absint($seconds);
    }
}
