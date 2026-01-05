<?php
/**
 * Recent Bookings Widget
 * 
 * Shows latest bookings with quick details
 * 
 * @package WazaBooking\Admin\Widgets
 */

namespace WazaBooking\Admin\Widgets;

class RecentBookingsWidget implements DashboardWidget {
    
    public function get_title(): string {
        return __('Recent Bookings', 'waza-booking');
    }
    
    public function get_icon(): string {
        return 'dashicons-calendar-alt';
    }
    
    public function get_order(): int {
        return 50;
    }
    
    public function get_column_span(): int {
        return 2;
    }
    
    public function render(): void {
        $bookings = $this->get_recent_bookings();
        ?>
        <div class="waza-widget-content">
            <?php if (empty($bookings)): ?>
                <p class="waza-empty-state">
                    <span class="dashicons dashicons-info"></span>
                    <?php esc_html_e('No bookings yet.', 'waza-booking'); ?>
                    <br><a href="<?php echo admin_url('edit.php?post_type=waza_booking'); ?>" class="button button-small" style="margin-top: 10px;">
                        <?php esc_html_e('View All Bookings', 'waza-booking'); ?>
                    </a>
                </p>
            <?php else: ?>
                <table class="waza-bookings-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('User', 'waza-booking'); ?></th>
                            <th><?php esc_html_e('Slot', 'waza-booking'); ?></th>
                            <th><?php esc_html_e('Status', 'waza-booking'); ?></th>
                            <th><?php esc_html_e('Amount', 'waza-booking'); ?></th>
                            <th><?php esc_html_e('Date', 'waza-booking'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr class="waza-booking-row">
                                <td><?php echo esc_html($booking['user']); ?></td>
                                <td><?php echo esc_html($booking['slot']); ?></td>
                                <td>
                                    <span class="waza-status-badge waza-status-<?php echo esc_attr($booking['status']); ?>">
                                        <?php echo esc_html($booking['status_label']); ?>
                                    </span>
                                </td>
                                <td><strong><?php echo esc_html($booking['amount']); ?></strong></td>
                                <td><?php echo esc_html($booking['date']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <style>
        .waza-bookings-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .waza-bookings-table th,
        .waza-bookings-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #f0f0f1;
        }
        
        .waza-bookings-table th {
            font-size: 11px;
            text-transform: uppercase;
            color: #646970;
            font-weight: 600;
        }
        
        .waza-bookings-table td {
            font-size: 13px;
        }
        
        .waza-bookings-table tr:last-child td {
            border-bottom: none;
        }
        
        .waza-status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .waza-status-confirmed,
        .waza-status-completed { background: #d7f8da; color: #00a32a; }
        .waza-status-pending { background: #fef8e7; color: #dba617; }
        .waza-status-cancelled { background: #fbe7e7; color: #d63638; }
        
        .waza-empty-state {
            text-align: center;
            padding: 30px 20px;
            color: #646970;
        }
        
        .waza-empty-state .dashicons {
            font-size: 24px;
            width: 24px;
            height: 24px;
            opacity: 0.5;
            display: block;
            margin: 0 auto 8px;
        }
        </style>
        <?php
    }
    
    private function get_recent_bookings(): array {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'waza_bookings';
        $slots_table = $wpdb->prefix . 'waza_slots';
        
        // FIXED: using booking_status and total_amount, joining with slots table
        $results = $wpdb->get_results(
            "SELECT 
                b.id,
                b.user_id,
                b.slot_id,
                b.total_amount,
                b.booking_status,
                b.created_at,
                s.activity_id
            FROM {$bookings_table} b
            LEFT JOIN {$slots_table} s ON b.slot_id = s.id
            ORDER BY b.created_at DESC
            LIMIT 10",
            ARRAY_A
        );
        
        if (!$results) {
            return [];
        }
        
        $bookings = [];
        foreach ($results as $row) {
            $user = get_userdata($row['user_id']);
            $user_name = $user ? $user->display_name : __('Guest', 'waza-booking');
            
            // Get activity title
            $slot_title = __('Slot', 'waza-booking') . ' #' . $row['slot_id'];
            if ($row['activity_id']) {
                $activity = get_post($row['activity_id']);
                if ($activity) {
                    $slot_title = $activity->post_title;
                }
            }
            
            $status_labels = [
                'confirmed' => __('Confirmed', 'waza-booking'),
                'pending' => __('Pending', 'waza-booking'),
                'cancelled' => __('Cancelled', 'waza-booking'),
                'completed' => __('Completed', 'waza-booking'),
            ];
            
            $bookings[] = [
                'user' => $user_name,
                'slot' => $slot_title,
                'status' => $row['booking_status'],
                'status_label' => $status_labels[$row['booking_status']] ?? $row['booking_status'],
                'amount' => get_waza_currency_symbol() . number_format((float)$row['total_amount'], 2),
                'date' => date('M d, Y h:i A', strtotime($row['created_at'])),
            ];
        }
        
        return $bookings;
    }
}
