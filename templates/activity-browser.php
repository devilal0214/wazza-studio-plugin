<?php
/**
 * Activity Browser Template
 * 
 * @package WazaBooking
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get all activity categories
$categories = get_terms([
    'taxonomy' => 'waza_instructor_specialty',
    'hide_empty' => true
]);

// Get activities - use simple query without capability checks
$args = [
    'post_type' => 'waza_activity',
    'post_status' => 'publish',
    'posts_per_page' => $atts['per_page'] ?? 12,
    'orderby' => 'date',
    'order' => 'DESC',
    'suppress_filters' => true  // Important: bypass capability filters
];

$activities_query = new WP_Query($args);

// Debug: Log query details
error_log('Activity Browser Query - Found posts: ' . $activities_query->found_posts);
error_log('Activity Browser Query - Post count: ' . $activities_query->post_count);
if ($activities_query->post_count === 0) {
    error_log('Activity Browser Query - SQL: ' . $activities_query->request);
}
?>

<div class="waza-activity-browser">
    <div class="activity-browser-header">
        <h1><?php esc_html_e('Browse Activities', 'waza-booking'); ?></h1>
        <p><?php esc_html_e('Discover and book your perfect fitness class or dance session', 'waza-booking'); ?></p>
    </div>

    <?php if ($atts['show_filters'] === 'yes') : ?>
    <div class="activity-filters">
        <div class="filter-group">
            <label><?php esc_html_e('Search:', 'waza-booking'); ?></label>
            <input type="text" id="activity-search" placeholder="<?php esc_attr_e('Search activities...', 'waza-booking'); ?>">
        </div>

        <div class="filter-group">
            <label><?php esc_html_e('Category:', 'waza-booking'); ?></label>
            <select id="activity-category">
                <option value=""><?php esc_html_e('All Categories', 'waza-booking'); ?></option>
                <?php foreach ($categories as $category) : ?>
                    <option value="<?php echo esc_attr($category->slug); ?>">
                        <?php echo esc_html($category->name); ?> (<?php echo $category->count; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label><?php esc_html_e('Sort By:', 'waza-booking'); ?></label>
            <select id="activity-sort">
                <option value="popular"><?php esc_html_e('Most Popular', 'waza-booking'); ?></option>
                <option value="newest"><?php esc_html_e('Newest First', 'waza-booking'); ?></option>
                <option value="price_low"><?php esc_html_e('Price: Low to High', 'waza-booking'); ?></option>
                <option value="price_high"><?php esc_html_e('Price: High to Low', 'waza-booking'); ?></option>
            </select>
        </div>
    </div>
    <?php endif; ?>

    <?php if (current_user_can('manage_options')) : ?>
    <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin-bottom: 20px; border-radius: 8px;">
        <strong>Debug Info (Admin Only):</strong><br>
        Total Activities Found: <?php echo $activities_query->found_posts; ?><br>
        Posts in Current Page: <?php echo $activities_query->post_count; ?><br>
        <?php if ($activities_query->post_count === 0) : ?>
            <br><strong style="color: #d63384;">⚠️ No activities found!</strong><br>
            <em>Check: 1) Do published waza_activity posts exist? 2) Run debug-activities.php to diagnose</em>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="activity-grid" id="activity-grid">
        <?php
        if ($activities_query->have_posts()) :
            while ($activities_query->have_posts()) : $activities_query->the_post();
                $activity_id = get_the_ID();
                // Use correct meta keys (_waza_price not _waza_activity_price)
                $price = get_post_meta($activity_id, '_waza_price', true);
                $duration = get_post_meta($activity_id, '_waza_duration', true);
                $rating = get_post_meta($activity_id, '_waza_rating', true) ?: '0';
                $booking_count = get_post_meta($activity_id, '_waza_booking_count', true) ?: 0;
                $terms = get_the_terms($activity_id, 'waza_instructor_specialty');
                $category = !empty($terms) ? $terms[0]->name : 'General';
                ?>
                <div class="activity-card">
                    <div class="activity-thumbnail">
                        <?php if (has_post_thumbnail()) : ?>
                            <?php the_post_thumbnail('medium'); ?>
                        <?php else : ?>
                            <div class="placeholder-image">
                                <span class="activity-icon">🎯</span>
                            </div>
                        <?php endif; ?>
                        <span class="activity-category-badge"><?php echo esc_html($category); ?></span>
                    </div>
                    
                    <div class="activity-content">
                        <h3 class="activity-title"><?php the_title(); ?></h3>

                        <p class="activity-excerpt"><?php echo wp_trim_words(get_the_content(), 15); ?></p>

                        <a href="<?php echo esc_url(add_query_arg('activity_id', $activity_id, home_url('/activity-booking/'))); ?>" 
                           class="waza-btn waza-btn-primary waza-btn-block activity-book-btn">
                            <?php esc_html_e('Book Now', 'waza-booking'); ?>
                        </a>
                    </div>
                </div>
                <?php
            endwhile;
            wp_reset_postdata();
        else :
            ?>
            <div class="no-activities">
                <p><?php esc_html_e('No activities found. Please check back later!', 'waza-booking'); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <div id="activity-pagination" class="activity-pagination">
        <!-- Pagination will be added here via AJAX -->
    </div>

    <div class="activity-loader" style="display: none;">
        <span class="spinner"></span>
        <p><?php esc_html_e('Loading activities...', 'waza-booking'); ?></p>
    </div>
</div>

<style>
.waza-activity-browser { max-width: 1400px; margin: 0 auto; padding: 40px 20px; }
.activity-browser-header { text-align: center; margin-bottom: 40px; }
.activity-browser-header h1 { font-size: 36px; margin-bottom: 10px; color: #333; }
.activity-filters { display: flex; gap: 20px; margin-bottom: 40px; flex-wrap: wrap; background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
.filter-group { flex: 1; min-width: 200px; }
.filter-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; }
.filter-group input, .filter-group select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
.activity-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px; margin-bottom: 40px; }
.activity-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: transform 0.3s, box-shadow 0.3s; }
.activity-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
.activity-thumbnail { position: relative; height: 200px; overflow: hidden; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.activity-thumbnail img { width: 100%; height: 100%; object-fit: cover; }
.placeholder-image { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; }
.activity-icon { font-size: 72px; }
.activity-category-badge { position: absolute; top: 15px; right: 15px; background: rgba(255,255,255,0.95); padding: 6px 15px; border-radius: 20px; font-size: 12px; font-weight: 600; color: #667eea; }
.activity-content { padding: 25px; }
.activity-title { font-size: 20px; margin-bottom: 12px; color: #333; font-weight: 700; }
.activity-meta { display: flex; gap: 15px; margin-bottom: 12px; font-size: 14px; color: #666; }
.activity-rating { color: #ffa500; font-weight: 600; }
.activity-excerpt { color: #666; font-size: 14px; line-height: 1.6; margin-bottom: 15px; }
.activity-details { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; }
.detail-item { font-size: 14px; color: #555; font-weight: 600; }
.detail-item.price { color: #2271b1; font-size: 18px; }
.activity-book-btn { margin-top: 10px; }
.activity-pagination { text-align: center; margin-top: 40px; }
.activity-loader { text-align: center; padding: 40px; }
.no-activities { text-align: center; padding: 60px 20px; grid-column: 1 / -1; }
</style>

<script>
jQuery(document).ready(function($) {
    const ajaxUrl = waza_frontend ? waza_frontend.ajax_url : ajaxurl;
    const nonce = '<?php echo wp_create_nonce('waza_booking_nonce'); ?>';
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
                category: $('#activity-category').val(),
                search: $('#activity-search').val(),
                sort: $('#activity-sort').val(),
                paged: currentPage
            },
            success: function(response) {
                if (response.success) {
                    displayActivities(response.data.activities);
                    updatePagination(response.data.current_page, response.data.total_pages);
                }
            },
            complete: function() {
                $('.activity-loader').hide();
                $('#activity-grid').css('opacity', '1');
            }
        });
    }
    
    function displayActivities(activities) {
        const grid = $('#activity-grid');
        grid.empty();
        
        if (activities.length === 0) {
            grid.append('<div class="no-activities"><p><?php esc_html_e('No activities found matching your criteria.', 'waza-booking'); ?></p></div>');
            return;
        }
        
        activities.forEach(function(activity) {
            const card = `
                <div class="activity-card">
                    <div class="activity-thumbnail">
                        ${activity.thumbnail ? 
                            '<img src="' + activity.thumbnail + '" alt="' + activity.title + '">' : 
                            '<div class="placeholder-image"><span class="activity-icon">🎯</span></div>'
                        }
                        <span class="activity-category-badge">${activity.category}</span>
                    </div>
                    <div class="activity-content">
                        <h3 class="activity-title">${activity.title}</h3>
                        <p class="activity-excerpt">${activity.description}</p>
                        <a href="${activity.permalink}" class="waza-btn waza-btn-primary waza-btn-block activity-book-btn">
                            <?php esc_html_e('Book Now', 'waza-booking'); ?>
                        </a>
                    </div>
                </div>
            `;
            grid.append(card);
        });
    }
    
    function updatePagination(current, total) {
        // Simple pagination implementation
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
    
    // Filter event listeners
    $('#activity-search').on('keyup', debounce(function() {
        currentPage = 1;
        loadActivities();
    }, 500));
    
    $('#activity-category, #activity-sort').on('change', function() {
        currentPage = 1;
        loadActivities();
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
