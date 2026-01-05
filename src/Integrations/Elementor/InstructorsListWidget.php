<?php
/**
 * Instructors List Elementor Widget
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

class InstructorsListWidget extends Widget_Base {
    
    /**
     * Get widget name
     */
    public function get_name() {
        return 'waza-instructors-list';
    }
    
    /**
     * Get widget title
     */
    public function get_title() {
        return __('Instructors List', 'waza-booking');
    }
    
    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-person';
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
                'label' => __('Instructors Settings', 'waza-booking'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'limit',
            [
                'label' => __('Number of Instructors', 'waza-booking'),
                'type' => Controls_Manager::NUMBER,
                'default' => -1,
                'min' => -1,
                'max' => 50,
                'description' => __('Set to -1 to show all instructors', 'waza-booking'),
            ]
        );
        
        $this->add_control(
            'columns',
            [
                'label' => __('Columns', 'waza-booking'),
                'type' => Controls_Manager::SELECT,
                'default' => '3',
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '6' => '6',
                ],
            ]
        );
        
        $this->add_control(
            'show_bio',
            [
                'label' => __('Show Biography', 'waza-booking'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'show_specialties',
            [
                'label' => __('Show Specialties', 'waza-booking'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'show_contact',
            [
                'label' => __('Show Contact Info', 'waza-booking'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'no',
                'description' => __('Display email and phone if available', 'waza-booking'),
            ]
        );
        
        $this->add_control(
            'show_social_links',
            [
                'label' => __('Show Social Links', 'waza-booking'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'link_to_profile',
            [
                'label' => __('Link to Profile Page', 'waza-booking'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => __('Make instructor cards clickable to view full profile', 'waza-booking'),
            ]
        );
        
        $this->end_controls_section();
        
        // Style Tab - Cards
        $this->start_controls_section(
            'card_style_section',
            [
                'label' => __('Instructor Cards', 'waza-booking'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'card_background',
            [
                'label' => __('Background Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .instructor-card' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'card_border',
                'selector' => '{{WRAPPER}} .instructor-card',
            ]
        );
        
        $this->add_control(
            'card_border_radius',
            [
                'label' => __('Border Radius', 'waza-booking'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .instructor-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'card_shadow',
                'selector' => '{{WRAPPER}} .instructor-card',
            ]
        );
        
        $this->add_control(
            'card_padding',
            [
                'label' => __('Padding', 'waza-booking'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .instructor-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'card_hover_transform',
            [
                'label' => __('Hover Effect', 'waza-booking'),
                'type' => Controls_Manager::SELECT,
                'default' => 'none',
                'options' => [
                    'none' => __('None', 'waza-booking'),
                    'translateY(-5px)' => __('Lift Up', 'waza-booking'),
                    'scale(1.03)' => __('Scale', 'waza-booking'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .instructor-card:hover' => 'transform: {{VALUE}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Style Tab - Avatar
        $this->start_controls_section(
            'avatar_style_section',
            [
                'label' => __('Avatar', 'waza-booking'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'avatar_size',
            [
                'label' => __('Avatar Size', 'waza-booking'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 50,
                        'max' => 200,
                        'step' => 5,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 80,
                ],
                'selectors' => [
                    '{{WRAPPER}} .instructor-avatar' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'avatar_border_radius',
            [
                'label' => __('Avatar Border Radius', 'waza-booking'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default' => [
                    'top' => 50,
                    'right' => 50,
                    'bottom' => 50,
                    'left' => 50,
                    'unit' => '%',
                ],
                'selectors' => [
                    '{{WRAPPER}} .instructor-avatar' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'avatar_border',
                'selector' => '{{WRAPPER}} .instructor-avatar',
            ]
        );
        
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'avatar_shadow',
                'selector' => '{{WRAPPER}} .instructor-avatar',
            ]
        );
        
        $this->end_controls_section();
        
        // Style Tab - Typography
        $this->start_controls_section(
            'typography_section',
            [
                'label' => __('Typography', 'waza-booking'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'name_typography',
                'label' => __('Name', 'waza-booking'),
                'selector' => '{{WRAPPER}} .instructor-name',
            ]
        );
        
        $this->add_control(
            'name_color',
            [
                'label' => __('Name Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .instructor-name' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'label' => __('Title', 'waza-booking'),
                'selector' => '{{WRAPPER}} .instructor-title',
            ]
        );
        
        $this->add_control(
            'title_color',
            [
                'label' => __('Title Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .instructor-title' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'bio_typography',
                'label' => __('Biography', 'waza-booking'),
                'selector' => '{{WRAPPER}} .instructor-bio',
            ]
        );
        
        $this->add_control(
            'bio_color',
            [
                'label' => __('Biography Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .instructor-bio' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Style Tab - Specialties
        $this->start_controls_section(
            'specialties_style_section',
            [
                'label' => __('Specialties', 'waza-booking'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_specialties' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'specialty_background',
            [
                'label' => __('Specialty Tag Background', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .specialty-tag' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'specialty_text_color',
            [
                'label' => __('Specialty Tag Text Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .specialty-tag' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'specialty_typography',
                'selector' => '{{WRAPPER}} .specialty-tag',
            ]
        );
        
        $this->add_control(
            'specialty_border_radius',
            [
                'label' => __('Specialty Tag Border Radius', 'waza-booking'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .specialty-tag' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Style Tab - Social Links
        $this->start_controls_section(
            'social_links_style_section',
            [
                'label' => __('Social Links', 'waza-booking'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_social_links' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'social_icon_size',
            [
                'label' => __('Icon Size', 'waza-booking'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 16,
                        'max' => 50,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 20,
                ],
                'selectors' => [
                    '{{WRAPPER}} .social-links a' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'social_icon_color',
            [
                'label' => __('Icon Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .social-links a' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'social_icon_hover_color',
            [
                'label' => __('Icon Hover Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .social-links a:hover' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'social_spacing',
            [
                'label' => __('Icon Spacing', 'waza-booking'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 10,
                ],
                'selectors' => [
                    '{{WRAPPER}} .social-links a:not(:last-child)' => 'margin-right: {{SIZE}}{{UNIT}};',
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
        
        if (!empty($settings['limit']) && $settings['limit'] !== -1) {
            $atts['limit'] = $settings['limit'];
        }
        
        if (!empty($settings['columns'])) {
            $atts['columns'] = $settings['columns'];
        }
        
        if ($settings['show_bio'] !== 'yes') {
            $atts['show_bio'] = 'false';
        }
        
        if ($settings['show_specialties'] !== 'yes') {
            $atts['show_specialties'] = 'false';
        }
        
        if ($settings['show_contact'] === 'yes') {
            $atts['show_contact'] = 'true';
        }
        
        if ($settings['show_social_links'] !== 'yes') {
            $atts['show_social_links'] = 'false';
        }
        
        if ($settings['link_to_profile'] !== 'yes') {
            $atts['link_to_profile'] = 'false';
        }
        
        // Render shortcode
        $shortcode_manager = new ShortcodeManager();
        echo $shortcode_manager->instructors_list_shortcode($atts);
    }
}