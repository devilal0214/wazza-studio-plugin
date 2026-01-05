<?php
/**
 * Activity Logs Manager
 * 
 * Handles activity logging for audit trail
 * 
 * @package WazaBooking\Admin
 */

namespace WazaBooking\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Activity Logs Manager Class
 */
class ActivityLogsManager {
    
    /**
     * Initialize activity logs
     */
    public function init() {
        add_action('admin_menu', [$this, 'add_admin_menu'], 27);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        
        // Hook into various actions for logging
        add_action('waza_log_activity', [$this, 'log_activity'], 10, 4);
        add_action('waza_booking_created', [$this, 'log_booking_created']);
        add_action('waza_booking_cancelled', [$this, 'log_booking_cancelled'], 10, 2);
        add_action('waza_slot_created', [$this, 'log_slot_created']);
        add_action('waza_payment_completed', [$this, 'log_payment'], 10, 2);
        
        // AJAX handlers
        add_action('wp_ajax_waza_get_activity_logs', [$this, 'ajax_get_activity_logs']);
        add_action('wp_ajax_waza_clear_old_logs', [$this, 'ajax_clear_old_logs']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'waza-booking',
            __('Activity Logs', 'waza-booking'),
            __('Activity Logs', 'waza-booking'),
            'manage_waza',
            'waza-activity-logs',
            [$this, 'admin_page']
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ('waza_page_waza-activity-logs' !== $hook) {
            return;
        }
        
        wp_localize_script('jquery', 'wazaActivityLogs', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('waza_admin_nonce')
        ]);
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        global $wpdb;
        
        // Get filter parameters
        $filter_action = isset($_GET['filter_action']) ? sanitize_text_field($_GET['filter_action']) : '';
        $filter_user = isset($_GET['filter_user']) ? intval($_GET['filter_user']) : 0;
        $filter_date_from = isset($_GET['filter_date_from']) ? sanitize_text_field($_GET['filter_date_from']) : '';
        $filter_date_to = isset($_GET['filter_date_to']) ? sanitize_text_field($_GET['filter_date_to']) : '';
        
        // Build query
        $where = ['1=1'];
        $params = [];
        
        if ($filter_action) {
            $where[] = 'action_type = %s';
            $params[] = $filter_action;
        }
        
        if ($filter_user) {
            $where[] = 'user_id = %d';
            $params[] = $filter_user;
        }
        
        if ($filter_date_from) {
            $where[] = 'DATE(created_at) >= %s';
            $params[] = $filter_date_from;
        }
        
        if ($filter_date_to) {
            $where[] = 'DATE(created_at) <= %s';
            $params[] = $filter_date_to;
        }
        
        $where_clause = implode(' AND ', $where);
        
        $query = "
            SELECT l.*, u.display_name as user_name
            FROM {$wpdb->prefix}waza_activity_logs l
            LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
            WHERE {$where_clause}
            ORDER BY l.created_at DESC
            LIMIT 200
        ";
        
        if (!empty($params)) {
            $logs = $wpdb->get_results($wpdb->prepare($query, ...$params));
        } else {
            $logs = $wpdb->get_results($query);
        }
        
        // Get unique action types for filter
        $action_types = $wpdb->get_col("
            SELECT DISTINCT action_type 
            FROM {$wpdb->prefix}waza_activity_logs 
            ORDER BY action_type
        ");
        
        // Get total count
        $total_logs = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}waza_activity_logs");
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Activity Logs', 'waza-booking'); ?></h1>
            <p><?php esc_html_e('View system activity and audit trail.', 'waza-booking'); ?></p>
            
            <!-- Stats -->
            <div class="waza-logs-stats" style="background:#fff; padding:20px; margin:15px 0; border:1px solid #ccc; border-radius:4px;">
                <p style="margin:0;">
                    <strong><?php esc_html_e('Total Logs:', 'waza-booking'); ?></strong> <?php echo number_format($total_logs); ?>
                    <span style="margin-left:20px;">
                        <strong><?php esc_html_e('Showing:', 'waza-booking'); ?></strong> <?php echo count($logs); ?>
                    </span>
                    <button type="button" id="waza-clear-old-logs" class="button" style="float:right;">
                        <?php esc_html_e('Clear Logs Older Than 90 Days', 'waza-booking'); ?>
                    </button>
                </p>
            </div>
            
            <!-- Filters -->
            <div class="waza-logs-filters" style="background:#fff; padding:15px; margin:15px 0; border:1px solid #ccc; border-radius:4px;">
                <form method="get" action="">
                    <input type="hidden" name="page" value="waza-activity-logs">
                    
                    <label><?php esc_html_e('Action Type:', 'waza-booking'); ?>
                        <select name="filter_action">
                            <option value=""><?php esc_html_e('All Actions', 'waza-booking'); ?></option>
                            <?php foreach ($action_types as $action): ?>
                                <option value="<?php echo esc_attr($action); ?>" <?php selected($filter_action, $action); ?>>
                                    <?php echo esc_html(ucwords(str_replace('_', ' ', $action))); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    
                    <label><?php esc_html_e('From Date:', 'waza-booking'); ?>
                        <input type="date" name="filter_date_from" value="<?php echo esc_attr($filter_date_from); ?>">
                    </label>
                    
                    <label><?php esc_html_e('To Date:', 'waza-booking'); ?>
                        <input type="date" name="filter_date_to" value="<?php echo esc_attr($filter_date_to); ?>">
                    </label>
                    
                    <button type="submit" class="button"><?php esc_html_e('Filter', 'waza-booking'); ?></button>
                    <a href="<?php echo admin_url('admin.php?page=waza-activity-logs'); ?>" class="button"><?php esc_html_e('Clear', 'waza-booking'); ?></a>
                </form>
            </div>
            
            <!-- Logs Table -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width:180px;"><?php esc_html_e('Date & Time', 'waza-booking'); ?></th>
                        <th style="width:150px;"><?php esc_html_e('Action', 'waza-booking'); ?></th>
                        <th style="width:150px;"><?php esc_html_e('User', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Description', 'waza-booking'); ?></th>
                        <th style="width:120px;"><?php esc_html_e('Object', 'waza-booking'); ?></th>
                        <th style="width:120px;"><?php esc_html_e('IP Address', 'waza-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center;">
                                <?php esc_html_e('No activity logs found.', 'waza-booking'); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <?php
                            $action_class = '';
                            $action_color = '#666';
                            
                            if (strpos($log->action_type, 'created') !== false) {
                                $action_color = '#4CAF50';
                            } elseif (strpos($log->action_type, 'deleted') !== false || strpos($log->action_type, 'cancelled') !== false) {
                                $action_color = '#f44336';
                            } elseif (strpos($log->action_type, 'updated') !== false) {
                                $action_color = '#2196F3';
                            } elseif (strpos($log->action_type, 'payment') !== false) {
                                $action_color = '#FF9800';
                            }
                            ?>
                            <tr>
                                <td><?php echo date('M j, Y g:i A', strtotime($log->created_at)); ?></td>
                                <td>
                                    <strong style="color:<?php echo $action_color; ?>;">
                                        <?php echo esc_html(ucwords(str_replace('_', ' ', $log->action_type))); ?>
                                    </strong>
                                </td>
                                <td><?php echo esc_html($log->user_name ?? 'System'); ?></td>
                                <td>
                                    <?php echo esc_html($log->description ?? ''); ?>
                                    <?php if ($log->metadata): ?>
                                        <br><small style="color:#666;">
                                            <?php
                                            $metadata = json_decode($log->metadata, true);
                                            if (is_array($metadata)) {
                                                $details = [];
                                                foreach ($metadata as $key => $value) {
                                                    if (is_string($value) || is_numeric($value)) {
                                                        $details[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . $value;
                                                    }
                                                }
                                                echo esc_html(implode(', ', $details));
                                            }
                                            ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($log->object_type && $log->object_id): ?>
                                        <code><?php echo esc_html($log->object_type . ' #' . $log->object_id); ?></code>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td><small><?php echo esc_html($log->ip_address ?? '—'); ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#waza-clear-old-logs').on('click', function() {
                if (!confirm('<?php esc_html_e('Are you sure you want to delete logs older than 90 days?', 'waza-booking'); ?>')) {
                    return;
                }
                
                var $btn = $(this);
                $btn.prop('disabled', true).text('<?php esc_html_e('Clearing...', 'waza-booking'); ?>');
                
                $.post(wazaActivityLogs.ajaxUrl, {
                    action: 'waza_clear_old_logs',
                    nonce: wazaActivityLogs.nonce
                }, function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + (response.data || 'Unknown error'));
                        $btn.prop('disabled', false).text('<?php esc_html_e('Clear Logs Older Than 90 Days', 'waza-booking'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Log activity
     * 
     * @param string $action_type Type of action performed
     * @param string $object_type Type of object (booking, slot, etc.)
     * @param int $object_id ID of the object
     * @param array $metadata Additional data to log
     */
    public function log_activity($action_type, $object_type = null, $object_id = null, $metadata = []) {
        global $wpdb;
        
        // Check if activity logging is enabled
        $settings = get_option('waza_booking_settings', []);
        if (empty($settings['enable_activity_logging'])) {
            return; // Logging disabled
        }
        
        $wpdb->insert($wpdb->prefix . 'waza_activity_logs', [
            'user_id' => get_current_user_id() ?: null,
            'action_type' => sanitize_text_field($action_type),
            'object_type' => $object_type ? sanitize_text_field($object_type) : null,
            'object_id' => $object_id ? intval($object_id) : null,
            'description' => isset($metadata['description']) ? sanitize_text_field($metadata['description']) : null,
            'metadata' => !empty($metadata) ? wp_json_encode($metadata) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null,
            'created_at' => current_time('mysql')
        ]);
    }
    
    /**
     * Log booking created
     */
    public function log_booking_created($booking_id) {
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT user_email, user_name FROM {$wpdb->prefix}waza_bookings WHERE id = %d",
            $booking_id
        ));
        
        if ($booking) {
            $this->log_activity('booking_created', 'booking', $booking_id, [
                'description' => sprintf('Booking created for %s', $booking->user_name),
                'user_email' => $booking->user_email
            ]);
        }
    }
    
    /**
     * Log booking cancelled
     */
    public function log_booking_cancelled($booking_id, $reason = '') {
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT user_email, user_name FROM {$wpdb->prefix}waza_bookings WHERE id = %d",
            $booking_id
        ));
        
        if ($booking) {
            $this->log_activity('booking_cancelled', 'booking', $booking_id, [
                'description' => sprintf('Booking cancelled for %s', $booking->user_name),
                'user_email' => $booking->user_email,
                'reason' => $reason
            ]);
        }
    }
    
    /**
     * Log slot created
     */
    public function log_slot_created($slot_id) {
        global $wpdb;
        
        $slot = $wpdb->get_row($wpdb->prepare(
            "SELECT start_datetime FROM {$wpdb->prefix}waza_slots WHERE id = %d",
            $slot_id
        ));
        
        if ($slot) {
            $this->log_activity('slot_created', 'slot', $slot_id, [
                'description' => sprintf('Slot created for %s', $slot->start_datetime)
            ]);
        }
    }
    
    /**
     * Log payment
     */
    public function log_payment($payment_id, $status) {
        $this->log_activity('payment_' . $status, 'payment', $payment_id, [
            'description' => sprintf('Payment %s', $status),
            'status' => $status
        ]);
    }
    
    /**
     * Clear old logs (AJAX)
     */
    public function ajax_clear_old_logs() {
        check_ajax_referer('waza_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_waza')) {
            wp_send_json_error(__('Permission denied', 'waza-booking'));
        }
        
        global $wpdb;
        
        $deleted = $wpdb->query("
            DELETE FROM {$wpdb->prefix}waza_activity_logs
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
        ");
        
        $this->log_activity('logs_cleared', null, null, [
            'description' => sprintf('Cleared %d old log entries', $deleted)
        ]);
        
        wp_send_json_success([
            'message' => sprintf(__('Successfully deleted %d old log entries', 'waza-booking'), $deleted)
        ]);
    }
}
