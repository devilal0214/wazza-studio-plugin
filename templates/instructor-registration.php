<?php
/**
 * Instructor Registration Form Template
 * 
 * @package WazaBooking
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="waza-instructor-registration">
    <div class="waza-form-container">
        <div class="waza-form-header">
            <h2><?php esc_html_e('Become an Instructor', 'waza-booking'); ?></h2>
            <p><?php esc_html_e('Join our team of professional instructors and trainers', 'waza-booking'); ?></p>
        </div>

        <form id="waza-instructor-registration-form" class="waza-form">
            <!-- Personal Information -->
            <div class="waza-form-section">
                <h3><?php esc_html_e('Personal Information', 'waza-booking'); ?></h3>
                
                <div class="waza-form-row">
                    <div class="waza-form-group">
                        <label for="instructor_name"><?php esc_html_e('Full Name', 'waza-booking'); ?> <span class="required">*</span></label>
                        <input type="text" id="instructor_name" name="instructor_name" required>
                    </div>
                </div>

                <div class="waza-form-row">
                    <div class="waza-form-group">
                        <label for="instructor_email"><?php esc_html_e('Email Address', 'waza-booking'); ?> <span class="required">*</span></label>
                        <input type="email" id="instructor_email" name="instructor_email" required>
                    </div>

                    <div class="waza-form-group">
                        <label for="instructor_phone"><?php esc_html_e('Phone Number', 'waza-booking'); ?> <span class="required">*</span></label>
                        <input type="tel" id="instructor_phone" name="instructor_phone" required>
                    </div>
                </div>
            </div>

            <!-- Professional Details -->
            <div class="waza-form-section">
                <h3><?php esc_html_e('Professional Details', 'waza-booking'); ?></h3>
                
                <div class="waza-form-row">
                    <div class="waza-form-group">
                        <label for="activity_type"><?php esc_html_e('Primary Activity Type', 'waza-booking'); ?> <span class="required">*</span></label>
                        <select id="activity_type" name="activity_type" required>
                            <option value=""><?php esc_html_e('Select Activity Type', 'waza-booking'); ?></option>
                            <option value="dance"><?php esc_html_e('Dance', 'waza-booking'); ?></option>
                            <option value="yoga"><?php esc_html_e('Yoga', 'waza-booking'); ?></option>
                            <option value="zumba"><?php esc_html_e('Zumba', 'waza-booking'); ?></option>
                            <option value="fitness"><?php esc_html_e('Fitness Training', 'waza-booking'); ?></option>
                            <option value="martial-arts"><?php esc_html_e('Martial Arts', 'waza-booking'); ?></option>
                            <option value="aerobics"><?php esc_html_e('Aerobics', 'waza-booking'); ?></option>
                            <option value="other"><?php esc_html_e('Other', 'waza-booking'); ?></option>
                        </select>
                    </div>

                    <div class="waza-form-group">
                        <label for="experience_years"><?php esc_html_e('Years of Experience', 'waza-booking'); ?> <span class="required">*</span></label>
                        <input type="number" id="experience_years" name="experience_years" min="0" max="50" required>
                    </div>
                </div>

                <div class="waza-form-row">
                    <div class="waza-form-group full-width">
                        <label for="instructor_bio"><?php esc_html_e('Bio / About You', 'waza-booking'); ?> <span class="required">*</span></label>
                        <textarea id="instructor_bio" name="instructor_bio" rows="4" required placeholder="<?php esc_attr_e('Tell us about your experience, certifications, and teaching style...', 'waza-booking'); ?>"></textarea>
                    </div>
                </div>
                
                <div class="waza-form-row">
                    <div class="waza-form-group">
                        <label for="certifications"><?php esc_html_e('Certifications', 'waza-booking'); ?></label>
                        <textarea id="certifications" name="certifications" rows="3" placeholder="<?php esc_attr_e('List your certifications...', 'waza-booking'); ?>"></textarea>
                    </div>
                </div>
                
                <div class="waza-form-row">
                    <div class="waza-form-group">
                        <label for="instructor_rating"><?php esc_html_e('Self Rating', 'waza-booking'); ?> <span class="required">*</span></label>
                        <select id="instructor_rating" name="instructor_rating" required>
                            <option value=""><?php esc_html_e('Select your skill level', 'waza-booking'); ?></option>
                            <option value="2">⭐⭐ (2 Stars - Developing)</option>
                            <option value="3">⭐⭐⭐ (3 Stars - Competent)</option>
                            <option value="5">⭐⭐⭐⭐⭐ (5 Stars - Expert)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Social Links (Optional) -->
            <div class="waza-form-section">
                <h3><?php esc_html_e('Social Links (Optional)', 'waza-booking'); ?></h3>
                
                <div id="social-links-container">
                    <div class="social-link-row" style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
                        <select name="social_platform[]" style="width: 150px;">
                            <option value="instagram">Instagram</option>
                            <option value="facebook">Facebook</option>
                            <option value="twitter">Twitter</option>
                            <option value="linkedin">LinkedIn</option>
                            <option value="youtube">YouTube</option>
                            <option value="website">Website</option>
                        </select>
                        <input type="url" name="social_url[]" placeholder="https://..." style="flex: 1;">
                        <button type="button" class="remove-social-link" style="padding: 8px 15px; background: #dc3232; color: white; border: none; border-radius: 4px; cursor: pointer;">×</button>
                    </div>
                </div>
                <button type="button" id="add-social-link" class="waza-btn-secondary" style="margin-top: 10px;">+ <?php esc_html_e('Add Social Link', 'waza-booking'); ?></button>
            </div>

            <!-- Terms & Conditions -->
            <div class="waza-form-section">
                <div class="waza-form-group">
                    <label class="waza-checkbox-label">
                        <input type="checkbox" id="accept_terms" name="accept_terms" required>
                        <span><?php esc_html_e('I agree to the terms and conditions and understand my application will be reviewed by the admin', 'waza-booking'); ?></span>
                    </label>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="waza-form-actions">
                <button type="submit" class="waza-btn waza-btn-primary waza-btn-lg">
                    <span class="button-text"><?php esc_html_e('Submit Application', 'waza-booking'); ?></span>
                    <span class="button-loader" style="display: none;">
                        <span class="spinner"></span> <?php esc_html_e('Submitting...', 'waza-booking'); ?>
                    </span>
                </button>
            </div>

            <!-- Messages -->
            <div id="instructor-registration-message" class="waza-message" style="display: none;"></div>
        </form>
    </div>
</div>
