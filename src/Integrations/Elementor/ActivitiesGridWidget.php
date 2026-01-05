<?php
/**
 * Activities Grid Elementor Widget
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

class ActivitiesGridWidget extends Widget_Base {
    
    /**
     * Get widget name
     */
    public function get_name() {
        return 'waza-activities-grid';
    }
    
    /**
     * Get widget title
     */
    public function get_title() {
        return __('Activities Grid', 'waza-booking');
    }
    
    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-gallery-grid';
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
                'label' => __('Content', 'waza-booking'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'category',
            [
                'label' => __('Category', 'waza-booking'),
                'type' => Controls_Manager::SELECT,
                'default' => '',
                'options' => $this->get_activity_categories(),
            ]
        );
        
        $this->add_control(
            'limit',
            [
                'label' => __('Number of Activities', 'waza-booking'),
                'type' => Controls_Manager::NUMBER,
                'default' => 6,
                'min' => 1,
                'max' => 50,
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
            'show_price',
            [
                'label' => __('Show Price', 'waza-booking'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'show_duration',
            [
                'label' => __('Show Duration', 'waza-booking'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'show_instructor',
            [
                'label' => __('Show Instructor', 'waza-booking'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        
        $this->end_controls_section();
        
        // Style Tab - Cards
        $this->start_controls_section(
            'card_style_section',
            [
                'label' => __('Cards', 'waza-booking'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'card_background',
            [
                'label' => __('Background Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .activity-card' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'card_border',
                'selector' => '{{WRAPPER}} .activity-card',
            ]
        );
        
        $this->add_control(
            'card_border_radius',
            [
                'label' => __('Border Radius', 'waza-booking'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .activity-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'card_shadow',
                'selector' => '{{WRAPPER}} .activity-card',
            ]
        );
        
        $this->add_control(
            'card_padding',
            [
                'label' => __('Padding', 'waza-booking'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .activity-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
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
                'name' => 'title_typography',
                'label' => __('Title', 'waza-booking'),
                'selector' => '{{WRAPPER}} .activity-title',
            ]
        );
        
        $this->add_control(
            'title_color',
            [
                'label' => __('Title Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .activity-title' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'description_typography',
                'label' => __('Description', 'waza-booking'),
                'selector' => '{{WRAPPER}} .activity-description',
            ]
        );
        
        $this->add_control(
            'description_color',
            [
                'label' => __('Description Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .activity-description' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'price_typography',
                'label' => __('Price', 'waza-booking'),
                'selector' => '{{WRAPPER}} .activity-price',
            ]
        );
        
        $this->add_control(
            'price_color',
            [
                'label' => __('Price Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .activity-price' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Style Tab - Button
        $this->start_controls_section(
            'button_style_section',
            [
                'label' => __('Button', 'waza-booking'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'button_background',
            [
                'label' => __('Background Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .book-now-btn' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'button_color',
            [
                'label' => __('Text Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .book-now-btn' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'button_hover_background',
            [
                'label' => __('Hover Background Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .book-now-btn:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'button_hover_color',
            [
                'label' => __('Hover Text Color', 'waza-booking'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .book-now-btn:hover' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'selector' => '{{WRAPPER}} .book-now-btn',
            ]
        );
        
        $this->add_control(
            'button_border_radius',
            [
                'label' => __('Border Radius', 'waza-booking'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .book-now-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'button_padding',
            [
                'label' => __('Padding', 'waza-booking'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .book-now-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
        
        if (!empty($settings['category'])) {
            $atts['category'] = $settings['category'];
        }
        
        if (!empty($settings['limit'])) {
            $atts['limit'] = $settings['limit'];
        }
        
        if (!empty($settings['columns'])) {
            $atts['columns'] = $settings['columns'];
        }
        
        if ($settings['show_price'] !== 'yes') {
            $atts['show_price'] = 'false';
        }
        
        if ($settings['show_duration'] !== 'yes') {
            $atts['show_duration'] = 'false';
        }
        
        if ($settings['show_instructor'] !== 'yes') {
            $atts['show_instructor'] = 'false';
        }
        
        // Render shortcode
        $shortcode_manager = new ShortcodeManager();
        echo $shortcode_manager->activities_list_shortcode($atts);
    }
    
    /**
     * Get activity categories for dropdown
     */
    private function get_activity_categories() {
        $categories = ['All Categories'];
        
        $terms = get_terms([
            'taxonomy' => 'activity_category',
            'hide_empty' => false,
        ]);
        
        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $term) {
                $categories[$term->slug] = $term->name;
            }
        }
        
        return $categories;
    }
}