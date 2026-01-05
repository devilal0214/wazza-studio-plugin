<?php
/**
 * Customization Manager
 * 
 * Handles the admin customization interface for Waza Booking plugin
 */

namespace WazaBooking\Admin;

class CustomizationManager {
    
    /**
     * Default customization options
     */
    private $default_options = [
        // General
        'calendar_theme' => 'modern',
        
        // Calendar Colors
        'calendar_primary_color' => '#3498db',
        'calendar_secondary_color' => '#2c3e50',
        'calendar_accent_color' => '#e74c3c',
        'calendar_background_color' => '#ffffff',
        'calendar_border_color' => '#e1e5e9',
        'calendar_text_color' => '#333333',
        'calendar_header_bg' => '#ffffff',
        
        // Typography
        'primary_font' => 'inherit',
        'heading_font' => 'inherit',
        'font_size' => '16',
        
        // Layout
        'border_radius' => '4',
        'spacing' => '15',
        
        // Custom CSS
        'custom_css' => ''
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_custom_styles']);
        // Asset enqueuing moved to AdminManager.php to prevent dual loading
        add_action('wp_ajax_waza_save_customization', [$this, 'save_customization_ajax']);
        add_action('wp_ajax_waza_load_customization', [$this, 'load_customization_ajax']);
        add_action('wp_ajax_waza_preview_theme', [$this, 'preview_theme_ajax']);
        add_action('wp_ajax_waza_reset_customization', [$this, 'reset_customization_ajax']);
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('waza_customization_options', 'waza_customization_options', [
            'sanitize_callback' => [$this, 'sanitize_options']
        ]);
    }
    
    /**
     * Enqueue custom styles on frontend
     */
    public function enqueue_custom_styles() {
        $options = $this->get_customization_options();
        
        // Generate CSS variables from settings
        $css_vars = ":root {\n";
        
        // Map settings to CSS variables
        $vars_map = [
            'calendar_primary_color' => '--waza-primary',
            'calendar_secondary_color' => '--waza-secondary',
            'calendar_accent_color' => '--waza-accent',
            'calendar_background_color' => '--waza-bg',
            'calendar_border_color' => '--waza-border',
            'calendar_text_color' => '--waza-text-main',
            'calendar_header_bg' => '--waza-surface', // Map header bg to surface
            'font_size' => '--waza-font-size-base',
            'border_radius' => '--waza-radius',
            'spacing' => '--waza-spacing',
        ];

        foreach ($vars_map as $setting => $var) {
            if (!empty($options[$setting])) {
                $value = $options[$setting];
                // Add units for numeric values
                if (in_array($setting, ['font_size', 'border_radius', 'spacing'])) {
                    $value .= 'px';
                }
                $css_vars .= "    {$var}: {$value};\n";
            }
        }
        
        // Font families
        if (!empty($options['primary_font']) && $options['primary_font'] !== 'inherit') {
            $css_vars .= "    --waza-font-family: '{$options['primary_font']}', sans-serif;\n";
        }
        
        $css_vars .= "}\n";

        // Add custom CSS from settings
        if (!empty($options['custom_css'])) {
            $css_vars .= $options['custom_css'];
        }
        
        wp_add_inline_style('waza-frontend', $css_vars);
        
        // Enqueue Google Fonts if needed
        $this->enqueue_google_fonts($options);
    }
    
    // enqueue_admin_assets removed (handled by AdminManager)
    
    /**
     * Admin page alias for compatibility
     */
    public function admin_page() {
        $this->render_customization_page();
    }
    
    /**
     * Render customization page
     */
    public function render_customization_page() {
        $options = $this->get_customization_options();
        ?>
        <div class="wrap waza-customization-admin" id="waza-customization-admin">
            <div class="customization-header">
                <h1><?php esc_html_e('Waza Booking Customization', 'waza-booking'); ?></h1>
                <p><?php esc_html_e('Customize the appearance and behavior of your booking system.', 'waza-booking'); ?></p>
            </div>
            
            <div class="customization-tabs waza-settings-tabs">
                <a href="#general" class="nav-tab nav-tab-active" data-tab="general">
                    <?php esc_html_e('General', 'waza-booking'); ?>
                </a>
                <a href="#calendar" class="nav-tab" data-tab="calendar">
                    <?php esc_html_e('Calendar', 'waza-booking'); ?>
                </a>
                <a href="#typography" class="nav-tab" data-tab="typography">
                    <?php esc_html_e('Typography', 'waza-booking'); ?>
                </a>
                <a href="#advanced" class="nav-tab" data-tab="advanced">
                    <?php esc_html_e('Advanced', 'waza-booking'); ?>
                </a>
            </div>
            
            <div class="customization-content">
                <form id="waza-customization-form" method="post" class="waza-form">
                    <?php wp_nonce_field('waza_customization_nonce', 'waza_customization_nonce'); ?>
                    
                    <!-- General Tab Panel -->
                    <div id="general" class="waza-tab-content active">
                        <input type="hidden" name="calendar_theme" id="calendar_theme" data-setting="calendar_theme" value="<?php echo esc_attr($options['calendar_theme'] ?? 'modern'); ?>">
                        <div class="control-group">
                            <h3><?php esc_html_e('Theme Presets', 'waza-booking'); ?></h3>
                            <div class="theme-presets">
                                <div class="theme-preset" data-preset="modern">
                                    <h4><?php esc_html_e('Modern', 'waza-booking'); ?></h4>
                                    <p><?php esc_html_e('Clean and contemporary design', 'waza-booking'); ?></p>
                                    <div class="theme-colors">
                                        <span class="theme-color" style="background: #3498db;"></span>
                                        <span class="theme-color" style="background: #2c3e50;"></span>
                                        <span class="theme-color" style="background: #e74c3c;"></span>
                                    </div>
                                </div>
                                <div class="theme-preset" data-preset="minimal">
                                    <h4><?php esc_html_e('Minimal', 'waza-booking'); ?></h4>
                                    <p><?php esc_html_e('Simple and elegant', 'waza-booking'); ?></p>
                                    <div class="theme-colors">
                                        <span class="theme-color" style="background: #000000;"></span>
                                        <span class="theme-color" style="background: #666666;"></span>
                                        <span class="theme-color" style="background: #999999;"></span>
                                    </div>
                                </div>
                                <div class="theme-preset" data-preset="colorful">
                                    <h4><?php esc_html_e('Colorful', 'waza-booking'); ?></h4>
                                    <p><?php esc_html_e('Vibrant and energetic', 'waza-booking'); ?></p>
                                    <div class="theme-colors">
                                        <span class="theme-color" style="background: #ff6b6b;"></span>
                                        <span class="theme-color" style="background: #4ecdc4;"></span>
                                        <span class="theme-color" style="background: #45b7d1;"></span>
                                    </div>
                                </div>
                                <div class="theme-preset" data-preset="dark">
                                    <h4><?php esc_html_e('Dark', 'waza-booking'); ?></h4>
                                    <p><?php esc_html_e('Sleek dark theme', 'waza-booking'); ?></p>
                                    <div class="theme-colors">
                                        <span class="theme-color" style="background: #121212;"></span>
                                        <span class="theme-color" style="background: #bb86fc;"></span>
                                        <span class="theme-color" style="background: #03dac6;"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Calendar Tab Panel -->
                    <div id="calendar" class="waza-tab-content">
                        <div class="control-group">
                            <h3><?php esc_html_e('Calendar Colors', 'waza-booking'); ?></h3>
                            <div class="control-row">
                                <div class="control-item">
                                    <label class="control-label" for="calendar_primary_color">
                                        <?php esc_html_e('Primary Color', 'waza-booking'); ?>
                                    </label>
                                    <div class="color-picker-wrapper">
                                        <input type="text" 
                                               id="calendar_primary_color" 
                                               class="waza-color-picker" 
                                               data-setting="calendar_primary_color" 
                                               value="<?php echo esc_attr($options['calendar_primary_color'] ?? '#3498db'); ?>">
                                    </div>
                                </div>
                                <div class="control-item">
                                    <label class="control-label" for="calendar_secondary_color">
                                        <?php esc_html_e('Secondary Color', 'waza-booking'); ?>
                                    </label>
                                    <div class="color-picker-wrapper">
                                        <input type="text" 
                                               id="calendar_secondary_color" 
                                               class="waza-color-picker" 
                                               data-setting="calendar_secondary_color" 
                                               value="<?php echo esc_attr($options['calendar_secondary_color'] ?? '#2c3e50'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Typography Tab Panel -->
                    <div id="typography" class="waza-tab-content">
                        <div class="control-group">
                            <h3><?php esc_html_e('Font Settings', 'waza-booking'); ?></h3>
                            <div class="control-row">
                                <div class="control-item">
                                    <label class="control-label" for="primary_font">
                                        <?php esc_html_e('Primary Font', 'waza-booking'); ?>
                                    </label>
                                    <select id="primary_font" 
                                            class="control-select" 
                                            data-setting="primary_font">
                                        <option value="inherit"><?php esc_html_e('Theme Default', 'waza-booking'); ?></option>
                                        <option value="Inter" <?php selected($options['primary_font'] ?? '', 'Inter'); ?>>Inter</option>
                                        <option value="Roboto" <?php selected($options['primary_font'] ?? '', 'Roboto'); ?>>Roboto</option>
                                        <option value="Open Sans" <?php selected($options['primary_font'] ?? '', 'Open Sans'); ?>>Open Sans</option>
                                        <option value="Lato" <?php selected($options['primary_font'] ?? '', 'Lato'); ?>>Lato</option>
                                        <option value="Poppins" <?php selected($options['primary_font'] ?? '', 'Poppins'); ?>>Poppins</option>
                                    </select>
                                    <div class="font-preview">
                                        <?php esc_html_e('The quick brown fox jumps over the lazy dog.', 'waza-booking'); ?>
                                    </div>
                                </div>
                                <div class="control-item">
                                    <label class="control-label" for="font_size">
                                        <?php esc_html_e('Font Size', 'waza-booking'); ?>
                                    </label>
                                    <input type="range" 
                                           id="font_size" 
                                           class="range-slider" 
                                           data-setting="font_size" 
                                           data-unit="px"
                                           min="12" 
                                           max="24" 
                                           value="<?php echo esc_attr($options['font_size'] ?? '16'); ?>">
                                    <span class="range-value"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Advanced Tab Panel -->
                    <div id="advanced" class="waza-tab-content">
                        <div class="control-group">
                            <h3><?php esc_html_e('Custom CSS', 'waza-booking'); ?></h3>
                            <div class="control-item">
                                <label class="control-label" for="custom_css">
                                    <?php esc_html_e('Additional CSS', 'waza-booking'); ?>
                                </label>
                                <textarea id="custom_css" 
                                          class="control-textarea" 
                                          data-setting="custom_css"
                                          placeholder="<?php esc_attr_e('/* Enter your custom CSS here */', 'waza-booking'); ?>"><?php echo esc_textarea($options['custom_css'] ?? ''); ?></textarea>
                                <p class="control-description">
                                    <?php esc_html_e('Add custom CSS to further customize the appearance.', 'waza-booking'); ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="control-group">
                            <h3><?php esc_html_e('Layout Options', 'waza-booking'); ?></h3>
                            <div class="control-row">
                                <div class="control-item">
                                    <label class="control-label" for="border_radius">
                                        <?php esc_html_e('Border Radius', 'waza-booking'); ?>
                                    </label>
                                    <input type="range" 
                                           id="border_radius" 
                                           class="range-slider" 
                                           data-setting="border_radius" 
                                           data-unit="px"
                                           min="0" 
                                           max="20" 
                                           value="<?php echo esc_attr($options['border_radius'] ?? '4'); ?>">
                                    <span class="range-value"></span>
                                </div>
                                <div class="control-item">
                                    <label class="control-label" for="spacing">
                                        <?php esc_html_e('Element Spacing', 'waza-booking'); ?>
                                    </label>
                                    <input type="range" 
                                           id="spacing" 
                                           class="range-slider" 
                                           data-setting="spacing" 
                                           data-unit="px"
                                           min="5" 
                                           max="30" 
                                           value="<?php echo esc_attr($options['spacing'] ?? '15'); ?>">
                                    <span class="range-value"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="button" class="btn btn-primary waza-button save-settings">
                            <?php esc_html_e('Save Settings', 'waza-booking'); ?>
                        </button>
                        <button type="button" class="btn btn-secondary waza-button preview-changes">
                            <?php esc_html_e('Preview Changes', 'waza-booking'); ?>
                        </button>
                        <button type="button" class="btn btn-danger waza-button reset-settings">
                            <?php esc_html_e('Reset to Defaults', 'waza-booking'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get customization options
     */
    public function get_customization_options() {
        $options = get_option('waza_customization_options', $this->default_options);
        return wp_parse_args($options, $this->default_options);
    }
    
    /**
     * Sanitize options
     */
    public function sanitize_options($input) {
        $sanitized = [];
        
        foreach ($this->default_options as $key => $default) {
            if (isset($input[$key])) {
                $value = $input[$key];
                
                switch ($key) {
                    case 'custom_css':
                        $sanitized[$key] = wp_strip_all_tags($value);
                        break;
                    case 'calendar_primary_color':
                    case 'calendar_secondary_color':
                    case 'calendar_accent_color':
                    case 'calendar_background_color':
                    case 'calendar_border_color':
                    case 'calendar_text_color':
                    case 'calendar_header_bg':
                        $sanitized[$key] = sanitize_hex_color($value);
                        break;
                    default:
                        $sanitized[$key] = sanitize_text_field($value);
                        break;
                }
            } else {
                $sanitized[$key] = $default;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * AJAX handler for saving customization settings
     */
    public function save_customization_ajax() {
        check_ajax_referer('waza_customization_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'waza-booking'));
        }
        
        // Use wp_unslash because WordPress always slashes $_POST
        $settings_json = wp_unslash($_POST['settings'] ?? '');
        $settings = json_decode($settings_json, true);
        
        if (!is_array($settings)) {
            wp_send_json_error(__('Invalid settings data.', 'waza-booking'));
        }
        
        // Get current options and merge so we don't lose settings from other tabs
        $current_options = $this->get_customization_options();
        $merged_settings = array_merge($current_options, $settings);
        
        $sanitized_settings = $this->sanitize_options($merged_settings);
        update_option('waza_customization_options', $sanitized_settings);
        
        wp_send_json_success(['message' => __('Settings saved successfully!', 'waza-booking')]);
    }
    
    /**
     * AJAX handler for loading customization settings
     */
    public function load_customization_ajax() {
        check_ajax_referer('waza_customization_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'waza-booking'));
        }
        
        $options = $this->get_customization_options();
        wp_send_json_success($options);
    }
    
    /**
     * AJAX handler for theme preview
     */
    public function preview_theme_ajax() {
        check_ajax_referer('waza_customization_nonce', 'nonce');
        
        $theme = sanitize_text_field($_POST['theme'] ?? '');
        $presets = $this->get_theme_presets();
        
        if (!isset($presets[$theme])) {
            wp_send_json_error(__('Invalid theme.', 'waza-booking'));
        }
        
        wp_send_json_success($presets[$theme]);
    }
    
    /**
     * AJAX handler for resetting customization
     */
    public function reset_customization_ajax() {
        check_ajax_referer('waza_customization_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'waza-booking'));
        }
        
        update_option('waza_customization_options', $this->default_options);
        
        wp_send_json_success(['message' => __('Customization reset to defaults!', 'waza-booking')]);
    }
    
    /**
     * Enqueue Google Fonts
     */
    private function enqueue_google_fonts($options) {
        $google_fonts = ['Open Sans', 'Roboto', 'Lato', 'Montserrat', 'Source Sans Pro', 'Raleway', 'Ubuntu', 'Nunito', 'Poppins', 'Work Sans'];
        
        $fonts_to_load = [];
        
        if (isset($options['primary_font']) && in_array($options['primary_font'], $google_fonts)) {
            $fonts_to_load[] = str_replace(' ', '+', $options['primary_font']);
        }
        
        if (isset($options['heading_font']) && in_array($options['heading_font'], $google_fonts) && $options['heading_font'] !== $options['primary_font']) {
            $fonts_to_load[] = str_replace(' ', '+', $options['heading_font']);
        }
        
        if (!empty($fonts_to_load)) {
            wp_enqueue_style(
                'waza-google-fonts',
                'https://fonts.googleapis.com/css2?family=' . implode('&family=', $fonts_to_load) . ':wght@300;400;500;600;700&display=swap',
                [],
                null
            );
        }
    }

    /**
     * Get theme presets
     * 
     * @return array
     */
    public function get_theme_presets() {
        return [
            'modern' => [
                'calendar_primary_color' => '#3498db',
                'calendar_secondary_color' => '#2c3e50',
                'calendar_accent_color' => '#e74c3c',
                'calendar_background_color' => '#ffffff',
                'calendar_border_color' => '#e1e5e9'
            ],
            'minimal' => [
                'calendar_primary_color' => '#000000',
                'calendar_secondary_color' => '#666666',
                'calendar_accent_color' => '#999999',
                'calendar_background_color' => '#ffffff',
                'calendar_border_color' => '#dddddd'
            ],
            'colorful' => [
                'calendar_primary_color' => '#ff6b6b',
                'calendar_secondary_color' => '#4ecdc4',
                'calendar_accent_color' => '#45b7d1',
                'calendar_background_color' => '#ffffff',
                'calendar_border_color' => '#f0f0f0'
            ],
            'dark' => [
                'calendar_primary_color' => '#bb86fc',
                'calendar_secondary_color' => '#03dac6',
                'calendar_accent_color' => '#cf6679',
                'calendar_background_color' => '#121212',
                'calendar_border_color' => '#333333',
                'calendar_text_color' => '#ffffff',
                'calendar_header_bg' => '#1f1f1f'
            ],
            'classic' => [
                'calendar_primary_color' => '#0073aa',
                'calendar_secondary_color' => '#005177',
                'calendar_accent_color' => '#d63638',
                'calendar_background_color' => '#f9f9f9',
                'calendar_border_color' => '#c3c4c7'
            ]
        ];
    }
}