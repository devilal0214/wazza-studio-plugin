<?php
/**
 * Activity Browser Manager
 * 
 * Handles activity browsing, filtering, and slot selection
 * 
 * @package WazaBooking\Activity
 */

namespace WazaBooking\Activity;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ActivityBrowserManager {
    
    public function __construct() {
        // Shortcodes
        add_shortcode('waza_activity_browser', [$this, 'activity_browser_shortcode']);
        add_shortcode('waza_activity_slots', [$this, 'activity_slots_shortcode']);
        
        // AJAX handlers
        add_action('wp_ajax_waza_get_activity_slots', [$this, 'get_activity_slots']);
        add_action('wp_ajax_nopriv_waza_get_activity_slots', [$this, 'get_activity_slots']);
        add_action('wp_ajax_waza_filter_activities', [$this, 'filter_activities']);
        add_action('wp_ajax_nopriv_waza_filter_activities', [$this, 'filter_activities']);
        add_action('wp_ajax_waza_autocomplete_activities', [$this, 'autocomplete_activities']);
        add_action('wp_ajax_nopriv_waza_autocomplete_activities', [$this, 'autocomplete_activities']);
    }
    
    /**
     * Activity browser shortcode
     */
    public function activity_browser_shortcode($atts) {
        $atts = shortcode_atts([
            'per_page' => 12,
            'show_filters' => 'yes'
        ], $atts);
        
        // Add inline script to footer to avoid encoding issues
        add_action('wp_footer', [$this, 'output_browser_script']);
        
        ob_start();
        include WAZA_BOOKING_PLUGIN_DIR . 'templates/activity-browser.php';
        return ob_get_clean();
    }
    
    /**
     * Output activity browser JavaScript
     */
    public function output_browser_script() {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            const ajaxUrl = waza_frontend ? waza_frontend.ajax_url : ajaxurl;
            const nonce = <?php echo json_encode(wp_create_nonce('waza_booking_nonce')); ?>;
            let currentPage = 1;
            
            function loadActivities() {
                $('.activity-loader').show();
                $('#activity-grid').css('opacity', '0.5');
                
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'waza_filter_activities',
                        nonce: nonce,
                        search: $('#activity-search').val(),
                        paged: currentPage
                    },
                    success: function(response) {
                        if (response.success && response.data.html) {
                            $('#activity-grid').html(response.data.html);
                            updatePagination(response.data.current_page, response.data.total_pages);
                        }
                    },
                    complete: function() {
                        $('.activity-loader').hide();
                        $('#activity-grid').css('opacity', '1');
                    }
                });
            }
            
            function loadSuggestions(query) {
                if (query.length < 2) {
                    $('#search-suggestions').hide().empty();
                    return;
                }
                
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'waza_autocomplete_activities',
                        nonce: nonce,
                        search: query
                    },
                    success: function(response) {
                        if (response.success && response.data.suggestions) {
                            displaySuggestions(response.data.suggestions);
                        }
                    }
                });
            }
            
            function displaySuggestions(suggestions) {
                const container = $('#search-suggestions');
                container.empty();
                
                if (suggestions.length === 0) {
                    container.hide();
                    return;
                }
                
                suggestions.forEach(function(item) {
                    if (!item || !item.title) return;
                    
                    const itemTitle = String(item.title);
                    const itemCategory = String(item.category || 'General');
                    
                    const title = $('<strong>').text(itemTitle);
                    const category = $('<span>').addClass('suggestion-category').text(itemCategory);
                    const div = $('<div>').addClass('suggestion-item')
                        .append(title)
                        .append(category)
                        .data('title', itemTitle)
                        .on('click', function() {
                            const selectedTitle = $(this).data('title');
                            $('#activity-search').val(selectedTitle);
                            container.hide();
                            currentPage = 1;
                            loadActivities();
                        });
                    container.append(div);
                });
                
                container.show();
            }
            
            function updatePagination(current, total) {
                const pagination = $('#activity-pagination');
                pagination.empty();
                
                if (total > 1) {
                    for (let i = 1; i <= total; i++) {
                        const btn = $('<button>')
                            .text(i)
                            .addClass('page-btn')
                            .toggleClass('active', i === current)
                            .on('click', function() {
                                currentPage = i;
                                loadActivities();
                            });
                        pagination.append(btn);
                    }
                }
            }
            
            $('#activity-search').on('input', debounce(function() {
                const query = $(this).val();
                loadSuggestions(query);
            }, 300));
            
            $('#activity-search').on('keyup', debounce(function() {
                currentPage = 1;
                loadActivities();
            }, 500));
            
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.search-wrapper').length) {
                    $('#search-suggestions').hide();
                }
            });
            
            function debounce(func, wait) {
                let timeout;
                return function() {
                    clearTimeout(timeout);
                    timeout = setTimeout(func, wait);
                };
            }
        });
        </script>
        <?php
    }
    
    /**
     * Activity slots selection shortcode
     */
    public function activity_slots_shortcode($atts) {
        $atts = shortcode_atts([
            'activity_id' => get_query_var('activity_id', 0)
        ], $atts);
        
        ob_start();
        include WAZA_BOOKING_PLUGIN_DIR . 'templates/activity-slots.php';
        return ob_get_clean();
    }
    
    /**
     * Get slots for specific activity
     */
    public function get_activity_slots() {
        check_ajax_referer('waza_booking_nonce', 'nonce');
        
        $activity_id = intval($_POST['activity_id']);
        $selected_date = sanitize_text_field($_POST['selected_date'] ?? date('Y-m-d'));
        
        if (!$activity_id) {
            wp_send_json_error(['message' => __('Invalid activity', 'waza-booking')]);
        }
        
        global $wpdb;
        
        // Debug logging
        error_log("Waza: Loading slots for activity_id=$activity_id, date=$selected_date");
        
        // Get available slots for this activity
        $slots = $wpdb->get_results($wpdb->prepare("
            SELECT 
                s.id,
                s.instructor_id,
                s.start_datetime,
                s.end_datetime,
                s.capacity,
                s.price,
                s.sale_price,
                s.original_price,
                s.booked_count,
                s.status,
                i.post_title as instructor_name
            FROM {$wpdb->prefix}waza_slots s
            LEFT JOIN {$wpdb->posts} i ON s.instructor_id = i.ID
            WHERE s.activity_id = %d
            AND DATE(s.start_datetime) = %s
            AND s.start_datetime > NOW()
            AND s.status IN ('active', 'available')
            AND s.booked_count < s.capacity
            ORDER BY s.start_datetime ASC
        ", $activity_id, $selected_date));
        
        // Debug logging
        error_log("Waza: Found " . count($slots) . " slots. Query: " . $wpdb->last_query);
        if ($wpdb->last_error) {
            error_log("Waza: DB Error: " . $wpdb->last_error);
        }
        
        if (empty($slots)) {
            wp_send_json_error(['message' => __('No available slots for this date', 'waza-booking'), 'debug' => ['activity_id' => $activity_id, 'date' => $selected_date, 'query' => $wpdb->last_query]]);
        }
        
        $formatted_slots = array_map(function($slot) {
            // Use sale_price if available, otherwise regular price
            $final_price = !empty($slot->sale_price) ? $slot->sale_price : $slot->price;
            
            return [
                'id' => $slot->id,
                'instructor_name' => $slot->instructor_name ?: 'TBA',
                'start_time' => date('g:i A', strtotime($slot->start_datetime)),
                'end_time' => date('g:i A', strtotime($slot->end_datetime)),
                'available_spots' => $slot->capacity - $slot->booked_count,
                'max_participants' => $slot->capacity,
                'price' => $final_price,
                'original_price' => $slot->original_price,
                'sale_price' => $slot->sale_price
            ];
        }, $slots);
        
        wp_send_json_success(['slots' => $formatted_slots]);
    }
    
    /**
     * Filter activities based on criteria
     */
    public function filter_activities() {
        // Verify nonce if provided (optional check - don't die on failure for public access)
        if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'waza_booking_nonce')) {
            // Log for debugging but allow to continue for public pages
            error_log('Waza Filter: Nonce verification failed but continuing for public access');
        }
        
        $category = sanitize_text_field($_POST['category'] ?? '');
        $search = sanitize_text_field($_POST['search'] ?? '');
        $sort = sanitize_text_field($_POST['sort'] ?? 'popular');
        
        $args = [
            'post_type' => 'waza_activity',
            'post_status' => 'publish',
            'posts_per_page' => 12,
            'paged' => intval($_POST['paged'] ?? 1)
        ];
        
        if ($category) {
            $args['tax_query'] = [[
                'taxonomy' => 'waza_instructor_specialty',
                'field' => 'slug',
                'terms' => $category
            ]];
        }
        
        if ($search) {
            $args['s'] = $search;
        }
        
        // Sorting
        switch ($sort) {
            case 'price_low':
                $args['meta_key'] = '_waza_price';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'ASC';
                break;
            case 'price_high':
                $args['meta_key'] = '_waza_price';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
            case 'newest':
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;
            default: // popular
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
        }
        
        $query = new \WP_Query($args);
        
        // Generate HTML for filtered activities
        ob_start();
        if ($query->have_posts()) :
            while ($query->have_posts()) : $query->the_post();
                $activity_id = get_the_ID();
                $price = get_post_meta($activity_id, '_waza_price', true);
                $duration = get_post_meta($activity_id, '_waza_duration', true);
                $rating = get_post_meta($activity_id, '_waza_rating', true) ?: '0';
                
                // Get custom card image or fallback to thumbnail
                $card_image = get_post_meta($activity_id, '_waza_card_image', true);
                if (!$card_image) {
                    $card_image = get_the_post_thumbnail_url($activity_id, 'medium');
                }
                if (!$card_image) {
                    $card_image = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="400" height="300"%3E%3Crect fill="%23667eea" width="400" height="300"/%3E%3Ctext fill="%23fff" font-family="Arial" font-size="48" x="50%25" y="50%25" text-anchor="middle" dy=".3em"%3E🎯%3C/text%3E%3C/svg%3E';
                }
                
                $terms = get_the_terms($activity_id, 'waza_instructor_specialty');
                $category = !empty($terms) ? $terms[0]->name : 'General';
                
                global $wpdb;
                $booking_count = $wpdb->get_var($wpdb->prepare("
                    SELECT SUM(booked_count) FROM {$wpdb->prefix}waza_slots 
                    WHERE activity_id = %d
                ", $activity_id)) ?: 0;
                
                $booking_url = add_query_arg('activity_id', $activity_id, home_url('/activity-booking/'));
                ?>
                <div class="activity-card">
                    <div class="activity-image">
                        <img src="<?php echo esc_url($card_image); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                        <span class="activity-category"><?php echo esc_html($category); ?></span>
                    </div>
                    <div class="activity-content">
                        <h3><?php the_title(); ?></h3>
                        <div class="activity-meta">
                            <span class="activity-price">₹<?php echo esc_html($price); ?></span>
                            <span class="activity-duration"><?php echo esc_html($duration); ?> min</span>
                        </div>
                        <div class="activity-rating">
                            <?php 
                            $rating_display = floatval($rating);
                            for ($i = 0; $i < 5; $i++) {
                                echo $i < $rating_display ? '★' : '☆';
                            }
                            ?>
                            <span>(<?php echo esc_html($booking_count); ?> bookings)</span>
                        </div>
                        <p><?php echo wp_trim_words(get_the_content(), 15); ?></p>
                        <a href="<?php echo esc_url($booking_url); ?>" class="waza-btn waza-btn-primary">
                            <?php esc_html_e('Book Now', 'waza-booking'); ?>
                        </a>
                    </div>
                </div>
                <?php
            endwhile;
        else :
            ?>
            <div class="no-activities">
                <p><?php esc_html_e('No activities found matching your criteria.', 'waza-booking'); ?></p>
            </div>
            <?php
        endif;
        wp_reset_postdata();
        
        $html = ob_get_clean();
        
        wp_send_json_success([
            'html' => $html,
            'total_pages' => $query->max_num_pages,
            'current_page' => $args['paged']
        ]);
    }
    
    /**
     * Format activity data for response
     */
    private function format_activity_data($activity_id) {
        global $wpdb;
        // Get actual booking count from slots
        $booking_count = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(booked_count) FROM {$wpdb->prefix}waza_slots 
            WHERE activity_id = %d
        ", $activity_id)) ?: 0;
        
        // Get custom images or fallback to post thumbnail
        $card_image = get_post_meta($activity_id, '_waza_card_image', true);
        if (!$card_image) {
            $card_image = get_the_post_thumbnail_url($activity_id, 'medium');
        }
        
        return [
            'id' => $activity_id,
            'title' => get_the_title($activity_id),
            'description' => wp_trim_words(get_the_content(null, false, $activity_id), 20),
            'thumbnail' => $card_image,
            'price' => get_post_meta($activity_id, '_waza_price', true),
            'duration' => get_post_meta($activity_id, '_waza_duration', true),
            'category' => $this->get_activity_category($activity_id),
            'rating' => get_post_meta($activity_id, '_waza_rating', true) ?: '0',
            'booking_count' => $booking_count,
            'permalink' => add_query_arg('activity_id', $activity_id, home_url('/activity-booking/'))
        ];
    }
    
    /**
     * Get activity category
     */
    private function get_activity_category($activity_id) {
        $terms = get_the_terms($activity_id, 'waza_instructor_specialty');
        return !empty($terms) ? $terms[0]->name : 'General';
    }
    
    /**
     * Autocomplete activities for search
     */
    public function autocomplete_activities() {
        $search = sanitize_text_field($_POST['search'] ?? '');
        
        if (strlen($search) < 2) {
            wp_send_json_success(['suggestions' => []]);
        }
        
        $args = [
            'post_type' => 'waza_activity',
            'post_status' => 'publish',
            'posts_per_page' => 8,
            's' => $search
        ];
        
        $query = new \WP_Query($args);
        $suggestions = [];
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $activity_id = get_the_ID();
                $terms = get_the_terms($activity_id, 'waza_instructor_specialty');
                $category = !empty($terms) ? $terms[0]->name : 'General';
                
                $suggestions[] = [
                    'id' => $activity_id,
                    'title' => get_the_title(),
                    'category' => $category
                ];
            }
            wp_reset_postdata();
        }
        
        wp_send_json_success(['suggestions' => $suggestions]);
    }
}
