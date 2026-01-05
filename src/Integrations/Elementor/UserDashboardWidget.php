<?php
/**
 * User Dashboard Elementor Widget
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

class UserDashboardWidget extends Widget_Base {
    
    /**
     * Get widget name
     */
    public function get_name() {
        return 'waza-user-dashboard';
    }
    
    /**
     * Get widget title
     */
    public function get_title() {
        return __('User Dashboard', 'waza-booking');
    }
    
    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-dashboard';
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
                'label' => __('Dashboard Settings', 'waza-booking'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'default_tab',
            [
                'label' => __('Default Tab', 'waza-booking'),
                'type' => Controls_Manager::SELECT,
                'default' => 'bookings',
                'options' => [
                    'bookings' => __('My Bookings', 'waza-booking'),
                    'profile' => __('Profile', 'waza-booking'),
                    'history' => __('History', 'waza-booking'),
                ],
            ]
        );
        
        $this->add_control(
            'show_tabs',
            [
                'label' => __('Visible Tabs', 'waza-booking'),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'default' => ['bookings', 'profile', 'history'],
                'options' => [
                    'bookings' => __('My Bookings', 'waza-booking'),
                    'profile' => __('Profile', 'waza-booking'),
                    'history' => __('History', 'waza-booking'),
                    'qr_codes' => __('QR Codes', 'waza-booking'),
                ],
            ]
        );
        
        $this->add_control(
            'bookings_per_page',
            [
                'label' => __('Bookings Per Page', 'waza-booking'),
                'type' => Controls_Manager::NUMBER,
                'default' => 10,
                'min' => 1,
                'max' => 50,
            ]
        );
        
        $this->end_controls_section();
        
        // Style Tab - Dashboard Container
        $this->start_controls_section(
            'dashboard_style_section',
            [
                'label' => __('Dashboard Container', 'waza-booking'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'dashboard_background',
            [
                'label' => __('Background Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .waza-user-dashboard' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'dashboard_border',
                'selector' => '{{WRAPPER}} .waza-user-dashboard',
            ]
        );
        
        $this->add_control(
            'dashboard_border_radius',
            [
                'label' => __('Border Radius', 'waza-booking'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .waza-user-dashboard' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'dashboard_shadow',
                'selector' => '{{WRAPPER}} .waza-user-dashboard',
            ]
        );
        
        $this->add_control(
            'dashboard_padding',
            [
                'label' => __('Padding', 'waza-booking'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .waza-user-dashboard' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Style Tab - Navigation Tabs
        $this->start_controls_section(
            'tabs_style_section',
            [
                'label' => __('Navigation Tabs', 'waza-booking'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'tab_background',
            [
                'label' => __('Tab Background', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .dashboard-nav .nav-tab' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'tab_text_color',
            [
                'label' => __('Tab Text Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .dashboard-nav .nav-tab' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'active_tab_background',
            [
                'label' => __('Active Tab Background', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .dashboard-nav .nav-tab.active' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'active_tab_text_color',
            [
                'label' => __('Active Tab Text Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .dashboard-nav .nav-tab.active' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'tab_typography',
                'selector' => '{{WRAPPER}} .dashboard-nav .nav-tab',
            ]
        );
        
        $this->add_control(
            'tab_border_radius',
            [
                'label' => __('Tab Border Radius', 'waza-booking'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .dashboard-nav .nav-tab' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Style Tab - Content Area
        $this->start_controls_section(
            'content_style_section',
            [
                'label' => __('Content Area', 'waza-booking'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'content_background',
            [
                'label' => __('Content Background', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .dashboard-content' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'content_text_color',
            [
                'label' => __('Content Text Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .dashboard-content' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'content_typography',
                'selector' => '{{WRAPPER}} .dashboard-content',
            ]
        );
        
        $this->end_controls_section();
        
        // Style Tab - Booking Cards
        $this->start_controls_section(
            'booking_cards_style_section',
            [
                'label' => __('Booking Cards', 'waza-booking'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'booking_card_background',
            [
                'label' => __('Card Background', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .booking-card' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'booking_card_border',
                'selector' => '{{WRAPPER}} .booking-card',
            ]
        );
        
        $this->add_control(
            'booking_card_border_radius',
            [
                'label' => __('Card Border Radius', 'waza-booking'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .booking-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'booking_card_shadow',
                'selector' => '{{WRAPPER}} .booking-card',
            ]
        );
        
        $this->add_control(
            'booking_card_padding',
            [
                'label' => __('Card Padding', 'waza-booking'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .booking-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Style Tab - Status Badges
        $this->start_controls_section(
            'status_badges_style_section',
            [
                'label' => __('Status Badges', 'waza-booking'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'confirmed_badge_color',
            [
                'label' => __('Confirmed Badge Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .status-badge.confirmed' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'pending_badge_color',
            [
                'label' => __('Pending Badge Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .status-badge.pending' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'cancelled_badge_color',
            [
                'label' => __('Cancelled Badge Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .status-badge.cancelled' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'badge_typography',
                'selector' => '{{WRAPPER}} .status-badge',
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
        
        if (!empty($settings['default_tab'])) {
            $atts['default_tab'] = $settings['default_tab'];
        }
        
        if (!empty($settings['show_tabs'])) {
            $atts['tabs'] = implode(',', $settings['show_tabs']);
        }
        
        if (!empty($settings['bookings_per_page'])) {
            $atts['per_page'] = $settings['bookings_per_page'];
        }
        
        // Render shortcode
        $shortcode_manager = new ShortcodeManager();
        echo $shortcode_manager->user_dashboard_shortcode($atts);
    }
}