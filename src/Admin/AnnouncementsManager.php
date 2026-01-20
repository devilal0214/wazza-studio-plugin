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
        add_action('wp_ajax_waza_save_announcement', [$this, 'ajax_save_announcement']);
        add_action('wp_ajax_waza_get_announcement', [$this, 'ajax_get_announcement']);
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
        
        // Get stats
        $total = count($announcements);
        $active = count(array_filter($announcements, function($a) { return $a->is_active; }));
        $inactive = $total - $active;
        $urgent = count(array_filter($announcements, function($a) { return $a->announcement_type === 'urgent'; }));
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('Studio Announcements', 'waza-booking'); ?></h1>
            <a href="#" class="page-title-action" id="add-new-announcement"><?php esc_html_e('Add New', 'waza-booking'); ?></a>
            <hr class="wp-header-end">
            
            <!-- Stats Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <div style="font-size: 32px; font-weight: bold; margin-bottom: 5px;"><?php echo $total; ?></div>
                    <div style="font-size: 14px; opacity: 0.9;">Total Announcements</div>
                </div>
                <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <div style="font-size: 32px; font-weight: bold; margin-bottom: 5px;"><?php echo $active; ?></div>
                    <div style="font-size: 14px; opacity: 0.9;">Active</div>
                </div>
                <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <div style="font-size: 32px; font-weight: bold; margin-bottom: 5px;"><?php echo $inactive; ?></div>
                    <div style="font-size: 14px; opacity: 0.9;">Inactive</div>
                </div>
                <div style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <div style="font-size: 32px; font-weight: bold; margin-bottom: 5px;"><?php echo $urgent; ?></div>
                    <div style="font-size: 14px; opacity: 0.9;">Urgent</div>
                </div>
            </div>
            
            <!-- Announcement Form (Initially Hidden) -->
            <div id="announcement-form-container" style="display: none; background: white; padding: 20px; border: 1px solid #ddd; border-radius: 8px; margin: 20px 0;">
                <h2 id="form-title"><?php esc_html_e('Add New Announcement', 'waza-booking'); ?></h2>
                <form id="announcement-form" style="max-width: 800px;">
                    <input type="hidden" id="announcement-id" name="announcement_id" value="">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="title"><?php esc_html_e('Title', 'waza-booking'); ?> <span style="color: red;">*</span></label></th>
                            <td><input type="text" id="title" name="title" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="message"><?php esc_html_e('Message', 'waza-booking'); ?> <span style="color: red;">*</span></label></th>
                            <td>
                                <textarea id="message" name="message" class="large-text" rows="5" required></textarea>
                                <p class="description"><?php esc_html_e('This message will be displayed to your target audience.', 'waza-booking'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="type"><?php esc_html_e('Type', 'waza-booking'); ?></label></th>
                            <td>
                                <select id="type" name="type">
                                    <option value="general"><?php esc_html_e('General', 'waza-booking'); ?></option>
                                    <option value="event"><?php esc_html_e('Event', 'waza-booking'); ?></option>
                                    <option value="maintenance"><?php esc_html_e('Maintenance', 'waza-booking'); ?></option>
                                    <option value="urgent"><?php esc_html_e('Urgent', 'waza-booking'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="target"><?php esc_html_e('Target Audience', 'waza-booking'); ?></label></th>
                            <td>
                                <select id="target" name="target">
                                    <option value="all"><?php esc_html_e('All Users', 'waza-booking'); ?></option>
                                    <option value="students"><?php esc_html_e('Students Only', 'waza-booking'); ?></option>
                                    <option value="instructors"><?php esc_html_e('Instructors Only', 'waza-booking'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="priority"><?php esc_html_e('Priority', 'waza-booking'); ?></label></th>
                            <td>
                                <input type="number" id="priority" name="priority" value="0" min="0" max="10" style="width: 100px;">
                                <p class="description"><?php esc_html_e('Higher priority announcements appear first (0-10).', 'waza-booking'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="starts_at"><?php esc_html_e('Starts At', 'waza-booking'); ?></label></th>
                            <td>
                                <input type="datetime-local" id="starts_at" name="starts_at">
                                <p class="description"><?php esc_html_e('Optional: When should this announcement become visible?', 'waza-booking'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="expires_at"><?php esc_html_e('Expires At', 'waza-booking'); ?></label></th>
                            <td>
                                <input type="datetime-local" id="expires_at" name="expires_at">
                                <p class="description"><?php esc_html_e('Optional: When should this announcement stop being visible?', 'waza-booking'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary button-large"><?php esc_html_e('Save Announcement', 'waza-booking'); ?></button>
                        <button type="button" class="button button-secondary button-large" id="cancel-form"><?php esc_html_e('Cancel', 'waza-booking'); ?></button>
                    </p>
                </form>
            </div>
            
            <!-- Announcements Table -->
            <div style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 8px; margin: 20px 0;">
                <?php if (empty($announcements)): ?>
                    <div style="text-align: center; padding: 40px;">
                        <div style="font-size: 64px; margin-bottom: 20px;">📢</div>
                        <h2><?php esc_html_e('No Announcements Yet', 'waza-booking'); ?></h2>
                        <p style="color: #666;"><?php esc_html_e('Create your first announcement to communicate with your studio members.', 'waza-booking'); ?></p>
                    </div>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th width="5%"><?php esc_html_e('Status', 'waza-booking'); ?></th>
                                <th width="25%"><?php esc_html_e('Title', 'waza-booking'); ?></th>
                                <th width="30%"><?php esc_html_e('Message', 'waza-booking'); ?></th>
                                <th width="10%"><?php esc_html_e('Type', 'waza-booking'); ?></th>
                                <th width="10%"><?php esc_html_e('Target', 'waza-booking'); ?></th>
                                <th width="10%"><?php esc_html_e('Author', 'waza-booking'); ?></th>
                                <th width="10%"><?php esc_html_e('Actions', 'waza-booking'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($announcements as $announcement): ?>
                                <tr>
                                    <td>
                                        <label class="switch" style="position: relative; display: inline-block; width: 50px; height: 24px;">
                                            <input type="checkbox" class="toggle-status" data-id="<?php echo $announcement->id; ?>" <?php checked($announcement->is_active, 1); ?>>
                                            <span style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 24px;"></span>
                                        </label>
                                    </td>
                                    <td><strong><?php echo esc_html($announcement->title); ?></strong></td>
                                    <td><?php echo esc_html(wp_trim_words($announcement->message, 15)); ?></td>
                                    <td>
                                        <?php
                                        $type_colors = [
                                            'general' => '#3b82f6',
                                            'event' => '#8b5cf6',
                                            'maintenance' => '#f59e0b',
                                            'urgent' => '#ef4444'
                                        ];
                                        $color = $type_colors[$announcement->announcement_type] ?? '#6b7280';
                                        ?>
                                        <span style="background: <?php echo $color; ?>; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500;">
                                            <?php echo esc_html(ucfirst($announcement->announcement_type)); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html(ucfirst($announcement->target_audience)); ?></td>
                                    <td><?php echo esc_html($announcement->author_name ?? 'Unknown'); ?></td>
                                    <td>
                                        <button class="button button-small edit-announcement" data-id="<?php echo $announcement->id; ?>"><?php esc_html_e('Edit', 'waza-booking'); ?></button>
                                        <button class="button button-small delete-announcement" data-id="<?php echo $announcement->id; ?>" style="color: #dc2626;"><?php esc_html_e('Delete', 'waza-booking'); ?></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div style="background: #f9fafb; padding: 15px; border-left: 4px solid #6366f1; border-radius: 4px;">
                <strong><?php esc_html_e('Display on Frontend:', 'waza-booking'); ?></strong>
                <code style="background: white; padding: 5px 10px; border-radius: 4px; margin-left: 10px;">[waza_announcements]</code>
                <p style="margin: 10px 0 0; color: #6b7280; font-size: 13px;">
                    <?php esc_html_e('Use this shortcode to display active announcements on any page.', 'waza-booking'); ?>
                </p>
            </div>
        </div>
        
        <style>
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .switch input:checked + span {
            background-color: #10b981;
        }
        .switch input:checked + span:before {
            transform: translateX(26px);
        }
        .switch span:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
            var nonce = '<?php echo wp_create_nonce('waza_admin_nonce'); ?>';
            
            // Show form for new announcement
            $('#add-new-announcement').on('click', function(e) {
                e.preventDefault();
                $('#announcement-form')[0].reset();
                $('#announcement-id').val('');
                $('#form-title').text('<?php esc_html_e('Add New Announcement', 'waza-booking'); ?>');
                $('#announcement-form-container').slideDown();
                $('html, body').animate({ scrollTop: $('#announcement-form-container').offset().top - 50 }, 500);
            });
            
            // Cancel form
            $('#cancel-form').on('click', function(e) {
                e.preventDefault();
                $('#announcement-form-container').slideUp();
                $('#announcement-form')[0].reset();
            });
            
            // Edit announcement
            $('.edit-announcement').on('click', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'waza_get_announcement',
                        nonce: nonce,
                        id: id
                    },
                    success: function(response) {
                        if (response.success) {
                            var data = response.data;
                            $('#announcement-id').val(data.id);
                            $('#title').val(data.title);
                            $('#message').val(data.message);
                            $('#type').val(data.announcement_type);
                            $('#target').val(data.target_audience);
                            $('#priority').val(data.priority);
                            
                            if (data.starts_at && data.starts_at !== '0000-00-00 00:00:00') {
                                $('#starts_at').val(data.starts_at.replace(' ', 'T').substring(0, 16));
                            }
                            if (data.expires_at && data.expires_at !== '0000-00-00 00:00:00') {
                                $('#expires_at').val(data.expires_at.replace(' ', 'T').substring(0, 16));
                            }
                            
                            $('#form-title').text('<?php esc_html_e('Edit Announcement', 'waza-booking'); ?>');
                            $('#announcement-form-container').slideDown();
                            $('html, body').animate({ scrollTop: $('#announcement-form-container').offset().top - 50 }, 500);
                        } else {
                            alert(response.data || '<?php esc_html_e('Failed to load announcement', 'waza-booking'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php esc_html_e('An error occurred. Please try again.', 'waza-booking'); ?>');
                    }
                });
            });
            
            // Delete announcement
            $('.delete-announcement').on('click', function(e) {
                e.preventDefault();
                if (!confirm('<?php esc_html_e('Are you sure you want to delete this announcement?', 'waza-booking'); ?>')) {
                    return;
                }
                
                var id = $(this).data('id');
                var $btn = $(this);
                $btn.prop('disabled', true).text('<?php esc_html_e('Deleting...', 'waza-booking'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'waza_delete_announcement',
                        nonce: nonce,
                        id: id
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data || '<?php esc_html_e('Failed to delete announcement', 'waza-booking'); ?>');
                            $btn.prop('disabled', false).text('<?php esc_html_e('Delete', 'waza-booking'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php esc_html_e('An error occurred. Please try again.', 'waza-booking'); ?>');
                        $btn.prop('disabled', false).text('<?php esc_html_e('Delete', 'waza-booking'); ?>');
                    }
                });
            });
            
            // Toggle status
            $('.toggle-status').on('change', function() {
                var id = $(this).data('id');
                var isActive = $(this).is(':checked') ? 1 : 0;
                var $checkbox = $(this);
                
                // Get full announcement data first
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'waza_get_announcement',
                        nonce: nonce,
                        id: id
                    },
                    success: function(response) {
                        if (response.success) {
                            var data = response.data;
                            
                            // Now save with all required fields
                            $.ajax({
                                url: ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'waza_save_announcement',
                                    nonce: nonce,
                                    announcement_id: id,
                                    title: data.title,
                                    message: data.message,
                                    type: data.announcement_type,
                                    target: data.target_audience,
                                    priority: data.priority,
                                    is_active: isActive,
                                    starts_at: data.starts_at || '',
                                    expires_at: data.expires_at || ''
                                },
                                success: function(updateResponse) {
                                    if (!updateResponse.success) {
                                        alert(updateResponse.data || '<?php esc_html_e('Failed to update status', 'waza-booking'); ?>');
                                        $checkbox.prop('checked', !isActive);
                                    }
                                },
                                error: function() {
                                    alert('<?php esc_html_e('An error occurred. Please try again.', 'waza-booking'); ?>');
                                    $checkbox.prop('checked', !isActive);
                                }
                            });
                        } else {
                            alert(response.data || '<?php esc_html_e('Failed to load announcement', 'waza-booking'); ?>');
                            $checkbox.prop('checked', !isActive);
                        }
                    },
                    error: function() {
                        alert('<?php esc_html_e('An error occurred. Please try again.', 'waza-booking'); ?>');
                        $checkbox.prop('checked', !isActive);
                    }
                });
            });
            
            // Submit form
            $('#announcement-form').on('submit', function(e) {
                e.preventDefault();
                
                var $submitBtn = $(this).find('button[type="submit"]');
                var originalText = $submitBtn.text();
                $submitBtn.prop('disabled', true).text('<?php esc_html_e('Saving...', 'waza-booking'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'waza_save_announcement',
                        nonce: nonce,
                        announcement_id: $('#announcement-id').val(),
                        title: $('#title').val(),
                        message: $('#message').val(),
                        type: $('#type').val(),
                        target: $('#target').val(),
                        priority: $('#priority').val(),
                        is_active: 1,
                        starts_at: $('#starts_at').val(),
                        expires_at: $('#expires_at').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data || '<?php esc_html_e('Failed to save announcement', 'waza-booking'); ?>');
                            $submitBtn.prop('disabled', false).text(originalText);
                        }
                    },
                    error: function() {
                        alert('<?php esc_html_e('An error occurred. Please try again.', 'waza-booking'); ?>');
                        $submitBtn.prop('disabled', false).text(originalText);
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX: Save announcement (create or update)
     */
    public function ajax_save_announcement() {
        check_ajax_referer('waza_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_waza')) {
            wp_send_json_error(__('Permission denied', 'waza-booking'));
        }
        
        global $wpdb;
        
        $announcement_id = isset($_POST['announcement_id']) ? intval($_POST['announcement_id']) : 0;
        $title = sanitize_text_field($_POST['title'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $type = sanitize_text_field($_POST['type'] ?? 'general');
        $target = sanitize_text_field($_POST['target'] ?? 'all');
        $priority = intval($_POST['priority'] ?? 0);
        $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;
        $starts_at = sanitize_text_field($_POST['starts_at'] ?? '');
        $expires_at = sanitize_text_field($_POST['expires_at'] ?? '');
        
        if (empty($title) || empty($message)) {
            wp_send_json_error(__('Title and message are required', 'waza-booking'));
        }
        
        // Convert datetime-local format to MySQL datetime
        if (!empty($starts_at)) {
            $starts_at = str_replace('T', ' ', $starts_at) . ':00';
        } else {
            $starts_at = null;
        }
        
        if (!empty($expires_at)) {
            $expires_at = str_replace('T', ' ', $expires_at) . ':00';
        } else {
            $expires_at = null;
        }
        
        $data = [
            'title' => $title,
            'message' => $message,
            'announcement_type' => $type,
            'target_audience' => $target,
            'priority' => $priority,
            'is_active' => $is_active,
            'starts_at' => $starts_at,
            'expires_at' => $expires_at,
        ];
        
        if ($announcement_id) {
            // Update existing
            $result = $wpdb->update(
                $wpdb->prefix . 'waza_announcements',
                $data,
                ['id' => $announcement_id],
                ['%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s'],
                ['%d']
            );
            
            if ($result !== false) {
                wp_send_json_success([
                    'message' => __('Announcement updated successfully', 'waza-booking'),
                    'id' => $announcement_id
                ]);
            }
        } else {
            // Create new
            $data['created_by'] = get_current_user_id();
            $data['created_at'] = current_time('mysql');
            
            $result = $wpdb->insert(
                $wpdb->prefix . 'waza_announcements',
                $data,
                ['%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%d', '%s']
            );
            
            if ($result) {
                wp_send_json_success([
                    'message' => __('Announcement created successfully', 'waza-booking'),
                    'id' => $wpdb->insert_id
                ]);
            }
        }
        
        wp_send_json_error(__('Failed to save announcement', 'waza-booking'));
    }
    
    /**
     * AJAX: Get announcement
     */
    public function ajax_get_announcement() {
        check_ajax_referer('waza_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_waza')) {
            wp_send_json_error(__('Permission denied', 'waza-booking'));
        }
        
        global $wpdb;
        $id = intval($_POST['id'] ?? 0);
        
        if (!$id) {
            wp_send_json_error(__('Invalid announcement ID', 'waza-booking'));
        }
        
        $announcement = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}waza_announcements WHERE id = %d",
            $id
        ));
        
        if ($announcement) {
            wp_send_json_success($announcement);
        }
        
        wp_send_json_error(__('Announcement not found', 'waza-booking'));
    }
    
    /**
     * AJAX: Delete announcement
     */
    public function ajax_delete_announcement() {
        check_ajax_referer('waza_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_waza')) {
            wp_send_json_error(__('Permission denied', 'waza-booking'));
        }
        
        global $wpdb;
        $id = intval($_POST['id'] ?? 0);
        
        if (!$id) {
            wp_send_json_error(__('Invalid announcement ID', 'waza-booking'));
        }
        
        $result = $wpdb->delete(
            $wpdb->prefix . 'waza_announcements',
            ['id' => $id],
            ['%d']
        );
        
        if ($result) {
            wp_send_json_success(['message' => __('Announcement deleted successfully', 'waza-booking')]);
        }
        
        wp_send_json_error(__('Failed to delete announcement', 'waza-booking'));
    }
    
    /**
     * AJAX: Get active announcements for frontend
     */
    public function ajax_get_active_announcements() {
        global $wpdb;
        
        try {
            $current_time = current_time('mysql');
            
            // Determine user role automatically
            $current_user = wp_get_current_user();
            $user_type = 'students'; // default
            
            if (in_array('waza_instructor', $current_user->roles)) {
                $user_type = 'instructors';
            } elseif (in_array('administrator', $current_user->roles)) {
                $user_type = 'all'; // admins see everything
            }
            
            // Allow override from AJAX parameter
            $target = isset($_POST['target']) ? sanitize_text_field($_POST['target']) : $user_type;
            
            // Build query - Show all active announcements regardless of start date
            // Only filter out expired ones
            $sql = "SELECT * FROM {$wpdb->prefix}waza_announcements WHERE is_active = 1";
            
            // Only filter by expiration date, NOT start date
            $sql .= " AND (expires_at IS NULL OR expires_at = '' OR expires_at = '0000-00-00 00:00:00' OR expires_at >= '{$current_time}')";
            
            // Add target audience filter - show 'all' + specific target
            if ($target !== 'all') {
                $sql .= $wpdb->prepare(" AND (target_audience = %s OR target_audience = 'all')", $target);
            }
            
            $sql .= " ORDER BY priority DESC, created_at DESC";
            
            $announcements = $wpdb->get_results($sql);
            
            // Debug info
            error_log('Announcements Query: ' . $sql);
            error_log('Announcements Count: ' . count($announcements));
            error_log('Target: ' . $target);
            error_log('User Type: ' . $user_type);
            
            // Return both announcements and count
            wp_send_json_success([
                'announcements' => $announcements ? $announcements : [],
                'count' => count($announcements),
                'user_type' => $user_type,
                'target' => $target
            ]);
            
        } catch (Exception $e) {
            error_log('Announcements Error: ' . $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Announcements shortcode for frontend
     */
    public function announcements_shortcode($atts) {
        $atts = shortcode_atts([
            'type' => '',
            'target' => '',
            'limit' => 50
        ], $atts);
        
        global $wpdb;
        $current_time = current_time('mysql');
        
        // Determine user role automatically if target not specified
        $current_user = wp_get_current_user();
        $user_type = 'students'; // default
        
        if (in_array('waza_instructor', $current_user->roles)) {
            $user_type = 'instructors';
        } elseif (in_array('administrator', $current_user->roles)) {
            $user_type = 'all'; // admins see everything
        }
        
        // Use attribute target if provided, otherwise use detected user type
        $target = !empty($atts['target']) ? $atts['target'] : $user_type;
        
        // Build query - show all active announcements, only filter expired
        $sql = "SELECT * FROM {$wpdb->prefix}waza_announcements WHERE is_active = 1";
        $sql .= " AND (expires_at IS NULL OR expires_at = '' OR expires_at = '0000-00-00 00:00:00' OR expires_at >= '{$current_time}')";
        
        if (!empty($atts['type'])) {
            $sql .= $wpdb->prepare(" AND announcement_type = %s", $atts['type']);
        }
        
        // Filter by target audience - show 'all' + specific target
        if ($target !== 'all') {
            $sql .= $wpdb->prepare(" AND (target_audience = %s OR target_audience = 'all')", $target);
        }
        
        $limit = intval($atts['limit']);
        $sql .= " ORDER BY priority DESC, created_at DESC LIMIT $limit";
        
        $announcements = $wpdb->get_results($sql);
        
        ob_start();
        ?>
        <style>
        .waza-announcements-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0;
        }
        .waza-announcements-list {
            display: grid;
            gap: 1.25rem;
            margin-top: 2rem;
        }
        .waza-announcement-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #6366f1;
            transition: all 0.3s ease;
            position: relative;
        }
        .waza-announcement-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        .waza-announcement-card.type-urgent {
            border-left-color: #ef4444;
            background: linear-gradient(to right, #fef2f2, #ffffff);
        }
        .waza-announcement-card.type-event {
            border-left-color: #8b5cf6;
            background: linear-gradient(to right, #faf5ff, #ffffff);
        }
        .waza-announcement-card.type-maintenance {
            border-left-color: #f59e0b;
            background: linear-gradient(to right, #fffbeb, #ffffff);
        }
        .waza-announcement-card.type-general {
            border-left-color: #3b82f6;
            background: linear-gradient(to right, #eff6ff, #ffffff);
        }
        .waza-announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.25rem;
            gap: 1rem;
        }
        .waza-announcement-title-wrapper {
            flex: 1;
        }
        .waza-announcement-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .waza-announcement-card.type-urgent .waza-announcement-icon::before {
            content: '🚨';
        }
        .waza-announcement-card.type-event .waza-announcement-icon::before {
            content: '🎉';
        }
        .waza-announcement-card.type-maintenance .waza-announcement-icon::before {
            content: '🔧';
        }
        .waza-announcement-card.type-general .waza-announcement-icon::before {
            content: '📢';
        }
        .waza-announcement-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            color: #1f2937;
            line-height: 1.3;
        }
        .waza-announcement-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        .waza-announcement-card.type-urgent .waza-announcement-badge {
            background: #ef4444;
            color: white;
        }
        .waza-announcement-card.type-event .waza-announcement-badge {
            background: #8b5cf6;
            color: white;
        }
        .waza-announcement-card.type-maintenance .waza-announcement-badge {
            background: #f59e0b;
            color: white;
        }
        .waza-announcement-card.type-general .waza-announcement-badge {
            background: #3b82f6;
            color: white;
        }
        .waza-announcement-message {
            color: #4b5563;
            line-height: 1.7;
            font-size: 1rem;
            margin-bottom: 1.5rem;
            white-space: pre-wrap;
        }
        .waza-announcement-footer {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
            font-size: 0.875rem;
            color: #6b7280;
        }
        .waza-announcement-date {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .waza-announcement-date::before {
            content: '📅';
            font-size: 1rem;
        }
        .waza-no-announcements {
            text-align: center;
            padding: 5rem 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-top: 2rem;
        }
        .waza-no-announcements-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            opacity: 0.3;
        }
        .waza-no-announcements h3 {
            color: #1f2937;
            font-size: 1.5rem;
            margin: 0 0 0.5rem 0;
        }
        .waza-no-announcements p {
            color: #6b7280;
            font-size: 1rem;
            margin: 0;
        }
        @media (max-width: 768px) {
            .waza-announcement-card {
                padding: 1.5rem;
            }
            .waza-announcement-header {
                flex-direction: column;
            }
            .waza-announcement-title {
                font-size: 1.25rem;
            }
            .waza-announcement-footer {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }
        }
        </style>
        
        <div class="waza-announcements-page">
            <?php if (empty($announcements)): ?>
                <div class="waza-no-announcements">
                    <div class="waza-no-announcements-icon">📢</div>
                    <h3><?php esc_html_e('No Announcements Yet', 'waza-booking'); ?></h3>
                    <p><?php esc_html_e('Check back later for updates and important information.', 'waza-booking'); ?></p>
                </div>
            <?php else: ?>
                <div class="waza-announcements-list">
                    <?php foreach ($announcements as $announcement): ?>
                        <div class="waza-announcement-card type-<?php echo esc_attr($announcement->announcement_type); ?>">
                            <div class="waza-announcement-header">
                                <div class="waza-announcement-title-wrapper">
                                    <div class="waza-announcement-icon"></div>
                                    <h2 class="waza-announcement-title"><?php echo esc_html($announcement->title); ?></h2>
                                </div>
                                <span class="waza-announcement-badge"><?php echo esc_html(ucfirst($announcement->announcement_type)); ?></span>
                            </div>
                            <div class="waza-announcement-message"><?php echo esc_html($announcement->message); ?></div>
                            <div class="waza-announcement-footer">
                                <span class="waza-announcement-date"><?php echo wp_date('F j, Y', strtotime($announcement->created_at)); ?></span>
                                <?php if (!empty($announcement->expires_at) && $announcement->expires_at !== '0000-00-00 00:00:00'): ?>
                                    <span style="color: #f59e0b;">⏰ Expires: <?php echo wp_date('F j, Y', strtotime($announcement->expires_at)); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
