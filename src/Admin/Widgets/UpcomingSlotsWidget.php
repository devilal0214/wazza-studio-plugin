<?php
/**
 * Upcoming Slots Widget
 * 
 * Shows next 5 upcoming slots with attendance info
 * 
 * @package WazaBooking\Admin\Widgets
 */

namespace WazaBooking\Admin\Widgets;

class UpcomingSlotsWidget implements DashboardWidget {
    
    public function get_title(): string {
        return __('Upcoming Slots', 'waza-booking');
    }
    
    public function get_icon(): string {
        return 'dashicons-clock';
    }
    
    public function get_order(): int {
        return 20;
    }
    
    public function get_column_span(): int {
        return 2;
    }
    
    public function render(): void {
        $slots = $this->get_upcoming_slots();
        ?>
        <div class="waza-widget-content">
            <?php if (empty($slots)): ?>
                <p class="waza-empty-state">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php esc_html_e('No upcoming slots scheduled.', 'waza-booking'); ?>
                    <br><a href="<?php echo admin_url('post-new.php?post_type=waza_slot'); ?>" class="button button-small" style="margin-top: 10px;">
                        <?php esc_html_e('Create Slot', 'waza-booking'); ?>
                    </a>
                </p>
            <?php else: ?>
                <div class="waza-slots-list">
                    <?php foreach ($slots as $slot): ?>
                        <a href="<?php echo get_edit_post_link($slot['id']); ?>" class="waza-slot-item waza-slot-clickable">
                            <div class="waza-slot-date">
                                <div class="waza-slot-day"><?php echo esc_html($slot['day']); ?></div>
                                <div class="waza-slot-month"><?php echo esc_html($slot['month']); ?></div>
                            </div>
                            <div class="waza-slot-details">
                                <div class="waza-slot-title"><?php echo esc_html($slot['title']); ?></div>
                                <div class="waza-slot-time">
                                    <span class="dashicons dashicons-clock"></span>
                                    <?php echo esc_html($slot['time']); ?>
                                </div>
                            </div>
                            <div class="waza-slot-attendance">
                                <div class="waza-attendance-count">
                                    <?php echo esc_html($slot['attendance']); ?>/<?php echo esc_html($slot['capacity']); ?>
                                </div>
                                <div class="waza-attendance-label"><?php esc_html_e('Booked', 'waza-booking'); ?></div>
                                <div class="waza-progress-bar">
                                    <div class="waza-progress-fill" style="width: <?php echo esc_attr($slot['percentage']); ?>%"></div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <style>
        .waza-slots-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .waza-slot-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px;
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            text-decoration: none;
            color: inherit;
            transition: all 0.2s;
        }
        
        .waza-slot-clickable:hover {
            background: #f0f0f1;
            border-color: #8c8f94;
        }
        
        .waza-slot-date {
            background: #f6f7f7;
            color: #1d2327;
            border: 1px solid #dcdcde;
            border-radius: 4px;
            padding: 8px 12px;
            text-align: center;
            min-width: 50px;
        }
        
        .waza-slot-day {
            font-size: 20px;
            font-weight: 700;
            line-height: 1;
        }
        
        .waza-slot-month {
            font-size: 11px;
            text-transform: uppercase;
            margin-top: 2px;
        }
        
        .waza-slot-details {
            flex: 1;
        }
        
        .waza-slot-title {
            font-weight: 600;
            margin-bottom: 4px;
            color: #2271b1;
        }
        
        .waza-slot-time {
            font-size: 12px;
            color: #646970;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .waza-slot-time .dashicons {
            font-size: 14px;
            width: 14px;
            height: 14px;
        }
        
        .waza-slot-attendance {
            text-align: right;
            min-width: 80px;
        }
        
        .waza-attendance-count {
            font-size: 14px;
            font-weight: 600;
            color: #1d2327;
        }
        
        .waza-attendance-label {
            font-size: 10px;
            color: #646970;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        
        .waza-progress-bar {
            width: 100%;
            height: 4px;
            background: #f0f0f1;
            border-radius: 2px;
            overflow: hidden;
            margin-top: 4px;
        }
        
        .waza-progress-fill {
            height: 100%;
            background: #2271b1;
            transition: width 0.3s;
        }
        
        .waza-empty-state {
            text-align: center;
            padding: 30px 20px;
            color: #646970;
        }
        
        .waza-empty-state .dashicons {
            font-size: 32px;
            width: 32px;
            height: 32px;
            opacity: 0.5;
            display: block;
            margin: 0 auto 10px;
        }
        </style>
        <?php
    }
    
    private function get_upcoming_slots(): array {
        $today = current_time('Y-m-d');
        
        $args = [
            'post_type' => 'waza_slot',
            'post_status' => 'publish',
            'posts_per_page' => 5,
            'meta_key' => '_waza_start_date',
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_waza_start_date',
                    'value' => $today,
                    'compare' => '>=',
                    'type' => 'DATE'
                ]
            ]
        ];
        
        $query = new \WP_Query($args);
        
        if (!$query->have_posts()) {
            return [];
        }
        
        $slots = [];
        
        foreach ($query->posts as $post) {
            $start_date = get_post_meta($post->ID, '_waza_start_date', true);
            $start_time = get_post_meta($post->ID, '_waza_start_time', true);
            $end_time = get_post_meta($post->ID, '_waza_end_time', true);
            $capacity = (int) get_post_meta($post->ID, '_waza_capacity', true);
            $booked = (int) get_post_meta($post->ID, '_waza_booked_seats', true);
            $activity_id = get_post_meta($post->ID, '_waza_activity_id', true);
            $activity = get_post($activity_id);
            
            // Format datetime
            $datetime_str = $start_date . ' ' . $start_time;
            $timestamp = strtotime($datetime_str);
            $end_timestamp = strtotime($start_date . ' ' . $end_time); // Assuming same day
            
            $slots[] = [
                'id' => $post->ID,
                'title' => $activity ? $activity->post_title : $post->post_title,
                'day' => date('d', $timestamp),
                'month' => date('M', $timestamp),
                'time' => date('h:i A', $timestamp) . ' - ' . date('h:i A', $end_timestamp),
                'attendance' => $booked,
                'capacity' => $capacity,
                'percentage' => $capacity > 0 ? min(100, round(($booked / $capacity) * 100)) : 0,
            ];
        }
        
        return $slots;
    }
}
