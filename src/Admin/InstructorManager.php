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
        add_action('wp_ajax_waza_sync_instructor', [$this, 'sync_single_instructor']);
        add_action('wp_ajax_waza_get_instructor_details', [$this, 'ajax_get_instructor_details']);
        
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
                $user_id = get_post_meta($post_id, '_waza_user_id', true);
                $is_approved = $post->post_status === 'publish';
                $class = $is_approved ? 'approved' : 'pending';
                $label = $is_approved ? __('Approved', 'waza-booking') : __('Pending', 'waza-booking');
                
                echo '<span class="waza-status-badge status-' . esc_attr($class) . '">' . esc_html($label) . '</span>';
                
                // Show user linkage status
                if ($user_id) {
                    $user = get_userdata($user_id);
                    if ($user) {
                        echo '<br><small style="color: #46a049;">✓ User: ' . esc_html($user->user_login) . '</small>';
                    } else {
                        echo '<br><small style="color: #dc3232;">⚠ User deleted</small>';
                    }
                } else {
                    $email = get_post_meta($post_id, '_waza_email', true);
                    if ($email) {
                        echo '<br><small style="color: #d63638;">⚠ Not linked</small>';
                        echo '<br><a href="#" class="waza-sync-instructor" data-id="' . esc_attr($post_id) . '" style="font-size: 11px;">Sync Now</a>';
                    }
                }
                
                echo '<br><a href="#" class="waza-view-details" data-id="' . esc_attr($post_id) . '" style="color: #2271b1;">' . __('View Details', 'waza-booking') . '</a>';
                echo ' | ';
                echo '<a href="#" class="waza-toggle-status" data-id="' . esc_attr($post_id) . '" data-status="' . esc_attr($post->post_status) . '">';
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
            
            $('.waza-sync-instructor').on('click', function(e) {
                e.preventDefault();
                var $link = $(this);
                var id = $link.data('id');
                var originalText = $link.text();
                
                if (!confirm('Create a WordPress user account for this instructor?')) {
                    return;
                }
                
                $link.text('Syncing...');
                
                $.post(ajaxurl, {
                    action: 'waza_sync_instructor',
                    instructor_id: id,
                    nonce: '<?php echo wp_create_nonce('waza_sync_instructor'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        window.location.reload();
                    } else {
                        alert(response.data || 'Error syncing instructor');
                        $link.text(originalText);
                    }
                });
            });
            
            // View instructor details modal
            $('.waza-view-details').on('click', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                
                $.post(ajaxurl, {
                    action: 'waza_get_instructor_details',
                    instructor_id: id,
                    nonce: '<?php echo wp_create_nonce('waza_instructor_details'); ?>'
                }, function(response) {
                    if (response.success) {
                        showInstructorModal(response.data);
                    } else {
                        alert('Error loading instructor details');
                    }
                });
            });
            
            function showInstructorModal(data) {
                var socialHtml = '';
                if (data.social_links && Object.keys(data.social_links).length > 0) {
                    socialHtml = '<div style=\"margin-top: 15px;\"><strong>Social Links:</strong><ul style=\"margin: 5px 0; padding-left: 20px;\">';
                    for (var platform in data.social_links) {
                        socialHtml += '<li><strong>' + platform.charAt(0).toUpperCase() + platform.slice(1) + ':</strong> <a href=\"' + data.social_links[platform] + '\" target=\"_blank\">' + data.social_links[platform] + '</a></li>';
                    }
                    socialHtml += '</ul></div>';
                }
                
                var modalHtml = `
                    <div id=\"instructor-details-modal\" style=\"position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 100000; display: flex; align-items: center; justify-content: center;\">
                        <div style=\"background: white; padding: 30px; border-radius: 8px; max-width: 600px; max-height: 80vh; overflow-y: auto; position: relative;\">
                            <button onclick=\"jQuery('#instructor-details-modal').remove()\" style=\"position: absolute; top: 15px; right: 15px; background: #dc3232; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px;\">&times;</button>
                            <h2 style=\"margin-top: 0;\">${data.name}</h2>
                            <div style=\"line-height: 1.8;\">
                                <p><strong>Email:</strong> ${data.email || 'N/A'}</p>
                                <p><strong>Phone:</strong> ${data.phone || 'N/A'}</p>
                                ${data.bio ? '<p><strong>Bio:</strong> ' + data.bio + '</p>' : ''}
                                <p><strong>Skills:</strong> ${data.skills || 'N/A'}</p>
                                ${data.experience ? '<p><strong>Experience:</strong> ' + data.experience + ' years</p>' : ''}
                                ${data.certifications ? '<p><strong>Certifications:</strong> ' + data.certifications + '</p>' : ''}
                                <p><strong>Rating:</strong> ${'★'.repeat(data.rating || 0)}</p>
                                ${data.hourly_rate ? '<p><strong>Hourly Rate:</strong> ₹' + data.hourly_rate + '</p>' : ''}
                                ${socialHtml}
                                <p><strong>Email Verified:</strong> ${data.email_verified ? 'Yes ✓' : 'No'}</p>
                                <p><strong>Status:</strong> ${data.status === 'publish' ? 'Approved' : 'Pending'}</p>
                            </div>
                        </div>
                    </div>
                `;
                $('body').append(modalHtml);
            }
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
        // Get user ID from post meta
        $user_id = get_post_meta($post->ID, '_waza_user_id', true);
        
        if (!$user_id) {
            return;
        }
        
        $user = get_userdata($user_id);
        
        if (!$user) {
            return;
        }
        
        // Generate password reset link
        $reset_key = get_password_reset_key($user);
        
        if (is_wp_error($reset_key)) {
            return;
        }
        
        $reset_url = network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($user->user_login), 'login');
        $dashboard_url = home_url('/instructor-dashboard/');
        
        // Use email template manager
        $email_template_manager = \WazaBooking\Core\Plugin::get_instance()->get_manager('email_template');
        
        if ($email_template_manager) {
            $email_template_manager->send_email('password_reset', $user->user_email, [
                'user_name' => $post->post_title,
                'user_first_name' => $post->post_title,
                'reset_url' => $reset_url,
                'dashboard_url' => $dashboard_url,
                'username' => $user->user_login,
                'user_email' => $user->user_email,
                'message' => __('Congratulations! Your instructor application has been approved!', 'waza-booking')
            ]);
        }
    }
    
    /**
     * Sync single instructor to WordPress user
     */
    public function sync_single_instructor() {
        check_ajax_referer('waza_sync_instructor', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'waza-booking'));
        }
        
        $instructor_id = intval($_POST['instructor_id']);
        $post = get_post($instructor_id);
        
        if (!$post || $post->post_type !== 'waza_instructor') {
            wp_send_json_error(__('Invalid instructor ID', 'waza-booking'));
        }
        
        // Check if already linked
        $existing_user_id = get_post_meta($instructor_id, '_waza_user_id', true);
        if ($existing_user_id && get_userdata($existing_user_id)) {
            wp_send_json_error(__('Instructor already linked to a user', 'waza-booking'));
        }
        
        // Get instructor email
        $email = get_post_meta($instructor_id, '_waza_email', true);
        
        if (!$email || !is_email($email)) {
            wp_send_json_error(__('No valid email found for this instructor', 'waza-booking'));
        }
        
        // Check if user already exists with this email
        $user = get_user_by('email', $email);
        
        if ($user) {
            // Link to existing user
            update_post_meta($instructor_id, '_waza_user_id', $user->ID);
            wp_send_json_success([
                'message' => sprintf(__('Linked to existing user: %s', 'waza-booking'), $user->user_login)
            ]);
            return;
        }
        
        // Create new WordPress user
        $username = sanitize_user(strtolower(str_replace(' ', '_', $post->post_title)), true);
        
        // Ensure unique username
        $base_username = $username;
        $counter = 1;
        while (username_exists($username)) {
            $username = $base_username . '_' . $counter;
            $counter++;
        }
        
        // Generate random password (user will set via reset link)
        $password = wp_generate_password(20, true, true);
        
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error($user_id->get_error_message());
        }
        
        // Assign waza_instructor role (instead of default subscriber)
        $user = new \WP_User($user_id);
        $user->set_role('waza_instructor');
        
        // Link instructor to user
        update_post_meta($instructor_id, '_waza_user_id', $user_id);
        
        // Copy meta fields for new system
        $experience = get_post_meta($instructor_id, '_waza_experience', true);
        if ($experience && !get_post_meta($instructor_id, '_waza_experience_years', true)) {
            update_post_meta($instructor_id, '_waza_experience_years', $experience);
        }
        
        // Get activity type from assigned activities
        global $wpdb;
        $activity = $wpdb->get_row($wpdb->prepare("
            SELECT p.post_title
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'waza_activity'
            AND pm.meta_key = '_waza_instructor'
            AND pm.meta_value = %d
            LIMIT 1
        ", $instructor_id));
        
        if ($activity && !get_post_meta($instructor_id, '_waza_activity_type', true)) {
            update_post_meta($instructor_id, '_waza_activity_type', $activity->post_title);
        }
        
        // Send welcome email with password reset link
        $reset_key = get_password_reset_key(get_userdata($user_id));
        
        if (!is_wp_error($reset_key)) {
            $reset_url = network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($username), 'login');
            $dashboard_url = home_url('/instructor-dashboard/');
            
            // Use email template manager
            $email_template_manager = \WazaBooking\Core\Plugin::get_instance()->get_manager('email_template');
            
            if ($email_template_manager) {
                $email_template_manager->send_email('welcome_email', $email, [
                    'user_name' => $post->post_title,
                    'user_first_name' => $post->post_title,
                    'reset_url' => $reset_url,
                    'dashboard_url' => $dashboard_url,
                    'username' => $username,
                    'user_email' => $email
                ]);
            }
        }
        
        wp_send_json_success([
            'message' => sprintf(__('User created and linked: %s. Welcome email sent to %s', 'waza-booking'), $username, $email)
        ]);
    }
    
    /**
     * AJAX handler to get instructor details
     */
    public function ajax_get_instructor_details() {
        check_ajax_referer('waza_instructor_details', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'waza-booking'));
        }
        
        $instructor_id = intval($_POST['instructor_id']);
        $post = get_post($instructor_id);
        
        if (!$post || $post->post_type !== 'waza_instructor') {
            wp_send_json_error(__('Invalid instructor ID', 'waza-booking'));
        }
        
        $skills_terms = wp_get_object_terms($instructor_id, 'waza_instructor_specialty');
        $skills = !empty($skills_terms) ? implode(', ', wp_list_pluck($skills_terms, 'name')) : '';
        
        $data = [
            'name' => get_the_title($instructor_id),
            'email' => get_post_meta($instructor_id, '_waza_email', true),
            'phone' => get_post_meta($instructor_id, '_waza_phone', true),
            'bio' => get_post_meta($instructor_id, '_waza_bio', true),
            'experience' => get_post_meta($instructor_id, '_waza_experience', true),
            'certifications' => get_post_meta($instructor_id, '_waza_certifications', true),
            'rating' => get_post_meta($instructor_id, '_waza_rating', true),
            'hourly_rate' => get_post_meta($instructor_id, '_waza_hourly_rate', true),
            'social_links' => get_post_meta($instructor_id, '_waza_social_links', true),
            'email_verified' => get_post_meta($instructor_id, '_waza_email_verified', true) === '1',
            'status' => $post->post_status,
            'skills' => $skills
        ];
        
        wp_send_json_success($data);
    }
}
