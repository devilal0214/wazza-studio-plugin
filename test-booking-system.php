<?php
/**
 * Test Booking System
 * 
 * This file demonstrates the complete Waza Booking system functionality.
 * Upload this as a WordPress page or template to test the booking flow.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="waza-test-page">
    <div class="container">
        <h1>Waza Booking System Test</h1>
        
        <!-- Test 1: Activity List -->
        <section class="test-section">
            <h2>1. Activities List</h2>
            <p>This should display all available activities:</p>
            <div class="test-output">
                <?php echo do_shortcode('[waza_activities_list]'); ?>
            </div>
        </section>
        
        <!-- Test 2: Activity Grid -->
        <section class="test-section">
            <h2>2. Activity Grid</h2>
            <p>This should display activities in a grid format:</p>
            <div class="test-output">
                <?php echo do_shortcode('[waza_activity_grid]'); ?>
            </div>
        </section>
        
        <!-- Test 3: Booking Calendar -->
        <section class="test-section">
            <h2>3. Booking Calendar</h2>
            <p>This should display an interactive calendar for booking:</p>
            <div class="test-output">
                <?php echo do_shortcode('[waza_booking_calendar]'); ?>
            </div>
        </section>
        
        <!-- Test 4: Featured Activities -->
        <section class="test-section">
            <h2>4. Featured Activities</h2>
            <p>This should display featured activities:</p>
            <div class="test-output">
                <?php echo do_shortcode('[waza_featured_activities]'); ?>
            </div>
        </section>
        
        <!-- Test 5: Instructors List -->
        <section class="test-section">
            <h2>5. Instructors List</h2>
            <p>This should display all instructors:</p>
            <div class="test-output">
                <?php echo do_shortcode('[waza_instructors_list]'); ?>
            </div>
        </section>
        
        <!-- Test 6: Activity Search -->
        <section class="test-section">
            <h2>6. Activity Search</h2>
            <p>This should display a search form for activities:</p>
            <div class="test-output">
                <?php echo do_shortcode('[waza_activity_search]'); ?>
            </div>
        </section>
        
        <!-- Test 7: Activity Filters -->
        <section class="test-section">
            <h2>7. Activity Filters</h2>
            <p>This should display filter options:</p>
            <div class="test-output">
                <?php echo do_shortcode('[waza_activity_filters]'); ?>
            </div>
        </section>
        
        <!-- Debug Information -->
        <section class="debug-section">
            <h2>Debug Information</h2>
            <div class="debug-info">
                <?php if (class_exists('WazaBooking\Core\Plugin')): ?>
                    <p>✅ Plugin class exists</p>
                    
                    <?php 
                    try {
                        $plugin = \WazaBooking\Core\Plugin::get_instance();
                        echo '<p>✅ Plugin instance created</p>';
                        
                        // Test managers
                        $managers = [
                            'Database Manager' => $plugin->get_database_manager(),
                            'Admin Manager' => $plugin->get_admin_manager(),
                            'Frontend Manager' => $plugin->get_frontend_manager(),
                            'Shortcode Manager' => $plugin->get_shortcode_manager(),
                            'AJAX Handler' => $plugin->get_ajax_handler(),
                            'Payment Manager' => $plugin->get_payment_manager(),
                            'Settings Manager' => $plugin->get_settings_manager(),
                        ];
                        
                        foreach ($managers as $name => $manager) {
                            if ($manager) {
                                echo '<p>✅ ' . esc_html($name) . ' initialized</p>';
                            } else {
                                echo '<p>❌ ' . esc_html($name) . ' missing</p>';
                            }
                        }
                        
                    } catch (Exception $e) {
                        echo '<p>❌ Plugin initialization error: ' . esc_html($e->getMessage()) . '</p>';
                    }
                else:
                    echo '<p>❌ Plugin class not found</p>';
                endif; ?>
                
                <!-- JavaScript Test -->
                <div id="js-test-result">
                    <p>🔄 Testing JavaScript...</p>
                </div>
                
                <!-- AJAX Test -->
                <div id="ajax-test-result">
                    <button id="test-ajax" class="button">Test AJAX Connection</button>
                </div>
            </div>
        </section>
    </div>
</div>

<style>
.waza-test-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.test-section {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin: 20px 0;
    padding: 20px;
}

.test-section h2 {
    color: #333;
    margin-top: 0;
}

.test-output {
    background: white;
    border: 1px solid #ccc;
    border-radius: 4px;
    min-height: 100px;
    padding: 15px;
    margin-top: 10px;
}

.debug-section {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    margin: 30px 0;
    padding: 20px;
}

.debug-info p {
    margin: 5px 0;
    font-family: monospace;
}

#ajax-test-result {
    margin-top: 10px;
}

.button {
    background: #0073aa;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
}

.button:hover {
    background: #005a87;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Test JavaScript loading
    $('#js-test-result').html('<p>✅ JavaScript loaded successfully</p>');
    
    // Test AJAX
    $('#test-ajax').on('click', function(e) {
        e.preventDefault();
        
        $('#ajax-test-result').append('<p>🔄 Testing AJAX...</p>');
        
        // Test if wazaBooking object exists
        if (typeof wazaBooking !== 'undefined') {
            $('#ajax-test-result').append('<p>✅ wazaBooking object available</p>');
            $('#ajax-test-result').append('<p>API URL: ' + wazaBooking.apiUrl + '</p>');
        } else {
            $('#ajax-test-result').append('<p>❌ wazaBooking object not found</p>');
        }
        
        // Simple AJAX test
        $.ajax({
            url: ajaxurl || '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: {
                action: 'waza_load_calendar',
                nonce: wazaBooking ? wazaBooking.nonce : '',
                year: new Date().getFullYear(),
                month: new Date().getMonth() + 1
            },
            success: function(response) {
                $('#ajax-test-result').append('<p>✅ AJAX request successful</p>');
                console.log('AJAX Response:', response);
            },
            error: function(xhr, status, error) {
                $('#ajax-test-result').append('<p>❌ AJAX request failed: ' + error + '</p>');
                console.error('AJAX Error:', xhr, status, error);
            }
        });
    });
});
</script>

<?php get_footer(); ?>