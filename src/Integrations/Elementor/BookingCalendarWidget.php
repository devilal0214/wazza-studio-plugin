<?php
/**
 * Booking Calendar Elementor Widget
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

class BookingCalendarWidget extends Widget_Base {
    
    /**
     * Get widget name
     */
    public function get_name() {
        return 'waza-booking-calendar';
    }
    
    /**
     * Get widget title
     */
    public function get_title() {
        return __('Booking Calendar', 'waza-booking');
    }
    
    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-calendar';
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
                'label' => __('Calendar Settings', 'waza-booking'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'activity_id',
            [
                'label' => __('Select Activity', 'waza-booking'),
                'type' => 'waza_activity_select',
                'default' => '',
                'description' => __('Choose an activity to display its booking calendar', 'waza-booking'),
            ]
        );
        
        $this->add_control(
            'view_type',
            [
                'label' => __('Default View', 'waza-booking'),
                'type' => Controls_Manager::SELECT,
                'default' => 'month',
                'options' => [
                    'month' => __('Month', 'waza-booking'),
                    'week' => __('Week', 'waza-booking'),
                    'day' => __('Day', 'waza-booking'),
                ],
            ]
        );
        
        $this->add_control(
            'show_legend',
            [
                'label' => __('Show Legend', 'waza-booking'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'show_time_slots',
            [
                'label' => __('Show Time Slots', 'waza-booking'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'enable_booking',
            [
                'label' => __('Enable Direct Booking', 'waza-booking'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => __('Allow users to book directly from the calendar', 'waza-booking'),
            ]
        );
        
        $this->end_controls_section();
        
        // Style Tab - Calendar
        $this->start_controls_section(
            'calendar_style_section',
            [
                'label' => __('Calendar', 'waza-booking'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'calendar_background',
            [
                'label' => __('Background Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .waza-calendar' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'calendar_border',
                'selector' => '{{WRAPPER}} .waza-calendar',
            ]
        );
        
        $this->add_control(
            'calendar_border_radius',
            [
                'label' => __('Border Radius', 'waza-booking'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .waza-calendar' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'calendar_shadow',
                'selector' => '{{WRAPPER}} .waza-calendar',
            ]
        );
        
        $this->end_controls_section();
        
        // Style Tab - Header
        $this->start_controls_section(
            'header_style_section',
            [
                'label' => __('Calendar Header', 'waza-booking'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'header_background',
            [
                'label' => __('Background Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .calendar-header' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'header_typography',
                'selector' => '{{WRAPPER}} .calendar-header',
            ]
        );
        
        $this->add_control(
            'header_text_color',
            [
                'label' => __('Text Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .calendar-header' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Style Tab - Days
        $this->start_controls_section(
            'days_style_section',
            [
                'label' => __('Calendar Days', 'waza-booking'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'day_background',
            [
                'label' => __('Day Background', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .calendar-day' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'day_text_color',
            [
                'label' => __('Day Text Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .calendar-day' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'today_background',
            [
                'label' => __('Today Background', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .calendar-day.today' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'available_slot_color',
            [
                'label' => __('Available Slot Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .slot.available' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'booked_slot_color',
            [
                'label' => __('Booked Slot Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .slot.booked' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'waitlist_slot_color',
            [
                'label' => __('Waitlist Slot Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .slot.waitlist' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'day_typography',
                'selector' => '{{WRAPPER}} .calendar-day',
            ]
        );
        
        $this->end_controls_section();
        
        // Style Tab - Time Slots
        $this->start_controls_section(
            'slots_style_section',
            [
                'label' => __('Time Slots', 'waza-booking'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'slot_height',
            [
                'label' => __('Slot Height', 'waza-booking'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 30,
                        'max' => 100,
                        'step' => 5,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 40,
                ],
                'selectors' => [
                    '{{WRAPPER}} .time-slot' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'slot_border_radius',
            [
                'label' => __('Slot Border Radius', 'waza-booking'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .time-slot' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'slot_typography',
                'selector' => '{{WRAPPER}} .time-slot',
            ]
        );
        
        $this->end_controls_section();
        
        // Style Tab - Navigation
        $this->start_controls_section(
            'navigation_style_section',
            [
                'label' => __('Navigation', 'waza-booking'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'nav_button_background',
            [
                'label' => __('Button Background', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .calendar-nav-btn' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'nav_button_color',
            [
                'label' => __('Button Text Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .calendar-nav-btn' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'nav_button_hover_background',
            [
                'label' => __('Button Hover Background', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .calendar-nav-btn:hover' => 'background-color: {{VALUE}};',
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
        
        if (!empty($settings['view_type'])) {
            $atts['view'] = $settings['view_type'];
        }
        
        if ($settings['show_legend'] !== 'yes') {
            $atts['show_legend'] = 'false';
        }
        
        if ($settings['show_time_slots'] !== 'yes') {
            $atts['show_time_slots'] = 'false';
        }
        
        if ($settings['enable_booking'] !== 'yes') {
            $atts['enable_booking'] = 'false';
        }
        
        // Render shortcode
        $shortcode_manager = new ShortcodeManager();
        echo $shortcode_manager->booking_calendar_shortcode($atts);
    }
}