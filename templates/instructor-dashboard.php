<?php
/**
 * Instructor Dashboard Template
 * 
 * @package WazaBooking
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Variables available: $instructor, $instructor_id
?>

<div class="waza-instructor-dashboard">
    <!-- Mobile Header -->
    <div class="waza-mobile-header">
        <div class="waza-header-content">
            <h1><?php printf(__('Hi, %s', 'waza-booking'), get_the_title($instructor_id)); ?></h1>
            <p class="waza-subtitle"><?php esc_html_e('Ready to inspire today?', 'waza-booking'); ?></p>
        </div>
        <button class="waza-profile-btn" onclick="document.querySelector('[data-tab=profile]').click()">
            <span class="waza-avatar">👤</span>
        </button>
    </div>

    <!-- Stats Overview -->
    <div class="waza-stats-row">
        <div class="waza-stat-card">
            <div class="waza-stat-icon">🎯</div>
            <div class="waza-stat-content">
                <span class="waza-stat-value" id="stat-workshops-today">0</span>
                <span class="waza-stat-label"><?php esc_html_e('Today', 'waza-booking'); ?></span>
            </div>
        </div>
        <div class="waza-stat-card">
            <div class="waza-stat-icon">📅</div>
            <div class="waza-stat-content">
                <span class="waza-stat-value" id="stat-upcoming">0</span>
                <span class="waza-stat-label"><?php esc_html_e('Upcoming', 'waza-booking'); ?></span>
            </div>
        </div>
        <div class="waza-stat-card">
            <div class="waza-stat-icon">👥</div>
            <div class="waza-stat-content">
                <span class="waza-stat-value" id="stat-students">0</span>
                <span class="waza-stat-label"><?php esc_html_e('Students', 'waza-booking'); ?></span>
            </div>
        </div>
    </div>

    <!-- Main Navigation Tabs -->
    <div class="waza-tab-navigation">
        <button class="waza-tab-btn active" data-tab="workshops">
            <span class="tab-icon">🎪</span>
            <span class="tab-label"><?php esc_html_e('Workshops', 'waza-booking'); ?></span>
        </button>
        <button class="waza-tab-btn" data-tab="schedule">
            <span class="tab-icon">📅</span>
            <span class="tab-label"><?php esc_html_e('Schedule', 'waza-booking'); ?></span>
        </button>
        <button class="waza-tab-btn" data-tab="students">
            <span class="tab-icon">👥</span>
            <span class="tab-label"><?php esc_html_e('Students', 'waza-booking'); ?></span>
        </button>
        <button class="waza-tab-btn" data-tab="profile">
            <span class="tab-icon">⚙️</span>
            <span class="tab-label"><?php esc_html_e('Profile', 'waza-booking'); ?></span>
        </button>
    </div>

    <!-- Tab Content -->
    <div class="waza-tab-container">
        
        <!-- Workshops Tab -->
        <div class="waza-tab-panel active" data-panel="workshops">
            <div class="waza-panel-header">
                <h2><?php esc_html_e('My Workshops', 'waza-booking'); ?></h2>
                <button class="waza-btn waza-btn-primary waza-create-workshop-btn">
                    <span>➕</span> <?php esc_html_e('New Workshop', 'waza-booking'); ?>
                </button>
            </div>

            <div class="waza-filter-tabs">
                <button class="waza-filter-tab active" data-filter="upcoming"><?php esc_html_e('Upcoming', 'waza-booking'); ?></button>
                <button class="waza-filter-tab" data-filter="today"><?php esc_html_e('Today', 'waza-booking'); ?></button>
                <button class="waza-filter-tab" data-filter="past"><?php esc_html_e('Past', 'waza-booking'); ?></button>
            </div>

            <div id="workshops-list">
                <div class="waza-loading">
                    <span class="spinner"></span>
                    <p><?php esc_html_e('Loading workshops...', 'waza-booking'); ?></p>
                </div>
            </div>
        </div>

        <!-- Schedule Tab -->
        <div class="waza-tab-panel" data-panel="schedule">
            <div class="waza-panel-header">
                <h2><?php esc_html_e('My Schedule', 'waza-booking'); ?></h2>
            </div>
            
            <div id="schedule-calendar">
                <div class="waza-loading">
                    <span class="spinner"></span>
                    <p><?php esc_html_e('Loading schedule...', 'waza-booking'); ?></p>
                </div>
            </div>
        </div>

        <!-- Students Tab -->
        <div class="waza-tab-panel" data-panel="students">
            <div class="waza-panel-header">
                <h2><?php esc_html_e('My Students', 'waza-booking'); ?></h2>
                <div class="waza-search-box">
                    <input type="text" id="student-search" placeholder="<?php esc_attr_e('Search students...', 'waza-booking'); ?>">
                </div>
            </div>
            
            <div id="students-list">
                <div class="waza-loading">
                    <span class="spinner"></span>
                    <p><?php esc_html_e('Loading students...', 'waza-booking'); ?></p>
                </div>
            </div>
        </div>

        <!-- Profile Tab -->
        <div class="waza-tab-panel" data-panel="profile">
            <div class="waza-panel-header">
                <h2><?php esc_html_e('Profile Settings', 'waza-booking'); ?></h2>
            </div>
            
            <div class="waza-profile-form">
                <div class="waza-profile-avatar">
                    <div class="avatar-preview">
                        <?php echo get_avatar(get_current_user_id(), 120); ?>
                    </div>
                    <button class="waza-btn waza-btn-sm waza-btn-secondary"><?php esc_html_e('Change Photo', 'waza-booking'); ?></button>
                </div>

                <form id="instructor-profile-form">
                    <div class="waza-form-group">
                        <label><?php esc_html_e('Full Name', 'waza-booking'); ?></label>
                        <input type="text" name="instructor_name" value="<?php echo esc_attr(get_the_title($instructor_id)); ?>" required>
                    </div>

                    <div class="waza-form-group">
                        <label><?php esc_html_e('Email', 'waza-booking'); ?></label>
                        <input type="email" value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>" disabled>
                        <small><?php esc_html_e('Contact admin to change email', 'waza-booking'); ?></small>
                    </div>

                    <div class="waza-form-group">
                        <label><?php esc_html_e('Phone', 'waza-booking'); ?></label>
                        <input type="tel" name="instructor_phone" value="<?php echo esc_attr(get_post_meta($instructor_id, '_waza_phone', true)); ?>">
                    </div>

                    <div class="waza-form-group">
                        <label><?php esc_html_e('Bio', 'waza-booking'); ?></label>
                        <textarea name="instructor_bio" rows="4"><?php echo esc_textarea($instructor->post_content); ?></textarea>
                    </div>

                    <div class="waza-form-group">
                        <label><?php esc_html_e('Instagram', 'waza-booking'); ?></label>
                        <input type="url" name="instagram_link" value="<?php echo esc_url(get_post_meta($instructor_id, '_waza_instagram', true)); ?>">
                    </div>

                    <div class="waza-form-group">
                        <label><?php esc_html_e('Portfolio/Website', 'waza-booking'); ?></label>
                        <input type="url" name="portfolio_link" value="<?php echo esc_url(get_post_meta($instructor_id, '_waza_portfolio', true)); ?>">
                    </div>

                    <button type="submit" class="waza-btn waza-btn-primary">
                        <?php esc_html_e('Save Changes', 'waza-booking'); ?>
                    </button>
                </form>
            </div>
        </div>

    </div>

    <!-- Floating Action Button (Mobile) -->
    <button class="waza-fab" onclick="document.querySelector('.waza-create-workshop-btn').click()">
        <span>➕</span>
    </button>
</div>

<!-- QR Modal Template -->
<div id="qr-modal-template" style="display: none;">
    <div class="waza-modal waza-qr-modal">
        <div class="waza-modal-overlay"></div>
        <div class="waza-modal-content">
            <div class="waza-modal-header">
                <h3><?php esc_html_e('Workshop QR Code', 'waza-booking'); ?></h3>
                <button class="waza-modal-close">&times;</button>
            </div>
            <div class="waza-modal-body">
                <div class="waza-qr-display">
                    <img id="workshop-qr-image" src="" alt="Workshop QR Code">
                </div>
                <p class="waza-qr-instructions">
                    <?php esc_html_e('Show this QR code at the studio to authenticate yourself and enable student check-in', 'waza-booking'); ?>
                </p>
            </div>
            <div class="waza-modal-footer">
                <button class="waza-btn waza-btn-primary" id="download-qr-btn">
                    <?php esc_html_e('Download QR', 'waza-booking'); ?>
                </button>
                <button class="waza-btn waza-btn-secondary waza-modal-close">
                    <?php esc_html_e('Close', 'waza-booking'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Create Workshop Modal -->
<div id="create-workshop-modal" class="waza-modal" style="display: none;">
    <div class="waza-modal-overlay"></div>
    <div class="waza-modal-content waza-create-workshop-modal">
        <div class="waza-modal-header">
            <h3><?php esc_html_e('Create New Workshop', 'waza-booking'); ?></h3>
            <button class="waza-modal-close">&times;</button>
        </div>
        <form id="create-workshop-form">
            <div class="waza-modal-body">
                <div class="waza-form-group">
                    <label for="workshop-activity"><?php esc_html_e('Activity', 'waza-booking'); ?> <span class="required">*</span></label>
                    <select id="workshop-activity" name="activity_id" required>
                        <option value=""><?php esc_html_e('Select Activity...', 'waza-booking'); ?></option>
                        <?php
                        // Get all activities
                        $activities = get_posts([
                            'post_type' => 'waza_activity',
                            'posts_per_page' => -1,
                            'orderby' => 'title',
                            'order' => 'ASC',
                            'post_status' => 'publish'
                        ]);
                        
                        foreach ($activities as $activity) {
                            printf(
                                '<option value="%d">%s</option>',
                                esc_attr($activity->ID),
                                esc_html($activity->post_title)
                            );
                        }
                        ?>
                    </select>
                    <small style="color: #666; display: block; margin-top: 0.5rem;">
                        <?php esc_html_e('Select an activity from the admin panel. The activity\'s default duration and price will be used.', 'waza-booking'); ?>
                    </small>
                </div>

                <div class="waza-form-row">
                    <div class="waza-form-group">
                        <label for="workshop-date"><?php esc_html_e('Date', 'waza-booking'); ?> <span class="required">*</span></label>
                        <input type="date" id="workshop-date" name="date" required>
                    </div>

                    <div class="waza-form-group">
                        <label for="workshop-time"><?php esc_html_e('Start Time', 'waza-booking'); ?> <span class="required">*</span></label>
                        <input type="time" id="workshop-time" name="time" required>
                    </div>
                </div>

                <div class="waza-form-row">
                    <div class="waza-form-group">
                        <label for="workshop-end-time"><?php esc_html_e('End Time', 'waza-booking'); ?> <span class="required">*</span></label>
                        <input type="time" id="workshop-end-time" name="end_time" required>
                    </div>

                    <div class="waza-form-group">
                        <label for="workshop-capacity"><?php esc_html_e('Max Capacity', 'waza-booking'); ?> <span class="required">*</span></label>
                        <input type="number" id="workshop-capacity" name="capacity" required min="1" value="20">
                    </div>
                </div>

                <div class="waza-form-row">
                    <div class="waza-form-group">
                        <label for="workshop-price"><?php esc_html_e('Price', 'waza-booking'); ?> <span class="required">*</span></label>
                        <input type="number" id="workshop-price" name="price" required min="0" step="0.01" value="0">
                    </div>

                    <div class="waza-form-group">
                        <label for="workshop-location"><?php esc_html_e('Location', 'waza-booking'); ?></label>
                        <input type="text" id="workshop-location" name="location" placeholder="e.g., Studio A">
                    </div>
                </div>
                
                <div class="waza-form-row">
                    <div class="waza-form-group">
                        <label for="workshop-original-price"><?php esc_html_e('Original Price (Optional)', 'waza-booking'); ?></label>
                        <input type="number" id="workshop-original-price" name="original_price" min="0" step="0.01" placeholder="0.00">
                        <small style="color: #666; display: block; margin-top: 0.5rem;">
                            <?php esc_html_e('Original price before discount (for display)', 'waza-booking'); ?>
                        </small>
                    </div>

                    <div class="waza-form-group">
                        <label for="workshop-sale-price"><?php esc_html_e('Sale Price (Optional)', 'waza-booking'); ?></label>
                        <input type="number" id="workshop-sale-price" name="sale_price" min="0" step="0.01" placeholder="0.00">
                        <small style="color: #666; display: block; margin-top: 0.5rem;">
                            <?php esc_html_e('Discounted sale price (if applicable)', 'waza-booking'); ?>
                        </small>
                    </div>
                </div>
                
                <div class="waza-form-group">
                    <label for="workshop-image"><?php esc_html_e('Workshop Image', 'waza-booking'); ?></label>
                    <input type="file" id="workshop-image" name="workshop_image" accept="image/*">
                    <small style="color: #666; display: block; margin-top: 0.5rem;">
                        <?php esc_html_e('Upload an image for this workshop (optional). Will be displayed in the activities browser.', 'waza-booking'); ?>
                    </small>
                    <div id="workshop-image-preview" style="margin-top:10px; display:none;">
                        <img src="" style="max-width:200px; border-radius:8px;" />
                    </div>
                </div>

                <div class="waza-form-group" style="display: none;">
                    <label for="workshop-location-old"><?php esc_html_e('Location Old', 'waza-booking'); ?></label>
                    <input type="text" id="workshop-location-old" name="location_old" placeholder="e.g., Studio A">
                </div>

                <div id="workshop-form-message" class="waza-form-message" style="display: none;"></div>
            </div>

            <div class="waza-modal-footer">
                <button type="button" class="waza-btn waza-btn-secondary waza-modal-close">
                    <?php esc_html_e('Cancel', 'waza-booking'); ?>
                </button>
                <button type="submit" class="waza-btn waza-btn-primary" id="submit-workshop-btn">
                    <?php esc_html_e('Create Workshop', 'waza-booking'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Cancellation Request Modal -->
<div id="cancel-workshop-modal" class="waza-modal" style="display: none;">
    <div class="waza-modal-overlay"></div>
    <div class="waza-modal-content">
        <div class="waza-modal-header">
            <h3><?php esc_html_e('Request Workshop Cancellation', 'waza-booking'); ?></h3>
            <button class="waza-modal-close">&times;</button>
        </div>
        <form id="cancel-workshop-form">
            <input type="hidden" id="cancel-slot-id" name="slot_id">
            <div class="waza-modal-body">
                <p><?php esc_html_e('Please provide a reason for the cancellation. This request will be sent to the admin for approval.', 'waza-booking'); ?></p>
                
                <div class="waza-form-group">
                    <label for="cancel-reason"><?php esc_html_e('Cancellation Reason', 'waza-booking'); ?> <span class="required">*</span></label>
                    <textarea id="cancel-reason" name="reason" rows="4" required placeholder="<?php esc_attr_e('e.g., Personal emergency, scheduling conflict...', 'waza-booking'); ?>"></textarea>
                </div>

                <div id="cancel-form-message" class="waza-form-message" style="display: none;"></div>
            </div>

            <div class="waza-modal-footer">
                <button type="button" class="waza-btn waza-btn-secondary waza-modal-close">
                    <?php esc_html_e('Cancel', 'waza-booking'); ?>
                </button>
                <button type="submit" class="waza-btn waza-btn-danger" id="submit-cancel-btn">
                    <?php esc_html_e('Submit Request', 'waza-booking'); ?>
                </button>
            </div>
        </form>
    </div>
</div>
