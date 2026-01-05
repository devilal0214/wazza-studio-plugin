<?php
/**
 * Frontend Manager
 * 
 * @package WazaBooking\Frontend
 */

namespace WazaBooking\Frontend;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Frontend Manager Class
 * Handles shortcodes, blocks, and frontend functionality
 */
class FrontendManager {
    
    /**
     * Initialize frontend functionality
     */
    public function init() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_head', [$this, 'output_custom_css']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('init', [$this, 'register_blocks']);
    }
    
    /**
     * Output custom CSS based on admin settings
     */
    public function output_custom_css() {
        $settings = get_option('waza_booking_settings', []);
        
        $primary_color = $settings['appearance_primary_color'] ?? '#5BC0BE';
        $secondary_color = $settings['appearance_secondary_color'] ?? '#3A506B';
        $background_color = $settings['appearance_background_color'] ?? '#F5F5F5';
        $text_color = $settings['appearance_text_color'] ?? '#333333';
        $border_radius = $settings['appearance_border_radius'] ?? '8';
        $slots_bg_color = $settings['appearance_slots_bg_color'] ?? '#D1FAE5';
        $font_family = $settings['appearance_font_family'] ?? 'system';
        
        // Map font family
        $font_family_map = [
            'system' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
            'roboto' => '"Roboto", sans-serif',
            'open-sans' => '"Open Sans", sans-serif',
            'lato' => '"Lato", sans-serif',
            'montserrat' => '"Montserrat", sans-serif'
        ];
        $font_family_css = $font_family_map[$font_family] ?? $font_family_map['system'];
        
        // Load Google Fonts if needed
        if ($font_family !== 'system') {
            $font_url_map = [
                'roboto' => 'https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700&display=swap',
                'open-sans' => 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap',
                'lato' => 'https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap',
                'montserrat' => 'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap'
            ];
            if (isset($font_url_map[$font_family])) {
                echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
                echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
                echo '<link href="' . esc_url($font_url_map[$font_family]) . '" rel="stylesheet">';
            }
        }
        
        ?>
        <style id="waza-custom-css">
            :root {
                --waza-primary: <?php echo esc_html($primary_color); ?>;
                --waza-secondary: <?php echo esc_html($secondary_color); ?>;
                --waza-background: <?php echo esc_html($background_color); ?>;
                --waza-text: <?php echo esc_html($text_color); ?>;
                --waza-radius: <?php echo esc_html($border_radius); ?>px;
                --waza-font: <?php echo $font_family_css; ?>;
                --waza-slots-bg: <?php echo esc_html($slots_bg_color); ?>;
            }
            
            /* Apply custom colors */
            .waza-calendar,
            .waza-modal,
            .waza-booking-form-wrapper {
                font-family: var(--waza-font);
                color: var(--waza-text);
            }
            
            .waza-modal-content {
                background-color: white;
            }
            
            .waza-progress-bar,
            .waza-progress-step.active .step-number {
                background-color: var(--waza-primary) !important;
            }
            
            .waza-btn-primary,
            .waza-next-step-btn,
            .waza-submit-booking {
                background-color: var(--waza-primary) !important;
                border-radius: var(--waza-radius) !important;
                color: white !important;
            }
            
            .waza-btn-primary:hover,
            .waza-next-step-btn:hover {
                background-color: <?php echo esc_html($this->adjust_brightness($primary_color, -20)); ?> !important;
            }
            
            .waza-btn-secondary,
            .waza-prev-step-btn,
            .waza-back-to-slots-btn {
                background-color: white !important;
                border: 2px solid var(--waza-primary) !important;
                color: var(--waza-primary) !important;
                border-radius: var(--waza-radius) !important;
            }
            
            .waza-form-group input,
            .waza-form-group select,
            .waza-form-group textarea {
                border-radius: var(--waza-radius) !important;
            }
            
            .waza-form-group input:focus {
                border-color: var(--waza-primary) !important;
            }
            
            .waza-calendar-day.has-slots {
                background-color: var(--waza-slots-bg) !important;
            }
            
            .waza-calendar-day.selected {
                border-color: var(--waza-primary) !important;
            }
            
            .waza-booking-summary {
                background: linear-gradient(135deg, var(--waza-primary) 0%, var(--waza-secondary) 100%) !important;
            }
            
            .waza-review-total {
                border-top-color: var(--waza-primary) !important;
            }
            
            .waza-review-total span {
                color: var(--waza-primary) !important;
            }
        </style>
        <?php
    }
    
    /**
     * Adjust color brightness
     */
    private function adjust_brightness($hex, $steps) {
        $steps = max(-255, min(255, $steps));
        $hex = str_replace('#', '', $hex);
        
        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
        }
        
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        $r = max(0, min(255, $r + $steps));
        $g = max(0, min(255, $g + $steps));
        $b = max(0, min(255, $b + $steps));
        
        return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        $css_file = WAZA_BOOKING_PLUGIN_DIR . 'assets/frontend.css';
        $js_file = WAZA_BOOKING_PLUGIN_DIR . 'assets/frontend.js';
        
        $css_ver = file_exists($css_file) ? filemtime($css_file) : WAZA_BOOKING_VERSION;
        $js_ver = file_exists($js_file) ? filemtime($js_file) : WAZA_BOOKING_VERSION;

        wp_enqueue_style('waza-frontend', WAZA_BOOKING_PLUGIN_URL . 'assets/frontend.css', [], $css_ver);
        wp_enqueue_script('waza-frontend', WAZA_BOOKING_PLUGIN_URL . 'assets/frontend.js', ['jquery'], $js_ver, true);
        
        // Get all plugin settings
        $settings = get_option('waza_booking_settings', []);
        
        // Localize script with API endpoints and settings
        wp_localize_script('waza-frontend', 'waza_frontend', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'apiUrl' => rest_url('waza/v1/'),
            'nonce' => wp_create_nonce('waza_frontend_nonce'),
            'is_logged_in' => is_user_logged_in() ? '1' : '0',
            'login_url' => get_permalink(get_option('waza_login_page_id')) ?: home_url('/login'),
            'appearance' => [
                'primary_color' => $settings['appearance_primary_color'] ?? '#5BC0BE',
                'secondary_color' => $settings['appearance_secondary_color'] ?? '#3A506B',
                'background_color' => $settings['appearance_background_color'] ?? '#F5F5F5',
                'text_color' => $settings['appearance_text_color'] ?? '#333333',
                'border_radius' => $settings['appearance_border_radius'] ?? '8',
                'progress_style' => $settings['appearance_progress_style'] ?? 'bar',
                'booking_steps' => $settings['appearance_booking_steps'] ?? '4',
                'step_labels' => $settings['appearance_step_labels'] ?? 'Time,Details,Payment,Done',
                'show_terms' => $settings['appearance_show_terms'] ?? '1',
                'terms_text' => $settings['appearance_terms_text'] ?? 'I agree to the terms of service',
                'button_next' => $settings['appearance_button_next'] ?? 'NEXT',
                'button_back' => $settings['appearance_button_back'] ?? 'BACK',
                'font_family' => $settings['appearance_font_family'] ?? 'system'
            ],
            'calendar_settings' => [
                'primary_color' => $settings['waza_calendar_primary_color'] ?? $settings['appearance_primary_color'] ?? '#5BC0BE',
                'start_of_week' => $settings['waza_calendar_start_of_week'] ?? 'sunday',
                'time_format' => $settings['waza_calendar_time_format'] ?? '12h',
                'show_instructor' => $settings['waza_calendar_show_instructor'] ?? 'yes',
                'show_price' => $settings['waza_calendar_show_price'] ?? 'yes',
                'slots_per_day' => $settings['waza_calendar_slots_per_day'] ?? '3'
            ],
            'strings' => [
                'bookingSuccess' => __('Booking created successfully!', 'waza-booking'),
                'bookingError' => __('Booking failed. Please try again.', 'waza-booking'),
                'loading' => __('Loading...', 'waza-booking')
            ]
        ]);
    }
    
    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('waza_calendar', [$this, 'calendar_shortcode']);
        add_shortcode('waza_booking_modal', [$this, 'booking_modal_shortcode']);
        add_shortcode('waza_booking_button', [$this, 'booking_button_shortcode']);
    }
    
    /**
     * Calendar shortcode
     */
    public function calendar_shortcode($atts) {
        $atts = shortcode_atts([
            'activity' => '',
            'instructor' => '',
            'theme' => 'default',
            'view' => 'month'
        ], $atts);
        
        ob_start();
        ?>
        <div class="waza-calendar" 
             data-activity="<?php echo esc_attr($atts['activity']); ?>"
             data-instructor="<?php echo esc_attr($atts['instructor']); ?>"
             data-theme="<?php echo esc_attr($atts['theme']); ?>"
             data-view="<?php echo esc_attr($atts['view']); ?>">
            <div class="waza-calendar-header">
                <button class="waza-nav-button prev">&laquo;</button>
                <span class="waza-current-month">Loading...</span>
                <button class="waza-nav-button next">&raquo;</button>
            </div>
            <div class="waza-calendar-grid">
                <div class="waza-loading"><?php esc_html_e('Loading calendar...', 'waza-booking'); ?></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Booking modal shortcode
     */
    public function booking_modal_shortcode($atts) {
        $atts = shortcode_atts([
            'slot_id' => '',
            'button_text' => __('Book Now', 'waza-booking')
        ], $atts);
        
        if (!$atts['slot_id']) {
            return '<p>' . esc_html__('Slot ID required for booking modal.', 'waza-booking') . '</p>';
        }
        
        ob_start();
        ?>
        <button class="waza-booking-button" data-slot-id="<?php echo esc_attr($atts['slot_id']); ?>">
            <?php echo esc_html($atts['button_text']); ?>
        </button>
        <div id="waza-booking-modal" class="waza-modal" style="display: none;">
            <div class="waza-modal-content">
                <span class="waza-close">&times;</span>
                <div class="waza-booking-form">
                    <!-- Booking form will be loaded here via AJAX -->
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Booking button shortcode
     */
    public function booking_button_shortcode($atts) {
        $atts = shortcode_atts([
            'text' => __('Book Activity', 'waza-booking'),
            'class' => 'waza-btn-primary',
            'url' => ''
        ], $atts);
        
        $url = $atts['url'] ?: get_option('waza_booking_page_url', '#');
        
        return sprintf(
            '<a href="%s" class="waza-booking-btn %s">%s</a>',
            esc_url($url),
            esc_attr($atts['class']),
            esc_html($atts['text'])
        );
    }
    
    /**
     * Register Gutenberg blocks
     */
    public function register_blocks() {
        if (!function_exists('register_block_type')) {
            return;
        }
        
        register_block_type('waza-booking/calendar', [
            'render_callback' => [$this, 'calendar_shortcode'],
            'attributes' => [
                'activity' => ['type' => 'string', 'default' => ''],
                'instructor' => ['type' => 'string', 'default' => ''],
                'theme' => ['type' => 'string', 'default' => 'default'],
                'view' => ['type' => 'string', 'default' => 'month']
            ]
        ]);
    }
}