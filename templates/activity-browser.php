<?php
/**
 * Slot Browser Template - Shows Upcoming Slots
 * 
 * @package WazaBooking
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get filter parameters
$day_filter = isset($_GET['day']) ? sanitize_text_field($_GET['day']) : 'all';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 0;
$sort = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'date_asc';

// Get max slot price for slider
$max_slot_price = $wpdb->get_var("SELECT MAX(COALESCE(sale_price, price)) FROM {$wpdb->prefix}waza_slots WHERE status = 'active'");
$max_slot_price = $max_slot_price ? ceil($max_slot_price) : 5000;
if ($max_price == 0) {
    $max_price = $max_slot_price;
}
$paged = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
$per_page = $atts['per_page'] ?? 12;
$offset = ($paged - 1) * $per_page;

// Build SQL query for upcoming slots
$slots_table = $wpdb->prefix . 'waza_slots';
$where_clauses = ["s.start_datetime > NOW()", "s.status = 'active'"];

// Day filter
if ($day_filter !== 'all') {
    $day_number = [
        'monday' => 2,
        'tuesday' => 3,
        'wednesday' => 4,
        'thursday' => 5,
        'friday' => 6,
        'saturday' => 7,
        'sunday' => 1
    ];
    if (isset($day_number[$day_filter])) {
        $where_clauses[] = $wpdb->prepare("DAYOFWEEK(s.start_datetime) = %d", $day_number[$day_filter]);
    }
}

// Price filter - use sale_price if available, otherwise regular price
if ($min_price > 0 || $max_price < $max_slot_price) {
    $where_clauses[] = $wpdb->prepare("COALESCE(s.sale_price, s.price) BETWEEN %f AND %f", $min_price, $max_price);
}

$where_sql = implode(' AND ', $where_clauses);

// Sort order
switch ($sort) {
    case 'price_asc':
        $order_by = 's.price ASC';
        break;
    case 'price_desc':
        $order_by = 's.price DESC';
        break;
    case 'date_desc':
        $order_by = 's.start_datetime DESC';
        break;
    default:
        $order_by = 's.start_datetime ASC';
        break;
}

// Get total count
$count_sql = "SELECT COUNT(*) FROM {$slots_table} s WHERE {$where_sql}";
$total_slots = $wpdb->get_var($count_sql);

// Get slots with activity and instructor info
$sql = "SELECT s.*, 
        a.post_title as activity_name,
        u.display_name as instructor_name
        FROM {$slots_table} s
        LEFT JOIN {$wpdb->posts} a ON s.activity_id = a.ID
        LEFT JOIN {$wpdb->users} u ON s.instructor_id = u.ID
        WHERE {$where_sql}
        ORDER BY {$order_by}
        LIMIT %d OFFSET %d";

$slots = $wpdb->get_results($wpdb->prepare($sql, $per_page, $offset));

// Calculate pagination
$total_pages = ceil($total_slots / $per_page);
?>

<div class="waza-slot-browser">
    <div class="slot-browser-header">
       <p><?php printf(esc_html__('Showing %d upcoming slots', 'waza-booking'), $total_slots); ?></p>
    </div>

    <div class="slot-browser-layout">
        <!-- Sidebar Filters -->
        <aside class="slot-sidebar">
            <div class="filter-section">
                <h3><?php esc_html_e('Browse by', 'waza-booking'); ?></h3>
                <ul class="day-filter-list">
                    <li><a href="<?php echo esc_url(remove_query_arg('day')); ?>" class="<?php echo $day_filter === 'all' ? 'active' : ''; ?>"><?php esc_html_e('All', 'waza-booking'); ?></a></li>
                    <li><a href="<?php echo esc_url(add_query_arg('day', 'monday')); ?>" class="<?php echo $day_filter === 'monday' ? 'active' : ''; ?>"><?php esc_html_e('Monday', 'waza-booking'); ?></a></li>
                    <li><a href="<?php echo esc_url(add_query_arg('day', 'tuesday')); ?>" class="<?php echo $day_filter === 'tuesday' ? 'active' : ''; ?>"><?php esc_html_e('Tuesday', 'waza-booking'); ?></a></li>
                    <li><a href="<?php echo esc_url(add_query_arg('day', 'wednesday')); ?>" class="<?php echo $day_filter === 'wednesday' ? 'active' : ''; ?>"><?php esc_html_e('Wednesday', 'waza-booking'); ?></a></li>
                    <li><a href="<?php echo esc_url(add_query_arg('day', 'thursday')); ?>" class="<?php echo $day_filter === 'thursday' ? 'active' : ''; ?>"><?php esc_html_e('Thursday', 'waza-booking'); ?></a></li>
                    <li><a href="<?php echo esc_url(add_query_arg('day', 'friday')); ?>" class="<?php echo $day_filter === 'friday' ? 'active' : ''; ?>"><?php esc_html_e('Friday', 'waza-booking'); ?></a></li>
                    <li><a href="<?php echo esc_url(add_query_arg('day', 'saturday')); ?>" class="<?php echo $day_filter === 'saturday' ? 'active' : ''; ?>"><?php esc_html_e('Saturday', 'waza-booking'); ?></a></li>
                    <li><a href="<?php echo esc_url(add_query_arg('day', 'sunday')); ?>" class="<?php echo $day_filter === 'sunday' ? 'active' : ''; ?>"><?php esc_html_e('Sunday', 'waza-booking'); ?></a></li>
                </ul>
            </div>

            <div class="filter-section">
                <h3><?php esc_html_e('Filter by Price', 'waza-booking'); ?></h3>
                <form id="price-filter-form" method="get">
                    <input type="hidden" name="day" value="<?php echo esc_attr($day_filter); ?>">
                    <div class="price-slider-container">
                        <div class="price-values">
                            <span>₹<span id="min-price-display"><?php echo $min_price; ?></span></span>
                            <span>₹<span id="max-price-display"><?php echo $max_price; ?></span></span>
                        </div>
                        <div id="price-slider"></div>
                        <input type="hidden" name="min_price" id="min-price-input" value="<?php echo $min_price; ?>">
                        <input type="hidden" name="max_price" id="max-price-input" value="<?php echo $max_price; ?>">
                    </div>
                    <button type="submit" class="filter-btn"><?php esc_html_e('Filter', 'waza-booking'); ?></button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="slot-main-content">
            <div class="slot-toolbar">
                <div class="result-count">
                    <?php printf(esc_html__('Showing %d of %d slots', 'waza-booking'), min($per_page, $total_slots - $offset), $total_slots); ?>
                </div>
                <div class="sort-controls">
                    <label><?php esc_html_e('Sort by:', 'waza-booking'); ?></label>
                    <select id="slot-sort" name="sort">
                        <option value="date_asc" <?php selected($sort, 'date_asc'); ?>><?php esc_html_e('Date: Earliest First', 'waza-booking'); ?></option>
                        <option value="date_desc" <?php selected($sort, 'date_desc'); ?>><?php esc_html_e('Date: Latest First', 'waza-booking'); ?></option>
                        <option value="price_asc" <?php selected($sort, 'price_asc'); ?>><?php esc_html_e('Price: Low to High', 'waza-booking'); ?></option>
                        <option value="price_desc" <?php selected($sort, 'price_desc'); ?>><?php esc_html_e('Price: High to Low', 'waza-booking'); ?></option>
                    </select>
                </div>
            </div>

            <div class="slot-grid" id="slot-grid" data-paged="<?php echo esc_attr($paged); ?>" data-total-pages="<?php echo esc_attr($total_pages); ?>">
                <?php if (!empty($slots)) : ?>
                    <?php foreach ($slots as $slot) : 
                        // Format date
                        $start_datetime = new DateTime($slot->start_datetime);
                        $date_label = $start_datetime->format('jS M');
                        $time_label = $start_datetime->format('g:i A');
                        
                        // Get image - prefer slot image, fallback to activity featured image
                        $slot_image = !empty($slot->image_url) ? $slot->image_url : '';
                        
                        if (!$slot_image && !empty($slot->activity_id)) {
                            $slot_image = get_the_post_thumbnail_url($slot->activity_id, 'medium');
                        }
                        
                        // Use sale_price if available, otherwise regular price
                        $display_price = !empty($slot->sale_price) ? $slot->sale_price : $slot->price;
                        $original_price = !empty($slot->original_price) ? $slot->original_price : $slot->price;
                        
                        // Calculate discount percentage
                        $has_discount = $display_price < $original_price;
                        $discount_percent = 0;
                        if ($has_discount) {
                            $discount_percent = round((($original_price - $display_price) / $original_price) * 100);
                        }
                        
                        // If still no image, use a gradient placeholder
                        $gradient_colors = ['667eea,764ba2', '00c6ff,0072ff', 'f093fb,f5576c', '4facfe,00f2fe', 'fa709a,fee140'];
                        $random_gradient = $gradient_colors[array_rand($gradient_colors)];
                        $placeholder_style = $slot_image ? '' : ' style="background: linear-gradient(135deg, #' . $random_gradient . ');"';
                        ?>
                        <div class="slot-card">
                            <div class="slot-image"<?php echo $placeholder_style; ?>>
                                <?php if ($slot_image) : ?>
                                    <img src="<?php echo esc_url($slot_image); ?>" alt="<?php echo esc_attr($slot->activity_name); ?>">
                                <?php else : ?>
                                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; font-size: 60px; opacity: 0.8;">🎯</div>
                                <?php endif; ?>
                                <span class="date-badge"><?php echo esc_html($date_label); ?></span>
                                <span class="instructor-badge"><?php echo esc_html($slot->instructor_name); ?></span>
                            </div>
                            <div class="slot-details">
                                <h3 class="slot-title"><?php echo esc_html($slot->activity_name); ?></h3>
                                <div class="slot-time"><?php echo esc_html($date_label . ' | ' . $slot->instructor_name); ?></div>
                                
                                <div class="slot-pricing">
                                    <?php if ($has_discount) : ?>
                                        <span class="original-price">₹<?php echo number_format($original_price, 2); ?></span>
                                        <span class="discount-badge">-<?php echo $discount_percent; ?>%</span>
                                    <?php endif; ?>
                                    <span class="current-price">₹<?php echo number_format($display_price, 2); ?></span>
                                </div>
                                
                                <div class="slot-status">
                                    <span class="status-label"><?php esc_html_e('Pre-registration', 'waza-booking'); ?></span>
                                    <span class="tax-info"><?php esc_html_e('Sales Tax Included', 'waza-booking'); ?></span>
                                </div>
                                
                                <a href="<?php echo esc_url(add_query_arg('slot_id', $slot->id, home_url('/activity-booking/'))); ?>" 
                                   class="book-now-btn">
                                    <?php esc_html_e('Book Now', 'waza-booking'); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="no-slots">
                        <p><?php esc_html_e('No upcoming slots found. Please adjust your filters.', 'waza-booking'); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($total_pages > 1) : ?>
            <div class="slot-pagination">
                <?php if ($paged > 1) : ?>
                    <a href="<?php echo esc_url(add_query_arg('paged', $paged - 1)); ?>" class="pagination-btn prev-btn">
                        <?php esc_html_e('← Previous', 'waza-booking'); ?>
                    </a>
                <?php endif; ?>
                
                <span class="page-info"><?php printf(esc_html__('Page %d of %d', 'waza-booking'), $paged, $total_pages); ?></span>
                
                <?php if ($paged < $total_pages) : ?>
                    <a href="<?php echo esc_url(add_query_arg('paged', $paged + 1)); ?>" class="pagination-btn next-btn load-more-btn">
                        <?php esc_html_e('Load More →', 'waza-booking'); ?>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="slot-loader" style="display: none;">
        <span class="spinner"></span>
        <p><?php esc_html_e('Loading slots...', 'waza-booking'); ?></p>
    </div>
</div>

<!-- jQuery UI for slider -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<style>
/* Main Container */
.waza-slot-browser { max-width: 1400px; margin: 0 auto; padding: 40px 20px; }
.slot-browser-header { text-align: center; margin-bottom: 30px; }
.slot-browser-header h1 { font-size: 36px; margin-bottom: 10px; color: #333; font-weight: 700; }
.slot-browser-header p { color: #666; font-size: 16px; }

/* Layout */
.slot-browser-layout { display: grid; grid-template-columns: 250px 1fr; gap: 30px; align-items: start; }

/* Sidebar */
.slot-sidebar { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); position: sticky; top: 20px; }
.filter-section { margin-bottom: 30px; }
.filter-section:last-child { margin-bottom: 0; }
.filter-section h3 { font-size: 16px; font-weight: 700; margin-bottom: 15px; color: #333; text-transform: uppercase; letter-spacing: 0.5px; }
.day-filter-list { list-style: none; padding: 0; margin: 0; }
.day-filter-list li { margin-bottom: 8px; }
.day-filter-list a { display: block; padding: 10px 15px; color: #555; text-decoration: none; border-radius: 4px; transition: all 0.2s; font-size: 14px; }
.day-filter-list a:hover { background: #f5f5f5; color: #333; }
.day-filter-list a.active { background: #667eea; color: white; font-weight: 600; }

/* Price Filter */
.price-slider-container { margin-bottom: 15px; }
.price-values { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; font-weight: 600; color: #667eea; }
#price-slider { margin: 15px 0; }
#price-slider .ui-slider-range { background: #667eea; }
#price-slider .ui-slider-handle { border-color: #667eea; background: white; cursor: pointer; }
#price-slider .ui-slider-handle:focus { outline: none; }
.filter-btn { width: 100%; padding: 10px; background: #667eea; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 14px; transition: background 0.2s; }
.filter-btn:hover { background: #5568d3; }

/* Toolbar */
.slot-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #f0f0f0; }
.result-count { font-size: 14px; color: #666; }
.sort-controls { display: flex; align-items: center; gap: 10px; }
.sort-controls label { font-size: 14px; color: #555; font-weight: 600; }
.sort-controls select { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; cursor: pointer; }

/* Slot Grid */
.slot-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }

/* Slot Card */
.slot-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08); transition: transform 0.3s, box-shadow 0.3s; }
.slot-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); }

.slot-image { position: relative; height: 220px; overflow: hidden; background: #f5f5f5; }
.slot-image img { width: 100%; height: 100%; object-fit: cover; }
.date-badge { position: absolute; top: 12px; right: 12px; background: rgba(255,255,255,0.95); padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 700; color: #333; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
.instructor-badge { position: absolute; top: 12px; left: 12px; background: rgba(102,126,234,0.95); padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; color: white; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }

.slot-details { padding: 20px; }
.slot-title { font-size: 18px; font-weight: 700; margin-bottom: 8px; color: #333; line-height: 1.3; }
.slot-time { font-size: 13px; color: #666; margin-bottom: 12px; }

.slot-pricing { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; flex-wrap: wrap; }
.original-price { font-size: 14px; color: #999; text-decoration: line-through; }
.discount-badge { background: #ff4444; color: white; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; }
.current-price { font-size: 22px; font-weight: 700; color: #2271b1; }

.slot-status { display: flex; flex-direction: column; gap: 4px; margin-bottom: 15px; }
.status-label { font-size: 12px; color: #667eea; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.tax-info { font-size: 11px; color: #999; }

.book-now-btn { display: block; width: 100%; padding: 12px; background: #667eea; color: white; text-align: center; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px; transition: background 0.2s; }
.book-now-btn:hover { background: #5568d3; }

/* Pagination */
.slot-pagination { display: flex; justify-content: center; align-items: center; gap: 20px; margin-top: 40px; }
.pagination-btn { padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; transition: background 0.2s; }
.pagination-btn:hover { background: #5568d3; }
.page-info { color: #666; font-size: 14px; }

/* Loader */
.slot-loader { text-align: center; padding: 40px; }
.spinner { display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #667eea; border-radius: 50%; animation: spin 1s linear infinite; }
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

/* No Slots */
.no-slots { grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: white; border-radius: 12px; }

/* Responsive */
@media (max-width: 992px) {
    .slot-browser-layout { grid-template-columns: 1fr; }
    .slot-sidebar { position: static; }
}
@media (max-width: 768px) {
    .slot-grid { grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 20px; }
    .slot-toolbar { flex-direction: column; align-items: flex-start; gap: 15px; }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Initialize price slider
    const minPrice = <?php echo $min_price; ?>;
    const maxPrice = <?php echo $max_price; ?>;
    const maxSlotPrice = <?php echo $max_slot_price; ?>;
    
    $('#price-slider').slider({
        range: true,
        min: 0,
        max: maxSlotPrice,
        values: [minPrice, maxPrice],
        slide: function(event, ui) {
            $('#min-price-display').text(ui.values[0]);
            $('#max-price-display').text(ui.values[1]);
            $('#min-price-input').val(ui.values[0]);
            $('#max-price-input').val(ui.values[1]);
        }
    });
    
    // Sort dropdown change
    $('#slot-sort').on('change', function() {
        const url = new URL(window.location.href);
        url.searchParams.set('sort', $(this).val());
        url.searchParams.set('paged', '1');
        window.location.href = url.toString();
    });

    // Infinite scroll on "Load More" button click
    $('.load-more-btn').on('click', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const $grid = $('#slot-grid');
        const currentPage = parseInt($grid.data('paged'));
        const totalPages = parseInt($grid.data('total-pages'));
        const nextPage = currentPage + 1;
        
        if (nextPage > totalPages) {
            return;
        }

        $btn.prop('disabled', true).text('Loading...');
        $('.slot-loader').show();

        // Get current URL params
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('paged', nextPage);
        urlParams.set('ajax', '1');

        $.ajax({
            url: window.location.pathname + '?' + urlParams.toString(),
            type: 'GET',
            success: function(response) {
                // Extract slot cards from response
                const $response = $(response);
                const $newCards = $response.find('.slot-card');
                
                if ($newCards.length > 0) {
                    $grid.append($newCards);
                    $grid.data('paged', nextPage);
                    
                    // Update button
                    if (nextPage >= totalPages) {
                        $btn.remove();
                    } else {
                        $btn.prop('disabled', false).text('Load More →');
                    }
                } else {
                    $btn.remove();
                }
            },
            error: function() {
                alert('Error loading more slots. Please try again.');
                $btn.prop('disabled', false).text('Load More →');
            },
            complete: function() {
                $('.slot-loader').hide();
            }
        });
    });
});
</script>
