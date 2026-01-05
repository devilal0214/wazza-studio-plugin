<?php
/**
 * Quick Calendar Error Check
 */

require_once __DIR__ . '/../../../wp-load.php';

// Turn on error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Calendar Error Test</title>
    <?php wp_head(); ?>
    <style>
        body { padding: 20px; font-family: Arial, sans-serif; }
        .error { background: #fee; color: #c00; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .success { background: #efe; color: #060; padding: 15px; border-radius: 4px; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Calendar Loading Test</h1>
    
    <div class="test-section">
        <h2>1. PHP Error Check</h2>
        <?php
        ob_start();
        try {
            echo do_shortcode('[waza_booking_calendar]');
            $output = ob_get_clean();
            
            if (strpos($output, 'waza-calendar-grid') !== false) {
                echo '<div class="success">✓ Calendar HTML generated successfully</div>';
            } else {
                echo '<div class="error">✗ Calendar HTML missing expected elements</div>';
                echo '<pre>' . htmlspecialchars($output) . '</pre>';
            }
            
            echo $output;
            
        } catch (Exception $e) {
            ob_end_clean();
            echo '<div class="error">PHP Error: ' . $e->getMessage() . '</div>';
            echo '<pre>' . $e->getTraceAsString() . '</pre>';
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>2. JavaScript Error Check</h2>
        <div id="js-status"></div>
        <script>
        jQuery(document).ready(function($) {
            let errors = [];
            let success = [];
            
            // Check waza_frontend
            if (typeof waza_frontend !== 'undefined') {
                success.push('✓ waza_frontend object exists');
                if (waza_frontend.ajax_url) {
                    success.push('✓ ajax_url: ' + waza_frontend.ajax_url);
                } else {
                    errors.push('✗ ajax_url missing');
                }
                if (waza_frontend.nonce) {
                    success.push('✓ nonce exists');
                } else {
                    errors.push('✗ nonce missing');
                }
            } else {
                errors.push('✗ waza_frontend object not found');
            }
            
            // Check calendar grid
            if ($('.waza-calendar-grid').length > 0) {
                success.push('✓ Calendar grid element found');
            } else {
                errors.push('✗ Calendar grid element not found');
            }
            
            // Display results
            let html = '';
            if (success.length > 0) {
                html += '<div class="success">' + success.join('<br>') + '</div>';
            }
            if (errors.length > 0) {
                html += '<div class="error">' + errors.join('<br>') + '</div>';
            }
            
            $('#js-status').html(html);
            
            // Monitor for AJAX errors
            $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
                if (settings.data && settings.data.includes('waza_')) {
                    $('#js-status').append(
                        '<div class="error">AJAX Error: ' + thrownError + 
                        '<br>Status: ' + jqxhr.status + 
                        '<br>Response: ' + jqxhr.responseText + '</div>'
                    );
                }
            });
        });
        </script>
    </div>
    
    <?php wp_footer(); ?>
</body>
</html>
