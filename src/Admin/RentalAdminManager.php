<?php
/**
 * Rental Admin Manager
 * 
 * Handles admin interface for managing studio rentals
 * 
 * @package WazaBooking\Admin
 */

namespace WazaBooking\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class RentalAdminManager {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_rental_menu'], 100);
        add_action('wp_ajax_waza_update_rental_status', [$this, 'update_rental_status']);
        add_action('wp_ajax_waza_delete_rental', [$this, 'delete_rental']);
        add_action('wp_ajax_waza_get_rental_details', [$this, 'get_rental_details']);
    }
    
    /**
     * Add rental management menu
     */
    public function add_rental_menu() {
        add_submenu_page(
            'waza-booking',
            __('Studio Rentals', 'waza-booking'),
            __('Studio Rentals', 'waza-booking'),
            'manage_options',
            'waza-rentals',
            [$this, 'render_rentals_page']
        );
    }
    
    /**
     * Render rentals management page
     */
    public function render_rentals_page() {
        global $wpdb;
        
        $status_filter = $_GET['status'] ?? 'all';
        $search = $_GET['s'] ?? '';
        
        $where = "WHERE 1=1";
        
        if ($status_filter !== 'all') {
            $where .= $wpdb->prepare(" AND booking_status = %s", $status_filter);
        }
        
        if (!empty($search)) {
            $where .= $wpdb->prepare(" AND (customer_name LIKE %s OR customer_email LIKE %s OR customer_phone LIKE %s)", 
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }
        
        $rentals = $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}waza_rentals 
            $where 
            ORDER BY created_at DESC
        ");
        
        // Get counts for filters
        $counts = [
            'all' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}waza_rentals"),
            'pending' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}waza_rentals WHERE booking_status = 'pending'"),
            'confirmed' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}waza_rentals WHERE booking_status = 'confirmed'"),
            'completed' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}waza_rentals WHERE booking_status = 'completed'"),
            'cancelled' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}waza_rentals WHERE booking_status = 'cancelled'"),
        ];
        ?>
        
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('Studio Rentals', 'waza-booking'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=waza-rental-settings'); ?>" class="page-title-action">
                <?php esc_html_e('Settings', 'waza-booking'); ?>
            </a>
            <hr class="wp-header-end">
            
            <ul class="subsubsub">
                <li><a href="?page=waza-rentals&status=all" <?php echo $status_filter === 'all' ? 'class="current"' : ''; ?>>
                    <?php esc_html_e('All', 'waza-booking'); ?> (<?php echo $counts['all']; ?>)
                </a> |</li>
                <li><a href="?page=waza-rentals&status=pending" <?php echo $status_filter === 'pending' ? 'class="current"' : ''; ?>>
                    <?php esc_html_e('Pending', 'waza-booking'); ?> (<?php echo $counts['pending']; ?>)
                </a> |</li>
                <li><a href="?page=waza-rentals&status=confirmed" <?php echo $status_filter === 'confirmed' ? 'class="current"' : ''; ?>>
                    <?php esc_html_e('Confirmed', 'waza-booking'); ?> (<?php echo $counts['confirmed']; ?>)
                </a> |</li>
                <li><a href="?page=waza-rentals&status=completed" <?php echo $status_filter === 'completed' ? 'class="current"' : ''; ?>>
                    <?php esc_html_e('Completed', 'waza-booking'); ?> (<?php echo $counts['completed']; ?>)
                </a> |</li>
                <li><a href="?page=waza-rentals&status=cancelled" <?php echo $status_filter === 'cancelled' ? 'class="current"' : ''; ?>>
                    <?php esc_html_e('Cancelled', 'waza-booking'); ?> (<?php echo $counts['cancelled']; ?>)
                </a></li>
            </ul>
            
            <form method="get" class="search-form">
                <input type="hidden" name="page" value="waza-rentals">
                <p class="search-box">
                    <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search rentals...', 'waza-booking'); ?>">
                    <input type="submit" class="button" value="<?php esc_attr_e('Search', 'waza-booking'); ?>">
                </p>
            </form>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('ID', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Customer', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Contact', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Rental Type', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Duration', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Date & Time', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Amount', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Payment', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Status', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Actions', 'waza-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rentals)) : ?>
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 40px;">
                                <?php esc_html_e('No rentals found.', 'waza-booking'); ?>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($rentals as $rental) : ?>
                            <tr>
                                <td><strong>#<?php echo $rental->id; ?></strong></td>
                                <td>
                                    <strong><?php echo esc_html($rental->customer_name); ?></strong>
                                </td>
                                <td>
                                    <?php echo esc_html($rental->customer_email); ?><br>
                                    <small><?php echo esc_html($rental->customer_phone); ?></small>
                                </td>
                                <td>
                                    <?php
                                    $icons = ['rehearsal' => '🎭', 'shoot' => '🎥', 'commercial' => '📢'];
                                    echo $icons[$rental->rental_type] ?? '';
                                    echo ' ' . esc_html(ucwords(str_replace('_', ' ', $rental->rental_type)));
                                    ?>
                                </td>
                                <td><?php echo esc_html(ucwords(str_replace('_', ' ', $rental->duration_type))); ?></td>
                                <td>
                                    <strong><?php echo date('M j, Y', strtotime($rental->rental_date)); ?></strong><br>
                                    <small><?php echo date('g:i A', strtotime($rental->start_time)) . ' - ' . date('g:i A', strtotime($rental->end_time)); ?></small>
                                </td>
                                <td><strong>₹<?php echo number_format($rental->total_amount, 2); ?></strong></td>
                                <td>
                                    <?php
                                    $payment_badges = [
                                        'pending' => '<span class="status-badge status-pending">Pending</span>',
                                        'paid' => '<span class="status-badge status-success">Paid</span>',
                                        'failed' => '<span class="status-badge status-failed">Failed</span>',
                                    ];
                                    echo $payment_badges[$rental->payment_status] ?? $rental->payment_status;
                                    ?>
                                </td>
                                <td>
                                    <select class="rental-status-select" data-rental-id="<?php echo $rental->id; ?>">
                                        <option value="pending" <?php selected($rental->booking_status, 'pending'); ?>>Pending</option>
                                        <option value="confirmed" <?php selected($rental->booking_status, 'confirmed'); ?>>Confirmed</option>
                                        <option value="completed" <?php selected($rental->booking_status, 'completed'); ?>>Completed</option>
                                        <option value="cancelled" <?php selected($rental->booking_status, 'cancelled'); ?>>Cancelled</option>
                                    </select>
                                </td>
                                <td>
                                    <button class="button view-rental-details" data-rental-id="<?php echo $rental->id; ?>">
                                        <?php esc_html_e('View', 'waza-booking'); ?>
                                    </button>
                                    <?php if ($rental->qr_code_path) : ?>
                                        <a href="<?php echo esc_url(wp_upload_dir()['baseurl'] . $rental->qr_code_path); ?>" 
                                           class="button" download>
                                            QR
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Rental Details Modal -->
        <div id="rental-details-modal" class="rental-modal" style="display: none;">
            <div class="rental-modal-content">
                <span class="rental-modal-close">&times;</span>
                <div id="rental-details-content"></div>
            </div>
        </div>
        
        <style>
        .status-badge { padding: 5px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-success { background: #d4edda; color: #155724; }
        .status-failed { background: #f8d7da; color: #721c24; }
        .rental-status-select { padding: 4px 8px; border-radius: 4px; }
        .rental-modal { position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); }
        .rental-modal-content { background-color: #fefefe; margin: 5% auto; padding: 30px; border-radius: 8px; width: 80%; max-width: 600px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
        .rental-modal-close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .rental-modal-close:hover { color: #000; }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Update rental status
            $('.rental-status-select').on('change', function() {
                const rentalId = $(this).data('rental-id');
                const newStatus = $(this).val();
                
                if (!confirm('<?php esc_html_e('Are you sure you want to change the status?', 'waza-booking'); ?>')) {
                    return;
                }
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'waza_update_rental_status',
                        rental_id: rentalId,
                        status: newStatus
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data.message);
                        }
                    }
                });
            });
            
            // View rental details
            $('.view-rental-details').on('click', function() {
                const rentalId = $(this).data('rental-id');
                // Load and display rental details in modal
                $('#rental-details-modal').show();
                $('#rental-details-content').html('<p>Loading details...</p>');
                
                // Fetch rental details via AJAX
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'waza_get_rental_details',
                        rental_id: rentalId,
                        _wpnonce: '<?php echo wp_create_nonce('wp_admin'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            const r = response.data;
                            let html = '<table class="form-table">';
                            html += '<tr><th>Rental ID</th><td>WR-' + String(r.id).padStart(5, '0') + '</td></tr>';
                            html += '<tr><th>Customer Name</th><td>' + r.customer_name + '</td></tr>';
                            html += '<tr><th>Email</th><td>' + r.customer_email + '</td></tr>';
                            html += '<tr><th>Phone</th><td>' + r.customer_phone + '</td></tr>';
                            html += '<tr><th>Rental Type</th><td>' + r.rental_type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) + '</td></tr>';
                            html += '<tr><th>Duration</th><td>' + r.duration_type.replace(/\b\w/g, l => l.toUpperCase()) + '</td></tr>';
                            html += '<tr><th>Date</th><td>' + r.rental_date + '</td></tr>';
                            html += '<tr><th>Time</th><td>' + r.start_time + ' - ' + r.end_time + '</td></tr>';
                            html += '<tr><th>Total Amount</th><td>₹' + parseFloat(r.total_amount).toFixed(2) + '</td></tr>';
                            html += '<tr><th>Payment Status</th><td><span class="status-badge status-' + r.payment_status + '">' + r.payment_status.toUpperCase() + '</span></td></tr>';
                            html += '<tr><th>Booking Status</th><td><span class="status-badge status-' + r.booking_status + '">' + r.booking_status.toUpperCase() + '</span></td></tr>';
                            if (r.special_requirements) {
                                html += '<tr><th>Special Requirements</th><td>' + r.special_requirements + '</td></tr>';
                            }
                            html += '<tr><th>Created</th><td>' + r.created_at + '</td></tr>';
                            html += '</table>';
                            $('#rental-details-content').html(html);
                        } else {
                            $('#rental-details-content').html('<p>Error loading details: ' + response.data.message + '</p>');
                        }
                    },
                    error: function() {
                        $('#rental-details-content').html('<p>Failed to load rental details.</p>');
                    }
                });
            });
            
            // Close modal
            $('.rental-modal-close').on('click', function() {
                $('#rental-details-modal').hide();
            });
            
            $(window).on('click', function(e) {
                if ($(e.target).is('#rental-details-modal')) {
                    $('#rental-details-modal').hide();
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Get rental details via AJAX
     */
    public function get_rental_details() {
        check_ajax_referer('wp_admin', '_wpnonce', false);
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'waza-booking')]);
        }
        
        $rental_id = intval($_POST['rental_id'] ?? 0);
        
        if (!$rental_id) {
            wp_send_json_error(['message' => __('Invalid rental ID', 'waza-booking')]);
        }
        
        global $wpdb;
        
        $rental = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}waza_rentals WHERE id = %d",
            $rental_id
        ));
        
        if (!$rental) {
            wp_send_json_error(['message' => __('Rental not found', 'waza-booking')]);
        }
        
        wp_send_json_success($rental);
    }
    
    /**
     * Update rental status via AJAX
     */
    public function update_rental_status() {
        check_ajax_referer('wp_admin', '_wpnonce', false);
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'waza-booking')]);
        }
        
        $rental_id = intval($_POST['rental_id']);
        $status = sanitize_text_field($_POST['status']);
        
        global $wpdb;
        
        $updated = $wpdb->update(
            $wpdb->prefix . 'waza_rentals',
            ['booking_status' => $status],
            ['id' => $rental_id],
            ['%s'],
            ['%d']
        );
        
        if ($updated !== false) {
            wp_send_json_success(['message' => __('Status updated successfully', 'waza-booking')]);
        } else {
            wp_send_json_error(['message' => __('Failed to update status', 'waza-booking')]);
        }
    }
}
