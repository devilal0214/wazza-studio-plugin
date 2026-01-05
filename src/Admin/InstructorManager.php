<?php
/**
 * Instructor Manager
 * 
 * Handles instructor approval, list columns, and backend management
 * 
 * @package WazaBooking\Admin
 */

namespace WazaBooking\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Instructor Manager Class
 */
class InstructorManager {
    
    /**
     * Initialize instructor management
     */
    public function __construct() {
        add_filter('manage_waza_instructor_posts_columns', [$this, 'add_instructor_columns']);
        add_action('manage_waza_instructor_posts_custom_column', [$this, 'render_instructor_columns'], 10, 2);
        add_filter('manage_edit-waza_instructor_sortable_columns', [$this, 'sortable_instructor_columns']);
        add_action('admin_footer', [$this, 'add_approval_js']);
        add_action('wp_ajax_waza_toggle_instructor_status', [$this, 'toggle_instructor_status']);
        
        // Handle post status transitions for emails
        add_action('transition_post_status', [$this, 'handle_status_transition'], 10, 3);
    }
    
    /**
     * Add custom columns to instructor list
     */
    public function add_instructor_columns($columns) {
        $new_columns = [];
        foreach ($columns as $key => $value) {
            if ($key === 'date') {
                $new_columns['specialty'] = __('Specialties', 'waza-booking');
                $new_columns['status'] = __('Approval Status', 'waza-booking');
                $new_columns['bookings'] = __('Bookings', 'waza-booking');
            }
            $new_columns[$key] = $value;
        }
        return $new_columns;
    }
    
    /**
     * Render custom column content
     */
    public function render_instructor_columns($column, $post_id) {
        switch ($column) {
            case 'specialty':
                $terms = get_the_term_list($post_id, 'waza_instructor_specialty', '', ', ');
                echo $terms ?: '—';
                break;
                
            case 'status':
                $post = get_post($post_id);
                $is_approved = $post->post_status === 'publish';
                $class = $is_approved ? 'approved' : 'pending';
                $label = $is_approved ? __('Approved', 'waza-booking') : __('Pending', 'waza-booking');
                
                echo '<span class="waza-status-badge status-' . esc_attr($class) . '">' . esc_html($label) . '</span>';
                echo '<br><a href="#" class="waza-toggle-status" data-id="' . esc_attr($post_id) . '" data-status="' . esc_attr($post->post_status) . '">';
                echo $is_approved ? __('Disapprove', 'waza-booking') : __('Approve', 'waza-booking');
                echo '</a>';
                break;
                
            case 'bookings':
                global $wpdb;
                $count = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) FROM {$wpdb->prefix}waza_bookings b
                    JOIN {$wpdb->postmeta} pm ON b.slot_id = pm.post_id
                    WHERE pm.meta_key = '_waza_instructor_id' AND pm.meta_value = %d
                ", $post_id));
                echo intval($count);
                break;
        }
    }
    
    /**
     * Make columns sortable
     */
    public function sortable_instructor_columns($columns) {
        $columns['status'] = 'status';
        return $columns;
    }
    
    /**
     * Add JS for status toggling
     */
    public function add_approval_js() {
        $screen = get_current_screen();
        if ($screen->id !== 'edit-waza_instructor') {
            return;
        }
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('.waza-toggle-status').on('click', function(e) {
                e.preventDefault();
                var $link = $(this);
                var id = $link.data('id');
                var currentStatus = $link.data('status');
                
                $link.text('<?php esc_html_e('Updating...', 'waza-booking'); ?>');
                
                $.post(ajaxurl, {
                    action: 'waza_toggle_instructor_status',
                    instructor_id: id,
                    nonce: '<?php echo wp_create_nonce('waza_instructor_status'); ?>'
                }, function(response) {
                    if (response.success) {
                        window.location.reload();
                    } else {
                        alert(response.data || '<?php esc_html_e('Error updating status', 'waza-booking'); ?>');
                        $link.text(currentStatus === 'publish' ? '<?php esc_html_e('Disapprove', 'waza-booking'); ?>' : '<?php esc_html_e('Approve', 'waza-booking'); ?>');
                    }
                });
            });
        });
        </script>
        <style>
        .waza-status-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .status-approved { background: #d1e7dd; color: #0f5132; }
        .status-pending { background: #fef5e7; color: #947600; }
        .waza-toggle-status { font-size: 11px; color: #2271b1; text-decoration: none; }
        .waza-toggle-status:hover { color: #135e96; }
        </style>
        <?php
    }
    
    /**
     * Handle AJAX status toggle
     */
    public function toggle_instructor_status() {
        check_ajax_referer('waza_instructor_status', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'waza-booking'));
        }
        
        $instructor_id = intval($_POST['instructor_id']);
        $post = get_post($instructor_id);
        
        if (!$post || $post->post_type !== 'waza_instructor') {
            wp_send_json_error(__('Invalid instructor ID', 'waza-booking'));
        }
        
        $new_status = $post->post_status === 'publish' ? 'pending' : 'publish';
        
        wp_update_post([
            'ID' => $instructor_id,
            'post_status' => $new_status
        ]);
        
        wp_send_json_success();
    }
    
    /**
     * Handle status transition (send email on approval)
     */
    public function handle_status_transition($new_status, $old_status, $post) {
        if ($post->post_type !== 'waza_instructor') {
            return;
        }
        
        if ($old_status !== 'publish' && $new_status === 'publish') {
            // Instructor approved
            $this->send_approval_email($post);
        }
    }
    
    /**
     * Send approval email to instructor
     */
    private function send_approval_email($post) {
        $email = get_post_meta($post->ID, '_waza_email', true);
        if (!$email) {
            return;
        }
        
        $subject = sprintf(__('Your Instructor Account on %s has been approved!', 'waza-booking'), get_bloginfo('name'));
        $message = sprintf(__('Hello %s,', 'waza-booking'), $post->post_title) . "\r\n\r\n";
        $message .= __('Congratulations! Your instructor account has been approved by the administrator.', 'waza-booking') . "\r\n";
        $message .= __('You can now log in to the dashboard and manage your slots.', 'waza-booking') . "\r\n\r\n";
        $message .= __('Login here: ', 'waza-booking') . home_url('/login/') . "\r\n\r\n";
        $message .= __('Thanks!', 'waza-booking');
        
        wp_mail($email, $subject, $message);
    }
}
