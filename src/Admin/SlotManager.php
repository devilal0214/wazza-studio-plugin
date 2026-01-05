<?php
/**
 * Slot Manager
 * 
 * Manages activity time slots and availability with pagination and edit functionality
 * 
 * @package WazaBooking\Admin
 */

namespace WazaBooking\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Slot Manager Class
 */
class SlotManager {
    
    /**
     * Initialize slot management
     */
    public function __construct() {
        // NOTE: Admin menu is registered by AdminManager, not here
        // add_action('admin_menu', [$this, 'add_admin_menu'], 15);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        
        // AJAX handlers
        add_action('wp_ajax_waza_save_slot', [$this, 'save_slot']);
        add_action('wp_ajax_waza_get_slot', [$this, 'get_slot']);
        add_action('wp_ajax_waza_update_slot', [$this, 'update_slot']);
        add_action('wp_ajax_waza_delete_slot', [$this, 'delete_slot']);
        add_action('wp_ajax_waza_bulk_create_slots', [$this, 'bulk_create_slots']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'waza-booking',
            __('Time Slots', 'waza-booking'),
            __('Time Slots', 'waza-booking'),
            'manage_options',
            'waza-slots',
            [$this, 'admin_page']
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        // Check if we're on the slots page
        // When registered as submenu of 'waza-booking', hook is 'waza-booking_page_waza-slots'
        if (strpos($hook, 'waza-slots') === false) {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('wp-jquery-ui-dialog');
        
        wp_enqueue_script(
            'waza-slot-admin',
            WAZA_BOOKING_PLUGIN_URL . 'assets/admin-slots.js',
            ['jquery', 'jquery-ui-datepicker'],
            WAZA_BOOKING_VERSION,
            true
        );
        
        wp_localize_script('waza-slot-admin', 'wazaSlots', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('waza_slot_nonce'),
            'strings' => [
                'confirmDelete' => __('Are you sure you want to delete this slot?', 'waza-booking'),
                'saved' => __('Slot saved successfully!', 'waza-booking'),
                'error' => __('An error occurred. Please try again.', 'waza-booking')
            ]
        ]);
    }
    
    /**
     * Admin page with client-side tabs
     */
    public function admin_page() {
        $current_tab = $_GET['tab'] ?? 'list';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Time Slots Management', 'waza-booking'); ?></h1>
            
            <nav class="nav-tab-wrapper">
                <a href="<?php echo esc_url(admin_url('admin.php?page=waza-slots&tab=list')); ?>" 
                   class="nav-tab <?php echo $current_tab === 'list' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('All Slots', 'waza-booking'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=waza-slots&tab=add')); ?>" 
                   class="nav-tab <?php echo $current_tab === 'add' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Add Single Slot', 'waza-booking'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=waza-slots&tab=bulk')); ?>" 
                   class="nav-tab <?php echo $current_tab === 'bulk' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Bulk Create', 'waza-booking'); ?>
                </a>
            </nav>
            
            <!-- Wrap content in divs for client-side tab switching -->
            <div id="tab-list" class="tab-content" style="display: <?php echo $current_tab === 'list' ? 'block' : 'none'; ?>;">
                <?php $this->render_slots_list(); ?>
            </div>
            
            <div id="tab-add" class="tab-content" style="display: <?php echo $current_tab === 'add' ? 'block' : 'none'; ?>;">
                <?php $this->render_add_slot_form(); ?>
            </div>
            
            <div id="tab-bulk" class="tab-content" style="display: <?php echo $current_tab === 'bulk' ? 'block' : 'none'; ?>;">
                <?php $this->render_bulk_create_form(); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render slots list with pagination
     */
    private function render_slots_list() {
        global $wpdb;
        
        // Pagination setup
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;
        
        // Get total count
        $total_slots = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->prefix}waza_slots
        ");
        
        $total_pages = ceil($total_slots / $per_page);
        
        // Get slots with activity details - LATEST FIRST (DESC)
        $slots = $wpdb->get_results($wpdb->prepare("
            SELECT s.*, p.post_title as activity_title,
                   COUNT(b.id) as bookings_count,
                   SUM(CASE WHEN b.booking_status = 'confirmed' THEN b.attendees_count ELSE 0 END) as confirmed_bookings
            FROM {$wpdb->prefix}waza_slots s
            LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
            LEFT JOIN {$wpdb->prefix}waza_bookings b ON s.id = b.slot_id
            GROUP BY s.id
            ORDER BY s.start_datetime DESC
            LIMIT %d OFFSET %d
        ", $per_page, $offset));
        
        ?>
        <div class="waza-slots-list">
            <div class="tablenav top">
                <div class="alignleft actions">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=waza-slots&tab=add')); ?>" 
                       class="button button-primary">
                        <?php esc_html_e('Add New Slot', 'waza-booking'); ?>
                    </a>
                </div>
                
                <?php if ($total_pages > 1): ?>
                <div class="tablenav-pages">
                    <span class="displaying-num"><?php echo sprintf(__('%s items', 'waza-booking'), number_format_i18n($total_slots)); ?></span>
                    <?php
                    echo paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $total_pages,
                        'current' => $current_page
                    ]);
                    ?>
                </div>
                <?php endif; ?>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Activity', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Date & Time', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Capacity', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Bookings', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Status', 'waza-booking'); ?></th>
                        <th><?php esc_html_e('Actions', 'waza-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($slots)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px;">
                                <p><?php esc_html_e('No time slots found.', 'waza-booking'); ?></p>
                                <p>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=waza-slots&tab=add')); ?>" 
                                       class="button button-primary">
                                        <?php esc_html_e('Create Your First Slot', 'waza-booking'); ?>
                                    </a>
                                </p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($slots as $slot): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($slot->activity_title ?: __('Unknown Activity', 'waza-booking')); ?></strong>
                                </td>
                                <td>
                                    <?php echo esc_html(wp_date('M j, Y @ g:i A', strtotime($slot->start_datetime))); ?>
                                    <br>
                                    <small><?php echo esc_html(wp_date('g:i A', strtotime($slot->end_datetime))); ?></small>
                                </td>
                                <td>
                                    <?php echo intval($slot->capacity); ?>
                                </td>
                                <td>
                                    <?php echo intval($slot->confirmed_bookings); ?> / <?php echo intval($slot->capacity); ?>
                                    <br>
                                    <small><?php echo intval($slot->bookings_count); ?> total</small>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo esc_attr($slot->status); ?>">
                                        <?php echo esc_html(ucfirst($slot->status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="#" class="button button-small edit-slot" 
                                       data-slot-id="<?php echo esc_attr($slot->id); ?>">
                                        <?php esc_html_e('Edit', 'waza-booking'); ?>
                                    </a>
                                    <a href="#" class="button button-small button-link-delete delete-slot" 
                                       data-slot-id="<?php echo esc_attr($slot->id); ?>">
                                        <?php esc_html_e('Delete', 'waza-booking'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if ($total_pages > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="displaying-num"><?php echo sprintf(__('%s items', 'waza-booking'), number_format_i18n($total_slots)); ?></span>
                    <?php
                    echo paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $total_pages,
                        'current' => $current_page
                    ]);
                    ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <style>
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-available { background: #d1e7dd; color: #0f5132; }
        .status-full { background: #f8d7da; color: #721c24; }
        .status-cancelled { background: #f1f3f4; color: #5f6368; }
        </style>
        <?php
    }
    
    /**
     * Render add slot form
     */
    private function render_add_slot_form() {
        // Get activities
        $activities = get_posts([
            'post_type' => 'waza_activity',
            'post_status' => 'publish',
            'numberposts' => -1
        ]);
        
        ?>
        <form id="add-slot-form" class="waza-form">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="activity_id"><?php esc_html_e('Activity', 'waza-booking'); ?></label>
                    </th>
                    <td>
                        <select id="activity_id" name="activity_id" required>
                            <option value=""><?php esc_html_e('Select an activity', 'waza-booking'); ?></option>
                            <?php foreach ($activities as $activity): ?>
                                <option value="<?php echo esc_attr($activity->ID); ?>">
                                    <?php echo esc_html($activity->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($activities)): ?>
                            <p class="description">
                                <?php esc_html_e('No activities found. Please create an activity first.', 'waza-booking'); ?>
                                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=waza_activity')); ?>">
                                    <?php esc_html_e('Create Activity', 'waza-booking'); ?>
                                </a>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="start_date"><?php esc_html_e('Start Date', 'waza-booking'); ?></label>
                    </th>
                    <td>
                        <input type="date" id="start_date" name="start_date" required 
                               min="<?php echo esc_attr(date('Y-m-d')); ?>" />
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="start_time"><?php esc_html_e('Start Time', 'waza-booking'); ?></label>
                    </th>
                    <td>
                        <input type="time" id="start_time" name="start_time" required />
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="end_time"><?php esc_html_e('End Time', 'waza-booking'); ?></label>
                    </th>
                    <td>
                        <input type="time" id="end_time" name="end_time" required />
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="instructor_id"><?php esc_html_e('Instructor', 'waza-booking'); ?></label>
                    </th>
                    <td>
                        <select id="instructor_id" name="instructor_id">
                            <option value=""><?php esc_html_e('No Instructor', 'waza-booking'); ?></option>
                            <?php 
                            $instructors = get_posts([
                                'post_type' => 'waza_instructor',
                                'numberposts' => -1,
                                'post_status' => ['publish', 'pending'],
                                'orderby' => 'title',
                                'order' => 'ASC'
                            ]);
                            foreach ($instructors as $instructor): 
                                $status_label = $instructor->post_status === 'pending' ? ' (Pending)' : '';
                            ?>
                                <option value="<?php echo esc_attr($instructor->ID); ?>">
                                    <?php echo esc_html($instructor->post_title . $status_label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Assign an instructor to this slot (optional).', 'waza-booking'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="capacity"><?php esc_html_e('Capacity', 'waza-booking'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="capacity" name="capacity" required 
                               min="1" max="1000" value="10" />
                        <p class="description">
                            <?php esc_html_e('Maximum number of participants for this slot.', 'waza-booking'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="price"><?php esc_html_e('Price', 'waza-booking'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="price" name="price" step="0.01" min="0" value="0.00" />
                        <p class="description">
                            <?php esc_html_e('Price for this slot. Leave as 0 for free slots.', 'waza-booking'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="location"><?php esc_html_e('Location', 'waza-booking'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="location" name="location" class="regular-text" />
                        <p class="description">
                            <?php esc_html_e('Specific location for this slot (optional).', 'waza-booking'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="notes"><?php esc_html_e('Notes', 'waza-booking'); ?></label>
                    </th>
                    <td>
                        <textarea id="notes" name="notes" rows="3" class="large-text"></textarea>
                        <p class="description">
                            <?php esc_html_e('Additional notes or instructions for this slot.', 'waza-booking'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary">
                    <?php esc_html_e('Create Slot', 'waza-booking'); ?>
                </button>
                <a href="<?php echo esc_url(admin_url('admin.php?page=waza-slots')); ?>" class="button">
                    <?php esc_html_e('Cancel', 'waza-booking'); ?>
                </a>
            </p>
        </form>
        <?php
    }
    
    /**
     * Render bulk create form
     */
    private function render_bulk_create_form() {
        // Get activities
        $activities = get_posts([
            'post_type' => 'waza_activity',
            'post_status' => 'publish',
            'numberposts' => -1
        ]);
        
        ?>
        <form id="bulk-create-form" class="waza-form">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="bulk_activity_id"><?php esc_html_e('Activity', 'waza-booking'); ?></label>
                    </th>
                    <td>
                        <select id="bulk_activity_id" name="activity_id" required>
                            <option value=""><?php esc_html_e('Select an activity', 'waza-booking'); ?></option>
                            <?php foreach ($activities as $activity): ?>
                                <option value="<?php echo esc_attr($activity->ID); ?>">
                                    <?php echo esc_html($activity->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="date_range_start"><?php esc_html_e('Date Range', 'waza-booking'); ?></label>
                    </th>
                    <td>
                        <input type="date" id="date_range_start" name="date_range_start" required 
                               min="<?php echo esc_attr(date('Y-m-d')); ?>" />
                        <span> <?php esc_html_e('to', 'waza-booking'); ?> </span>
                        <input type="date" id="date_range_end" name="date_range_end" required 
                               min="<?php echo esc_attr(date('Y-m-d')); ?>" />
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e('Days of Week', 'waza-booking'); ?></label>
                    </th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text">
                                <?php esc_html_e('Select days of the week', 'waza-booking'); ?>
                            </legend>
                            <?php
                            $days = [
                                'monday' => __('Monday', 'waza-booking'),
                                'tuesday' => __('Tuesday', 'waza-booking'),
                                'wednesday' => __('Wednesday', 'waza-booking'),
                                'thursday' => __('Thursday', 'waza-booking'),
                                'friday' => __('Friday', 'waza-booking'),
                                'saturday' => __('Saturday', 'waza-booking'),
                                'sunday' => __('Sunday', 'waza-booking')
                            ];
                            
                            foreach ($days as $key => $label):
                            ?>
                                <label style="display: inline-block; margin-right: 15px;">
                                    <input type="checkbox" name="days[]" value="<?php echo esc_attr($key); ?>" />
                                    <?php echo esc_html($label); ?>
                                </label>
                            <?php endforeach; ?>
                        </fieldset>
                        <p class="description">
                            <?php esc_html_e('Only slots on selected weekdays within the date range will be created. Example: Select "Monday" and "Wednesday" to create slots only on those days between your start and end dates.', 'waza-booking'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="bulk_start_time"><?php esc_html_e('Time Slots', 'waza-booking'); ?></label>
                    </th>
                    <td>
                        <div id="time-slots-container">
                            <div class="time-slot-row">
                                <input type="time" name="time_slots[0][start]" required />
                                <span> - </span>
                                <input type="time" name="time_slots[0][end]" required />
                                <button type="button" class="button remove-time-slot" style="margin-left: 10px;">
                                    <?php esc_html_e('Remove', 'waza-booking'); ?>
                                </button>
                            </div>
                        </div>
                        <button type="button" id="add-time-slot" class="button">
                            <?php esc_html_e('Add Another Time Slot', 'waza-booking'); ?>
                        </button>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="bulk_capacity"><?php esc_html_e('Capacity', 'waza-booking'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="bulk_capacity" name="capacity" required 
                               min="1" max="1000" value="10" />
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="bulk_instructor_id"><?php esc_html_e('Instructor', 'waza-booking'); ?></label>
                    </th>
                    <td>
                        <select id="bulk_instructor_id" name="instructor_id">
                            <option value=""><?php esc_html_e('No Instructor', 'waza-booking'); ?></option>
                            <?php
                            $instructors = get_posts([
                                'post_type' => 'waza_instructor',
                                'numberposts' => -1,
                                'post_status' => ['publish', 'pending'],
                                'orderby' => 'title',
                                'order' => 'ASC'
                            ]);
                            foreach ($instructors as $instructor): 
                                $status_label = $instructor->post_status === 'pending' ? ' (Pending)' : '';
                            ?>
                                <option value="<?php echo esc_attr($instructor->ID); ?>">
                                    <?php echo esc_html($instructor->post_title . $status_label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Assign an instructor to all slots (optional).', 'waza-booking'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="bulk_price"><?php esc_html_e('Price', 'waza-booking'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="bulk_price" name="price" step="0.01" min="0" value="0.00" />
                        <p class="description">
                            <?php esc_html_e('Price for these slots. Leave as 0 for free slots.', 'waza-booking'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary">
                    <?php esc_html_e('Create Slots', 'waza-booking'); ?>
                </button>
                <a href="<?php echo esc_url(admin_url('admin.php?page=waza-slots')); ?>" class="button">
                    <?php esc_html_e('Cancel', 'waza-booking'); ?>
                </a>
            </p>
        </form>
        <?php
    }
    
    /**
     * Save slot via AJAX
     */
    public function save_slot() {
        check_ajax_referer('waza_slot_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'waza-booking'));
        }
        
        $activity_id = intval($_POST['activity_id']);
        $instructor_id = !empty($_POST['instructor_id']) ? intval($_POST['instructor_id']) : null;
        $start_date = sanitize_text_field($_POST['start_date']);
        $start_time = sanitize_text_field($_POST['start_time']);
        $end_time = sanitize_text_field($_POST['end_time']);
        $capacity = intval($_POST['capacity']);
        $price = floatval($_POST['price'] ?? 0);
        $location = sanitize_text_field($_POST['location']);
        $notes = sanitize_textarea_field($_POST['notes']);
        
        if (!$activity_id || !$start_date || !$start_time || !$end_time) {
            wp_send_json_error(__('Please fill in all required fields.', 'waza-booking'));
        }
        
        global $wpdb;
        
        $start_datetime = $start_date . ' ' . $start_time . ':00';
        $end_datetime = $start_date . ' ' . $end_time . ':00';
        
        // Check instructor availability if instructor is assigned
        if ($instructor_id) {
            $conflict = $wpdb->get_row($wpdb->prepare("
                SELECT id, start_datetime, end_datetime 
                FROM {$wpdb->prefix}waza_slots 
                WHERE instructor_id = %d 
                AND status != 'cancelled'
                AND (
                    (start_datetime <= %s AND end_datetime > %s) OR
                    (start_datetime < %s AND end_datetime >= %s) OR
                    (start_datetime >= %s AND end_datetime <= %s)
                )
                LIMIT 1
            ", $instructor_id, $start_datetime, $start_datetime, $end_datetime, $end_datetime, $start_datetime, $end_datetime));
            
            if ($conflict) {
                wp_send_json_error(sprintf(
                    __('Instructor is already assigned to another slot at this time (Slot #%d: %s to %s). Please choose a different time or instructor.', 'waza-booking'),
                    $conflict->id,
                    date('M j, Y g:i A', strtotime($conflict->start_datetime)),
                    date('g:i A', strtotime($conflict->end_datetime))
                ));
            }
        }
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'waza_slots',
            [
                'activity_id' => $activity_id,
                'instructor_id' => $instructor_id,
                'start_datetime' => $start_datetime,
                'end_datetime' => $end_datetime,
                'capacity' => $capacity,
                'price' => $price,
                'location' => $location,
                'notes' => $notes,
                'status' => 'available'
            ],
            [
                '%d',
                $instructor_id ? '%d' : '%s',
                '%s',
                '%s',
                '%d',
                '%f',
                '%s',
                '%s',
                '%s'
            ]
        );
        
        if ($result) {
            wp_send_json_success([
                'message' => __('Slot created successfully!', 'waza-booking'),
                'slot_id' => $wpdb->insert_id
            ]);
        } else {
            wp_send_json_error(__('Failed to create slot. Please try again.', 'waza-booking'));
        }
    }
    
    /**
     * Get slot data via AJAX
     */
    public function get_slot() {
        check_ajax_referer('waza_slot_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'waza-booking'));
        }
        
        $slot_id = intval($_POST['slot_id']);
        
        if (!$slot_id) {
            wp_send_json_error(__('Invalid slot ID.', 'waza-booking'));
        }
        
        global $wpdb;
        
        $slot = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, p.post_title as activity_title
            FROM {$wpdb->prefix}waza_slots s
            LEFT JOIN {$wpdb->posts} p ON s.activity_id = p.ID
            WHERE s.id = %d",
            $slot_id
        ), ARRAY_A);
        
        if (!$slot) {
            wp_send_json_error(__('Slot not found.', 'waza-booking'));
        }
        
        // Format data for form
        $slot['start_date'] = date('Y-m-d', strtotime($slot['start_datetime']));
        $slot['start_time'] = date('H:i', strtotime($slot['start_datetime']));
        $slot['end_time'] = date('H:i', strtotime($slot['end_datetime']));
        
        // Get instructors for dropdown
        $instructors = get_posts([
            'post_type' => 'waza_instructor',
            'numberposts' => -1,
            'post_status' => ['publish', 'pending'],
            'orderby' => 'title',
            'order' => 'ASC'
        ]);
        
        $instructors_html = '';
        foreach ($instructors as $instructor) {
            $selected = ($slot['instructor_id'] == $instructor->ID) ? ' selected' : '';
            $status_label = $instructor->post_status === 'pending' ? ' (Pending)' : '';
            $instructors_html .= '<option value="' . esc_attr($instructor->ID) . '"' . $selected . '>' . 
                                 esc_html($instructor->post_title . $status_label) . '</option>';
        }
        $slot['instructors_options'] = $instructors_html;
        
        wp_send_json_success($slot);
    }
    
    /**
     * Update slot via AJAX
     */
    public function update_slot() {
        check_ajax_referer('waza_slot_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'waza-booking'));
        }
        
        $slot_id = intval($_POST['slot_id']);
        $instructor_id = !empty($_POST['instructor_id']) ? intval($_POST['instructor_id']) : null;
        $start_date = sanitize_text_field($_POST['start_date']);
        $start_time = sanitize_text_field($_POST['start_time']);
        $end_time = sanitize_text_field($_POST['end_time']);
        $capacity = intval($_POST['capacity']);
        $price = floatval($_POST['price'] ?? 0);
        $location = sanitize_text_field($_POST['location']);
        $notes = sanitize_textarea_field($_POST['notes']);
        
        if (!$slot_id || !$start_date || !$start_time || !$end_time) {
            wp_send_json_error(__('Please fill in all required fields.', 'waza-booking'));
        }
        
        global $wpdb;
        
        $start_datetime = $start_date . ' ' . $start_time . ':00';
        $end_datetime = $start_date . ' ' . $end_time . ':00';
        
        $result = $wpdb->update(
            $wpdb->prefix . 'waza_slots',
            [
                'instructor_id' => $instructor_id,
                'start_datetime' => $start_datetime,
                'end_datetime' => $end_datetime,
                'capacity' => $capacity,
                'price' => $price,
                'location' => $location,
                'notes' => $notes
            ],
            ['id' => $slot_id],
            [
                $instructor_id ? '%d' : '%s',
                '%s',
                '%s',
                '%d',
                '%f',
                '%s',
                '%s'
            ],
            ['%d']
        );
        
        if ($result !== false) {
            wp_send_json_success(__('Slot updated successfully!', 'waza-booking'));
        } else {
            wp_send_json_error(__('Failed to update slot. Please try again.', 'waza-booking'));
        }
    }
    
    /**
     * Delete slot via AJAX
     */
    public function delete_slot() {
        check_ajax_referer('waza_slot_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'waza-booking'));
        }
        
        $slot_id = intval($_POST['slot_id']);
        
        if (!$slot_id) {
            wp_send_json_error(__('Invalid slot ID.', 'waza-booking'));
        }
        
        global $wpdb;
        
        // Check if slot has bookings
        $booking_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}waza_bookings 
            WHERE slot_id = %d AND booking_status IN ('confirmed', 'pending')
        ", $slot_id));
        
        if ($booking_count > 0) {
            wp_send_json_error(__('Cannot delete slot with existing bookings.', 'waza-booking'));
        }
        
        $result = $wpdb->delete(
            $wpdb->prefix . 'waza_slots',
            ['id' => $slot_id],
            ['%d']
        );
        
        if ($result) {
            wp_send_json_success(__('Slot deleted successfully!', 'waza-booking'));
        } else {
            wp_send_json_error(__('Failed to delete slot.', 'waza-booking'));
        }
    }
    
    /**
     * Bulk create slots via AJAX
     */
    public function bulk_create_slots() {
        check_ajax_referer('waza_slot_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'waza-booking'));
        }
        
        $activity_id = intval($_POST['activity_id']);
        $start_date = sanitize_text_field($_POST['date_range_start']);
        $end_date = sanitize_text_field($_POST['date_range_end']);
        $days = array_map('sanitize_text_field', $_POST['days'] ?? []);
        $time_slots = $_POST['time_slots'] ?? [];
        $capacity = intval($_POST['capacity']);
        $instructor_id = !empty($_POST['instructor_id']) ? intval($_POST['instructor_id']) : null;
        $price = floatval($_POST['price'] ?? 0);
        
        if (!$activity_id || !$start_date || !$end_date || empty($days) || empty($time_slots)) {
            wp_send_json_error(__('Please fill in all required fields.', 'waza-booking'));
        }
        
        global $wpdb;
        
        $created_count = 0;
        $skipped_count = 0;
        $conflicts = [];
        $current_date = strtotime($start_date);
        $end_timestamp = strtotime($end_date);
        
        $day_map = [
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
            'sunday' => 0
        ];
        
        while ($current_date <= $end_timestamp) {
            $day_of_week = date('w', $current_date);
            $day_name = array_search($day_of_week, $day_map);
            
            if (in_array($day_name, $days)) {
                $date_string = date('Y-m-d', $current_date);
                
                foreach ($time_slots as $time_slot) {
                    $start_time = sanitize_text_field($time_slot['start']);
                    $end_time = sanitize_text_field($time_slot['end']);
                    
                    if (!$start_time || !$end_time) continue;
                    
                    $start_datetime = $date_string . ' ' . $start_time . ':00';
                    $end_datetime = $date_string . ' ' . $end_time . ':00';
                    
                    // Check instructor availability if instructor is assigned
                    if ($instructor_id) {
                        $conflict = $wpdb->get_row($wpdb->prepare("
                            SELECT id, start_datetime, end_datetime 
                            FROM {$wpdb->prefix}waza_slots 
                            WHERE instructor_id = %d 
                            AND status != 'cancelled'
                            AND (
                                (start_datetime <= %s AND end_datetime > %s) OR
                                (start_datetime < %s AND end_datetime >= %s) OR
                                (start_datetime >= %s AND end_datetime <= %s)
                            )
                            LIMIT 1
                        ", $instructor_id, $start_datetime, $start_datetime, $end_datetime, $end_datetime, $start_datetime, $end_datetime));
                        
                        if ($conflict) {
                            $skipped_count++;
                            $conflicts[] = sprintf(
                                __('Skipped %s %s-%s (instructor already assigned to slot #%d)', 'waza-booking'),
                                date('M j, Y', strtotime($date_string)),
                                date('g:i A', strtotime($start_time)),
                                date('g:i A', strtotime($end_time)),
                                $conflict->id
                            );
                            continue; // Skip this slot
                        }
                    }
                    
                    $result = $wpdb->insert(
                        $wpdb->prefix . 'waza_slots',
                        [
                            'activity_id' => $activity_id,
                            'instructor_id' => $instructor_id,
                            'start_datetime' => $start_datetime,
                            'end_datetime' => $end_datetime,
                            'capacity' => $capacity,
                            'price' => $price,
                            'status' => 'available'
                        ],
                        ['%d', '%d', '%s', '%s', '%d', '%f', '%s']
                    );
                    
                    if ($result) {
                        $created_count++;
                    }
                }
            }
            
            $current_date = strtotime('+1 day', $current_date);
        }
        
        // Build response message
        $message_parts = [];
        
        if ($created_count > 0) {
            $message_parts[] = sprintf(
                __('%d slots created successfully!', 'waza-booking'),
                $created_count
            );
        }
        
        if ($skipped_count > 0) {
            $message_parts[] = sprintf(
                __('%d slots skipped due to instructor conflicts.', 'waza-booking'),
                $skipped_count
            );
        }
        
        if (!empty($conflicts)) {
            $message_parts[] = "\n\n" . __('Conflicts:', 'waza-booking') . "\n" . implode("\n", $conflicts);
        }
        
        if ($created_count > 0) {
            wp_send_json_success([
                'message' => implode(' ', $message_parts),
                'created' => $created_count,
                'skipped' => $skipped_count,
                'conflicts' => $conflicts
            ]);
        } else {
            $error_message = $skipped_count > 0 
                ? __('No slots were created. All slots conflicted with instructor\'s existing schedule.', 'waza-booking') . "\n\n" . implode("\n", $conflicts)
                : __('No slots were created. Please check your settings.', 'waza-booking');
            
            wp_send_json_error($error_message);
        }
    }
}