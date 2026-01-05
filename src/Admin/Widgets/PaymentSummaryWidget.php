<?php
/**
 * Payment Summary Widget
 * 
 * Shows payment statistics and paid users list
 * 
 * @package WazaBooking\Admin\Widgets
 */

namespace WazaBooking\Admin\Widgets;

class PaymentSummaryWidget implements DashboardWidget {
    
    public function get_title(): string {
        return __('Payment Summary', 'waza-booking');
    }
    
    public function get_icon(): string {
        return 'dashicons-money-alt';
    }
    
    public function get_order(): int {
        return 30;
    }
    
    public function get_column_span(): int {
        return 2;
    }
    
    public function render(): void {
        $summary = $this->get_payment_summary();
        ?>
        <div class="waza-widget-content">
            <div class="waza-payment-stats">
                <div class="waza-payment-stat">
                    <div class="waza-payment-stat-value waza-success"><?php echo esc_html($summary['paid_count']); ?></div>
                    <div class="waza-payment-stat-label"><?php esc_html_e('Paid', 'waza-booking'); ?></div>
                </div>
                <div class="waza-payment-stat">
                    <div class="waza-payment-stat-value waza-warning"><?php echo esc_html($summary['pending_count']); ?></div>
                    <div class="waza-payment-stat-label"><?php esc_html_e('Pending', 'waza-booking'); ?></div>
                </div>
                <div class="waza-payment-stat">
                    <div class="waza-payment-stat-value waza-error"><?php echo esc_html($summary['failed_count']); ?></div>
                    <div class="waza-payment-stat-label"><?php esc_html_e('Failed', 'waza-booking'); ?></div>
                </div>
            </div>
            
            <div class="waza-recent-payments">
                <h4><?php esc_html_e('Recent Payments', 'waza-booking'); ?></h4>
                <?php if (empty($summary['recent_payments'])): ?>
                    <p class="waza-empty-state">
                        <span class="dashicons dashicons-info"></span>
                        <?php esc_html_e('No recent payments.', 'waza-booking'); ?>
                    </p>
                <?php else: ?>
                    <table class="waza-payments-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('User', 'waza-booking'); ?></th>
                                <th><?php esc_html_e('Amount', 'waza-booking'); ?></th>
                                <th><?php esc_html_e('Status', 'waza-booking'); ?></th>
                                <th><?php esc_html_e('Date', 'waza-booking'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($summary['recent_payments'] as $payment): ?>
                                <tr class="waza-payment-row">
                                    <td><?php echo esc_html($payment['user_name']); ?></td>
                                    <td><strong><?php echo esc_html($payment['amount']); ?></strong></td>
                                    <td>
                                        <span class="waza-status-badge waza-status-<?php echo esc_attr($payment['status']); ?>">
                                            <?php echo esc_html($payment['status_label']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html($payment['date']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <style>
        .waza-payment-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .waza-payment-stat {
            text-align: center;
            padding: 15px;
            background: #f6f7f7;
            border-radius: 6px;
        }
        
        .waza-payment-stat-value {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .waza-payment-stat-value.waza-success { color: #00a32a; }
        .waza-payment-stat-value.waza-warning { color: #dba617; }
        .waza-payment-stat-value.waza-error { color: #d63638; }
        
        .waza-payment-stat-label {
            font-size: 11px;
            color: #646970;
            text-transform: uppercase;
        }
        
        .waza-recent-payments h4 {
            font-size: 13px;
            font-weight: 600;
            margin: 0 0 12px 0;
            color: #1d2327;
        }
        
        .waza-payments-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .waza-payments-table th,
        .waza-payments-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #f0f0f1;
        }
        
        .waza-payments-table th {
            font-size: 11px;
            text-transform: uppercase;
            color: #646970;
            font-weight: 600;
        }
        
        .waza-payments-table td {
            font-size: 13px;
        }
        
        .waza-payments-table tr:last-child td {
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
        
        .waza-status-completed { background: #d7f8da; color: #00a32a; }
        .waza-status-pending { background: #fef8e7; color: #dba617; }
        .waza-status-failed { background: #fbe7e7; color: #d63638; }
        
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
    
    private function get_payment_summary(): array {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'waza_bookings';
        
        // Payment counts by status
        $paid_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$bookings_table} WHERE payment_status = 'completed'"
        );
        $paid_count = $paid_count !== null ? (int)$paid_count : 0;
        
        $pending_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$bookings_table} WHERE payment_status = 'pending'"
        );
        $pending_count = $pending_count !== null ? (int)$pending_count : 0;
        
        $failed_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$bookings_table} WHERE payment_status = 'failed'"
        );
        $failed_count = $failed_count !== null ? (int)$failed_count : 0;
        
        // Recent payments - FIXED: using total_amount
        $recent = $wpdb->get_results(
            "SELECT 
                b.id,
                b.user_id,
                b.total_amount,
                b.payment_status,
                b.created_at
            FROM {$bookings_table} b
            WHERE b.payment_status IN ('completed', 'pending', 'failed')
            ORDER BY b.created_at DESC
            LIMIT 5",
            ARRAY_A
        );
        
        $recent_payments = [];
        if ($recent) {
            foreach ($recent as $payment) {
                $user = get_userdata($payment['user_id']);
                $user_name = $user ? $user->display_name : __('Guest', 'waza-booking');
                
                $status_labels = [
                    'completed' => __('Paid', 'waza-booking'),
                    'pending' => __('Pending', 'waza-booking'),
                    'failed' => __('Failed', 'waza-booking'),
                ];
                
                $recent_payments[] = [
                    'user_name' => $user_name,
                    'amount' => get_waza_currency_symbol() . number_format((float)$payment['total_amount'], 2),
                    'status' => $payment['payment_status'],
                    'status_label' => $status_labels[$payment['payment_status']] ?? $payment['payment_status'],
                    'date' => date('M d, Y', strtotime($payment['created_at'])),
                ];
            }
        }
        
        return [
            'paid_count' => number_format($paid_count),
            'pending_count' => number_format($pending_count),
            'failed_count' => number_format($failed_count),
            'recent_payments' => $recent_payments,
        ];
    }
}
