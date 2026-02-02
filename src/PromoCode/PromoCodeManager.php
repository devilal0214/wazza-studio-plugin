<?php
/**
 * Promo Code Manager
 * 
 * Handles promo code creation, validation, and application
 * 
 * @package WazaBooking\PromoCode
 */

namespace WazaBooking\PromoCode;

class PromoCodeManager {
    
    public function __construct() {
        // Admin menu
        add_action('admin_menu', [$this, 'add_admin_menu'], 25);
        
        // AJAX handlers
        add_action('wp_ajax_waza_create_promo_code', [$this, 'create_promo_code']);
        add_action('wp_ajax_waza_delete_promo_code', [$this, 'delete_promo_code']);
        add_action('wp_ajax_waza_validate_promo_code', [$this, 'validate_promo_code']);
        add_action('wp_ajax_nopriv_waza_validate_promo_code', [$this, 'validate_promo_code']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'waza-booking',
            __('Promo Codes', 'waza-booking'),
            __('Promo Codes', 'waza-booking'),
            'manage_options',
            'waza-promo-codes',
            [$this, 'render_promo_codes_page']
        );
    }
    
    /**
     * Render promo codes page
     */
    public function render_promo_codes_page() {
        global $wpdb;
        
        $promo_table = $wpdb->prefix . 'waza_promo_codes';
        $promo_codes = $wpdb->get_results("SELECT * FROM {$promo_table} ORDER BY created_at DESC");
        
        ?>
        <div class="wrap waza-promo-codes-page">
            <h1><?php esc_html_e('Promo Codes', 'waza-booking'); ?>
                <button class="button button-primary" id="add-promo-code">
                    <?php esc_html_e('Add New Promo Code', 'waza-booking'); ?>
                </button>
            </h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Code', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Type', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Discount', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Usage', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Expiry', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Status', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Actions', 'waza-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($promo_codes)) : ?>
                        <tr>
                            <td colspan="7" style="text-align:center;">
                                <?php esc_html_e('No promo codes found. Create your first one!', 'waza-booking'); ?>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($promo_codes as $code) : ?>
                            <tr>
                                <td><strong><?php echo esc_html($code->code); ?></strong></td>
                                <td><?php echo esc_html(ucfirst($code->discount_type)); ?></td>
                                <td>
                                    <?php 
                                    if ($code->discount_type === 'percentage') {
                                        echo esc_html($code->discount_amount) . '%';
                                    } else {
                                        echo '₹' . esc_html($code->discount_amount);
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php echo esc_html($code->used_count); ?>
                                    <?php if ($code->usage_limit) echo ' / ' . esc_html($code->usage_limit); ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($code->expiry_date) {
                                        echo esc_html(date('M d, Y', strtotime($code->expiry_date)));
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo esc_attr($code->status); ?>">
                                        <?php echo esc_html(ucfirst($code->status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="button button-small delete-promo" data-id="<?php echo esc_attr($code->id); ?>">
                                        <?php esc_html_e('Delete', 'waza-booking'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Add Promo Code Modal -->
        <div id="promo-code-modal" class="waza-modal" style="display:none;">
            <div class="waza-modal-content" style="max-width:600px;">
                <span class="waza-close">&times;</span>
                <h2><?php esc_html_e('Create Promo Code', 'waza-booking'); ?></h2>
                
                <form id="promo-code-form">
                    <table class="form-table">
                        <tr>
                            <th><label><?php esc_html_e('Code', 'waza-booking'); ?> *</label></th>
                            <td><input type="text" name="code" required class="regular-text" placeholder="SAVE20"></td>
                        </tr>
                        <tr>
                            <th><label><?php esc_html_e('Description', 'waza-booking'); ?></label></th>
                            <td><textarea name="description" class="large-text" rows="2"></textarea></td>
                        </tr>
                        <tr>
                            <th><label><?php esc_html_e('Discount Type', 'waza-booking'); ?> *</label></th>
                            <td>
                                <select name="discount_type" required>
                                    <option value="percentage"><?php esc_html_e('Percentage', 'waza-booking'); ?></option>
                                    <option value="fixed"><?php esc_html_e('Fixed Amount', 'waza-booking'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php esc_html_e('Discount Amount', 'waza-booking'); ?> *</label></th>
                            <td><input type="number" name="discount_amount" required step="0.01" min="0"></td>
                        </tr>
                        <tr>
                            <th><label><?php esc_html_e('Usage Limit', 'waza-booking'); ?></label></th>
                            <td><input type="number" name="usage_limit" min="0" placeholder="Unlimited"></td>
                        </tr>
                        <tr>
                            <th><label><?php esc_html_e('Expiry Date', 'waza-booking'); ?></label></th>
                            <td><input type="date" name="expiry_date"></td>
                        </tr>
                        <tr>
                            <th><label><?php esc_html_e('Min Booking Amount', 'waza-booking'); ?></label></th>
                            <td><input type="number" name="min_booking_amount" step="0.01" min="0" placeholder="0"></td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php esc_html_e('Create Promo Code', 'waza-booking'); ?></button>
                    </p>
                </form>
            </div>
        </div>
        
        <style>
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#add-promo-code').on('click', function() {
                $('#promo-code-modal').fadeIn();
            });
            
            $('.waza-close').on('click', function() {
                $('#promo-code-modal').fadeOut();
            });
            
            $('#promo-code-form').on('submit', function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'waza_create_promo_code',
                        ...Object.fromEntries(new FormData(this))
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Promo code created successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    }
                });
            });
            
            $('.delete-promo').on('click', function() {
                if (!confirm('Are you sure you want to delete this promo code?')) return;
                
                const id = $(this).data('id');
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'waza_delete_promo_code',
                        id: id
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Create promo code
     */
    public function create_promo_code() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        global $wpdb;
        $promo_table = $wpdb->prefix . 'waza_promo_codes';
        
        $data = [
            'code' => strtoupper(sanitize_text_field($_POST['code'])),
            'description' => sanitize_textarea_field($_POST['description'] ?? ''),
            'discount_type' => sanitize_text_field($_POST['discount_type']),
            'discount_amount' => floatval($_POST['discount_amount']),
            'usage_limit' => !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null,
            'expiry_date' => !empty($_POST['expiry_date']) ? sanitize_text_field($_POST['expiry_date']) : null,
            'min_booking_amount' => !empty($_POST['min_booking_amount']) ? floatval($_POST['min_booking_amount']) : null,
            'created_by' => get_current_user_id(),
            'status' => 'active'
        ];
        
        $result = $wpdb->insert($promo_table, $data);
        
        if ($result) {
            wp_send_json_success(['message' => 'Promo code created successfully']);
        } else {
            wp_send_json_error(['message' => 'Failed to create promo code']);
        }
    }
    
    /**
     * Delete promo code
     */
    public function delete_promo_code() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error();
        }
        
        global $wpdb;
        $promo_table = $wpdb->prefix . 'waza_promo_codes';
        
        $id = intval($_POST['id']);
        $wpdb->delete($promo_table, ['id' => $id]);
        
        wp_send_json_success();
    }
    
    /**
     * Validate promo code (AJAX)
     */
    public function validate_promo_code() {
        check_ajax_referer('waza_frontend_nonce', 'nonce');
        
        $code = strtoupper(sanitize_text_field($_POST['code']));
        $booking_amount = floatval($_POST['booking_amount']);
        
        $result = $this->validate_code($code, $booking_amount);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }
    
    /**
     * Validate promo code
     */
    public function validate_code($code, $booking_amount = 0) {
        global $wpdb;
        $promo_table = $wpdb->prefix . 'waza_promo_codes';
        
        $promo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$promo_table} WHERE code = %s AND status = 'active'",
            $code
        ));
        
        if (!$promo) {
            return new \WP_Error('invalid_code', 'Invalid promo code');
        }
        
        // Check expiry
        if ($promo->expiry_date && strtotime($promo->expiry_date) < time()) {
            return new \WP_Error('expired', 'This promo code has expired');
        }
        
        // Check usage limit
        if ($promo->usage_limit && $promo->used_count >= $promo->usage_limit) {
            return new \WP_Error('limit_reached', 'This promo code has reached its usage limit');
        }
        
        // Check minimum amount
        if ($promo->min_booking_amount && $booking_amount < $promo->min_booking_amount) {
            return new \WP_Error('min_amount', sprintf('Minimum booking amount of ₹%s required', $promo->min_booking_amount));
        }
        
        // Calculate discount
        $discount = 0;
        if ($promo->discount_type === 'percentage') {
            $discount = ($booking_amount * $promo->discount_amount) / 100;
        } else {
            $discount = $promo->discount_amount;
        }
        
        // Don't let discount exceed booking amount
        $discount = min($discount, $booking_amount);
        
        $message = sprintf(
            'Promo code applied! You save ₹%s',
            number_format($discount, 2)
        );
        
        return [
            'id' => $promo->id,
            'code' => $promo->code,
            'discount_type' => $promo->discount_type,
            'discount_value' => $promo->discount_amount,
            'discount_amount' => $discount,
            'final_amount' => $booking_amount - $discount,
            'message' => $message
        ];
    }
    
    /**
     * Apply promo code (increment usage)
     */
    public function apply_promo_code($code) {
        global $wpdb;
        $promo_table = $wpdb->prefix . 'waza_promo_codes';
        
        $wpdb->query($wpdb->prepare(
            "UPDATE {$promo_table} SET used_count = used_count + 1 WHERE code = %s",
            $code
        ));
    }
}
