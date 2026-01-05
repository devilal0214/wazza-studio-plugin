<?php
/**
 * Booking Form Elementor Widget
 *
 * @package WazaBooking
 */

namespace WazaBooking\Integrations\Elementor;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use WazaBooking\Frontend\ShortcodeManager;

if (!defined('ABSPATH')) {
    exit;
}

class BookingFormWidget extends Widget_Base {
    
    /**
     * Get widget name
     */
    public function get_name() {
        return 'waza-booking-form';
    }
    
    /**
     * Get widget title
     */
    public function get_title() {
        return __('Booking Form', 'waza-booking');
    }
    
    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-form-horizontal';
    }
    
    /**
     * Get widget categories
     */
    public function get_categories() {
        return ['waza-booking'];
    }
    
    /**
     * Register widget controls
     */
    protected function _register_controls() {
        
        // Content Tab
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Form Settings', 'waza-booking'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'activity_id',
            [
                'label' => __('Select Activity', 'waza-booking'),
                'type' => 'waza_activity_select',
                'default' => '',
                'description' => __('Choose an activity for this booking form', 'waza-booking'),
            ]
        );
        
        $this->add_control(
            'show_activity_info',
            [
                'label' => __('Show Activity Info', 'waza-booking'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => __('Display activity details above the form', 'waza-booking'),
            ]
        );
        
        $this->add_control(
            'show_pricing',
            [
                'label' => __('Show Pricing', 'waza-booking'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'enable_guest_booking',
            [
                'label' => __('Allow Guest Booking', 'waza-booking'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'no',
                'description' => __('Allow users to book without registration', 'waza-booking'),
            ]
        );
        
        $this->add_control(
            'redirect_after_booking',
            [
                'label' => __('Redirect After Booking', 'waza-booking'),
                'type' => Controls_Manager::URL,
                'placeholder' => __('https://your-link.com', 'waza-booking'),
                'show_external' => false,
                'default' => [
                    'url' => '',
                    'is_external' => false,
                    'nofollow' => false,
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Style Tab - Form Container
        $this->start_controls_section(
            'form_container_style_section',
            [
                'label' => __('Form Container', 'waza-booking'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'form_background',
            [
                'label' => __('Background Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .waza-booking-form' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'form_border',
                'selector' => '{{WRAPPER}} .waza-booking-form',
            ]
        );
        
        $this->add_control(
            'form_border_radius',
            [
                'label' => __('Border Radius', 'waza-booking'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .waza-booking-form' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'form_shadow',
                'selector' => '{{WRAPPER}} .waza-booking-form',
            ]
        );
        
        $this->add_control(
            'form_padding',
            [
                'label' => __('Padding', 'waza-booking'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .waza-booking-form' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Style Tab - Form Fields
        $this->start_controls_section(
            'form_fields_style_section',
            [
                'label' => __('Form Fields', 'waza-booking'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'field_background',
            [
                'label' => __('Field Background', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .form-field input, {{WRAPPER}} .form-field select, {{WRAPPER}} .form-field textarea' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'field_text_color',
            [
                'label' => __('Field Text Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .form-field input, {{WRAPPER}} .form-field select, {{WRAPPER}} .form-field textarea' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'field_border',
                'selector' => '{{WRAPPER}} .form-field input, {{WRAPPER}} .form-field select, {{WRAPPER}} .form-field textarea',
            ]
        );
        
        $this->add_control(
            'field_border_radius',
            [
                'label' => __('Field Border Radius', 'waza-booking'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .form-field input, {{WRAPPER}} .form-field select, {{WRAPPER}} .form-field textarea' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'field_focus_border_color',
            [
                'label' => __('Focus Border Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .form-field input:focus, {{WRAPPER}} .form-field select:focus, {{WRAPPER}} .form-field textarea:focus' => 'border-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'field_typography',
                'selector' => '{{WRAPPER}} .form-field input, {{WRAPPER}} .form-field select, {{WRAPPER}} .form-field textarea',
            ]
        );
        
        $this->add_control(
            'field_padding',
            [
                'label' => __('Field Padding', 'waza-booking'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .form-field input, {{WRAPPER}} .form-field select, {{WRAPPER}} .form-field textarea' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Style Tab - Labels
        $this->start_controls_section(
            'label_style_section',
            [
                'label' => __('Labels', 'waza-booking'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'label_color',
            [
                'label' => __('Label Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .form-field label' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'label_typography',
                'selector' => '{{WRAPPER}} .form-field label',
            ]
        );
        
        $this->add_control(
            'required_indicator_color',
            [
                'label' => __('Required Indicator Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .form-field label .required' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Style Tab - Submit Button
        $this->start_controls_section(
            'submit_button_style_section',
            [
                'label' => __('Submit Button', 'waza-booking'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'submit_background',
            [
                'label' => __('Background Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .submit-booking-btn' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'submit_text_color',
            [
                'label' => __('Text Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .submit-booking-btn' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'submit_hover_background',
            [
                'label' => __('Hover Background Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .submit-booking-btn:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'submit_hover_text_color',
            [
                'label' => __('Hover Text Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .submit-booking-btn:hover' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'submit_typography',
                'selector' => '{{WRAPPER}} .submit-booking-btn',
            ]
        );
        
        $this->add_control(
            'submit_border_radius',
            [
                'label' => __('Border Radius', 'waza-booking'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .submit-booking-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'submit_padding',
            [
                'label' => __('Padding', 'waza-booking'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .submit-booking-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'submit_width',
            [
                'label' => __('Button Width', 'waza-booking'),
                'type' => Controls_Manager::SELECT,
                'default' => 'auto',
                'options' => [
                    'auto' => __('Auto', 'waza-booking'),
                    '100%' => __('Full Width', 'waza-booking'),
                    'custom' => __('Custom', 'waza-booking'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .submit-booking-btn' => 'width: {{VALUE}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Style Tab - Activity Info
        $this->start_controls_section(
            'activity_info_style_section',
            [
                'label' => __('Activity Info', 'waza-booking'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_activity_info' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'activity_info_background',
            [
                'label' => __('Background Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .activity-info' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'activity_title_typography',
                'label' => __('Title Typography', 'waza-booking'),
                'selector' => '{{WRAPPER}} .activity-info .activity-title',
            ]
        );
        
        $this->add_control(
            'activity_title_color',
            [
                'label' => __('Title Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .activity-info .activity-title' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'activity_description_typography',
                'label' => __('Description Typography', 'waza-booking'),
                'selector' => '{{WRAPPER}} .activity-info .activity-description',
            ]
        );
        
        $this->add_control(
            'activity_description_color',
            [
                'label' => __('Description Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .activity-info .activity-description' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->end_controls_section();
    }
    
    /**
     * Render widget output
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Build shortcode attributes
        $atts = [];
        
        if (!empty($settings['activity_id'])) {
            $atts['activity'] = $settings['activity_id'];
        }
        
        if ($settings['show_activity_info'] !== 'yes') {
            $atts['show_activity_info'] = 'false';
        }
        
        if ($settings['show_pricing'] !== 'yes') {
            $atts['show_pricing'] = 'false';
        }
        
        if ($settings['enable_guest_booking'] === 'yes') {
            $atts['guest_booking'] = 'true';
        }
        
        if (!empty($settings['redirect_after_booking']['url'])) {
            $atts['redirect'] = $settings['redirect_after_booking']['url'];
        }
        
        // Render shortcode
        $shortcode_manager = new ShortcodeManager();
        echo $shortcode_manager->booking_form_shortcode($atts);
    }
}