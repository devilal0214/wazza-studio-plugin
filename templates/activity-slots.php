<?php
/**
 * Slot Booking Template - Direct Booking from Slot ID
 * 
 * @package WazaBooking
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check for slot_id (direct booking from activities-2 page)
$slot_id = 0;
if (isset($_GET['slot_id'])) {
    $slot_id = intval($_GET['slot_id']);
    
    // Security: Verify slot exists and is active
    global $wpdb;
    $slot = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}waza_slots WHERE id = %d AND status = 'active'",
        $slot_id
    ));
    
    if (!$slot) {
        echo '<div class="waza-error">';
        echo '<h2>' . esc_html__('Invalid Slot', 'waza-booking') . '</h2>';
        echo '<p>' . esc_html__('This slot is no longer available or doesn\'t exist.', 'waza-booking') . '</p>';
        echo '<a href="' . esc_url(home_url('/activities-2/')) . '" class="waza-btn waza-btn-primary">' . esc_html__('Browse Available Slots', 'waza-booking') . '</a>';
        echo '</div>';
        return;
    }
    
    $activity_id = $slot->activity_id;
    
} else {
    // Fallback to activity_id (legacy support)
    $activity_id = 0;
    if (isset($atts['activity_id']) && $atts['activity_id']) {
        $activity_id = intval($atts['activity_id']);
    } elseif (isset($_GET['activity_id'])) {
        $activity_id = intval($_GET['activity_id']);
    } else {
        $activity_id = get_query_var('activity_id', 0);
    }
}

if (!$activity_id) {
    echo '<div class="waza-error">';
    echo '<p>' . esc_html__('Please select a slot or activity to book.', 'waza-booking') . '</p>';
    echo '<a href="' . esc_url(home_url('/activities-2/')) . '" class="waza-btn waza-btn-primary">' . esc_html__('Browse Available Slots', 'waza-booking') . '</a>';
    echo '</div>';
    return;
}

$activity = get_post($activity_id);
if (!$activity || $activity->post_type !== 'waza_activity') {
    echo '<p>' . esc_html__('Invalid activity selected.', 'waza-booking') . '</p>';
    return;
}

$duration = get_post_meta($activity_id, '_waza_duration', true);
?>

<div class="waza-activity-booking-container">
    <!-- Activity Header -->
    <div class="activity-booking-header">
        <a href="<?php echo esc_url(home_url('/activities-2/')); ?>" class="back-link">
            ← <?php esc_html_e('Back to Activities', 'waza-booking'); ?>
        </a>
        
        <div class="activity-hero">
            <div class="activity-hero-image">
                <?php 
                $detail_image = get_post_meta($activity_id, '_waza_detail_image', true);
                if ($detail_image) : ?>
                    <img src="<?php echo esc_url($detail_image); ?>" alt="<?php echo esc_attr($activity->post_title); ?>" />
                <?php elseif (has_post_thumbnail($activity_id)) : ?>
                    <?php echo get_the_post_thumbnail($activity_id, 'large'); ?>
                <?php else : ?>
                    <div class="placeholder-hero">
                        <span class="hero-icon">🎯</span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="activity-hero-content">
                <h1><?php echo esc_html($activity->post_title); ?></h1>
                <div class="activity-quick-info">
                    <?php if ($duration) : ?>
                        <span class="info-badge">⏱️ <?php echo esc_html($duration); ?> mins</span>
                    <?php endif; ?>
                </div>
                <div class="activity-description">
                    <?php echo wpautop($activity->post_content); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Steps -->
    <?php if (!isset($slot_id)) : ?>
    <div class="booking-steps">
        <div class="step active" data-step="1">
            <span class="step-number">1</span>
            <span class="step-label"><?php esc_html_e('Select Date', 'waza-booking'); ?></span>
        </div>
        <div class="step" data-step="2">
            <span class="step-number">2</span>
            <span class="step-label"><?php esc_html_e('Choose Slot', 'waza-booking'); ?></span>
        </div>
        <div class="step" data-step="3">
            <span class="step-number">3</span>
            <span class="step-label"><?php esc_html_e('Your Details', 'waza-booking'); ?></span>
        </div>
        <div class="step" data-step="4">
            <span class="step-number">4</span>
            <span class="step-label"><?php esc_html_e('Payment', 'waza-booking'); ?></span>
        </div>
    </div>
    <?php else : ?>
    <div class="booking-steps">
        <div class="step active" data-step="1">
            <span class="step-number">1</span>
            <span class="step-label"><?php esc_html_e('Booking Details', 'waza-booking'); ?></span>
        </div>
        <div class="step" data-step="2">
            <span class="step-number">2</span>
            <span class="step-label"><?php esc_html_e('Payment', 'waza-booking'); ?></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Step 1: Date Selection (hidden when slot_id provided) -->
    <div class="booking-step-content" id="step-1" <?php echo isset($slot_id) ? 'style="display: none;"' : ''; ?>>
        <h3><?php esc_html_e('Select a Date', 'waza-booking'); ?></h3>
        <div class="date-selector">
            <input type="date" id="activity-date-picker" min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="form-navigation" style="margin-top: 20px;">
            <a href="<?php echo esc_url(home_url('/activities-2/')); ?>" class="waza-btn waza-btn-secondary">
                ← <?php esc_html_e('Back to Activities', 'waza-booking'); ?>
            </a>
        </div>
    </div>

    <!-- Step 2: Slot Selection (hidden when slot_id provided) -->
    <div class="booking-step-content" id="step-2" style="display: none;">
        <h3><?php esc_html_e('Available Time Slots', 'waza-booking'); ?></h3>
        <div class="selected-date-display">
            <strong><?php esc_html_e('Selected Date:', 'waza-booking'); ?></strong> 
            <span id="selected-date-text"></span>
        </div>
        
        <div id="slots-container" class="slots-grid">
            <!-- Slots will be loaded via AJAX -->
        </div>
        
        <div class="slots-loader" style="display: none;">
            <span class="spinner"></span>
            <p><?php esc_html_e('Loading available slots...', 'waza-booking'); ?></p>
        </div>
        
        <div class="form-navigation" style="margin-top: 20px;">
            <button type="button" id="back-to-date-selection" class="waza-btn waza-btn-secondary">
                ← <?php esc_html_e('Back to Date Selection', 'waza-booking'); ?>
            </button>
        </div>
    </div>

    <!-- Step 3: Booking Details -->
    <div class="booking-step-content" id="step-3" style="display: none;">
        <h3><?php esc_html_e('Your Booking Details', 'waza-booking'); ?></h3>
        
        <div class="booking-summary">
            <h4><?php esc_html_e('Summary', 'waza-booking'); ?></h4>
            <div class="summary-item">
                <span><?php esc_html_e('Activity:', 'waza-booking'); ?></span>
                <strong><?php echo esc_html($activity->post_title); ?></strong>
            </div>
            <div class="summary-item">
                <span><?php esc_html_e('Date:', 'waza-booking'); ?></span>
                <strong id="summary-date"></strong>
            </div>
            <div class="summary-item">
                <span><?php esc_html_e('Time:', 'waza-booking'); ?></span>
                <strong id="summary-time"></strong>
            </div>
            <div class="summary-item">
                <span><?php esc_html_e('Instructor:', 'waza-booking'); ?></span>
                <strong id="summary-instructor"></strong>
            </div>
            <div class="summary-item total">
                <span><?php esc_html_e('Total Amount:', 'waza-booking'); ?></span>
                <strong id="summary-amount"></strong>
            </div>
        </div>

        <form id="booking-details-form" class="waza-form">
            <input type="hidden" id="selected_slot_id" name="slot_id">
            <input type="hidden" name="activity_id" value="<?php echo esc_attr($activity_id); ?>">
            <input type="hidden" name="payment_method" value="online">
            
            <?php 
            // Get user data if logged in
            $current_user = wp_get_current_user();
            $user_name = $current_user->ID ? $current_user->display_name : '';
            $user_email = $current_user->ID ? $current_user->user_email : '';
            $user_phone = $current_user->ID ? get_user_meta($current_user->ID, 'phone', true) : '';
            ?>
            
            <div class="waza-form-group">
                <label for="booking_name"><?php esc_html_e('Full Name', 'waza-booking'); ?> <span class="required">*</span></label>
                <input type="text" id="booking_name" name="customer_name" value="<?php echo esc_attr($user_name); ?>" required>
            </div>

            <div class="waza-form-row">
                <div class="waza-form-group">
                    <label for="booking_email"><?php esc_html_e('Email', 'waza-booking'); ?> <span class="required">*</span></label>
                    <input type="email" id="booking_email" name="customer_email" value="<?php echo esc_attr($user_email); ?>" required>
                </div>

                <div class="waza-form-group">
                    <label for="booking_phone"><?php esc_html_e('Phone', 'waza-booking'); ?> <span class="required">*</span></label>
                    <input type="tel" id="booking_phone" name="customer_phone" value="<?php echo esc_attr($user_phone); ?>" required>
                </div>
            </div>

            <div class="waza-form-group">
                <label for="booking_notes"><?php esc_html_e('Special Requests (Optional)', 'waza-booking'); ?></label>
                <textarea id="booking_notes" name="notes" rows="3" placeholder="<?php esc_attr_e('Any special requirements or questions?', 'waza-booking'); ?>"></textarea>
            </div>

            <div class="form-navigation">
                <?php if (isset($slot_id)) : ?>
                    <a href="<?php echo esc_url(home_url('/activities-2/')); ?>" class="waza-btn waza-btn-secondary">
                        ← <?php esc_html_e('Back to Activities', 'waza-booking'); ?>
                    </a>
                <?php else : ?>
                    <button type="button" class="waza-btn waza-btn-secondary" id="back-to-slots">
                        ← <?php esc_html_e('Back to Slots', 'waza-booking'); ?>
                    </button>
                <?php endif; ?>
                <button type="submit" class="waza-btn waza-btn-primary waza-btn-lg">
                    <?php esc_html_e('Proceed to Payment', 'waza-booking'); ?> →
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.waza-activity-booking-container { max-width: 1200px; margin: 0 auto; padding: 30px 20px; }
.back-link { display: inline-block; margin-bottom: 20px; color: #2271b1; text-decoration: none; font-weight: 600; }
.back-link:hover { text-decoration: underline; }
.activity-hero { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
.activity-hero-image { height: 400px; }
.activity-hero-image img { width: 100%; height: 100%; object-fit: cover; }
.placeholder-hero { width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; }
.hero-icon { font-size: 120px; }
.activity-hero-content { padding: 40px; }
.activity-hero-content h1 { margin-bottom: 15px; font-size: 32px; color: #333; }
.activity-quick-info { display: flex; gap: 15px; margin-bottom: 20px; }
.info-badge { background: #f0f0f0; padding: 8px 16px; border-radius: 20px; font-size: 14px; font-weight: 600; }
.info-badge.price { background: #e7f3ff; color: #2271b1; }
.booking-steps { display: flex; justify-content: space-between; margin: 40px 0; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
.step { display: flex; align-items: center; gap: 10px; position: relative; flex: 1; }
.step::after { content: ''; position: absolute; left: 100%; top: 50%; width: 100%; height: 2px; background: #ddd; transform: translateY(-50%); z-index: -1; }
.step:last-child::after { display: none; }
.step-number { width: 40px; height: 40px; border-radius: 50%; background: #e0e0e0; color: #666; display: flex; align-items: center; justify-content: center; font-weight: 700; }
.step.active .step-number { background: #2271b1; color: white; }
.step.completed .step-number { background: #46b450; color: white; }
.booking-step-content { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); margin-bottom: 30px; }
.booking-step-content h3 { margin-bottom: 25px; font-size: 24px; color: #333; }
.date-selector { max-width: 400px; }
.date-selector input[type="date"] { width: 100%; padding: 15px; font-size: 16px; border: 2px solid #ddd; border-radius: 8px; }
.selected-date-display { background: #e7f3ff; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
.slots-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
.slot-card { background: white; border: 2px solid #ddd; border-radius: 12px; padding: 20px; cursor: pointer; transition: all 0.3s; }
.slot-card:hover { border-color: #2271b1; box-shadow: 0 4px 15px rgba(34, 113, 177, 0.2); }
.slot-card.selected { border-color: #2271b1; background: #e7f3ff; }
.slot-time { font-size: 18px; font-weight: 700; color: #333; margin-bottom: 10px; }
.slot-instructor { color: #666; margin-bottom: 10px; }
.slot-availability { font-size: 14px; color: #46b450; }
.slot-price { font-size: 20px; font-weight: 700; color: #2271b1; margin-top: 10px; }
.slot-price .sale-price { color: #e02b20; margin-right: 8px; }
.slot-price .original-price { text-decoration: line-through; color: #999; font-size: 16px; font-weight: 400; }
.booking-summary { background: #f8f9fa; padding: 25px; border-radius: 12px; margin-bottom: 30px; }
.booking-summary h4 { margin-bottom: 20px; color: #333; }
.summary-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #ddd; }
.summary-item:last-child { border-bottom: none; }
.summary-item.total { font-size: 20px; color: #2271b1; margin-top: 10px; padding-top: 15px; border-top: 2px solid #2271b1; }
.form-navigation { display: flex; justify-content: space-between; gap: 20px; margin-top: 30px; }
.slots-loader { text-align: center; padding: 40px; }
@media (max-width: 768px) {
    .activity-hero { grid-template-columns: 1fr; }
    .booking-steps { flex-direction: column; gap: 15px; }
    .step::after { display: none; }
}
</style>

<script>
jQuery(document).ready(function($) {
    const ajaxUrl = waza_frontend ? waza_frontend.ajax_url : ajaxurl;
    const nonce = '<?php echo wp_create_nonce('waza_booking_nonce'); ?>';
    const activityId = <?php echo intval($activity_id); ?>;
    const preSelectedSlotId = <?php echo isset($slot_id) ? intval($slot_id) : 0; ?>;
    const isDirectBooking = preSelectedSlotId > 0;
    let selectedSlot = null;
    
    // If slot_id is in URL, load slot details and go to booking form
    if (isDirectBooking) {
        // For direct booking, we only have 2 steps total
        // Step 3 (booking details) becomes Step 1
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'waza_get_slot_details',
                nonce: nonce,
                slot_id: preSelectedSlotId
            },
            success: function(response) {
                if (response.success && response.data) {
                    selectedSlot = response.data;
                    updateSummary();
                    showStep(3); // Show booking details as step 1
                } else {
                    alert('<?php esc_html_e('Failed to load slot details', 'waza-booking'); ?>');
                    window.location.href = '<?php echo esc_url(home_url('/activities-2/')); ?>';
                }
            },
            error: function() {
                alert('<?php esc_html_e('Error loading slot details', 'waza-booking'); ?>');
                window.location.href = '<?php echo esc_url(home_url('/activities-2/')); ?>';
            }
        });
    } else {
        // Normal flow: show date selection
        showStep(1);
    }
    
    // Date selection
    $('#activity-date-picker').on('change', function() {
        const selectedDate = $(this).val();
        loadSlots(selectedDate);
        $('#selected-date-text').text(formatDate(selectedDate));
        showStep(2);
    });
    
    // Auto-load and SHOW today's slots on page load
    const todayDate = $('#activity-date-picker').val();
    if (todayDate) {
        loadSlots(todayDate);
        $('#selected-date-text').text(formatDate(todayDate));
        // Automatically show step 2 with today's slots
        showStep(2);
    }
    
    // Load slots for selected date
    function loadSlots(date) {
        $('.slots-loader').show();
        $('#slots-container').empty();
        
        // Convert date to YYYY-MM-DD format if needed
        let formattedDate = date;
        if (date.includes('/')) {
            // MM/DD/YYYY to YYYY-MM-DD
            const parts = date.split('/');
            formattedDate = parts[2] + '-' + parts[0].padStart(2, '0') + '-' + parts[1].padStart(2, '0');
        } else if (date.match(/^\d{2}-\d{2}-\d{4}$/)) {
            // MM-DD-YYYY to YYYY-MM-DD
            const parts = date.split('-');
            formattedDate = parts[2] + '-' + parts[0] + '-' + parts[1];
        }
        
        console.log('Loading slots for activity:', activityId, 'date:', date, 'formatted:', formattedDate);
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'waza_get_activity_slots',
                nonce: nonce,
                activity_id: activityId,
                selected_date: formattedDate
            },
            success: function(response) {
                console.log('Slots response:', response);
                if (response.success) {
                    displaySlots(response.data.slots);
                } else {
                    console.error('No slots found:', response.data);
                    $('#slots-container').html('<p class="no-slots">' + (response.data.message || 'No slots available') + '</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error, xhr.responseText);
                $('#slots-container').html('<p class="no-slots">Error loading slots. Please try again.</p>');
            },
            complete: function() {
                $('.slots-loader').hide();
            }
        });
    }
    
    // Display slots
    function displaySlots(slots) {
        const container = $('#slots-container');
        container.empty();
        
        console.log('Displaying ' + slots.length + ' slots:', slots);
        
        if (!slots || slots.length === 0) {
            container.html('<p class="no-slots">No slots available for this date.</p>');
            return;
        }
        
        slots.forEach(function(slot) {
            // Display price with sale price logic
            let priceHtml = '';
            if (slot.sale_price && slot.original_price) {
                priceHtml = `<div class="slot-price">
                    <span class="sale-price">₹${parseFloat(slot.sale_price).toLocaleString()}</span>
                    <span class="original-price">₹${parseFloat(slot.original_price).toLocaleString()}</span>
                </div>`;
            } else {
                priceHtml = `<div class="slot-price">₹${parseFloat(slot.price).toLocaleString()}</div>`;
            }
            
            const card = $(`
                <div class="slot-card" data-slot-id="${slot.id}">
                    <div class="slot-time">${slot.start_time} - ${slot.end_time}</div>
                    <div class="slot-instructor">👤 ${slot.instructor_name}</div>
                    <div class="slot-availability">✓ ${slot.available_spots} spots available</div>
                    ${priceHtml}
                </div>
            `);
            
            card.on('click', function() {
                $('.slot-card').removeClass('selected');
                $(this).addClass('selected');
                selectedSlot = slot;
                updateSummary();
                showStep(3);
            });
            
            container.append(card);
        });
    }
    
    // Update booking summary
    function updateSummary() {
        if (selectedSlot) {
            // Use formatted_date from server if available (direct booking), otherwise format from date picker
            const displayDate = selectedSlot.formatted_date || formatDate($('#activity-date-picker').val());
            $('#summary-date').text(displayDate);
            $('#summary-time').text(selectedSlot.start_time + ' - ' + selectedSlot.end_time);
            $('#summary-instructor').text(selectedSlot.instructor_name || selectedSlot.activity_name || 'TBA');
            
            // Show final price (sale price if available)
            const finalPrice = selectedSlot.price; // Already calculated on server
            if (selectedSlot.sale_price && selectedSlot.original_price) {
                $('#summary-amount').html('₹' + parseFloat(selectedSlot.sale_price).toLocaleString() + 
                    ' <span style="text-decoration:line-through;color:#999;font-size:14px;">₹' + 
                    parseFloat(selectedSlot.original_price).toLocaleString() + '</span>');
            } else {
                $('#summary-amount').text('₹' + parseFloat(finalPrice).toLocaleString());
            }
            
            $('#selected_slot_id').val(selectedSlot.id);
        }
    }
    
    // Show step
    function showStep(stepNum) {
        $('.booking-step-content').hide();
        $('#step-' + stepNum).show();
        
        if (isDirectBooking) {
            // For direct booking: only 2 steps (step 3 = step 1, step 4 = step 2)
            $('.step').removeClass('active completed');
            if (stepNum === 3) {
                $('.step[data-step="1"]').addClass('active');
            } else if (stepNum === 4) {
                $('.step[data-step="1"]').addClass('completed');
                $('.step[data-step="2"]').addClass('active');
            }
        } else {
            // Normal flow: 4 steps
            $('.step').removeClass('active completed');
            for (let i = 1; i < stepNum; i++) {
                $('.step[data-step="' + i + '"]').addClass('completed');
            }
            $('.step[data-step="' + stepNum + '"]').addClass('active');
        }
    }
    
    // Back to date selection button
    $('#back-to-date-selection').on('click', function() {
        showStep(1);
    });
    
    // Back to slots
    $('#back-to-slots').on('click', function() {
        showStep(2);
    });
    
    // Submit booking form
    $('#booking-details-form').on('submit', function(e) {
        e.preventDefault();
        // Use existing booking AJAX process
        const formData = $(this).serializeArray();
        processActivityBooking(formData);
    });
    
    // Process activity booking using existing system
    function processActivityBooking(formData) {
        const bookingData = {};
        formData.forEach(item => {
            bookingData[item.name] = item.value;
        });
        
        // Override slot_id from selected slot (in case hidden field wasn't set)
        bookingData.slot_id = selectedSlot.id;
        bookingData.quantity = 1; // Default to 1 for activity browser
        bookingData.payment_method = 'online'; // Default payment method
        bookingData.nonce = '<?php echo wp_create_nonce('waza_frontend_nonce'); ?>';
        
        console.log('Submitting booking data:', bookingData);
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'waza_process_booking',
                ...bookingData
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.payment_required) {
                        // Redirect to payment using existing PaymentManager
                        handlePayment(response.data);
                    } else {
                        // Free booking - show success
                        showBookingSuccess(response.data);
                    }
                } else {
                    alert(response.data.message || 'Booking failed. Please try again.');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    }
    
    // Handle payment redirect
    function handlePayment(data) {
        // Get customer details from form
        const customerName = $('#booking_name').val() || '';
        const customerEmail = $('#booking_email').val() || '';
        
        // Build checkout URL with all required parameters
        const checkoutUrl = '<?php echo home_url('/checkout/'); ?>?' + 
            'booking_id=' + data.booking_id + 
            '&amount=' + data.total_amount + 
            '&type=booking' +
            '&customer_name=' + encodeURIComponent(customerName) +
            '&customer_email=' + encodeURIComponent(customerEmail);
        
        console.log('Redirecting to checkout:', checkoutUrl);
        window.location.href = checkoutUrl;
    }
    
    // Show success message
    function showBookingSuccess(data) {
        alert('Booking confirmed! Booking ID: ' + data.booking_id);
        window.location.href = '<?php echo home_url('/my-bookings/'); ?>';
    }
    
    // Helper: Format date
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    }
});
</script>
