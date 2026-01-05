<?php
/**
 * Test Admin Appearance Settings
 * 
 * This file demonstrates how the new appearance settings work
 * and allows you to test the customizations.
 */

require_once '../../../wp-load.php';

if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

// Get current settings
$settings = get_option('waza_booking_settings', []);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Appearance Settings Test - Waza Studio</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .test-panel {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .test-panel h2 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #5BC0BE;
            padding-bottom: 10px;
        }
        .setting-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .setting-label {
            font-weight: 600;
            color: #555;
        }
        .setting-value {
            color: #333;
            font-family: monospace;
        }
        .color-preview {
            display: inline-block;
            width: 30px;
            height: 20px;
            border-radius: 4px;
            border: 1px solid #ccc;
            vertical-align: middle;
            margin-left: 10px;
        }
        .instructions {
            background: #FFF7ED;
            border-left: 4px solid #F59E0B;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .demo-booking {
            background: white;
            padding: 0;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        @media (max-width: 1024px) {
            .test-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="test-panel">
        <h1>🎨 Appearance Settings Test</h1>
        <p><strong>Admin Panel:</strong> WordPress Admin → Waza Booking → Settings → Appearance Tab</p>
    </div>

    <div class="instructions">
        <h3>📋 How to Test</h3>
        <ol>
            <li>Go to <strong>WordPress Admin → Waza Booking → Settings → Appearance</strong></li>
            <li>Change any setting (color, text, style, etc.)</li>
            <li>Click <strong>Save Settings</strong></li>
            <li>Refresh this page to see changes applied</li>
            <li>Scroll down to see a live booking form preview</li>
        </ol>
    </div>

    <div class="test-container">
        <!-- Current Settings Panel -->
        <div class="test-panel">
            <h2>📊 Current Settings</h2>
            
            <div class="setting-item">
                <span class="setting-label">Primary Color:</span>
                <span class="setting-value">
                    <?php 
                    $primary = $settings['appearance_primary_color'] ?? '#5BC0BE';
                    echo esc_html($primary); 
                    ?>
                    <span class="color-preview" style="background-color: <?php echo esc_attr($primary); ?>;"></span>
                </span>
            </div>
            
            <div class="setting-item">
                <span class="setting-label">Secondary Color:</span>
                <span class="setting-value">
                    <?php 
                    $secondary = $settings['appearance_secondary_color'] ?? '#3A506B';
                    echo esc_html($secondary); 
                    ?>
                    <span class="color-preview" style="background-color: <?php echo esc_attr($secondary); ?>;"></span>
                </span>
            </div>
            
            <div class="setting-item">
                <span class="setting-label">Background Color:</span>
                <span class="setting-value">
                    <?php 
                    $bg = $settings['appearance_background_color'] ?? '#F5F5F5';
                    echo esc_html($bg); 
                    ?>
                    <span class="color-preview" style="background-color: <?php echo esc_attr($bg); ?>;"></span>
                </span>
            </div>
            
            <div class="setting-item">
                <span class="setting-label">Text Color:</span>
                <span class="setting-value">
                    <?php 
                    $text = $settings['appearance_text_color'] ?? '#333333';
                    echo esc_html($text); 
                    ?>
                    <span class="color-preview" style="background-color: <?php echo esc_attr($text); ?>;"></span>
                </span>
            </div>
            
            <div class="setting-item">
                <span class="setting-label">Border Radius:</span>
                <span class="setting-value">
                    <?php echo esc_html($settings['appearance_border_radius'] ?? '8'); ?>px
                </span>
            </div>
            
            <div class="setting-item">
                <span class="setting-label">Font Family:</span>
                <span class="setting-value">
                    <?php echo esc_html($settings['appearance_font_family'] ?? 'system'); ?>
                </span>
            </div>
        </div>

        <!-- Booking Flow Settings Panel -->
        <div class="test-panel">
            <h2>🔄 Booking Flow Configuration</h2>
            
            <div class="setting-item">
                <span class="setting-label">Number of Steps:</span>
                <span class="setting-value">
                    <?php echo esc_html($settings['appearance_booking_steps'] ?? '4'); ?>
                </span>
            </div>
            
            <div class="setting-item">
                <span class="setting-label">Step Labels:</span>
                <span class="setting-value">
                    <?php echo esc_html($settings['appearance_step_labels'] ?? 'Time,Details,Payment,Done'); ?>
                </span>
            </div>
            
            <div class="setting-item">
                <span class="setting-label">Progress Style:</span>
                <span class="setting-value">
                    <?php echo esc_html($settings['appearance_progress_style'] ?? 'bar'); ?>
                </span>
            </div>
            
            <div class="setting-item">
                <span class="setting-label">Show Terms:</span>
                <span class="setting-value">
                    <?php echo ($settings['appearance_show_terms'] ?? '1') == '1' ? 'Yes' : 'No'; ?>
                </span>
            </div>
            
            <div class="setting-item">
                <span class="setting-label">Terms Text:</span>
                <span class="setting-value">
                    <?php echo esc_html($settings['appearance_terms_text'] ?? 'I agree to the terms of service'); ?>
                </span>
            </div>
            
            <div class="setting-item">
                <span class="setting-label">Next Button:</span>
                <span class="setting-value">
                    <?php echo esc_html($settings['appearance_button_next'] ?? 'NEXT'); ?>
                </span>
            </div>
            
            <div class="setting-item">
                <span class="setting-label">Back Button:</span>
                <span class="setting-value">
                    <?php echo esc_html($settings['appearance_button_back'] ?? 'BACK'); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Live Preview -->
    <div class="test-panel">
        <h2>👁️ Live Preview</h2>
        <p>This shows how your booking form will look with current settings:</p>
    </div>

    <div class="demo-booking">
        <?php echo do_shortcode('[waza_booking_calendar]'); ?>
    </div>

    <div class="test-panel" style="margin-top: 20px;">
        <h2>✅ Testing Checklist</h2>
        <ul>
            <li>Change primary color → See buttons, progress bar update</li>
            <li>Change border radius → See form fields, buttons roundness change</li>
            <li>Change step labels → See progress indicator text update</li>
            <li>Toggle progress style → See bar vs circles</li>
            <li>Change button text → See NEXT/BACK button labels</li>
            <li>Toggle terms checkbox → See checkbox appear/disappear</li>
            <li>Change font family → See text font change</li>
            <li>Change number of steps → See 3 vs 4 step flow</li>
        </ul>
    </div>

    <div class="test-panel" style="margin-top: 20px; background: #ECFDF5; border-left: 4px solid #10B981;">
        <h2>🚀 Quick Actions</h2>
        <p><strong>Edit Settings:</strong> <a href="<?php echo admin_url('admin.php?page=waza-booking-settings'); ?>" target="_blank">Go to Admin Panel →</a></p>
        <p><strong>View Calendar:</strong> <a href="<?php echo site_url('/booking-calendar'); ?>">Go to Booking Page →</a></p>
        <p><strong>Documentation:</strong> See ADMIN-APPEARANCE-CONTROL.md for full details</p>
    </div>

    <?php wp_footer(); ?>
</body>
</html>
