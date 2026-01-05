<?php
/**
 * Activity Select Control for Elementor
 *
 * @package WazaBooking
 */

namespace WazaBooking\Integrations\Elementor\Controls;

use Elementor\Base_Data_Control;

if (!defined('ABSPATH')) {
    exit;
}

class ActivitySelectControl extends Base_Data_Control {
    
    /**
     * Get control type
     */
    public function get_type() {
        return 'waza_activity_select';
    }
    
    /**
     * Enqueue control scripts and styles
     */
    public function enqueue() {
        wp_enqueue_script(
            'waza-activity-select-control',
            WAZA_BOOKING_PLUGIN_URL . 'assets/admin/elementor-activity-select.js',
            ['elementor-editor'],
            WAZA_BOOKING_VERSION,
            true
        );
        
        wp_localize_script('waza-activity-select-control', 'waza_elementor_activity_select', [
            'activities' => $this->get_activities(),
        ]);
    }
    
    /**
     * Get default settings
     */
    protected function get_default_settings() {
        return [
            'label_block' => true,
            'multiple' => false,
            'options' => $this->get_activities(),
        ];
    }
    
    /**
     * Render control output in the editor
     */
    public function content_template() {
        $control_uid = $this->get_control_uid();
        ?>
        <div class="elementor-control-field">
            <# if ( data.label ) {#>
                <label for="<?php echo $control_uid; ?>" class="elementor-control-title">{{{ data.label }}}</label>
            <# } #>
            <div class="elementor-control-input-wrapper">
                <select id="<?php echo $control_uid; ?>" data-setting="{{ data.name }}" class="elementor-control-tag-area">
                    <option value=""><?php _e('Select Activity', 'waza-booking'); ?></option>
                    <# _.each( data.options, function( option_title, option_value ) { #>
                        <option value="{{ option_value }}">{{{ option_title }}}</option>
                    <# }); #>
                </select>
            </div>
            <# if ( data.description ) { #>
                <div class="elementor-control-field-description">{{{ data.description }}}</div>
            <# } #>
        </div>
        <?php
    }
    
    /**
     * Get activities for the dropdown
     */
    private function get_activities() {
        $activities = [];
        
        $posts = get_posts([
            'post_type' => 'waza_activity',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);
        
        foreach ($posts as $post) {
            $activities[$post->ID] = $post->post_title;
        }
        
        return $activities;
    }
}