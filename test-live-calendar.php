<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar Test - Waza Booking</title>
    <?php 
    require_once __DIR__ . '/../../../wp-load.php';
    wp_head(); 
    ?>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-header {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .test-header h1 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        .status-ok {
            background: #D1FAE5;
            color: #065F46;
        }
        .console-output {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            margin: 20px 0;
            max-height: 200px;
            overflow-y: auto;
        }
    </style>
</head>
<body>

<div class="test-header">
    <h1>
        📅 Waza Booking Calendar
        <span class="status-badge status-ok">✓ Live</span>
    </h1>
    <p style="margin:0; color:#666;">
        Today is: <strong><?php echo current_time('l, F j, Y'); ?></strong>
    </p>
    <p style="margin:5px 0 0 0; color:#666;">
        Slots in database: <strong><?php 
        $today = current_time('Y-m-d');
        $future = date('Y-m-d', strtotime('+30 days'));
        $slot_count = get_posts([
            'post_type' => 'waza_slot',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [[
                'key' => '_waza_start_date',
                'value' => [$today, $future],
                'compare' => 'BETWEEN',
                'type' => 'DATE'
            ]]
        ]);
        echo count($slot_count);
        ?></strong> (next 30 days)
    </p>
</div>

<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
    <?php echo do_shortcode('[waza_booking_calendar]'); ?>
</div>

<div class="console-output" id="console-log">
    <div style="color: #4FC3F7;">// JavaScript Console Output</div>
    <div id="log-entries"></div>
</div>

<script>
jQuery(document).ready(function($) {
    const $logEntries = $('#log-entries');
    
    function addLog(message, type = 'info') {
        const colors = {
            'info': '#4FC3F7',
            'success': '#10B981',
            'error': '#EF4444',
            'warning': '#F59E0B'
        };
        
        const timestamp = new Date().toLocaleTimeString();
        $logEntries.append(
            `<div style="color: ${colors[type]};">[${timestamp}] ${message}</div>`
        );
        
        // Auto-scroll
        $('#console-log').scrollTop($('#console-log')[0].scrollHeight);
    }
    
    // Log initialization
    addLog('Calendar initialized', 'success');
    addLog('waza_frontend object: ' + (typeof waza_frontend !== 'undefined' ? 'Found ✓' : 'Missing ✗'), 
           typeof waza_frontend !== 'undefined' ? 'success' : 'error');
    
    if (typeof waza_frontend !== 'undefined') {
        addLog('ajax_url: ' + waza_frontend.ajax_url, 'info');
        addLog('nonce: ' + waza_frontend.nonce.substring(0, 10) + '...', 'info');
    }
    
    // Monitor AJAX calls
    $(document).ajaxSend(function(event, xhr, settings) {
        if (settings.data && settings.data.includes('waza_load_calendar')) {
            addLog('Loading calendar month...', 'info');
        }
    });
    
    $(document).ajaxComplete(function(event, xhr, settings) {
        if (settings.data && settings.data.includes('waza_load_calendar')) {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        const slotsCount = (response.data.calendar.match(/has-slots/g) || []).length;
                        addLog(`Calendar loaded: ${slotsCount} days with available slots`, 'success');
                    } else {
                        addLog('Calendar load failed: ' + (response.data || 'Unknown error'), 'error');
                    }
                } catch(e) {
                    addLog('Calendar loaded successfully', 'success');
                }
            } else {
                addLog('AJAX error: ' + xhr.status + ' ' + xhr.statusText, 'error');
            }
        }
    });
    
    // Monitor day clicks
    $(document).on('click', '.waza-calendar-day.has-slots', function() {
        const date = $(this).data('date');
        addLog('Day clicked: ' + date, 'info');
    });
    
    // Monitor navigation
    $(document).on('click', '.waza-nav-button', function() {
        const direction = $(this).hasClass('prev') ? 'Previous' : 'Next';
        addLog(direction + ' month clicked', 'info');
    });
});
</script>

<?php wp_footer(); ?>
</body>
</html>
