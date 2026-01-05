<?php
/**
 * QR Activity Widget
 * 
 * Shows QR code scan activity and verification statistics
 * 
 * @package WazaBooking\Admin\Widgets
 */

namespace WazaBooking\Admin\Widgets;

class QRActivityWidget implements DashboardWidget {
    
    public function get_title(): string {
        return __('QR Code Activity', 'waza-booking');
    }
    
    public function get_icon(): string {
        return 'dashicons-smartphone';
    }
    
    public function get_order(): int {
        return 40;
    }
    
    public function get_column_span(): int {
        return 2;
    }
    
    public function render(): void {
        $activity = $this->get_qr_activity();
        ?>
        <div class="waza-widget-content">
            <div class="waza-qr-stats-grid">
                <div class="waza-qr-stat">
                    <div class="waza-qr-stat-value"><?php echo esc_html($activity['total_scans']); ?></div>
                    <div class="waza-qr-stat-label"><?php esc_html_e('Total Scans', 'waza-booking'); ?></div>
                </div>
                <div class="waza-qr-stat">
                    <div class="waza-qr-stat-value"><?php echo esc_html($activity['active_tokens']); ?></div>
                    <div class="waza-qr-stat-label"><?php esc_html_e('Active QR Codes', 'waza-booking'); ?></div>
                </div>
                <div class="waza-qr-stat">
                    <div class="waza-qr-stat-value"><?php echo esc_html($activity['today_scans']); ?></div>
                    <div class="waza-qr-stat-label"><?php esc_html_e('Today', 'waza-booking'); ?></div>
                </div>
            </div>
            
            <div class="waza-recent-scans">
                <h4><?php esc_html_e('Recent Scans', 'waza-booking'); ?></h4>
                <?php if (empty($activity['recent_scans'])): ?>
                    <p class="waza-empty-state">
                        <span class="dashicons dashicons-info"></span>
                        <?php esc_html_e('No QR scans yet.', 'waza-booking'); ?>
                    </p>
                <?php else: ?>
                    <div class="waza-scans-list">
                        <?php foreach ($activity['recent_scans'] as $scan): ?>
                            <div class="waza-scan-item">
                                <span class="dashicons dashicons-yes waza-scan-icon <?php echo esc_attr($scan['class']); ?>"></span>
                                <div class="waza-scan-details">
                                    <div class="waza-scan-user"><?php echo esc_html($scan['user']); ?></div>
                                    <div class="waza-scan-time"><?php echo esc_html($scan['time']); ?></div>
                                </div>
                                <span class="waza-scan-status <?php echo esc_attr($scan['class']); ?>">
                                    <?php echo esc_html($scan['status']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <style>
        .waza-qr-stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .waza-qr-stat {
            text-align: center;
            padding: 15px;
            background: #f6f7f7;
            border-radius: 6px;
        }
        
        .waza-qr-stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #2271b1;
            margin-bottom: 4px;
        }
        
        .waza-qr-stat-label {
            font-size: 11px;
            color: #646970;
            text-transform: uppercase;
        }
        
        .waza-recent-scans h4 {
            font-size: 13px;
            font-weight: 600;
            margin: 0 0 12px 0;
            color: #1d2327;
        }
        
        .waza-scans-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .waza-scan-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px;
            background: #f6f7f7;
            border-radius: 6px;
        }
        
        .waza-scan-icon {
            font-size: 20px;
            width: 20px;
            height: 20px;
        }
        
        .waza-scan-icon.verified { color: #00a32a; }
        .waza-scan-icon.failed { color: #d63638; }
        
        .waza-scan-details {
            flex: 1;
        }
        
        .waza-scan-user {
            font-weight: 600;
            font-size: 13px;
        }
        
        .waza-scan-time {
            font-size: 11px;
            color: #646970;
        }
        
        .waza-scan-status {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .waza-scan-status.verified { color: #00a32a; }
        .waza-scan-status.failed { color: #d63638; }
        
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
    
    private function get_qr_activity(): array {
        global $wpdb;
        
        $qr_table = $wpdb->prefix . 'waza_qr_tokens';
        
        // Total scans - FIXED: using used_count > 0 instead of scanned_at
        $total_scans = $wpdb->get_var(
            "SELECT SUM(used_count) FROM {$qr_table} WHERE used_count > 0"
        );
        $total_scans = $total_scans !== null ? (int)$total_scans : 0;
        
        // Active tokens (not expired and still has uses left)
        $now = current_time('mysql');
        $active_tokens = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$qr_table} WHERE is_active = 1 AND expires_at > %s",
            $now
        ));
        $active_tokens = $active_tokens !== null ? (int)$active_tokens : 0;
        
        // Today's scans
        $today = current_time('Y-m-d');
        $today_scans = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(used_count) FROM {$qr_table} WHERE DATE(last_used_at) = %s",
            $today
        ));
        $today_scans = $today_scans !== null ? (int)$today_scans : 0;
        
        // Recent scans - FIXED: using last_used_at and used_count
        $recent = $wpdb->get_results(
            "SELECT 
                q.id,
                q.booking_id,
                q.last_used_at,
                q.used_count,
                q.max_uses,
                b.user_id
            FROM {$qr_table} q
            LEFT JOIN {$wpdb->prefix}waza_bookings b ON q.booking_id = b.id
            WHERE q.last_used_at IS NOT NULL
            ORDER BY q.last_used_at DESC
            LIMIT 5",
            ARRAY_A
        );
        
        $recent_scans = [];
        if ($recent) {
            foreach ($recent as $scan) {
                $user = get_userdata($scan['user_id']);
                $user_name = $user ? $user->display_name : __('Guest', 'waza-booking');
                
                // Consider it verified if used_count is within max_uses
                $verified = (int)$scan['used_count'] <= (int)$scan['max_uses'];
                
                $recent_scans[] = [
                    'user' => $user_name,
                    'time' => human_time_diff(strtotime($scan['last_used_at']), current_time('timestamp')) . ' ' . __('ago', 'waza-booking'),
                    'status' => $verified ? __('Verified', 'waza-booking') : __('Exceeded', 'waza-booking'),
                    'class' => $verified ? 'verified' : 'failed',
                ];
            }
        }
        
        return [
            'total_scans' => number_format($total_scans),
            'active_tokens' => number_format($active_tokens),
            'today_scans' => number_format($today_scans),
            'recent_scans' => $recent_scans,
        ];
    }
}
