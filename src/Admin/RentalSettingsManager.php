<?php
/**
 * Rental Settings Manager
 * 
 * Manages studio rental rates, types, and configuration
 * 
 * @package WazaBooking\Admin
 */

namespace WazaBooking\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class RentalSettingsManager {
    
    const OPTION_NAME = 'waza_rental_settings';
    
    /**
     * Default rental types and durations
     */
    private $default_rental_types = [
        'rehearsal' => [
            'label' => 'Rehearsal',
            'icon' => '🎭',
            'includes' => ['AC & Fan', 'Basic white lights', 'Drinking water', 'Music system'],
            'excludes' => ['Shooting (one frame only)', 'No extra lights', 'No commercial usage'],
            'enabled' => true
        ],
        'shoot' => [
            'label' => 'Photo/Video Shoot',
            'icon' => '📸',
            'includes' => ['AC & Fan', 'Basic white lights', 'Drinking water', 'Music system', 'Extra lights available'],
            'excludes' => ['Bulk products not allowed'],
            'enabled' => true
        ],
        'commercial' => [
            'label' => 'Commercial Event',
            'icon' => '🎪',
            'includes' => ['AC & Fan', 'Basic white lights', 'Drinking water', 'Music system', 'Extra lights', 'Sound system', 'Full amenities'],
            'excludes' => [],
            'enabled' => true
        ]
    ];
    
    private $default_durations = [
        'hourly' => [
            'label' => 'Hourly',
            'hours' => 1,
            'enabled' => true
        ],
        '3hrs' => [
            'label' => '3 Hours',
            'hours' => 3,
            'enabled' => true
        ],
        'half_day' => [
            'label' => 'Half Day',
            'hours' => 6,
            'enabled' => true
        ],
        'full_day' => [
            'label' => 'Full Day',
            'hours' => 12,
            'enabled' => true
        ]
    ];
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page'], 100);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }
    
    /**
     * Add settings page to admin menu
     */
    public function add_settings_page() {
        add_submenu_page(
            'waza-booking',
            __('Rental Settings', 'waza-booking'),
            __('Rental Settings', 'waza-booking'),
            'manage_options',
            'waza-rental-settings',
            [$this, 'render_settings_page']
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'waza_rental_settings_group',
            self::OPTION_NAME,
            [$this, 'sanitize_settings']
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'waza-booking_page_waza-rental-settings') {
            return;
        }
        
        wp_enqueue_style('waza-rental-settings', WAZA_BOOKING_PLUGIN_URL . 'assets/admin/rental-settings.css', [], WAZA_BOOKING_VERSION);
        wp_enqueue_script('waza-rental-settings', WAZA_BOOKING_PLUGIN_URL . 'assets/admin/rental-settings.js', ['jquery'], WAZA_BOOKING_VERSION, true);
        
        wp_localize_script('waza-rental-settings', 'wazaRentalSettings', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('waza_rental_settings'),
            'strings' => [
                'confirm_delete' => __('Are you sure you want to delete this item?', 'waza-booking'),
                'saved' => __('Settings saved successfully!', 'waza-booking'),
                'error' => __('Error saving settings. Please try again.', 'waza-booking')
            ]
        ]);
    }
    
    /**
     * Get rental settings
     */
    public static function get_settings() {
        $defaults = [
            'rental_types' => [
                'rehearsal' => [
                    'label' => 'Rehearsal',
                    'icon' => '🎭',
                    'includes' => 'AC & Fan, Basic white lights, Drinking water, Music system',
                    'excludes' => 'Shooting (one frame only), No extra lights, No commercial usage',
                    'enabled' => true
                ],
                'shoot' => [
                    'label' => 'Photo/Video Shoot',
                    'icon' => '📸',
                    'includes' => 'AC & Fan, Basic white lights, Drinking water, Music system, Extra lights available',
                    'excludes' => 'Bulk products not allowed',
                    'enabled' => true
                ],
                'commercial' => [
                    'label' => 'Commercial Event',
                    'icon' => '🎪',
                    'includes' => 'AC & Fan, Basic white lights, Drinking water, Music system, Extra lights, Sound system, Full amenities',
                    'excludes' => '',
                    'enabled' => true
                ]
            ],
            'durations' => [
                'hourly' => ['label' => 'Hourly', 'hours' => 1, 'enabled' => true],
                '3hrs' => ['label' => '3 Hours', 'hours' => 3, 'enabled' => true],
                'half_day' => ['label' => 'Half Day', 'hours' => 6, 'enabled' => true],
                'full_day' => ['label' => 'Full Day', 'hours' => 12, 'enabled' => true]
            ],
            'pricing' => [
                'rehearsal' => [
                    'hourly' => 1000,
                    '3hrs' => 2700,
                    'half_day' => 5500,
                    'full_day' => 10000
                ],
                'shoot' => [
                    'hourly' => 1700,
                    '3hrs' => 4500,
                    'half_day' => 9000,
                    'full_day' => 15000
                ],
                'commercial' => [
                    'hourly' => 3500,
                    '3hrs' => 6500,
                    'half_day' => 18000,
                    'full_day' => 25000
                ]
            ],
            'currency_symbol' => '₹',
            'tax_percentage' => 0,
            'advance_percentage' => 50
        ];
        
        return wp_parse_args(get_option(self::OPTION_NAME, []), $defaults);
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = [];
        
        // Sanitize rental types
        if (isset($input['rental_types']) && is_array($input['rental_types'])) {
            $sanitized['rental_types'] = [];
            foreach ($input['rental_types'] as $key => $type) {
                $sanitized_key = sanitize_key($key);
                $sanitized['rental_types'][$sanitized_key] = [
                    'label' => sanitize_text_field($type['label'] ?? ''),
                    'icon' => sanitize_text_field($type['icon'] ?? ''),
                    'includes' => sanitize_textarea_field($type['includes'] ?? ''),
                    'excludes' => sanitize_textarea_field($type['excludes'] ?? ''),
                    'enabled' => !empty($type['enabled'])
                ];
            }
        }
        
        // Sanitize durations
        if (isset($input['durations']) && is_array($input['durations'])) {
            $sanitized['durations'] = [];
            foreach ($input['durations'] as $key => $duration) {
                $sanitized_key = sanitize_key($key);
                $sanitized['durations'][$sanitized_key] = [
                    'label' => sanitize_text_field($duration['label'] ?? ''),
                    'hours' => floatval($duration['hours'] ?? 1),
                    'enabled' => !empty($duration['enabled'])
                ];
            }
        }
        
        // Sanitize pricing
        if (isset($input['pricing']) && is_array($input['pricing'])) {
            $sanitized['pricing'] = [];
            foreach ($input['pricing'] as $type_key => $durations) {
                $sanitized['pricing'][sanitize_key($type_key)] = [];
                if (is_array($durations)) {
                    foreach ($durations as $dur_key => $price) {
                        $sanitized['pricing'][sanitize_key($type_key)][sanitize_key($dur_key)] = floatval($price);
                    }
                }
            }
        }
        
        // Sanitize other settings
        $sanitized['currency_symbol'] = sanitize_text_field($input['currency_symbol'] ?? '₹');
        $sanitized['tax_percentage'] = floatval($input['tax_percentage'] ?? 0);
        $sanitized['advance_percentage'] = floatval($input['advance_percentage'] ?? 50);
        
        return $sanitized;
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        $settings = self::get_settings();
        ?>
        <div class="wrap waza-rental-settings-wrap">
            <h1><?php esc_html_e('Studio Rental Settings', 'waza-booking'); ?></h1>
            <p class="description">
                <?php esc_html_e('Configure rental types, durations, and pricing for your studio space', 'waza-booking'); ?>
            </p>
            
            <?php settings_errors(); ?>
            
            <form method="post" action="options.php" id="waza-rental-settings-form">
                <?php settings_fields('waza_rental_settings_group'); ?>
                
                <div class="waza-rental-tabs">
                    <button type="button" class="waza-tab-btn active" data-tab="types">
                        <?php esc_html_e('Rental Types', 'waza-booking'); ?>
                    </button>
                    <button type="button" class="waza-tab-btn" data-tab="durations">
                        <?php esc_html_e('Durations', 'waza-booking'); ?>
                    </button>
                    <button type="button" class="waza-tab-btn" data-tab="pricing">
                        <?php esc_html_e('Pricing Matrix', 'waza-booking'); ?>
                    </button>
                    <button type="button" class="waza-tab-btn" data-tab="general">
                        <?php esc_html_e('General Settings', 'waza-booking'); ?>
                    </button>
                </div>
                
                <!-- Rental Types Tab -->
                <div class="waza-tab-content active" id="tab-types">
                    <h2><?php esc_html_e('Rental Types', 'waza-booking'); ?></h2>
                    <p class="description"><?php esc_html_e('Configure different types of studio rentals', 'waza-booking'); ?></p>
                    
                    <div id="rental-types-container">
                        <?php foreach ($settings['rental_types'] as $key => $type) : ?>
                            <div class="rental-type-item" data-type-key="<?php echo esc_attr($key); ?>">
                                <div class="rental-type-header">
                                    <h3>
                                        <span class="type-icon"><?php echo esc_html($type['icon']); ?></span>
                                        <span class="type-label"><?php echo esc_html($type['label']); ?></span>
                                    </h3>
                                    <label class="toggle-switch">
                                        <input type="checkbox" 
                                               name="<?php echo self::OPTION_NAME; ?>[rental_types][<?php echo esc_attr($key); ?>][enabled]" 
                                               value="1" <?php checked($type['enabled'], true); ?>>
                                        <span class="toggle-slider"></span>
                                        <?php esc_html_e('Enabled', 'waza-booking'); ?>
                                    </label>
                                </div>
                                
                                <table class="form-table">
                                    <tr>
                                        <th><label><?php esc_html_e('Label', 'waza-booking'); ?></label></th>
                                        <td>
                                            <input type="text" 
                                                   name="<?php echo self::OPTION_NAME; ?>[rental_types][<?php echo esc_attr($key); ?>][label]" 
                                                   value="<?php echo esc_attr($type['label']); ?>" 
                                                   class="regular-text" required>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label><?php esc_html_e('Icon/Emoji', 'waza-booking'); ?></label></th>
                                        <td>
                                            <input type="text" 
                                                   name="<?php echo self::OPTION_NAME; ?>[rental_types][<?php echo esc_attr($key); ?>][icon]" 
                                                   value="<?php echo esc_attr($type['icon']); ?>" 
                                                   class="small-text">
                                            <p class="description"><?php esc_html_e('Single emoji or icon character', 'waza-booking'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label><?php esc_html_e('Includes', 'waza-booking'); ?></label></th>
                                        <td>
                                            <textarea name="<?php echo self::OPTION_NAME; ?>[rental_types][<?php echo esc_attr($key); ?>][includes]" 
                                                      rows="3" 
                                                      class="large-text"><?php echo esc_textarea($type['includes']); ?></textarea>
                                            <p class="description"><?php esc_html_e('Comma-separated list of included items', 'waza-booking'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label><?php esc_html_e('Excludes', 'waza-booking'); ?></label></th>
                                        <td>
                                            <textarea name="<?php echo self::OPTION_NAME; ?>[rental_types][<?php echo esc_attr($key); ?>][excludes]" 
                                                      rows="3" 
                                                      class="large-text"><?php echo esc_textarea($type['excludes']); ?></textarea>
                                            <p class="description"><?php esc_html_e('Comma-separated list of excluded items', 'waza-booking'); ?></p>
                                        </td>
                                    </tr>
                                </table>
                                
                                <button type="button" class="button delete-rental-type" data-type-key="<?php echo esc_attr($key); ?>">
                                    <?php esc_html_e('Delete Type', 'waza-booking'); ?>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <button type="button" id="add-rental-type" class="button button-secondary">
                        <?php esc_html_e('+ Add New Rental Type', 'waza-booking'); ?>
                    </button>
                </div>
                
                <!-- Durations Tab -->
                <div class="waza-tab-content" id="tab-durations">
                    <h2><?php esc_html_e('Duration Options', 'waza-booking'); ?></h2>
                    <p class="description"><?php esc_html_e('Configure available rental duration options', 'waza-booking'); ?></p>
                    
                    <div id="durations-container">
                        <?php foreach ($settings['durations'] as $key => $duration) : ?>
                            <div class="duration-item" data-duration-key="<?php echo esc_attr($key); ?>">
                                <div class="duration-header">
                                    <h3><?php echo esc_html($duration['label']); ?></h3>
                                    <label class="toggle-switch">
                                        <input type="checkbox" 
                                               name="<?php echo self::OPTION_NAME; ?>[durations][<?php echo esc_attr($key); ?>][enabled]" 
                                               value="1" <?php checked($duration['enabled'], true); ?>>
                                        <span class="toggle-slider"></span>
                                        <?php esc_html_e('Enabled', 'waza-booking'); ?>
                                    </label>
                                </div>
                                
                                <table class="form-table">
                                    <tr>
                                        <th><label><?php esc_html_e('Label', 'waza-booking'); ?></label></th>
                                        <td>
                                            <input type="text" 
                                                   name="<?php echo self::OPTION_NAME; ?>[durations][<?php echo esc_attr($key); ?>][label]" 
                                                   value="<?php echo esc_attr($duration['label']); ?>" 
                                                   class="regular-text" required>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label><?php esc_html_e('Hours', 'waza-booking'); ?></label></th>
                                        <td>
                                            <input type="number" 
                                                   name="<?php echo self::OPTION_NAME; ?>[durations][<?php echo esc_attr($key); ?>][hours]" 
                                                   value="<?php echo esc_attr($duration['hours']); ?>" 
                                                   min="0.5" 
                                                   step="0.5" 
                                                   class="small-text" required>
                                            <p class="description"><?php esc_html_e('Number of hours for this duration', 'waza-booking'); ?></p>
                                        </td>
                                    </tr>
                                </table>
                                
                                <button type="button" class="button delete-duration" data-duration-key="<?php echo esc_attr($key); ?>">
                                    <?php esc_html_e('Delete Duration', 'waza-booking'); ?>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <button type="button" id="add-duration" class="button button-secondary">
                        <?php esc_html_e('+ Add New Duration', 'waza-booking'); ?>
                    </button>
                </div>
                
                <!-- Pricing Matrix Tab -->
                <div class="waza-tab-content" id="tab-pricing">
                    <h2><?php esc_html_e('Pricing Matrix', 'waza-booking'); ?></h2>
                    <p class="description"><?php esc_html_e('Set prices for each rental type and duration combination', 'waza-booking'); ?></p>
                    
                    <div class="pricing-matrix">
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Rental Type', 'waza-booking'); ?></th>
                                    <?php foreach ($settings['durations'] as $dur_key => $duration) : ?>
                                        <?php if ($duration['enabled']) : ?>
                                            <th><?php echo esc_html($duration['label']); ?></th>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($settings['rental_types'] as $type_key => $type) : ?>
                                    <?php if ($type['enabled']) : ?>
                                        <tr>
                                            <td>
                                                <strong>
                                                    <?php echo esc_html($type['icon'] . ' ' . $type['label']); ?>
                                                </strong>
                                            </td>
                                            <?php foreach ($settings['durations'] as $dur_key => $duration) : ?>
                                                <?php if ($duration['enabled']) : ?>
                                                    <td>
                                                        <input type="number" 
                                                               name="<?php echo self::OPTION_NAME; ?>[pricing][<?php echo esc_attr($type_key); ?>][<?php echo esc_attr($dur_key); ?>]" 
                                                               value="<?php echo esc_attr($settings['pricing'][$type_key][$dur_key] ?? 0); ?>" 
                                                               min="0" 
                                                               step="0.01" 
                                                               class="small-text"
                                                               placeholder="0.00">
                                                    </td>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- General Settings Tab -->
                <div class="waza-tab-content" id="tab-general">
                    <h2><?php esc_html_e('General Rental Settings', 'waza-booking'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th><label><?php esc_html_e('Currency Symbol', 'waza-booking'); ?></label></th>
                            <td>
                                <input type="text" 
                                       name="<?php echo self::OPTION_NAME; ?>[currency_symbol]" 
                                       value="<?php echo esc_attr($settings['currency_symbol']); ?>" 
                                       class="small-text">
                                <p class="description"><?php esc_html_e('Symbol to display before prices', 'waza-booking'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php esc_html_e('Tax Percentage', 'waza-booking'); ?></label></th>
                            <td>
                                <input type="number" 
                                       name="<?php echo self::OPTION_NAME; ?>[tax_percentage]" 
                                       value="<?php echo esc_attr($settings['tax_percentage']); ?>" 
                                       min="0" 
                                       max="100" 
                                       step="0.01" 
                                       class="small-text">
                                <span>%</span>
                                <p class="description"><?php esc_html_e('Tax to add to rental prices (e.g., GST)', 'waza-booking'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php esc_html_e('Advance Payment Required', 'waza-booking'); ?></label></th>
                            <td>
                                <input type="number" 
                                       name="<?php echo self::OPTION_NAME; ?>[advance_percentage]" 
                                       value="<?php echo esc_attr($settings['advance_percentage']); ?>" 
                                       min="0" 
                                       max="100" 
                                       step="1" 
                                       class="small-text">
                                <span>%</span>
                                <p class="description"><?php esc_html_e('Percentage of total amount required as advance payment', 'waza-booking'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <p class="submit">
                    <?php submit_button(__('Save All Settings', 'waza-booking'), 'primary', 'submit', false); ?>
                </p>
            </form>
        </div>
        <?php
    }
}
