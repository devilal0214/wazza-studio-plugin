<?php
/**
 * Dashboard Widget Interface
 * 
 * @package WazaBooking\Admin\Widgets
 */

namespace WazaBooking\Admin\Widgets;

interface DashboardWidget {
    
    /**
     * Get widget title
     * 
     * @return string
     */
    public function get_title(): string;
    
    /**
     * Render widget content
     */
    public function render(): void;
    
    /**
     * Get widget icon (dashicon class)
     * 
     * @return string
     */
    public function get_icon(): string;
    
    /**
     * Get widget display order (lower = earlier)
     * 
     * @return int
     */
    public function get_order(): int;
    
    /**
     * Get widget column span (1-4, for responsive grid)
     * 
     * @return int
     */
    public function get_column_span(): int;
}
