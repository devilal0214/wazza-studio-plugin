<?php
/**
 * Calendar Debug Script
 * 
 * This script tests:
 * 1. JavaScript localization
 * 2. Slot availability 
 * 3. AJAX endpoint functionality
 * 4. Calendar rendering
 */

// Load WordPress
require_once __DIR__ . '/../../../wp-load.php';

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar Debug - Waza Booking</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .debug-section {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .debug-section h2 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #4F46E5;
            padding-bottom: 10px;
        }
        .status-ok {
            color: #10B981;
            font-weight: bold;
        }
        .status-error {
            color: #EF4444;
            font-weight: bold;
        }
        .status-warning {
            color: #F59E0B;
            font-weight: bold;
        }
        pre {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .test-result {
            padding: 10px;
            margin: 10px 0;
            border-left: 4px solid;
            background: #f9fafb;
        }
        .test-result.pass {
            border-color: #10B981;
        }
        .test-result.fail {
            border-color: #EF4444;
        }
        .test-result.info {
            border-color: #3B82F6;
        }
    </style>
    <?php wp_head(); ?>
</head>
<body>

<h1>🔍 Waza Booking Calendar Debug Report</h1>

<!-- 1. JavaScript Localization Test -->
<div class="debug-section">
    <h2>1. JavaScript Localization Test</h2>
    <?php 
    $localization_test = array(
        'waza_frontend.ajax_url' => "typeof waza_frontend !== 'undefined' && waza_frontend.ajax_url",
        'waza_frontend.nonce' => "typeof waza_frontend !== 'undefined' && waza_frontend.nonce",
        'waza_frontend.calendar_settings' => "typeof waza_frontend !== 'undefined' && waza_frontend.calendar_settings"
    );
    ?>
    <div id="localization-results">
        <p>Testing JavaScript variables...</p>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        let results = '';
        
        // Test waza_frontend object
        if (typeof waza_frontend !== 'undefined') {
            results += '<div class="test-result pass"><strong>✓</strong> waza_frontend object exists</div>';
            
            if (waza_frontend.ajax_url) {
                results += '<div class="test-result pass"><strong>✓</strong> ajax_url: ' + waza_frontend.ajax_url + '</div>';
            } else {
                results += '<div class="test-result fail"><strong>✗</strong> ajax_url is missing!</div>';
            }
            
            if (waza_frontend.nonce) {
                results += '<div class="test-result pass"><strong>✓</strong> nonce: ' + waza_frontend.nonce.substring(0, 10) + '...</div>';
            } else {
                results += '<div class="test-result fail"><strong>✗</strong> nonce is missing!</div>';
            }
            
            if (waza_frontend.calendar_settings) {
                results += '<div class="test-result pass"><strong>✓</strong> calendar_settings exists</div>';
                results += '<pre>' + JSON.stringify(waza_frontend.calendar_settings, null, 2) + '</pre>';
            } else {
                results += '<div class="test-result fail"><strong>✗</strong> calendar_settings is missing!</div>';
            }
            
            results += '<h3>Full waza_frontend object:</h3>';
            results += '<pre>' + JSON.stringify(waza_frontend, null, 2) + '</pre>';
            
        } else {
            results += '<div class="test-result fail"><strong>✗</strong> waza_frontend object NOT FOUND!</div>';
            results += '<p>This means the JavaScript is not being localized correctly.</p>';
        }
        
        $('#localization-results').html(results);
    });
    </script>
</div>

<!-- 2. Database Slots Test -->
<div class="debug-section">
    <h2>2. Database Slots Test</h2>
    <?php
    global $wpdb;
    
    // Check for slots in the next 30 days
    $today = current_time('Y-m-d');
    $future = date('Y-m-d', strtotime('+30 days'));
    
    $args = array(
        'post_type' => 'waza_slot',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_waza_start_date',
                'value' => array($today, $future),
                'compare' => 'BETWEEN',
                'type' => 'DATE'
            )
        )
    );
    
    $query = new WP_Query($args);
    $slot_count = $query->found_posts;
    
    if ($slot_count > 0) {
        echo '<div class="test-result pass">';
        echo '<strong>✓</strong> Found ' . $slot_count . ' slots in the next 30 days';
        echo '</div>';
        
        echo '<h3>Sample Slots:</h3>';
        echo '<table style="width:100%; border-collapse: collapse;">';
        echo '<tr style="background:#f3f4f6; font-weight:bold;">';
        echo '<th style="padding:8px; text-align:left; border:1px solid #ddd;">ID</th>';
        echo '<th style="padding:8px; text-align:left; border:1px solid #ddd;">Date</th>';
        echo '<th style="padding:8px; text-align:left; border:1px solid #ddd;">Time</th>';
        echo '<th style="padding:8px; text-align:left; border:1px solid #ddd;">Activity</th>';
        echo '<th style="padding:8px; text-align:left; border:1px solid #ddd;">Instructor</th>';
        echo '<th style="padding:8px; text-align:left; border:1px solid #ddd;">Capacity</th>';
        echo '<th style="padding:8px; text-align:left; border:1px solid #ddd;">Booked</th>';
        echo '</tr>';
        
        $count = 0;
        while ($query->have_posts() && $count < 10) {
            $query->the_post();
            $id = get_the_ID();
            $start_date = get_post_meta($id, '_waza_start_date', true);
            $start_time = get_post_meta($id, '_waza_start_time', true);
            $end_time = get_post_meta($id, '_waza_end_time', true);
            $activity_id = get_post_meta($id, '_waza_activity_id', true);
            $instructor_id = get_post_meta($id, '_waza_instructor_id', true);
            $capacity = get_post_meta($id, '_waza_capacity', true);
            $booked = get_post_meta($id, '_waza_booked_seats', true);
            
            $activity_title = $activity_id ? get_the_title($activity_id) : 'N/A';
            $instructor_name = $instructor_id ? get_the_title($instructor_id) : 'N/A';
            
            echo '<tr>';
            echo '<td style="padding:8px; border:1px solid #ddd;">' . $id . '</td>';
            echo '<td style="padding:8px; border:1px solid #ddd;">' . $start_date . '</td>';
            echo '<td style="padding:8px; border:1px solid #ddd;">' . $start_time . ' - ' . $end_time . '</td>';
            echo '<td style="padding:8px; border:1px solid #ddd;">' . esc_html($activity_title) . '</td>';
            echo '<td style="padding:8px; border:1px solid #ddd;">' . esc_html($instructor_name) . '</td>';
            echo '<td style="padding:8px; border:1px solid #ddd;">' . $capacity . '</td>';
            echo '<td style="padding:8px; border:1px solid #ddd;">' . $booked . '</td>';
            echo '</tr>';
            $count++;
        }
        
        echo '</table>';
        
        wp_reset_postdata();
    } else {
        echo '<div class="test-result fail">';
        echo '<strong>✗</strong> No slots found in the next 30 days!';
        echo '</div>';
        echo '<p>You need to create some slots first. Use the admin panel to create slots or run the seed_slots.php script.</p>';
    }
    ?>
</div>

<!-- 3. AJAX Endpoint Test -->
<div class="debug-section">
    <h2>3. AJAX Endpoint Test</h2>
    <p>Testing if the AJAX endpoint for calendar loading works...</p>
    
    <button id="test-ajax" class="button" style="padding:10px 20px; background:#4F46E5; color:white; border:none; border-radius:4px; cursor:pointer;">
        Test AJAX Load Calendar
    </button>
    
    <div id="ajax-results" style="margin-top:20px;"></div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#test-ajax').on('click', function() {
            const $btn = $(this);
            const $results = $('#ajax-results');
            
            $btn.prop('disabled', true).text('Testing...');
            $results.html('<p>Loading...</p>');
            
            const testData = {
                action: 'waza_load_calendar',
                year: new Date().getFullYear(),
                month: new Date().getMonth() + 1,
                activity_id: '',
                nonce: typeof waza_frontend !== 'undefined' ? waza_frontend.nonce : ''
            };
            
            $.ajax({
                url: typeof waza_frontend !== 'undefined' ? waza_frontend.ajax_url : '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: testData,
                success: function(response) {
                    $btn.prop('disabled', false).text('Test AJAX Load Calendar');
                    
                    let html = '';
                    if (response.success) {
                        html += '<div class="test-result pass">';
                        html += '<strong>✓</strong> AJAX request successful!';
                        html += '</div>';
                        
                        html += '<h3>Response Data:</h3>';
                        html += '<pre>' + JSON.stringify(response.data, null, 2) + '</pre>';
                        
                        html += '<h3>Calendar HTML Preview:</h3>';
                        html += '<div style="max-height:400px; overflow:auto; border:1px solid #ddd; padding:10px;">';
                        html += response.data.calendar;
                        html += '</div>';
                    } else {
                        html += '<div class="test-result fail">';
                        html += '<strong>✗</strong> AJAX request failed!';
                        html += '</div>';
                        html += '<pre>' + JSON.stringify(response, null, 2) + '</pre>';
                    }
                    
                    $results.html(html);
                },
                error: function(xhr, status, error) {
                    $btn.prop('disabled', false).text('Test AJAX Load Calendar');
                    
                    let html = '<div class="test-result fail">';
                    html += '<strong>✗</strong> AJAX error: ' + error;
                    html += '</div>';
                    html += '<pre>Status: ' + status + '\n';
                    html += 'Error: ' + error + '\n';
                    html += 'Response: ' + xhr.responseText + '</pre>';
                    
                    $results.html(html);
                }
            });
        });
    });
    </script>
</div>

<!-- 4. Live Calendar Test -->
<div class="debug-section">
    <h2>4. Live Calendar Test</h2>
    <p>This is a live calendar using the shortcode:</p>
    
    <?php echo do_shortcode('[waza_booking_calendar]'); ?>
</div>

<!-- 5. Calendar Settings Check -->
<div class="debug-section">
    <h2>5. Calendar Settings Check</h2>
    <?php
    $settings = array(
        'Primary Color' => get_option('waza_calendar_primary_color', '#4F46E5'),
        'Start of Week' => get_option('waza_calendar_start_of_week', '0'),
        'Time Format' => get_option('waza_calendar_time_format', '24'),
        'Show Instructor' => get_option('waza_calendar_show_instructor', '1'),
        'Show Price' => get_option('waza_calendar_show_price', '1'),
        'Slots Per Day' => get_option('waza_calendar_slots_per_day', '3')
    );
    
    echo '<table style="width:100%; border-collapse: collapse;">';
    foreach ($settings as $label => $value) {
        echo '<tr>';
        echo '<td style="padding:8px; border:1px solid #ddd; font-weight:bold;">' . $label . '</td>';
        echo '<td style="padding:8px; border:1px solid #ddd;">' . esc_html($value) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    ?>
</div>

<!-- 6. Console Log Monitor -->
<div class="debug-section">
    <h2>6. Browser Console Monitor</h2>
    <p>Open your browser's developer console (F12) to see JavaScript logs and errors.</p>
    <div id="console-monitor" style="background:#1e1e1e; color:#d4d4d4; padding:15px; border-radius:4px; font-family:monospace; max-height:300px; overflow:auto;">
        <div style="color:#4FC3F7;">// Monitoring console...</div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Log initial state
        console.log('%c=== Waza Calendar Debug ===', 'color: #4F46E5; font-size: 16px; font-weight: bold;');
        console.log('waza_frontend object:', typeof waza_frontend !== 'undefined' ? waza_frontend : 'NOT FOUND');
        console.log('Calendar grid found:', $('.waza-calendar-grid').length > 0);
        console.log('Navigation buttons found:', $('.waza-nav-button').length);
        console.log('View dropdown found:', $('.waza-calendar-view').length);
        
        // Monitor for AJAX calls
        $(document).ajaxSend(function(event, xhr, settings) {
            if (settings.url.includes('admin-ajax.php')) {
                const data = settings.data || '';
                if (data.includes('waza_')) {
                    console.log('%cAJAX Request:', 'color: #10B981', settings.url);
                    console.log('Data:', settings.data);
                }
            }
        });
        
        $(document).ajaxComplete(function(event, xhr, settings) {
            if (settings.url.includes('admin-ajax.php')) {
                const data = settings.data || '';
                if (data.includes('waza_')) {
                    console.log('%cAJAX Response:', 'color: #3B82F6', xhr.status, xhr.statusText);
                    try {
                        console.log('Data:', JSON.parse(xhr.responseText));
                    } catch(e) {
                        console.log('Raw:', xhr.responseText);
                    }
                }
            }
        });
    });
    </script>
</div>

<?php wp_footer(); ?>
</body>
</html>
