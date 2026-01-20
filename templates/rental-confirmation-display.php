<?php
/**
 * Rental Confirmation Display
 * 
 * @package WazaBooking
 */

if (!defined('ABSPATH') || !isset($rental)) {
    exit;
}

$settings = \WazaBooking\Admin\RentalSettingsManager::get_settings();
$currency = $settings['currency_symbol'] ?? '₹';

// Generate QR code for rental
$rental_code = 'WR-' . str_pad($rental->id, 5, '0', STR_PAD_LEFT);

// Generate QR code image using QRManager
$qr_manager = new \WazaBooking\QR\QRManager();
$qr_code_base64 = $qr_manager->generate_qr_image($rental_code, 250);

// Set timezone to Asia/Kolkata for all date operations
date_default_timezone_set('Asia/Kolkata');
?>

<div class="waza-booking-confirmation waza-rental-confirmation">
    <div class="waza-confirmation-header">
        <div class="waza-success-icon">
            <svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="32" cy="32" r="32" fill="#10B981"/>
                <path d="M20 32L28 40L44 24" stroke="white" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <h1><?php esc_html_e('Studio Rental Confirmed!', 'waza-booking'); ?></h1>
        <p class="waza-confirmation-message">
            <?php esc_html_e('Your studio rental has been successfully booked and payment confirmed.', 'waza-booking'); ?>
        </p>
    </div>

    <div class="waza-confirmation-content">
        <!-- QR Code Card -->
        <div class="waza-confirmation-card waza-qr-card">
            <h2><?php esc_html_e('Rental QR Code', 'waza-booking'); ?></h2>
            <div class="waza-qr-container">
                <?php if ($qr_code_base64): ?>
                    <img src="<?php echo esc_attr($qr_code_base64); ?>" alt="Rental QR Code" class="waza-qr-image" id="rental-qr-image" />
                    <p class="waza-qr-code-text"><?php echo esc_html($rental_code); ?></p>
                    <p class="waza-qr-instruction"><?php esc_html_e('Show this QR code at the studio entrance', 'waza-booking'); ?></p>
                    <button onclick="downloadRentalQR()" class="waza-btn-download-qr">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                        <?php esc_html_e('Download QR Code', 'waza-booking'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Rental Details Card -->
        <div class="waza-confirmation-card">
            <h2><?php esc_html_e('Rental Details', 'waza-booking'); ?></h2>
            <div class="waza-detail-row">
                <span class="waza-detail-label"><?php esc_html_e('Rental ID:', 'waza-booking'); ?></span>
                <span class="waza-detail-value"><strong>#<?php echo esc_html($rental->id); ?></strong></span>
            </div>
            <div class="waza-detail-row">
                <span class="waza-detail-label"><?php esc_html_e('Rental Code:', 'waza-booking'); ?></span>
                <span class="waza-detail-value"><strong><?php echo esc_html($rental_code); ?></strong></span>
            </div>
            <div class="waza-detail-row">
                <span class="waza-detail-label"><?php esc_html_e('Rental Type:', 'waza-booking'); ?></span>
                <span class="waza-detail-value"><?php echo esc_html(ucwords(str_replace('_', ' ', $rental->rental_type))); ?></span>
            </div>
            <div class="waza-detail-row">
                <span class="waza-detail-label"><?php esc_html_e('Duration:', 'waza-booking'); ?></span>
                <span class="waza-detail-value"><?php echo esc_html(ucwords(str_replace('_', ' ', $rental->duration_type))); ?></span>
            </div>
            <div class="waza-detail-row">
                <span class="waza-detail-label"><?php esc_html_e('Date:', 'waza-booking'); ?></span>
                <span class="waza-detail-value"><?php echo esc_html(date_i18n('l, F j, Y', strtotime($rental->rental_date))); ?></span>
            </div>
            <div class="waza-detail-row">
                <span class="waza-detail-label"><?php esc_html_e('Time:', 'waza-booking'); ?></span>
                <span class="waza-detail-value">
                    <?php echo esc_html(date_i18n('g:i A', strtotime($rental->start_time))); ?> - 
                    <?php echo esc_html(date_i18n('g:i A', strtotime($rental->end_time))); ?>
                </span>
            </div>
            <?php if (!empty($rental->special_requirements)) : ?>
                <div class="waza-detail-row">
                    <span class="waza-detail-label"><?php esc_html_e('Special Requirements:', 'waza-booking'); ?></span>
                    <span class="waza-detail-value"><?php echo esc_html($rental->special_requirements); ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Customer Information Card -->
        <div class="waza-confirmation-card">
            <h2><?php esc_html_e('Customer Information', 'waza-booking'); ?></h2>
            <div class="waza-detail-row">
                <span class="waza-detail-label"><?php esc_html_e('Name:', 'waza-booking'); ?></span>
                <span class="waza-detail-value"><?php echo esc_html($rental->customer_name); ?></span>
            </div>
            <div class="waza-detail-row">
                <span class="waza-detail-label"><?php esc_html_e('Email:', 'waza-booking'); ?></span>
                <span class="waza-detail-value"><?php echo esc_html($rental->customer_email); ?></span>
            </div>
            <div class="waza-detail-row">
                <span class="waza-detail-label"><?php esc_html_e('Phone:', 'waza-booking'); ?></span>
                <span class="waza-detail-value"><?php echo esc_html($rental->customer_phone); ?></span>
            </div>
        </div>

        <!-- Payment Information Card -->
        <div class="waza-confirmation-card waza-payment-card">
            <h2><?php esc_html_e('Payment Information', 'waza-booking'); ?></h2>
            <div class="waza-detail-row">
                <span class="waza-detail-label"><?php esc_html_e('Amount Paid:', 'waza-booking'); ?></span>
                <span class="waza-detail-value waza-amount">
                    <?php echo esc_html($currency . number_format($rental->total_amount, 2)); ?>
                </span>
            </div>
            <div class="waza-detail-row">
                <span class="waza-detail-label"><?php esc_html_e('Payment Status:', 'waza-booking'); ?></span>
                <span class="waza-detail-value">
                    <span class="waza-badge waza-badge-success">
                        <?php echo esc_html(ucfirst($rental->payment_status ?? 'Completed')); ?>
                    </span>
                </span>
            </div>
            <div class="waza-detail-row">
                <span class="waza-detail-label"><?php esc_html_e('Booking Status:', 'waza-booking'); ?></span>
                <span class="waza-detail-value">
                    <span class="waza-badge waza-badge-success">
                        <?php echo esc_html(ucfirst($rental->booking_status ?? 'Confirmed')); ?>
                    </span>
                </span>
            </div>
        </div>

        <!-- Important Information -->
        <div class="waza-confirmation-card waza-info-card">
            <h2><?php esc_html_e('Important Information', 'waza-booking'); ?></h2>
            <ul class="waza-info-list">
                <li>
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <?php esc_html_e('Please arrive 10-15 minutes before your scheduled time.', 'waza-booking'); ?>
                </li>
                <li>
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <?php esc_html_e('A confirmation email has been sent to your email address.', 'waza-booking'); ?>
                </li>
                <li>
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <?php esc_html_e('For any changes or cancellations, please contact us immediately.', 'waza-booking'); ?>
                </li>
            </ul>
        </div>
    </div>

    <!-- Actions -->
    <div class="waza-confirmation-actions">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="waza-btn waza-btn-primary">
            <?php esc_html_e('Go to Home', 'waza-booking'); ?>
        </a>
    </div>
</div>

<script>
function downloadRentalQR() {
    const img = document.getElementById('rental-qr-image');
    const link = document.createElement('a');
    link.href = img.src;
    link.download = '<?php echo esc_js($rental_code); ?>-QR.png';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

<style>
.waza-rental-confirmation {
    max-width: 800px;
    margin: 40px auto;
    padding: 0 20px;
}

.waza-confirmation-header {
    text-align: center;
    margin-bottom: 40px;
}

.waza-success-icon {
    margin-bottom: 20px;
}

.waza-confirmation-header h1 {
    color: #10B981;
    margin: 20px 0 10px;
    font-size: 32px;
}

.waza-confirmation-message {
    color: #6B7280;
    font-size: 16px;
}

.waza-confirmation-card {
    background: white;
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    padding: 24px;
    margin-bottom: 20px;
}

.waza-confirmation-card h2 {
    margin: 0 0 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #E5E7EB;
    font-size: 20px;
    color: #111827;
}

.waza-detail-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #F3F4F6;
}

.waza-detail-row:last-child {
    border-bottom: none;
}

.waza-detail-label {
    color: #6B7280;
    font-weight: 500;
}

.waza-detail-value {
    color: #111827;
    text-align: right;
}

.waza-amount {
    font-size: 24px;
    font-weight: bold;
    color: #10B981;
}

.waza-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
}

.waza-badge-success {
    background: #D1FAE5;
    color: #065F46;
}

.waza-info-card {
    background: #F0F9FF;
    border-color: #BAE6FD;
}

.waza-info-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.waza-info-list li {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 0;
    color: #1E40AF;
}

.waza-info-list li svg {
    flex-shrink: 0;
    margin-top: 2px;
}

.waza-qr-card {
    text-align: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.waza-qr-card h2 {
    color: white;
    border-color: rgba(255, 255, 255, 0.3);
}

.waza-qr-container {
    padding: 20px;
}

.waza-qr-image {
    max-width: 250px;
    width: 100%;
    height: auto;
    background: white;
    padding: 15px;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.waza-qr-code-text {
    font-size: 24px;
    font-weight: bold;
    margin: 16px 0 8px;
    letter-spacing: 2px;
}

.waza-qr-instruction {
    font-size: 14px;
    opacity: 0.9;
    margin: 0 0 16px;
}

.waza-btn-download-qr {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 24px;
    background: rgba(255, 255, 255, 0.2);
    border: 2px solid white;
    color: white;
    font-size: 14px;
    font-weight: 600;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.waza-btn-download-qr:hover {
    background: white;
    color: #667eea;
}

.waza-confirmation-actions {
    display: flex;
    gap: 16px;
    justify-content: center;
    margin-top: 32px;
}

.waza-btn {
    padding: 12px 32px;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 600;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.waza-btn-primary {
    background: #2563EB;
    color: white;
}

.waza-btn-primary:hover {
    background: #1D4ED8;
}

.waza-btn-secondary {
    background: white;
    color: #374151;
    border: 1px solid #D1D5DB;
}

.waza-btn-secondary:hover {
    background: #F9FAFB;
}

@media print {
    .waza-confirmation-actions {
        display: none;
    }
}

@media (max-width: 640px) {
    .waza-detail-row {
        flex-direction: column;
        gap: 4px;
    }
    
    .waza-detail-value {
        text-align: left;
    }
    
    .waza-confirmation-actions {
        flex-direction: column;
    }
    
    .waza-btn {
        width: 100%;
    }
}
</style>
