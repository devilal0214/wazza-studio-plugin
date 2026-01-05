<?php
/**
 * Announcements Manager
 * 
 * Handles studio announcements and notifications
 * 
 * @package WazaBooking\Admin
 */

namespace WazaBooking\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Announcements Manager Class
 */
class AnnouncementsManager {
    
    /**
     * Initialize announcements functionality
     */
    public function init() {
        add_action('admin_menu', [$this, 'add_admin_menu'], 25);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_ajax_waza_create_announcement', [$this, 'ajax_create_announcement']);
        add_action('wp_ajax_waza_save_announcement', [$this, 'ajax_save_announcement']);
        add_action('wp_ajax_waza_get_announcement', [$this, 'ajax_get_announcement']);
        add_action('wp_ajax_waza_get_announcements', [$this, 'ajax_get_announcements']);
        add_action('wp_ajax_waza_update_announcement', [$this, 'ajax_update_announcement']);
        add_action('wp_ajax_waza_delete_announcement', [$this, 'ajax_delete_announcement']);
        add_action('wp_ajax_nopriv_waza_get_active_announcements', [$this, 'ajax_get_active_announcements']);
        add_action('wp_ajax_waza_get_active_announcements', [$this, 'ajax_get_active_announcements']);
        add_shortcode('waza_announcements', [$this, 'announcements_shortcode']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'waza-booking',
            __('Announcements', 'waza-booking'),
            __('Announcements', 'waza-booking'),
            'manage_waza',
            'waza-announcements',
            [$this, 'admin_page']
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ('waza_page_waza-announcements' !== $hook) {
            return;
        }
        
        wp_localize_script('jquery', 'wazaAnnouncements', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('waza_admin_nonce')
        ]);
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        global $wpdb;
        
        $announcements = $wpdb->get_results("
            SELECT a.*, u.display_name as author_name
            FROM {$wpdb->prefix}waza_announcements a
            LEFT JOIN {$wpdb->users} u ON a.created_by = u.ID
            ORDER BY a.priority DESC, a.created_at DESC
        ");
        ?>
        <div class=\"wrap\">
            <h1><?php esc_html_e('Studio Announcements', 'waza-booking'); ?></h1>
            <p><?php esc_html_e('Create and manage announcements for your studio members.', 'waza-booking'); ?></p>
            
            <button type=\"button\" class=\"button button-primary\" id=\"waza-add-announcement-btn\">
                <?php esc_html_e('Add New Announcement', 'waza-booking'); ?>
            </button>
            
            <table class=\"wp-list-table widefat fixed striped\" style=\"margin-top: 20px;\">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Title', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Type', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Target', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Priority', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Status', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Starts', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Expires', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Author', 'waza-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($announcements)): ?>
                        <tr>
                            <td colspan=\"8\"><?php esc_html_e('No announcements found.', 'waza-booking'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($announcements as $announcement): ?>
                            <tr>
                                <td><strong><?php echo esc_html($announcement->title); ?></strong></td>
                                <td><?php echo esc_html(ucfirst($announcement->announcement_type)); ?></td>
                                <td><?php echo esc_html(ucfirst($announcement->target_audience)); ?></td>
                                <td><?php echo esc_html($announcement->priority); ?></td>
                                <td>
                                    <span style=\"color: <?php echo $announcement->is_active ? 'green' : 'red'; ?>\">
                                        <?php echo $announcement->is_active ? __('Active', 'waza-booking') : __('Inactive', 'waza-booking'); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($announcement->starts_at ?? 'Immediately'); ?></td>
                                <td><?php echo esc_html($announcement->expires_at ?? 'Never'); ?></td>
                                <td><?php echo esc_html($announcement->author_name ?? 'Unknown'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <br>
            <p class=\"description\">
                <strong><?php esc_html_e('Display on Frontend:', 'waza-booking'); ?></strong>
                <?php esc_html_e('Use shortcode [waza_announcements] to display active announcements.', 'waza-booking'); ?>
            </p>
        </div>
        
        <!-- Announcement Modal -->
        <div id=\"waza-announcement-modal\" style=\"display:none;\">
            <div style=\"max-width: 600px;\">
                <h2 id=\"waza-announcement-modal-title\"><?php esc_html_e('Add New Announcement', 'waza-booking'); ?></h2>
                <form id=\"waza-announcement-form\">
                    <input type=\"hidden\" id=\"announcement-id\" name=\"announcement_id\" value=\"\" />
                    
                    <table class=\"form-table\">
                        <tr>
                            <th><label for=\"announcement-title\"><?php esc_html_e('Title', 'waza-booking'); ?> *</label></th>
                            <td><input type=\"text\" id=\"announcement-title\" name=\"title\" class=\"regular-text\" required /></td>
                        </tr>
                        <tr>
                            <th><label for=\"announcement-message\"><?php esc_html_e('Message', 'waza-booking'); ?> *</label></th>
                            <td><textarea id=\"announcement-message\" name=\"message\" rows=\"5\" class=\"large-text\" required></textarea></td>
                        </tr>
                        <tr>
                            <th><label for=\"announcement-type\"><?php esc_html_e('Type', 'waza-booking'); ?></label></th>
                            <td>
                                <select id=\"announcement-type\" name=\"type\">
                                    <option value=\"general\"><?php esc_html_e('General', 'waza-booking'); ?></option>
                                    <option value=\"event\"><?php esc_html_e('Event', 'waza-booking'); ?></option>
                                    <option value=\"maintenance\"><?php esc_html_e('Maintenance', 'waza-booking'); ?></option>
                                    <option value=\"urgent\"><?php esc_html_e('Urgent', 'waza-booking'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for=\"announcement-target\"><?php esc_html_e('Target Audience', 'waza-booking'); ?></label></th>
                            <td>
                                <select id=\"announcement-target\" name=\"target\">
                                    <option value=\"all\"><?php esc_html_e('All Users', 'waza-booking'); ?></option>
                                    <option value=\"students\"><?php esc_html_e('Students Only', 'waza-booking'); ?></option>
                                    <option value=\"instructors\"><?php esc_html_e('Instructors Only', 'waza-booking'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for=\"announcement-priority\"><?php esc_html_e('Priority', 'waza-booking'); ?></label></th>
                            <td><input type=\"number\" id=\"announcement-priority\" name=\"priority\" value=\"0\" min=\"0\" max=\"10\" /></td>
                        </tr>
                        <tr>
                            <th><label for=\"announcement-starts\"><?php esc_html_e('Starts At', 'waza-booking'); ?></label></th>
                            <td><input type=\"datetime-local\" id=\"announcement-starts\" name=\"starts_at\" /></td>
                        </tr>
                        <tr>
                            <th><label for=\"announcement-expires\"><?php esc_html_e('Expires At', 'waza-booking'); ?></label></th>
                            <td><input type=\"datetime-local\" id=\"announcement-expires\" name=\"expires_at\" /></td>
                        </tr>
                    </table>
                    
                    <p class=\"submit\">
                        <button type=\"submit\" class=\"button button-primary\"><?php esc_html_e('Save Announcement', 'waza-booking'); ?></button>
                        <button type=\"button\" class=\"button\" onclick=\"tb_remove();\"><?php esc_html_e('Cancel', 'waza-booking'); ?></button>
                    </p>
                </form>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Add New Announcement Button
            $('#waza-add-announcement-btn').on('click', function(e) {
                e.preventDefault();
                $('#announcement-id').val('');
                $('#waza-announcement-form')[0].reset();
                $('#waza-announcement-modal-title').text('<?php esc_html_e('Add New Announcement', 'waza-booking'); ?>');
                tb_show('<?php esc_html_e('Add New Announcement', 'waza-booking'); ?>', '#TB_inline?width=600&height=550&inlineId=waza-announcement-modal');
            });
            
            // Form Submit
            $('#waza-announcement-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = {
                    action: 'waza_save_announcement',
                    nonce: '<?php echo wp_create_nonce('waza_admin_nonce'); ?>',
                    announcement_id: $('#announcement-id').val(),
                    title: $('#announcement-title').val(),
                    message: $('#announcement-message').val(),
                    type: $('#announcement-type').val(),
                    target: $('#announcement-target').val(),
                    priority: $('#announcement-priority').val(),
                    starts_at: $('#announcement-starts').val(),
                    expires_at: $('#announcement-expires').val()
                };
                
                $.post(ajaxurl, formData, function(response) {
                    if (response.success) {
                        alert(response.data.message || '<?php esc_html_e('Announcement saved successfully!', 'waza-booking'); ?>');
                        tb_remove();
                        location.reload();
                    } else {
                        alert(response.data || '<?php esc_html_e('Failed to save announcement', 'waza-booking'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Create announcement
     */
    public function ajax_create_announcement() {
        check_ajax_referer('waza_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_waza')) {
            wp_send_json_error(__('Permission denied', 'waza-booking'));
        }
        
        $title = sanitize_text_field($_POST['title'] ?? '');
        $message = wp_kses_post($_POST['message'] ?? '');
        $type = sanitize_text_field($_POST['type'] ?? 'general');
        $target = sanitize_text_field($_POST['target'] ?? 'all');
        $priority = intval($_POST['priority'] ?? 0);
        $starts_at = isset($_POST['starts_at']) ? sanitize_text_field($_POST['starts_at']) : null;
        $expires_at = isset($_POST['expires_at']) ? sanitize_text_field($_POST['expires_at']) : null;
        
        if (!$title || !$message) {
            wp_send_json_error(__('Title and message are required', 'waza-booking'));
        }
        
        global $wpdb;
        
        $result = $wpdb->insert($wpdb->prefix . 'waza_announcements', [
            'title' => $title,
            'message' => $message,
            'announcement_type' => $type,
            'target_audience' => $target,
            'priority' => $priority,
            'is_active' => 1,
            'starts_at' => $starts_at,
            'expires_at' => $expires_at,
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        ]);
        
        if ($result) {
            // Send announcement notifications
            do_action('waza_announcement_created', $wpdb->insert_id);
            
            wp_send_json_success([
                'message' => __('Announcement created successfully', 'waza-booking'),
                'announcement_id' => $wpdb->insert_id
            ]);
        } else {
            wp_send_json_error(__('Failed to create announcement', 'waza-booking'));
        }
    }
    
    /**
     * Get announcements (admin)
     */
    public function ajax_get_announcements() {
        check_ajax_referer('waza_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_waza')) {
            wp_send_json_error(__('Permission denied', 'waza-booking'));
        }
        
        global $wpdb;
        
        $announcements = $wpdb->get_results("
            SELECT a.*, u.display_name as author_name
            FROM {$wpdb->prefix}waza_announcements a
            LEFT JOIN {$wpdb->users} u ON a.created_by = u.ID
            ORDER BY a.priority DESC, a.created_at DESC
        ");
        
        wp_send_json_success(['announcements' => $announcements]);
    }
    
    /**
     * Get active announcements (public)
     */
    public function ajax_get_active_announcements() {
        check_ajax_referer('waza_frontend_nonce', 'nonce');
        
        $target = 'all';
        
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            if (user_can($user, 'edit_waza_slots')) {
                $target = 'instructors';
            } else {
                $target = 'students';
            }
        }
        
        $announcements = $this->get_active_announcements($target);
        
        wp_send_json_success(['announcements' => $announcements]);
    }
    
    /**
     * Save announcement (create or update)
     */
    public function ajax_save_announcement() {
        check_ajax_referer('waza_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_waza')) {
            wp_send_json_error(__('Permission denied', 'waza-booking'));
        }
        
        global $wpdb;
        
        $id = intval($_POST['announcement_id'] ?? 0);
        $title = sanitize_text_field($_POST['title'] ?? '');
        $message = wp_kses_post($_POST['message'] ?? '');
        $type = sanitize_text_field($_POST['type'] ?? 'general');
        $target = sanitize_text_field($_POST['target'] ?? 'all');
        $priority = intval($_POST['priority'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $starts_at = !empty($_POST['starts_at']) ? sanitize_text_field($_POST['starts_at']) : null;
        $expires_at = !empty($_POST['expires_at']) ? sanitize_text_field($_POST['expires_at']) : null;
        
        if (!$title || !$message) {
            wp_send_json_error(__('Title and message are required', 'waza-booking'));
        }
        
        $data = [
            'title' => $title,
            'message' => $message,
            'announcement_type' => $type,
            'target_audience' => $target,
            'priority' => $priority,
            'is_active' => $is_active,
            'starts_at' => $starts_at,
            'expires_at' => $expires_at
        ];
        
        if ($id > 0) {
            // Update existing
            $result = $wpdb->update(
                $wpdb->prefix . 'waza_announcements',
                $data,
                ['id' => $id]
            );
            
            if ($result !== false) {
                wp_send_json_success(['message' => __('Announcement updated successfully', 'waza-booking')]);
            } else {
                wp_send_json_error(__('Failed to update announcement', 'waza-booking'));
            }
        } else {
            // Create new
            $data['created_by'] = get_current_user_id();
            $data['created_at'] = current_time('mysql');
            
            $result = $wpdb->insert($wpdb->prefix . 'waza_announcements', $data);
            
            if ($result) {
                wp_send_json_success([
                    'message' => __('Announcement created successfully', 'waza-booking'),
                    'announcement_id' => $wpdb->insert_id
                ]);
            } else {
                wp_send_json_error(__('Failed to create announcement', 'waza-booking'));
            }
        }
    }
    
    /**
     * Get single announcement
     */
    public function ajax_get_announcement() {
        check_ajax_referer('waza_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_waza')) {
            wp_send_json_error(__('Permission denied', 'waza-booking'));
        }
        
        $id = intval($_POST['id'] ?? 0);
        
        if (!$id) {
            wp_send_json_error(__('Invalid announcement ID', 'waza-booking'));
        }
        
        global $wpdb;
        
        $announcement = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}waza_announcements WHERE id = %d",
            $id
        ));
        
        if ($announcement) {
            wp_send_json_success($announcement);
        } else {
            wp_send_json_error(__('Announcement not found', 'waza-booking'));
        }
    }
    
    /**
     * Update announcement
     */
    public function ajax_update_announcement() {
        check_ajax_referer('waza_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_waza')) {
            wp_send_json_error(__('Permission denied', 'waza-booking'));
        }
        
        $id = intval($_POST['id'] ?? 0);
        $title = sanitize_text_field($_POST['title'] ?? '');
        $message = wp_kses_post($_POST['message'] ?? '');
        $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;
        
        if (!$id || !$title || !$message) {
            wp_send_json_error(__('Missing required fields', 'waza-booking'));
        }
        
        global $wpdb;
        
        $result = $wpdb->update(
            $wpdb->prefix . 'waza_announcements',
            [
                'title' => $title,
                'message' => $message,
                'is_active' => $is_active
            ],
            ['id' => $id]
        );
        
        if ($result !== false) {
            wp_send_json_success(['message' => __('Announcement updated successfully', 'waza-booking')]);
        } else {
            wp_send_json_error(__('Failed to update announcement', 'waza-booking'));
        }
    }
    
    /**
     * Delete announcement
     */
    public function ajax_delete_announcement() {
        check_ajax_referer('waza_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_waza')) {
            wp_send_json_error(__('Permission denied', 'waza-booking'));
        }
        
        $id = intval($_POST['id'] ?? 0);
        
        if (!$id) {
            wp_send_json_error(__('Invalid announcement ID', 'waza-booking'));
        }
        
        global $wpdb;
        
        $result = $wpdb->delete($wpdb->prefix . 'waza_announcements', ['id' => $id]);
        
        if ($result) {
            wp_send_json_success(['message' => __('Announcement deleted successfully', 'waza-booking')]);
        } else {
            wp_send_json_error(__('Failed to delete announcement', 'waza-booking'));
        }
    }
    
    /**
     * Get active announcements
     */
    private function get_active_announcements($target = 'all') {
        global $wpdb;
        
        $now = current_time('mysql');
        
        $announcements = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}waza_announcements
            WHERE is_active = 1
                AND (target_audience = %s OR target_audience = 'all')
                AND (starts_at IS NULL OR starts_at <= %s)
                AND (expires_at IS NULL OR expires_at >= %s)
            ORDER BY priority DESC, created_at DESC
        ", $target, $now, $now));
        
        return $announcements;
    }
    
    /**
     * Announcements shortcode
     */
    public function announcements_shortcode($atts) {
        $atts = shortcode_atts([
            'target' => 'all',
            'type' => '',
            'limit' => 5
        ], $atts);
        
        global $wpdb;
        
        $where = ['is_active = 1'];
        $params = [];
        
        if ($atts['type']) {
            $where[] = 'announcement_type = %s';
            $params[] = $atts['type'];
        }
        
        $now = current_time('mysql');
        $where[] = '(starts_at IS NULL OR starts_at <= %s)';
        $where[] = '(expires_at IS NULL OR expires_at >= %s)';
        $params[] = $now;
        $params[] = $now;
        
        $where_clause = implode(' AND ', $where);
        
        $query = "
            SELECT * FROM {$wpdb->prefix}waza_announcements
            WHERE {$where_clause}
            ORDER BY priority DESC, created_at DESC
            LIMIT %d
        ";
        
        $params[] = intval($atts['limit']);
        
        $announcements = $wpdb->get_results($wpdb->prepare($query, $params));
        
        if (empty($announcements)) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="waza-announcements">
            <?php foreach ($announcements as $announcement): ?>
                <div class="waza-announcement waza-announcement-<?php echo esc_attr($announcement->announcement_type); ?>" data-priority="<?php echo esc_attr($announcement->priority); ?>">
                    <div class="waza-announcement-header">
                        <h3 class="waza-announcement-title"><?php echo esc_html($announcement->title); ?></h3>
                        <span class="waza-announcement-date"><?php echo wp_date('F j, Y', strtotime($announcement->created_at)); ?></span>
                    </div>
                    <div class="waza-announcement-message">
                        <?php echo wp_kses_post($announcement->message); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <style>
        .waza-announcements {
            margin: 20px 0;
        }
        .waza-announcement {
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
            background: #f9fafb;
        }
        .waza-announcement-urgent {
            border-left-color: #ef4444;
            background: #fef2f2;
        }
        .waza-announcement-important {
            border-left-color: #f59e0b;
            background: #fffbeb;
        }
        .waza-announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .waza-announcement-title {
            margin: 0;
            font-size: 18px;
            color: #111827;
        }
        .waza-announcement-date {
            font-size: 12px;
            color: #6b7280;
        }
        .waza-announcement-message {
            color: #374151;
            line-height: 1.6;
        }
        </style>
        <?php
        return ob_get_clean();
    }
}
