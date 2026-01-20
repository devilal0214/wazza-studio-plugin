<?php
/**
 * Studio Rental Form Template
 * 
 * @package WazaBooking
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$rental_manager = \WazaBooking\Core\Plugin::get_instance()->get_manager('rental');
$settings = $rental_manager ? $rental_manager->get_rental_settings() : [];
$rental_types = $rental_manager ? $rental_manager->get_rental_types() : [];
$durations = $rental_manager ? $rental_manager->get_durations() : [];
$pricing = $settings['pricing'] ?? [];
$currency_symbol = $settings['currency_symbol'] ?? '₹';
?>

<div class="waza-rental-container">
    <div class="waza-rental-header">
        <h2><?php esc_html_e('WAZA STUDIO - RENTAL BOOKING', 'waza-booking'); ?></h2>
        <p><?php esc_html_e('Book our professional studio space for rehearsals, shoots, or commercial events', 'waza-booking'); ?></p>
    </div>

    <!-- Pricing Cards -->
    <div class="waza-rental-pricing">
        <?php foreach ($rental_types as $type_key => $type) : ?>
            <div class="pricing-card <?php echo esc_attr($type_key); ?>">
                <div class="pricing-header">
                    <span class="pricing-icon"><?php echo esc_html($type['icon'] ?? '🎨'); ?></span>
                    <h3><?php echo esc_html(strtoupper($type['label'] ?? ucfirst($type_key))); ?></h3>
                </div>
                <div class="pricing-rates">
                    <?php foreach ($durations as $dur_key => $duration) : ?>
                        <?php if (isset($pricing[$type_key][$dur_key]) && $pricing[$type_key][$dur_key] > 0) : ?>
                            <div class="rate-item">
                                <strong><?php echo esc_html($currency_symbol . number_format($pricing[$type_key][$dur_key])); ?></strong> 
                                / <?php echo esc_html($duration['label']); ?>
                                <?php if ($duration['hours'] > 1) : ?>
                                    (<?php echo esc_html($duration['hours']); ?> hrs)
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php if (!empty($type['includes'])) : ?>
                    <div class="pricing-includes">
                        <p><strong><?php esc_html_e('Includes:', 'waza-booking'); ?></strong></p>
                        <ul>
                            <?php 
                            $includes = array_map('trim', explode(',', $type['includes']));
                            foreach ($includes as $include) : 
                                if (!empty($include)) :
                            ?>
                                <li>✔ <?php echo esc_html($include); ?></li>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <?php if (!empty($type['excludes'])) : ?>
                    <div class="pricing-excludes">
                        <p><strong><?php esc_html_e('Excludes:', 'waza-booking'); ?></strong></p>
                        <ul>
                            <?php 
                            $excludes = array_map('trim', explode(',', $type['excludes']));
                            foreach ($excludes as $exclude) : 
                                if (!empty($exclude)) :
                            ?>
                                <li>✖ <?php echo esc_html($exclude); ?></li>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Booking Form -->
    <div class="waza-rental-form-container">
        <h3><?php esc_html_e('Book Your Studio Time', 'waza-booking'); ?></h3>
        
        <form id="waza-rental-booking-form" class="waza-form">
            <div class="waza-form-section">
                <h4><?php esc_html_e('Personal Information', 'waza-booking'); ?></h4>
                
                <div class="waza-form-row">
                    <div class="waza-form-group">
                        <label for="customer_name"><?php esc_html_e('Full Name', 'waza-booking'); ?> <span class="required">*</span></label>
                        <input type="text" id="customer_name" name="customer_name" required>
                    </div>
                </div>

                <div class="waza-form-row">
                    <div class="waza-form-group">
                        <label for="customer_email"><?php esc_html_e('Email Address', 'waza-booking'); ?> <span class="required">*</span></label>
                        <input type="email" id="customer_email" name="customer_email" required>
                    </div>

                    <div class="waza-form-group">
                        <label for="customer_phone"><?php esc_html_e('Phone Number', 'waza-booking'); ?> <span class="required">*</span></label>
                        <input type="tel" id="customer_phone" name="customer_phone" required>
                    </div>
                </div>
            </div>

            <div class="waza-form-section">
                <h4><?php esc_html_e('Rental Details', 'waza-booking'); ?></h4>
                
                <div class="waza-form-row">
                    <div class="waza-form-group">
                        <label for="rental_type"><?php esc_html_e('Rental Type', 'waza-booking'); ?> <span class="required">*</span></label>
                        <select id="rental_type" name="rental_type" required>
                            <option value=""><?php esc_html_e('Select Rental Type', 'waza-booking'); ?></option>
                            <?php foreach ($rental_types as $type_key => $type) : ?>
                                <option value="<?php echo esc_attr($type_key); ?>">
                                    <?php echo esc_html($type['icon'] . ' ' . $type['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="waza-form-group">
                        <label for="duration_type"><?php esc_html_e('Duration', 'waza-booking'); ?> <span class="required">*</span></label>
                        <select id="duration_type" name="duration_type" required>
                            <option value=""><?php esc_html_e('Select Duration', 'waza-booking'); ?></option>
                            <?php foreach ($durations as $dur_key => $duration) : ?>
                                <option value="<?php echo esc_attr($dur_key); ?>">
                                    <?php echo esc_html($duration['label']); ?>
                                    <?php if ($duration['hours'] > 1) : ?>
                                        (<?php echo esc_html($duration['hours']); ?> hours)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="waza-form-row">
                    <div class="waza-form-group">
                        <label for="rental_date"><?php esc_html_e('Date', 'waza-booking'); ?> <span class="required">*</span></label>
                        <input type="date" id="rental_date" name="rental_date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <div class="waza-form-row">
                    <div class="waza-form-group">
                        <label for="start_time"><?php esc_html_e('Start Time', 'waza-booking'); ?> <span class="required">*</span></label>
                        <input type="time" id="start_time" name="start_time" min="10:00" max="22:00" required>
                        <small><?php esc_html_e('Studio hours: 10:00 AM - 10:00 PM', 'waza-booking'); ?></small>
                    </div>

                    <div class="waza-form-group">
                        <label for="end_time"><?php esc_html_e('End Time', 'waza-booking'); ?> <span class="required">*</span></label>
                        <input type="time" id="end_time" name="end_time" min="10:00" max="22:00" required>
                    </div>
                </div>

                <div class="rental-amount-display" style="display: none;">
                    <h4><?php esc_html_e('Total Amount:', 'waza-booking'); ?> <span id="rental_amount">₹0</span></h4>
                </div>

                <button type="button" id="check_availability_btn" class="waza-btn waza-btn-secondary">
                    <?php esc_html_e('Check Availability', 'waza-booking'); ?>
                </button>

                <div id="availability_message" style="display: none; margin-top: 15px;"></div>
            </div>

            <div class="waza-form-section">
                <div class="waza-form-group">
                    <label for="special_requirements"><?php esc_html_e('Special Requirements (Optional)', 'waza-booking'); ?></label>
                    <textarea id="special_requirements" name="special_requirements" rows="3" placeholder="<?php esc_attr_e('Any special setup or equipment needs?', 'waza-booking'); ?>"></textarea>
                </div>
            </div>

            <div class="waza-form-actions">
                <button type="submit" class="waza-btn waza-btn-primary waza-btn-lg" disabled id="submit_rental_btn">
                    <span class="button-text"><?php esc_html_e('Proceed to Payment', 'waza-booking'); ?></span>
                    <span class="button-loader" style="display: none;">
                        <span class="spinner"></span> <?php esc_html_e('Processing...', 'waza-booking'); ?>
                    </span>
                </button>
            </div>

            <div id="rental-booking-message" class="waza-message" style="display: none;"></div>
        </form>
    </div>

    <div class="waza-rental-info">
        <p><strong>⏰ Studio Timings:</strong> 10:00 AM – 10:00 PM</p>
        <p><strong>📌</strong> Advance payment required to confirm booking</p>
        <p><strong>📌</strong> Extra time will be charged additionally</p>
    </div>
</div>

<style>
.waza-rental-container { max-width: 1200px; margin: 0 auto; padding: 30px 20px; }
.waza-rental-header { text-align: center; margin-bottom: 40px; }
.waza-rental-header h2 { font-size: 32px; margin-bottom: 10px; color: #333; }
.waza-rental-pricing { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-bottom: 50px; }
.pricing-card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); position: relative; }
.pricing-card.commercial { border: 3px solid #2271b1; }
.pricing-header { text-align: center; margin-bottom: 20px; position: relative; }
.pricing-icon { font-size: 48px; display: block; margin-bottom: 10px; }
.pricing-header h3 { font-size: 20px; color: #333; margin: 0; }
.popular-badge { position: absolute; top: -10px; right: -10px; background: #2271b1; color: white; padding: 5px 15px; border-radius: 20px; font-size: 12px; }
.pricing-rates { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
.rate-item { padding: 8px 0; border-bottom: 1px solid #ddd; }
.rate-item:last-child { border-bottom: none; }
.pricing-includes ul, .pricing-excludes ul { list-style: none; padding: 0; margin: 10px 0; }
.pricing-includes li, .pricing-excludes li { padding: 5px 0; font-size: 14px; }
.waza-rental-form-container { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); margin-bottom: 30px; }
.waza-form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px; }
.rental-amount-display { background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center; }
.rental-amount-display h4 { margin: 0; font-size: 24px; color: #2271b1; }
.waza-rental-info { background: #fff3cd; padding: 20px; border-radius: 8px; border-left: 4px solid #ffc107; }
.waza-rental-info p { margin: 5px 0; }
#availability_message.success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; border: 1px solid #c3e6cb; }
#availability_message.error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; border: 1px solid #f5c6cb; }
</style>

<script>
jQuery(document).ready(function($) {
    const pricing = <?php echo json_encode($pricing); ?>;
    const ajaxUrl = waza_frontend ? waza_frontend.ajax_url : ajaxurl;
    const nonce = '<?php echo wp_create_nonce('waza_rental_nonce'); ?>';
    
    // Calculate and display amount
    function updateAmount() {
        const rentalType = $('#rental_type').val();
        const durationType = $('#duration_type').val();
        
        if (rentalType && durationType && pricing[rentalType] && pricing[rentalType][durationType]) {
            const amount = pricing[rentalType][durationType];
            $('#rental_amount').text('₹' + amount.toLocaleString());
            $('.rental-amount-display').slideDown();
        } else {
            $('.rental-amount-display').slideUp();
        }
    }
    
    $('#rental_type, #duration_type').on('change', updateAmount);
    
    // Check availability
    $('#check_availability_btn').on('click', function() {
        const date = $('#rental_date').val();
        const startTime = $('#start_time').val();
        const endTime = $('#end_time').val();
        
        if (!date || !startTime || !endTime) {
            alert('<?php esc_html_e('Please select date and time', 'waza-booking'); ?>');
            return;
        }
        
        $(this).prop('disabled', true).text('<?php esc_html_e('Checking...', 'waza-booking'); ?>');
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'waza_check_rental_availability',
                nonce: nonce,
                date: date,
                start_time: startTime,
                end_time: endTime
            },
            success: function(response) {
                if (response.success) {
                    $('#availability_message')
                        .removeClass('error')
                        .addClass('success')
                        .html('✅ ' + response.data.message)
                        .slideDown();
                    $('#submit_rental_btn').prop('disabled', false);
                } else {
                    $('#availability_message')
                        .removeClass('success')
                        .addClass('error')
                        .html('❌ ' + response.data.message)
                        .slideDown();
                    $('#submit_rental_btn').prop('disabled', true);
                }
            },
            complete: function() {
                $('#check_availability_btn').prop('disabled', false).text('<?php esc_html_e('Check Availability', 'waza-booking'); ?>');
            }
        });
    });
    
    // Submit rental booking
    $('#waza-rental-booking-form').on('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = $('#submit_rental_btn');
        const buttonText = submitBtn.find('.button-text');
        const buttonLoader = submitBtn.find('.button-loader');
        
        submitBtn.prop('disabled', true);
        buttonText.hide();
        buttonLoader.show();
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: $(this).serialize() + '&action=waza_submit_rental_booking&nonce=' + nonce,
            success: function(response) {
                if (response.success) {
                    $('#rental-booking-message')
                        .removeClass('error')
                        .addClass('success')
                        .html('✅ ' + response.data.message)
                        .fadeIn();
                    
                    // Store temp_id and amount for payment
                    const paymentData = {
                        temp_rental_id: response.data.temp_id,
                        amount: response.data.amount,
                        type: 'rental'
                    };
                    
                    // Trigger payment modal (if exists) or redirect
                    if (typeof wazaPayment !== 'undefined' && wazaPayment.initiatePayment) {
                        // Use existing payment modal
                        setTimeout(function() {
                            wazaPayment.initiateRentalPayment(paymentData);
                        }, 1000);
                    } else {
                        // Fallback: redirect to payment page
                        setTimeout(function() {
                            window.location.href = response.data.redirect;
                        }, 2000);
                    }
                } else {
                    $('#rental-booking-message')
                        .removeClass('success')
                        .addClass('error')
                        .html('❌ ' + response.data.message)
                        .fadeIn();
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false);
                buttonText.show();
                buttonLoader.hide();
            }
        });
    });
});
</script>
