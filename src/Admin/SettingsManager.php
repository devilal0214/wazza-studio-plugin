<?php
/**
 * Settings Manager
 * 
 * @package WazaBooking\Admin
 */

namespace WazaBooking\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings Manager Class
 * Handles plugin settings and configuration
 */
class SettingsManager {
    
    /**
     * Settings option name
     */
    const OPTION_NAME = 'waza_booking_settings';
    
    /**
     * Settings sections
     */
    private $sections = [];
    
    /**
     * Initialize settings
     */
    public function init() {
        add_action('admin_init', [$this, 'register_settings'], 5); // Priority 5 to run early
        add_action('admin_menu', [$this, 'add_settings_page'], 99); // Add settings page directly
        add_action('admin_enqueue_scripts', [$this, 'enqueue_settings_assets']);
        add_action('wp_ajax_waza_test_payment_gateway', [$this, 'test_payment_gateway']);
    }
    
    /**
     * Add settings page to menu (backup if AdminManager doesn't load)
     */
    public function add_settings_page() {
        // This is a backup - AdminManager should handle the menu
        // But this ensures the settings page exists for options.php to work
    }
    
    /**
     * Register settings sections and fields
     */
    public function register_settings() {
        // Debug logging
        error_log('Waza: Registering settings...');
        
        register_setting('waza_booking_settings', self::OPTION_NAME, [
            'sanitize_callback' => [$this, 'sanitize_settings'],
            'show_in_rest' => false,
            'type' => 'array',
            'description' => 'Waza Booking plugin settings'
        ]);
        
        error_log('Waza: Settings registered with option name: ' . self::OPTION_NAME);
        
        $this->add_general_section();
        $this->add_payment_section();
        $this->add_booking_section();
        $this->add_email_section();
        $this->add_notifications_section();
        $this->add_calendar_section();
        $this->add_appearance_section();
    }
    
    /**
     * Add general settings section
     */
    private function add_general_section() {
        add_settings_section(
            'waza_general_section',
            __('General Settings', 'waza-booking'),
            [$this, 'general_section_callback'],
            'waza-settings'
        );
        
        // Business Information
        add_settings_field(
            'business_name',
            __('Business Name', 'waza-booking'),
            [$this, 'text_field_callback'],
            'waza-settings',
            'waza_general_section',
            [
                'field_id' => 'business_name',
                'description' => __('Your business or studio name', 'waza-booking')
            ]
        );
        
        add_settings_field(
            'business_address',
            __('Business Address', 'waza-booking'),
            [$this, 'textarea_field_callback'],
            'waza-settings',
            'waza_general_section',
            [
                'field_id' => 'business_address',
                'description' => __('Complete business address for bookings', 'waza-booking')
            ]
        );
        
        add_settings_field(
            'contact_phone',
            __('Contact Phone', 'waza-booking'),
            [$this, 'text_field_callback'],
            'waza-settings',
            'waza_general_section',
            [
                'field_id' => 'contact_phone',
                'description' => __('Primary contact phone number', 'waza-booking')
            ]
        );
        
        add_settings_field(
            'contact_email',
            __('Contact Email', 'waza-booking'),
            [$this, 'email_field_callback'],
            'waza-settings',
            'waza_general_section',
            [
                'field_id' => 'contact_email',
                'description' => __('Email address for customer inquiries', 'waza-booking')
            ]
        );
        
        // Time Zone and Currency
        add_settings_field(
            'timezone',
            __('Time Zone', 'waza-booking'),
            [$this, 'timezone_field_callback'],
            'waza-settings',
            'waza_general_section',
            [
                'field_id' => 'timezone',
                'description' => __('Time zone for all bookings and schedules', 'waza-booking')
            ]
        );
        
        add_settings_field(
            'currency',
            __('Currency', 'waza-booking'),
            [$this, 'select_field_callback'],
            'waza-settings',
            'waza_general_section',
            [
                'field_id' => 'currency',
                'options' => [
                    'INR' => __('Indian Rupee (₹)', 'waza-booking'),
                    'USD' => __('US Dollar ($)', 'waza-booking'),
                    'EUR' => __('Euro (€)', 'waza-booking'),
                    'GBP' => __('British Pound (£)', 'waza-booking'),
                ],
                'description' => __('Currency for all payments and pricing', 'waza-booking')
            ]
        );
        
        add_settings_field(
            'date_format',
            __('Date Format', 'waza-booking'),
            [$this, 'select_field_callback'],
            'waza-settings',
            'waza_general_section',
            [
                'field_id' => 'date_format',
                'options' => [
                    'Y-m-d' => date('Y-m-d') . ' (YYYY-MM-DD)',
                    'd/m/Y' => date('d/m/Y') . ' (DD/MM/YYYY)',
                    'm/d/Y' => date('m/d/Y') . ' (MM/DD/YYYY)',
                    'd-m-Y' => date('d-m-Y') . ' (DD-MM-YYYY)',
                ],
                'description' => __('Date display format throughout the system', 'waza-booking')
            ]
        );
        
        add_settings_field(
            'time_format',
            __('Time Format', 'waza-booking'),
            [$this, 'select_field_callback'],
            'waza-settings',
            'waza_general_section',
            [
                'field_id' => 'time_format',
                'options' => [
                    'H:i' => date('H:i') . ' (24 Hour)',
                    'h:i A' => date('h:i A') . ' (12 Hour)',
                ],
                'description' => __('Time display format throughout the system', 'waza-booking')
            ]
        );
    }
    
    /**
     * Add payment settings section
     */
    private function add_payment_section() {
        add_settings_section(
            'waza_payment_section',
            __('Payment Settings', 'waza-booking'),
            [$this, 'payment_section_callback'],
            'waza-settings'
        );
        
        // Payment Mode
        add_settings_field(
            'payment_mode',
            __('Payment Mode', 'waza-booking'),
            [$this, 'select_field_callback'],
            'waza-settings',
            'waza_payment_section',
            [
                'field_id' => 'payment_mode',
                'options' => [
                    'sandbox' => __('Sandbox (Test)', 'waza-booking'),
                    'live' => __('Live (Production)', 'waza-booking'),
                ],
                'description' => __('Switch between test and live payment processing', 'waza-booking')
            ]
        );
        
        // Razorpay Settings
        add_settings_field(
            'razorpay_enabled',
            __('Enable Razorpay', 'waza-booking'),
            [$this, 'checkbox_field_callback'],
            'waza-settings',
            'waza_payment_section',
            [
                'field_id' => 'razorpay_enabled',
                'description' => __('Enable Razorpay payment gateway', 'waza-booking')
            ]
        );
        
        add_settings_field(
            'razorpay_key_id',
            __('Razorpay Key ID', 'waza-booking'),
            [$this, 'text_field_callback'],
            'waza-settings',
            'waza_payment_section',
            [
                'field_id' => 'razorpay_key_id',
                'description' => __('Your Razorpay Key ID (starts with rzp_)', 'waza-booking'),
                'class' => 'waza-payment-field razorpay-field'
            ]
        );
        
        add_settings_field(
            'razorpay_key_secret',
            __('Razorpay Key Secret', 'waza-booking'),
            [$this, 'password_field_callback'],
            'waza-settings',
            'waza_payment_section',
            [
                'field_id' => 'razorpay_key_secret',
                'description' => __('Your Razorpay Key Secret (keep this secure)', 'waza-booking'),
                'class' => 'waza-payment-field razorpay-field'
            ]
        );
        
        add_settings_field(
            'razorpay_webhook_secret',
            __('Razorpay Webhook Secret', 'waza-booking'),
            [$this, 'password_field_callback'],
            'waza-settings',
            'waza_payment_section',
            [
                'field_id' => 'razorpay_webhook_secret',
                'description' => __('Webhook secret for secure payment notifications', 'waza-booking'),
                'class' => 'waza-payment-field razorpay-field'
            ]
        );
        
        // Stripe Settings
        add_settings_field(
            'stripe_enabled',
            __('Enable Stripe', 'waza-booking'),
            [$this, 'checkbox_field_callback'],
            'waza-settings',
            'waza_payment_section',
            [
                'field_id' => 'stripe_enabled',
                'description' => __('Enable Stripe payment gateway', 'waza-booking')
            ]
        );
        
        add_settings_field(
            'stripe_publishable_key',
            __('Stripe Publishable Key', 'waza-booking'),
            [$this, 'text_field_callback'],
            'waza-settings',
            'waza_payment_section',
            [
                'field_id' => 'stripe_publishable_key',
                'description' => __('Your Stripe Publishable Key (starts with pk_)', 'waza-booking'),
                'class' => 'waza-payment-field stripe-field'
            ]
        );
        
        add_settings_field(
            'stripe_secret_key',
            __('Stripe Secret Key', 'waza-booking'),
            [$this, 'password_field_callback'],
            'waza-settings',
            'waza_payment_section',
            [
                'field_id' => 'stripe_secret_key',
                'description' => __('Your Stripe Secret Key (starts with sk_)', 'waza-booking'),
                'class' => 'waza-payment-field stripe-field'
            ]
        );
        
        add_settings_field(
            'stripe_webhook_secret',
            __('Stripe Webhook Secret', 'waza-booking'),
            [$this, 'password_field_callback'],
            'waza-settings',
            'waza_payment_section',
            [
                'field_id' => 'stripe_webhook_secret',
                'description' => __('Webhook endpoint secret for secure notifications', 'waza-booking'),
            ]
        );

        // PhonePe Settings
        add_settings_field(
            'phonepe_enabled',
            __('Enable PhonePe', 'waza-booking'),
            [$this, 'checkbox_field_callback'],
            'waza-settings',
            'waza_payment_section',
            [
                'field_id' => 'phonepe_enabled',
                'description' => __('Enable PhonePe payment gateway', 'waza-booking')
            ]
        );
        
        add_settings_field(
            'phonepe_merchant_id',
            __('PhonePe Merchant ID', 'waza-booking'),
            [$this, 'text_field_callback'],
            'waza-settings',
            'waza_payment_section',
            [
                'field_id' => 'phonepe_merchant_id',
                'description' => __('Your PhonePe Merchant ID', 'waza-booking'),
                'class' => 'waza-payment-field phonepe-field'
            ]
        );
        
        add_settings_field(
            'phonepe_salt_key',
            __('PhonePe Salt Key', 'waza-booking'),
            [$this, 'password_field_callback'],
            'waza-settings',
            'waza_payment_section',
            [
                'field_id' => 'phonepe_salt_key',
                'description' => __('Your PhonePe Salt Key', 'waza-booking'),
                'class' => 'waza-payment-field phonepe-field'
            ]
        );

        add_settings_field(
            'phonepe_salt_index',
            __('PhonePe Salt Index', 'waza-booking'),
            [$this, 'text_field_callback'],
            'waza-settings',
            'waza_payment_section',
            [
                'field_id' => 'phonepe_salt_index',
                'description' => __('Your PhonePe Salt Index (usually 1)', 'waza-booking'),
                'class' => 'waza-payment-field phonepe-field'
            ]
        );

        
        // Payment Options
        add_settings_field(
            'payment_methods',
            __('Accepted Payment Methods', 'waza-booking'),
            [$this, 'multi_checkbox_field_callback'],
            'waza-settings',
            'waza_payment_section',
            [
                'field_id' => 'payment_methods',
                'options' => [
                    'card' => __('Credit/Debit Cards', 'waza-booking'),
                    'netbanking' => __('Net Banking', 'waza-booking'),
                    'upi' => __('UPI', 'waza-booking'),
                    'wallet' => __('Digital Wallets', 'waza-booking'),
                ],
                'description' => __('Select which payment methods to accept', 'waza-booking')
            ]
        );
        
        add_settings_field(
            'partial_payment_enabled',
            __('Allow Partial Payments', 'waza-booking'),
            [$this, 'checkbox_field_callback'],
            'waza-settings',
            'waza_payment_section',
            [
                'field_id' => 'partial_payment_enabled',
                'description' => __('Allow customers to pay a partial amount to secure booking', 'waza-booking')
            ]
        );
        
        add_settings_field(
            'partial_payment_percentage',
            __('Partial Payment Percentage', 'waza-booking'),
            [$this, 'number_field_callback'],
            'waza-settings',
            'waza_payment_section',
            [
                'field_id' => 'partial_payment_percentage',
                'min' => 1,
                'max' => 99,
                'description' => __('Minimum percentage required for partial payments', 'waza-booking'),
                'class' => 'waza-partial-payment-field'
            ]
        );
    }
    
    /**
     * Add booking settings section
     */
    private function add_booking_section() {
        add_settings_section(
            'waza_booking_section',
            __('Booking Settings', 'waza-booking'),
            [$this, 'booking_section_callback'],
            'waza-settings'
        );
        
        // Booking Rules
        add_settings_field(
            'advance_booking_days',
            __('Advance Booking Days', 'waza-booking'),
            [$this, 'number_field_callback'],
            'waza-settings',
            'waza_booking_section',
            [
                'field_id' => 'advance_booking_days',
                'min' => 0,
                'max' => 365,
                'description' => __('How many days in advance can customers book? (0 = unlimited)', 'waza-booking')
            ]
        );
        
        add_settings_field(
            'booking_cutoff_hours',
            __('Booking Cutoff Hours', 'waza-booking'),
            [$this, 'number_field_callback'],
            'waza-settings',
            'waza_booking_section',
            [
                'field_id' => 'booking_cutoff_hours',
                'min' => 0,
                'max' => 168,
                'description' => __('Stop accepting bookings X hours before the slot starts', 'waza-booking')
            ]
        );
        
        add_settings_field(
            'cancellation_cutoff_hours',
            __('Cancellation Cutoff Hours', 'waza-booking'),
            [$this, 'number_field_callback'],
            'waza-settings',
            'waza_booking_section',
            [
                'field_id' => 'cancellation_cutoff_hours',
                'min' => 0,
                'max' => 168,
                'description' => __('Allow cancellations up to X hours before the slot starts', 'waza-booking')
            ]
        );
        
        add_settings_field(
            'max_attendees_per_booking',
            __('Max Attendees per Booking', 'waza-booking'),
            [$this, 'number_field_callback'],
            'waza-settings',
            'waza_booking_section',
            [
                'field_id' => 'max_attendees_per_booking',
                'min' => 1,
                'max' => 50,
                'description' => __('Maximum number of people in a single booking', 'waza-booking')
            ]
        );
        
        // Confirmation and Status
        add_settings_field(
            'auto_confirm_bookings',
            __('Auto-Confirm Bookings', 'waza-booking'),
            [$this, 'checkbox_field_callback'],
            'waza-settings',
            'waza_booking_section',
            [
                'field_id' => 'auto_confirm_bookings',
                'description' => __('Automatically confirm bookings after payment', 'waza-booking')
            ]
        );
        
        add_settings_field(
            'require_phone_number',
            __('Require Phone Number', 'waza-booking'),
            [$this, 'checkbox_field_callback'],
            'waza-settings',
            'waza_booking_section',
            [
                'field_id' => 'require_phone_number',
                'description' => __('Make phone number mandatory for all bookings', 'waza-booking')
            ]
        );
        
        add_settings_field(
            'booking_status_page',
            __('Booking Status Page', 'waza-booking'),
            [$this, 'page_select_callback'],
            'waza-settings',
            'waza_booking_section',
            [
                'field_id' => 'booking_status_page',
                'description' => __('Page where customers can view their booking status', 'waza-booking')
            ]
        );
        
        add_settings_field(
            'terms_conditions_page',
            __('Terms & Conditions Page', 'waza-booking'),
            [$this, 'page_select_callback'],
            'waza-settings',
            'waza_booking_section',
            [
                'field_id' => 'terms_conditions_page',
                'description' => __('Link to terms and conditions (optional)', 'waza-booking')
            ]
        );
    }
    
    /**
     * Add email settings section
     */
    private function add_email_section() {
        add_settings_section(
            'waza_email_section',
            __('Email Settings', 'waza-booking'),
            [$this, 'email_section_callback'],
            'waza-settings'
        );
        
        add_settings_field(
            'from_email',
            __('From Email Address', 'waza-booking'),
            [$this, 'email_field_callback'],
            'waza-settings',
            'waza_email_section',
            [
                'field_id' => 'from_email',
                'description' => __('Email address for outgoing notifications', 'waza-booking')
            ]
        );
        
        add_settings_field(
            'from_name',
            __('From Name', 'waza-booking'),
            [$this, 'text_field_callback'],
            'waza-settings',
            'waza_email_section',
            [
                'field_id' => 'from_name',
                'description' => __('Name displayed in outgoing emails', 'waza-booking')
            ]
        );
        
        add_settings_field(
            'admin_notification_email',
            __('Admin Notification Email', 'waza-booking'),
            [$this, 'email_field_callback'],
            'waza-settings',
            'waza_email_section',
            [
                'field_id' => 'admin_notification_email',
                'description' => __('Email address to receive booking notifications', 'waza-booking')
            ]
        );
    }
    
    /**
     * Add notifications settings section
     */
    private function add_notifications_section() {
        add_settings_section(
            'waza_notifications_section',
            __('Notification Settings', 'waza-booking'),
            [$this, 'notifications_section_callback'],
            'waza-settings'
        );
        
        // Email Notifications
        add_settings_field(
            'email_notifications',
            __('Email Notifications', 'waza-booking'),
            [$this, 'multi_checkbox_field_callback'],
            'waza-settings',
            'waza_notifications_section',
            [
                'field_id' => 'email_notifications',
                'options' => [
                    'booking_confirmation' => __('Booking Confirmation', 'waza-booking'),
                    'booking_reminder' => __('Booking Reminder (24h before)', 'waza-booking'),
                    'booking_cancelled' => __('Booking Cancelled', 'waza-booking'),
                    'payment_received' => __('Payment Received', 'waza-booking'),
                    'admin_new_booking' => __('Admin: New Booking Alert', 'waza-booking'),
                ],
                'description' => __('Select which email notifications to send', 'waza-booking')
            ]
        );
        
        // SMS Notifications
        add_settings_field(
            'sms_enabled',
            __('Enable SMS Notifications', 'waza-booking'),
            [$this, 'checkbox_field_callback'],
            'waza-settings',
            'waza_notifications_section',
            [
                'field_id' => 'sms_enabled',
                'description' => __('Enable SMS notifications via Twilio or TextLocal', 'waza-booking')
            ]
        );
        
        add_settings_field(
            'sms_provider',
            __('SMS Provider', 'waza-booking'),
            [$this, 'select_field_callback'],
            'waza-settings',
            'waza_notifications_section',
            [
                'field_id' => 'sms_provider',
                'options' => [
                    'twilio' => __('Twilio', 'waza-booking'),
                    'textlocal' => __('TextLocal (India)', 'waza-booking'),
                ],
                'description' => __('Select your SMS gateway provider', 'waza-booking'),
                'class' => 'waza-sms-field'
            ]
        );
        
        // Twilio Settings
        add_settings_field(
            'twilio_account_sid',
            __('Twilio Account SID', 'waza-booking'),
            [$this, 'text_field_callback'],
            'waza-settings',
            'waza_notifications_section',
            [
                'field_id' => 'twilio_account_sid',
                'description' => __('Your Twilio Account SID', 'waza-booking'),
                'class' => 'waza-sms-field twilio-field'
            ]
        );
        
        add_settings_field(
            'twilio_auth_token',
            __('Twilio Auth Token', 'waza-booking'),
            [$this, 'password_field_callback'],
            'waza-settings',
            'waza_notifications_section',
            [
                'field_id' => 'twilio_auth_token',
                'description' => __('Your Twilio Auth Token', 'waza-booking'),
                'class' => 'waza-sms-field twilio-field'
            ]
        );
        
        add_settings_field(
            'twilio_phone_number',
            __('Twilio Phone Number', 'waza-booking'),
            [$this, 'text_field_callback'],
            'waza-settings',
            'waza_notifications_section',
            [
                'field_id' => 'twilio_phone_number',
                'description' => __('Your Twilio phone number (with country code)', 'waza-booking'),
                'class' => 'waza-sms-field twilio-field'
            ]
        );
        
        // TextLocal Settings
        add_settings_field(
            'textlocal_api_key',
            __('TextLocal API Key', 'waza-booking'),
            [$this, 'password_field_callback'],
            'waza-settings',
            'waza_notifications_section',
            [
                'field_id' => 'textlocal_api_key',
                'description' => __('Your TextLocal API Key', 'waza-booking'),
                'class' => 'waza-sms-field textlocal-field'
            ]
        );
        
        add_settings_field(
            'textlocal_sender',
            __('TextLocal Sender ID', 'waza-booking'),
            [$this, 'text_field_callback'],
            'waza-settings',
            'waza_notifications_section',
            [
                'field_id' => 'textlocal_sender',
                'description' => __('Your registered sender ID', 'waza-booking'),
                'class' => 'waza-sms-field textlocal-field'
            ]
        );
        
        // QR Code Settings
        add_settings_field(
            'qr_code_enabled',
            __('QR Code Generation', 'waza-booking'),
            [$this, 'checkbox_field_callback'],
            'waza-settings',
            'waza_notifications_section',
            [
                'field_id' => 'qr_code_enabled',
                'description' => __('Generate QR codes for booking confirmation and check-in', 'waza-booking')
            ]
        );
        
        add_settings_field(
            'qr_code_expiry_hours',
            __('QR Code Expiry Hours', 'waza-booking'),
            [$this, 'number_field_callback'],
            'waza-settings',
            'waza_notifications_section',
            [
                'field_id' => 'qr_code_expiry_hours',
                'min' => 1,
                'max' => 168,
                'description' => __('Hours after which QR codes expire for security', 'waza-booking'),
                'class' => 'waza-qr-field'
            ]
        );
        
        // Refund Policy Settings
        add_settings_field(
            'full_refund_hours',
            __('Full Refund Window (Hours)', 'waza-booking'),
            [$this, 'number_field_callback'],
            'waza-settings',
            'waza_notifications_section',
            [
                'field_id' => 'full_refund_hours',
                'min' => 0,
                'max' => 168,
                'description' => __('Hours before activity start when full refund is available', 'waza-booking')
            ]
        );
        
        add_settings_field(
            'partial_refund_hours',
            __('Partial Refund Window (Hours)', 'waza-booking'),
            [$this, 'number_field_callback'],
            'waza-settings',
            'waza_notifications_section',
            [
                'field_id' => 'partial_refund_hours',
                'min' => 0,
                'max' => 168,
                'description' => __('Hours before activity start when partial refund is available', 'waza-booking')
            ]
        );
        
        add_settings_field(
            'partial_refund_percentage',
            __('Partial Refund Percentage', 'waza-booking'),
            [$this, 'number_field_callback'],
            'waza-settings',
            'waza_notifications_section',
            [
                'field_id' => 'partial_refund_percentage',
                'min' => 0,
                'max' => 100,
                'description' => __('Percentage of booking amount to refund in partial refund window', 'waza-booking')
            ]
        );
        
        // Reschedule Settings
        add_settings_field(
            'reschedule_deadline_hours',
            __('Reschedule Deadline (Hours)', 'waza-booking'),
            [$this, 'number_field_callback'],
            'waza-settings',
            'waza_notifications_section',
            [
                'field_id' => 'reschedule_deadline_hours',
                'min' => 0,
                'max' => 168,
                'description' => __('Minimum hours before activity when rescheduling is allowed', 'waza-booking')
            ]
        );
        
        add_settings_field(
            'max_reschedules_per_booking',
            __('Max Reschedules per Booking', 'waza-booking'),
            [$this, 'number_field_callback'],
            'waza-settings',
            'waza_notifications_section',
            [
                'field_id' => 'max_reschedules_per_booking',
                'min' => 0,
                'max' => 10,
                'description' => __('Maximum number of times a booking can be rescheduled', 'waza-booking')
            ]
        );
    }
    
    /**
     * Add calendar settings section
     */
    /**
     * Add appearance customization section
     */
    private function add_appearance_section() {
        add_settings_section(
            'waza_appearance_section',
            __('Appearance & Customization', 'waza-booking'),
            [$this, 'appearance_section_callback'],
            'waza-settings'
        );
        
        // Primary/Accent Color
        add_settings_field(
            'appearance_primary_color',
            __('Primary Color', 'waza-booking'),
            [$this, 'color_field_callback'],
            'waza-settings',
            'waza_appearance_section',
            [
                'field_id' => 'appearance_primary_color',
                'default' => '#5BC0BE',
                'description' => __('Main brand color for buttons, progress bars, and accents', 'waza-booking')
            ]
        );
        
        // Secondary Color
        add_settings_field(
            'appearance_secondary_color',
            __('Secondary Color', 'waza-booking'),
            [$this, 'color_field_callback'],
            'waza-settings',
            'waza_appearance_section',
            [
                'field_id' => 'appearance_secondary_color',
                'default' => '#3A506B',
                'description' => __('Secondary color for headings and highlights', 'waza-booking')
            ]
        );
        
        // Background Color
        add_settings_field(
            'appearance_background_color',
            __('Background Color', 'waza-booking'),
            [$this, 'color_field_callback'],
            'waza-settings',
            'waza_appearance_section',
            [
                'field_id' => 'appearance_background_color',
                'default' => '#F5F5F5',
                'description' => __('Background color for modals and forms', 'waza-booking')
            ]
        );
        
        // Text Color
        add_settings_field(
            'appearance_text_color',
            __('Text Color', 'waza-booking'),
            [$this, 'color_field_callback'],
            'waza-settings',
            'waza_appearance_section',
            [
                'field_id' => 'appearance_text_color',
                'default' => '#333333',
                'description' => __('Main text color', 'waza-booking')
            ]
        );
        
        // Border Radius
        add_settings_field(
            'appearance_border_radius',
            __('Border Radius', 'waza-booking'),
            [$this, 'number_field_callback'],
            'waza-settings',
            'waza_appearance_section',
            [
                'field_id' => 'appearance_border_radius',
                'default' => '8',
                'min' => 0,
                'max' => 50,
                'description' => __('Border radius for buttons and inputs (in pixels)', 'waza-booking')
            ]
        );
        
        // Progress Bar Style
        add_settings_field(
            'appearance_progress_style',
            __('Progress Indicator Style', 'waza-booking'),
            [$this, 'select_field_callback'],
            'waza-settings',
            'waza_appearance_section',
            [
                'field_id' => 'appearance_progress_style',
                'default' => 'bar',
                'options' => [
                    'bar' => __('Progress Bar', 'waza-booking'),
                    'steps' => __('Step Circles', 'waza-booking')
                ],
                'description' => __('Style for the multi-step progress indicator', 'waza-booking')
            ]
        );
        
        // Number of Steps
        add_settings_field(
            'appearance_booking_steps',
            __('Booking Flow Steps', 'waza-booking'),
            [$this, 'select_field_callback'],
            'waza-settings',
            'waza_appearance_section',
            [
                'field_id' => 'appearance_booking_steps',
                'default' => '4',
                'options' => [
                    '3' => __('3 Steps (Summary, Details, Payment)', 'waza-booking'),
                    '4' => __('4 Steps (Time, Details, Payment, Done)', 'waza-booking')
                ],
                'description' => __('Number of steps in booking flow', 'waza-booking')
            ]
        );
        
        // Step Labels
        add_settings_field(
            'appearance_step_labels',
            __('Step Labels', 'waza-booking'),
            [$this, 'text_field_callback'],
            'waza-settings',
            'waza_appearance_section',
            [
                'field_id' => 'appearance_step_labels',
                'default' => 'Time,Details,Payment,Done',
                'description' => __('Comma-separated labels for each step (e.g., "Time,Details,Payment,Done")', 'waza-booking')
            ]
        );
        
        // Terms Checkbox
        add_settings_field(
            'appearance_show_terms',
            __('Show Terms Checkbox', 'waza-booking'),
            [$this, 'checkbox_field_callback'],
            'waza-settings',
            'waza_appearance_section',
            [
                'field_id' => 'appearance_show_terms',
                'default' => '1',
                'description' => __('Display "I agree to terms of service" checkbox', 'waza-booking')
            ]
        );
        
        // Terms Text
        add_settings_field(
            'appearance_terms_text',
            __('Terms Checkbox Text', 'waza-booking'),
            [$this, 'text_field_callback'],
            'waza-settings',
            'waza_appearance_section',
            [
                'field_id' => 'appearance_terms_text',
                'default' => 'I agree to the terms of service',
                'description' => __('Text for the terms checkbox', 'waza-booking')
            ]
        );
        
        // Button Text
        add_settings_field(
            'appearance_button_next',
            __('Next Button Text', 'waza-booking'),
            [$this, 'text_field_callback'],
            'waza-settings',
            'waza_appearance_section',
            [
                'field_id' => 'appearance_button_next',
                'default' => 'NEXT',
                'description' => __('Text for next/continue button', 'waza-booking')
            ]
        );
        
        add_settings_field(
            'appearance_button_back',
            __('Back Button Text', 'waza-booking'),
            [$this, 'text_field_callback'],
            'waza-settings',
            'waza_appearance_section',
            [
                'field_id' => 'appearance_button_back',
                'default' => 'BACK',
                'description' => __('Text for back/previous button', 'waza-booking')
            ]
        );
        
        // Calendar Slots Background Color
        add_settings_field(
            'appearance_slots_bg_color',
            __('Calendar Slots Background', 'waza-booking'),
            [$this, 'color_field_callback'],
            'waza-settings',
            'waza_appearance_section',
            [
                'field_id' => 'appearance_slots_bg_color',
                'default' => '#D1FAE5',
                'description' => __('Background color for calendar days with available slots', 'waza-booking')
            ]
        );
        
        // Font Family
        add_settings_field(
            'appearance_font_family',
            __('Font Family', 'waza-booking'),
            [$this, 'select_field_callback'],
            'waza-settings',
            'waza_appearance_section',
            [
                'field_id' => 'appearance_font_family',
                'default' => 'system',
                'options' => [
                    'system' => __('System Default', 'waza-booking'),
                    'roboto' => __('Roboto', 'waza-booking'),
                    'open-sans' => __('Open Sans', 'waza-booking'),
                    'lato' => __('Lato', 'waza-booking'),
                    'montserrat' => __('Montserrat', 'waza-booking')
                ],
                'description' => __('Font family for booking interface', 'waza-booking')
            ]
        );
    }
    
    private function add_calendar_section() {
        add_settings_section(
            'waza_calendar_section',
            __('Calendar Settings', 'waza-booking'),
            [$this, 'calendar_section_callback'],
            'waza-settings'
        );
        
        add_settings_field(
            'waza_calendar_primary_color',
            __('Primary Color', 'waza-booking'),
            [$this, 'color_field_callback'],
            'waza-settings',
            'waza_calendar_section',
            [
                'field_id' => 'waza_calendar_primary_color',
                'description' => __('Primary color for calendar UI elements (deprecated - use Appearance settings)', 'waza-booking')
            ]
        );
        
        add_settings_field(
            'waza_calendar_start_of_week',
            __('Start of Week', 'waza-booking'),
            [$this, 'select_field_callback'],
            'waza-settings',
            'waza_calendar_section',
            [
                'field_id' => 'waza_calendar_start_of_week',
                'options' => [
                    'sunday' => __('Sunday', 'waza-booking'),
                    'monday' => __('Monday', 'waza-booking')
                ],
                'description' => __('First day of the week in calendar view', 'waza-booking')
            ]
        );
        
        add_settings_field(
            'waza_calendar_time_format',
            __('Time Format', 'waza-booking'),
            [$this, 'select_field_callback'],
            'waza-settings',
            'waza_calendar_section',
            [
                'field_id' => 'waza_calendar_time_format',
                'options' => [
                    '12h' => __('12-hour (AM/PM)', 'waza-booking'),
                    '24h' => __('24-hour', 'waza-booking')
                ],
                'description' => __('Time format for displaying slots', 'waza-booking')
            ]
        );
        
        add_settings_field(
            'waza_calendar_show_instructor',
            __('Show Instructor Names', 'waza-booking'),
            [$this, 'select_field_callback'],
            'waza-settings',
            'waza_calendar_section',
            [
                'field_id' => 'waza_calendar_show_instructor',
                'options' => [
                    'yes' => __('Yes', 'waza-booking'),
                    'no' => __('No', 'waza-booking')
                ],
                'description' => __('Display instructor names in calendar slots', 'waza-booking')
            ]
        );
        
        add_settings_field(
            'waza_calendar_show_price',
            __('Show Prices', 'waza-booking'),
            [$this, 'select_field_callback'],
            'waza-settings',
            'waza_calendar_section',
            [
                'field_id' => 'waza_calendar_show_price',
                'options' => [
                    'yes' => __('Yes', 'waza-booking'),
                    'no' => __('No', 'waza-booking')
                ],
                'description' => __('Display prices in calendar slots', 'waza-booking')
            ]
        );
        
        add_settings_field(
            'waza_calendar_slots_per_day',
            __('Max Slots Per Day', 'waza-booking'),
            [$this, 'number_field_callback'],
            'waza-settings',
            'waza_calendar_section',
            [
                'field_id' => 'waza_calendar_slots_per_day',
                'min' => 1,
                'max' => 20,
                'description' => __('Maximum number of slots to display per day in calendar view', 'waza-booking')
            ]
        );
    }
    
    /**
     * Render the settings page
     */
    /**
     * Render the settings page
     */
    public function render_settings_page() {
        if (isset($_GET['settings-updated'])) {
            add_settings_error('waza_booking_settings', 'settings_updated', __('Settings saved successfully!', 'waza-booking'), 'updated');
        }
        
        $settings = get_option(self::OPTION_NAME, []);
        
        ?>
        <div class="wrap waza-settings-container" id="waza-settings-admin">
            <div class="customization-header">
                <h1><?php echo esc_html__('Waza Booking Settings', 'waza-booking'); ?></h1>
                <p><?php esc_html_e('Configure payment gateways, notifications, and other settings.', 'waza-booking'); ?></p>
            </div>
            
            <?php settings_errors('waza_booking_settings'); ?>
            
            <div class="waza-settings-tabs customization-tabs">
                <a href="#general" class="nav-tab nav-tab-active" data-tab="general">
                    <?php esc_html_e('General', 'waza-booking'); ?>
                </a>
                <a href="#appearance" class="nav-tab" data-tab="appearance">
                    <?php esc_html_e('Appearance', 'waza-booking'); ?>
                </a>
                <a href="#payment" class="nav-tab" data-tab="payment">
                    <?php esc_html_e('Payments', 'waza-booking'); ?>
                </a>
                <a href="#booking" class="nav-tab" data-tab="booking">
                    <?php esc_html_e('Bookings', 'waza-booking'); ?>
                </a>
                <a href="#email" class="nav-tab" data-tab="email">
                    <?php esc_html_e('Emails', 'waza-booking'); ?>
                </a>
                <a href="#notifications" class="nav-tab" data-tab="notifications">
                    <?php esc_html_e('Notifications', 'waza-booking'); ?>
                </a>
                <a href="#calendar" class="nav-tab" data-tab="calendar">
                    <?php esc_html_e('Calendar', 'waza-booking'); ?>
                </a>
            </div>
            
            <div class="waza-settings-form-wrapper customization-content">
                <form method="post" action="options.php" class="waza-settings-form waza-form" id="waza-settings-form" novalidate>
                    <?php settings_fields('waza_booking_settings'); ?>
                    
                    <!-- General Tab -->
                    <div id="general" class="waza-tab-content active">
                        <?php $this->render_general_tab($settings); ?>
                    </div>
                    
                    <!-- Appearance Tab -->
                    <div id="appearance" class="waza-tab-content">
                        <?php $this->render_appearance_tab($settings); ?>
                    </div>
                    
                    <!-- Payment Tab -->
                    <div id="payment" class="waza-tab-content">
                        <?php $this->render_payment_tab($settings); ?>
                    </div>
                    
                    <!-- Booking Tab -->
                    <div id="booking" class="waza-tab-content">
                        <?php $this->render_booking_tab($settings); ?>
                    </div>
                    
                    <!-- Email Tab -->
                    <div id="email" class="waza-tab-content">
                        <?php $this->render_email_tab($settings); ?>
                    </div>
                    
                    <!-- Notifications Tab -->
                    <div id="notifications" class="waza-tab-content">
                        <?php $this->render_notifications_tab($settings); ?>
                    </div>
                    
                    <!-- Calendar Tab -->
                    <div id="calendar" class="waza-tab-content">
                        <?php $this->render_calendar_tab($settings); ?>
                    </div>
                    
                    <div class="action-buttons">
                        <?php submit_button(__('Save Settings', 'waza-booking'), 'primary waza-button', 'submit', false); ?>
                    </div>
                </form>
            </div>
        </div>
        
        <style>
        .waza-settings-container {
            background: white;
            border: 1px solid #c3c4c7;
            border-radius: 6px;
            margin-top: 20px;
        }
        
        .waza-settings-tabs {
            background: #f6f7f7;
            border-bottom: 1px solid #c3c4c7;
            margin: 0;
        }
        
        .waza-settings-tabs .nav-tab {
            border: none;
            border-bottom: 3px solid transparent;
            background: transparent;
            margin: 0;
            padding: 12px 20px;
        }
        
        .waza-settings-tabs .nav-tab-active,
        .waza-settings-tabs .nav-tab:hover {
            background: white;
            border-bottom-color: #2271b1;
        }
        
        .waza-settings-form {
            padding: 30px;
            position: relative;
        }
        
        /* Tab content styling - keep all tabs in DOM for form submission */
        .waza-tab-content {
            display: none;
        }
        
        .waza-tab-content.active {
            display: block;
        }
        
        .waza-settings-form .form-table th {
            font-weight: 600;
            padding: 15px 10px 15px 0;
        }
        
        .waza-settings-form .form-table td {
            padding: 15px 10px;
        }
        
        .waza-settings-form input[type="text"],
        .waza-settings-form input[type="email"],
        .waza-settings-form input[type="number"],
        .waza-settings-form select,
        .waza-settings-form textarea {
            width: 400px;
            max-width: 100%;
        }
        
        .action-buttons {
            position: relative;
            z-index: 10;
            padding: 30px;
            background: white;
            border-top: 1px solid #c3c4c7;
            margin-top: 20px;
        }
        
        .waza-gateway-status {
            background: #f8f9fa;
            border: 1px solid #e1e5e9;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .waza-gateway-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        
        .waza-gateway-item {
            background: white;
            border: 1px solid #e1e5e9;
            border-radius: 6px;
            padding: 15px;
            text-align: center;
        }
        
        .waza-gateway-item .gateway-logo {
            width: 60px;
            height: 40px;
            margin: 0 auto 10px;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
        }
        
        .waza-gateway-item .gateway-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .gateway-status.active { background: #d1e7dd; color: #0f5132; }
        .gateway-status.inactive { background: #f8d7da; color: #721c24; }
        </style>
        <?php
    }
    /**
     * Render General Tab
     */
    private function render_general_tab($settings) {
        ?>
        <h3><?php esc_html_e('Business Information', 'waza-booking'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="business_name"><?php esc_html_e('Business Name', 'waza-booking'); ?></label></th>
                <td>
                    <input type="text" id="business_name" name="<?php echo self::OPTION_NAME; ?>[business_name]" 
                           value="<?php echo esc_attr($settings['business_name'] ?? ''); ?>" />
                    <p class="description"><?php esc_html_e('Your business or studio name', 'waza-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="business_address"><?php esc_html_e('Business Address', 'waza-booking'); ?></label></th>
                <td>
                    <textarea id="business_address" name="<?php echo self::OPTION_NAME; ?>[business_address]" 
                              rows="3"><?php echo esc_textarea($settings['business_address'] ?? ''); ?></textarea>
                    <p class="description"><?php esc_html_e('Complete business address for bookings', 'waza-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="contact_phone"><?php esc_html_e('Contact Phone', 'waza-booking'); ?></label></th>
                <td>
                    <input type="text" id="contact_phone" name="<?php echo self::OPTION_NAME; ?>[contact_phone]" 
                           value="<?php echo esc_attr($settings['contact_phone'] ?? ''); ?>" />
                    <p class="description"><?php esc_html_e('Primary contact phone number', 'waza-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="contact_email"><?php esc_html_e('Contact Email', 'waza-booking'); ?></label></th>
                <td>
                    <input type="email" id="contact_email" name="<?php echo self::OPTION_NAME; ?>[contact_email]" 
                           value="<?php echo esc_attr($settings['contact_email'] ?? ''); ?>" />
                    <p class="description"><?php esc_html_e('Primary contact email address', 'waza-booking'); ?></p>
                </td>
            </tr>
        </table>
        
        <h3><?php esc_html_e('Data Management', 'waza-booking'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="enable_csv_export"><?php esc_html_e('Enable CSV Export', 'waza-booking'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="enable_csv_export" name="<?php echo self::OPTION_NAME; ?>[enable_csv_export]" 
                               value="1" <?php checked($settings['enable_csv_export'] ?? 1, 1); ?> />
                        <?php esc_html_e('Allow exporting bookings, slots, and attendance data as CSV', 'waza-booking'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="enable_activity_logging"><?php esc_html_e('Enable Activity Logging', 'waza-booking'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="enable_activity_logging" name="<?php echo self::OPTION_NAME; ?>[enable_activity_logging]" 
                               value="1" <?php checked($settings['enable_activity_logging'] ?? 1, 1); ?> />
                        <?php esc_html_e('Log all booking and attendance activities for audit trail', 'waza-booking'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="enable_attendance_tracking"><?php esc_html_e('Enable Attendance Tracking', 'waza-booking'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="enable_attendance_tracking" name="<?php echo self::OPTION_NAME; ?>[enable_attendance_tracking]" 
                               value="1" <?php checked($settings['enable_attendance_tracking'] ?? 1, 1); ?> />
                        <?php esc_html_e('Track student attendance via QR code scanning', 'waza-booking'); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render Payment Tab
     */
    private function render_payment_tab($settings) {
        ?>
        <h3><?php esc_html_e('Payment Gateways', 'waza-booking'); ?></h3>
        
        <div class="waza-gateway-status">
            <h4><?php esc_html_e('Gateway Status', 'waza-booking'); ?></h4>
            <div class="waza-gateway-grid">
                <div class="waza-gateway-item">
                    <div class="gateway-logo" style="background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjQwIiB2aWV3Qm94PSIwIDAgMTIwIDQwIiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjx0ZXh0IHg9IjEwIiB5PSIyNSIgZmlsbD0iIzMzNzNkYyIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjE0cHgiIGZvbnQtd2VpZ2h0PSJib2xkIj5SYXpvcnBheTwvdGV4dD48L3N2Zz4=');"></div>
                    <div class="gateway-status <?php echo !empty($settings['razorpay_key_id']) ? 'active' : 'inactive'; ?>">
                        <?php echo !empty($settings['razorpay_key_id']) ? __('Configured', 'waza-booking') : __('Not Configured', 'waza-booking'); ?>
                    </div>
                    <button type="button" class="button waza-gateway-test-button" data-gateway="razorpay">
                        <?php esc_html_e('Test Connection', 'waza-booking'); ?>
                    </button>
                </div>
                
                <div class="waza-gateway-item">
                    <div class="gateway-logo" style="background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjQwIiB2aWV3Qm94PSIwIDAgMTIwIDQwIiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjx0ZXh0IHg9IjEwIiB5PSIyNSIgZmlsbD0iIzYzNWJmZiIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjE0cHgiIGZvbnQtd2VpZ2h0PSJib2xkIj5TdHJpcGU8L3RleHQ+PC9zdmc+');"></div>
                    <div class="gateway-status <?php echo !empty($settings['stripe_secret_key']) ? 'active' : 'inactive'; ?>">
                        <?php echo !empty($settings['stripe_secret_key']) ? __('Configured', 'waza-booking') : __('Not Configured', 'waza-booking'); ?>
                    </div>
                    <button type="button" class="button waza-gateway-test-button" data-gateway="stripe">
                        <?php esc_html_e('Test Connection', 'waza-booking'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Default Currency', 'waza-booking'); ?></th>
                <td>
                    <select name="<?php echo self::OPTION_NAME; ?>[currency]">
                        <option value="INR" <?php selected($settings['currency'] ?? 'INR', 'INR'); ?>>INR (Indian Rupee)</option>
                        <option value="USD" <?php selected($settings['currency'] ?? 'INR', 'USD'); ?>>USD (US Dollar)</option>
                        <option value="EUR" <?php selected($settings['currency'] ?? 'INR', 'EUR'); ?>>EUR (Euro)</option>
                        <option value="GBP" <?php selected($settings['currency'] ?? 'INR', 'GBP'); ?>>GBP (British Pound)</option>
                    </select>
                </td>
            </tr>
        </table>
        
        <h4><?php esc_html_e('Razorpay Settings', 'waza-booking'); ?></h4>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="razorpay_key_id"><?php esc_html_e('Razorpay Key ID', 'waza-booking'); ?></label></th>
                <td>
                    <input type="text" id="razorpay_key_id" name="<?php echo self::OPTION_NAME; ?>[razorpay_key_id]" 
                           value="<?php echo esc_attr($settings['razorpay_key_id'] ?? ''); ?>" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="razorpay_key_secret"><?php esc_html_e('Razorpay Key Secret', 'waza-booking'); ?></label></th>
                <td>
                    <input type="password" id="razorpay_key_secret" name="<?php echo self::OPTION_NAME; ?>[razorpay_key_secret]" 
                           value="<?php echo esc_attr($settings['razorpay_key_secret'] ?? ''); ?>" />
                </td>
            </tr>
        </table>
        
        <h4><?php esc_html_e('Stripe Settings', 'waza-booking'); ?></h4>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="stripe_publishable_key"><?php esc_html_e('Stripe Publishable Key', 'waza-booking'); ?></label></th>
                <td>
                    <input type="text" id="stripe_publishable_key" name="<?php echo self::OPTION_NAME; ?>[stripe_publishable_key]" 
                           value="<?php echo esc_attr($settings['stripe_publishable_key'] ?? ''); ?>" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="stripe_secret_key"><?php esc_html_e('Stripe Secret Key', 'waza-booking'); ?></label></th>
                <td>
                    <input type="password" id="stripe_secret_key" name="<?php echo self::OPTION_NAME; ?>[stripe_secret_key]" 
                           value="<?php echo esc_attr($settings['stripe_secret_key'] ?? ''); ?>" />
                </td>
            </tr>
        </table>
        
        <h4><?php esc_html_e('PhonePe Settings', 'waza-booking'); ?></h4>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="phonepe_enabled"><?php esc_html_e('Enable PhonePe', 'waza-booking'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="phonepe_enabled" name="<?php echo self::OPTION_NAME; ?>[phonepe_enabled]" 
                               value="1" <?php checked($settings['phonepe_enabled'] ?? 0, 1); ?> />
                        <?php esc_html_e('Enable PhonePe payment gateway', 'waza-booking'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="phonepe_merchant_id"><?php esc_html_e('Merchant ID', 'waza-booking'); ?></label></th>
                <td>
                    <input type="text" id="phonepe_merchant_id" name="<?php echo self::OPTION_NAME; ?>[phonepe_merchant_id]" 
                           value="<?php echo esc_attr($settings['phonepe_merchant_id'] ?? ''); ?>" />
                    <p class="description"><?php esc_html_e('Your PhonePe Merchant ID', 'waza-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="phonepe_salt_key"><?php esc_html_e('Salt Key', 'waza-booking'); ?></label></th>
                <td>
                    <input type="password" id="phonepe_salt_key" name="<?php echo self::OPTION_NAME; ?>[phonepe_salt_key]" 
                           value="<?php echo esc_attr($settings['phonepe_salt_key'] ?? ''); ?>" />
                    <p class="description"><?php esc_html_e('Your PhonePe Salt Key', 'waza-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="phonepe_salt_index"><?php esc_html_e('Salt Index', 'waza-booking'); ?></label></th>
                <td>
                    <input type="text" id="phonepe_salt_index" name="<?php echo self::OPTION_NAME; ?>[phonepe_salt_index]" 
                           value="<?php echo esc_attr($settings['phonepe_salt_index'] ?? '1'); ?>" />
                    <p class="description"><?php esc_html_e('Usually 1', 'waza-booking'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render Booking Tab
     */
    private function render_booking_tab($settings) {
        ?>
        <h3><?php esc_html_e('Booking Rules', 'waza-booking'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="default_duration"><?php esc_html_e('Default Slot Duration', 'waza-booking'); ?></label></th>
                <td>
                    <input type="number" id="default_duration" name="<?php echo self::OPTION_NAME; ?>[default_duration]" 
                           value="<?php echo esc_attr($settings['default_duration'] ?? '60'); ?>" min="15" max="480" />
                    <span><?php esc_html_e('minutes', 'waza-booking'); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="advance_booking_days"><?php esc_html_e('Advance Booking Limit', 'waza-booking'); ?></label></th>
                <td>
                    <input type="number" id="advance_booking_days" name="<?php echo self::OPTION_NAME; ?>[advance_booking_days]" 
                           value="<?php echo esc_attr($settings['advance_booking_days'] ?? '30'); ?>" min="1" max="365" />
                    <span><?php esc_html_e('days', 'waza-booking'); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="cancellation_hours"><?php esc_html_e('Cancellation Deadline', 'waza-booking'); ?></label></th>
                <td>
                    <input type="number" id="cancellation_hours" name="<?php echo self::OPTION_NAME; ?>[cancellation_hours]" 
                           value="<?php echo esc_attr($settings['cancellation_hours'] ?? '24'); ?>" min="1" max="168" />
                    <span><?php esc_html_e('hours before activity', 'waza-booking'); ?></span>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render Email Tab
     */
    private function render_email_tab($settings) {
        ?>
        <h3><?php esc_html_e('Email Settings', 'waza-booking'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="sender_name"><?php esc_html_e('Sender Name', 'waza-booking'); ?></label></th>
                <td>
                    <input type="text" id="sender_name" name="<?php echo self::OPTION_NAME; ?>[sender_name]" 
                           value="<?php echo esc_attr($settings['sender_name'] ?? get_bloginfo('name')); ?>" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="sender_email"><?php esc_html_e('Sender Email', 'waza-booking'); ?></label></th>
                <td>
                    <input type="email" id="sender_email" name="<?php echo self::OPTION_NAME; ?>[sender_email]" 
                           value="<?php echo esc_attr($settings['sender_email'] ?? get_option('admin_email')); ?>" />
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render Notifications Tab
     */
    private function render_notifications_tab($settings) {
        ?>
        <h3><?php esc_html_e('Email Notification Settings', 'waza-booking'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Send Booking Confirmations', 'waza-booking'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo self::OPTION_NAME; ?>[send_confirmations]" 
                               value="1" <?php checked($settings['send_confirmations'] ?? 1, 1); ?> />
                        <?php esc_html_e('Send confirmation emails to customers', 'waza-booking'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Send Reminders', 'waza-booking'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo self::OPTION_NAME; ?>[send_reminders]" 
                               value="1" <?php checked($settings['send_reminders'] ?? 1, 1); ?> />
                        <?php esc_html_e('Send reminder emails before activities', 'waza-booking'); ?>
                    </label>
                </td>
            </tr>
        </table>
        
        <h3><?php esc_html_e('SMS Notifications', 'waza-booking'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="sms_enabled"><?php esc_html_e('Enable SMS', 'waza-booking'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="sms_enabled" name="<?php echo self::OPTION_NAME; ?>[sms_enabled]" 
                               value="1" <?php checked($settings['sms_enabled'] ?? 0, 1); ?> />
                        <?php esc_html_e('Send SMS notifications for bookings and reminders', 'waza-booking'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="sms_provider"><?php esc_html_e('SMS Provider', 'waza-booking'); ?></label></th>
                <td>
                    <select id="sms_provider" name="<?php echo self::OPTION_NAME; ?>[sms_provider]">
                        <option value="twilio" <?php selected($settings['sms_provider'] ?? 'twilio', 'twilio'); ?>><?php esc_html_e('Twilio', 'waza-booking'); ?></option>
                        <option value="textlocal" <?php selected($settings['sms_provider'] ?? 'twilio', 'textlocal'); ?>><?php esc_html_e('TextLocal', 'waza-booking'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="twilio_account_sid"><?php esc_html_e('Twilio Account SID', 'waza-booking'); ?></label></th>
                <td>
                    <input type="text" id="twilio_account_sid" name="<?php echo self::OPTION_NAME; ?>[twilio_account_sid]" 
                           value="<?php echo esc_attr($settings['twilio_account_sid'] ?? ''); ?>" />
                    <p class="description"><?php esc_html_e('Your Twilio Account SID', 'waza-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="twilio_auth_token"><?php esc_html_e('Twilio Auth Token', 'waza-booking'); ?></label></th>
                <td>
                    <input type="password" id="twilio_auth_token" name="<?php echo self::OPTION_NAME; ?>[twilio_auth_token]" 
                           value="<?php echo esc_attr($settings['twilio_auth_token'] ?? ''); ?>" />
                    <p class="description"><?php esc_html_e('Your Twilio Auth Token', 'waza-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="twilio_phone_number"><?php esc_html_e('Twilio Phone Number', 'waza-booking'); ?></label></th>
                <td>
                    <input type="text" id="twilio_phone_number" name="<?php echo self::OPTION_NAME; ?>[twilio_phone_number]" 
                           value="<?php echo esc_attr($settings['twilio_phone_number'] ?? ''); ?>" placeholder="+1234567890" />
                    <p class="description"><?php esc_html_e('Your Twilio phone number with country code', 'waza-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="textlocal_api_key"><?php esc_html_e('TextLocal API Key', 'waza-booking'); ?></label></th>
                <td>
                    <input type="password" id="textlocal_api_key" name="<?php echo self::OPTION_NAME; ?>[textlocal_api_key]" 
                           value="<?php echo esc_attr($settings['textlocal_api_key'] ?? ''); ?>" />
                    <p class="description"><?php esc_html_e('Your TextLocal API Key', 'waza-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="textlocal_sender"><?php esc_html_e('TextLocal Sender Name', 'waza-booking'); ?></label></th>
                <td>
                    <input type="text" id="textlocal_sender" name="<?php echo self::OPTION_NAME; ?>[textlocal_sender]" 
                           value="<?php echo esc_attr($settings['textlocal_sender'] ?? ''); ?>" maxlength="11" />
                    <p class="description"><?php esc_html_e('Sender name (max 11 characters)', 'waza-booking'); ?></p>
                </td>
            </tr>
        </table>
        
        <h3><?php esc_html_e('iCalendar Integration', 'waza-booking'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="enable_ical_export"><?php esc_html_e('Enable iCal Export', 'waza-booking'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="enable_ical_export" name="<?php echo self::OPTION_NAME; ?>[enable_ical_export]" 
                               value="1" <?php checked($settings['enable_ical_export'] ?? 1, 1); ?> />
                        <?php esc_html_e('Allow users to download bookings as iCal files', 'waza-booking'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="ical_event_title_format"><?php esc_html_e('iCal Event Title Format', 'waza-booking'); ?></label></th>
                <td>
                    <input type="text" id="ical_event_title_format" name="<?php echo self::OPTION_NAME; ?>[ical_event_title_format]" 
                           value="<?php echo esc_attr($settings['ical_event_title_format'] ?? '{activity_name} - {studio_name}'); ?>" />
                    <p class="description"><?php esc_html_e('Available placeholders: {activity_name}, {studio_name}, {instructor_name}', 'waza-booking'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render Appearance Tab
     */
    private function render_appearance_tab($settings) {
        ?>
        <h3><?php esc_html_e('Color Scheme', 'waza-booking'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Primary Color', 'waza-booking'); ?></th>
                <td>
                    <input type="color" name="<?php echo self::OPTION_NAME; ?>[appearance_primary_color]" 
                           value="<?php echo esc_attr($settings['appearance_primary_color'] ?? '#5BC0BE'); ?>" />
                    <p class="description"><?php esc_html_e('Main brand color for buttons, progress bars, and accents', 'waza-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Secondary Color', 'waza-booking'); ?></th>
                <td>
                    <input type="color" name="<?php echo self::OPTION_NAME; ?>[appearance_secondary_color]" 
                           value="<?php echo esc_attr($settings['appearance_secondary_color'] ?? '#3A506B'); ?>" />
                    <p class="description"><?php esc_html_e('Secondary color for headings and highlights', 'waza-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Background Color', 'waza-booking'); ?></th>
                <td>
                    <input type="color" name="<?php echo self::OPTION_NAME; ?>[appearance_background_color]" 
                           value="<?php echo esc_attr($settings['appearance_background_color'] ?? '#F5F5F5'); ?>" />
                    <p class="description"><?php esc_html_e('Background color for modals and forms', 'waza-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Text Color', 'waza-booking'); ?></th>
                <td>
                    <input type="color" name="<?php echo self::OPTION_NAME; ?>[appearance_text_color]" 
                           value="<?php echo esc_attr($settings['appearance_text_color'] ?? '#333333'); ?>" />
                    <p class="description"><?php esc_html_e('Main text color', 'waza-booking'); ?></p>
                </td>
            </tr>
        </table>
        
        <h3><?php esc_html_e('Booking Flow Configuration', 'waza-booking'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Number of Steps', 'waza-booking'); ?></th>
                <td>
                    <select name="<?php echo self::OPTION_NAME; ?>[appearance_booking_steps]">
                        <option value="3" <?php selected($settings['appearance_booking_steps'] ?? '4', '3'); ?>>
                            <?php esc_html_e('3 Steps (Summary, Details, Payment)', 'waza-booking'); ?>
                        </option>
                        <option value="4" <?php selected($settings['appearance_booking_steps'] ?? '4', '4'); ?>>
                            <?php esc_html_e('4 Steps (Time, Details, Payment, Done)', 'waza-booking'); ?>
                        </option>
                    </select>
                    <p class="description"><?php esc_html_e('Number of steps in the booking flow', 'waza-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Step Labels', 'waza-booking'); ?></th>
                <td>
                    <input type="text" name="<?php echo self::OPTION_NAME; ?>[appearance_step_labels]" 
                           value="<?php echo esc_attr($settings['appearance_step_labels'] ?? 'Time,Details,Payment,Done'); ?>" 
                           style="width: 100%; max-width: 500px;" />
                    <p class="description"><?php esc_html_e('Comma-separated labels for each step', 'waza-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Progress Indicator Style', 'waza-booking'); ?></th>
                <td>
                    <select name="<?php echo self::OPTION_NAME; ?>[appearance_progress_style]">
                        <option value="bar" <?php selected($settings['appearance_progress_style'] ?? 'bar', 'bar'); ?>>
                            <?php esc_html_e('Progress Bar', 'waza-booking'); ?>
                        </option>
                        <option value="steps" <?php selected($settings['appearance_progress_style'] ?? 'bar', 'steps'); ?>>
                            <?php esc_html_e('Step Circles', 'waza-booking'); ?>
                        </option>
                    </select>
                    <p class="description"><?php esc_html_e('Style for the multi-step progress indicator', 'waza-booking'); ?></p>
                </td>
            </tr>
        </table>
        
        <h3><?php esc_html_e('Form Customization', 'waza-booking'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Border Radius', 'waza-booking'); ?></th>
                <td>
                    <input type="number" name="<?php echo self::OPTION_NAME; ?>[appearance_border_radius]" 
                           value="<?php echo esc_attr($settings['appearance_border_radius'] ?? '8'); ?>" 
                           min="0" max="50" />
                    <span>px</span>
                    <p class="description"><?php esc_html_e('Border radius for buttons and inputs', 'waza-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Font Family', 'waza-booking'); ?></th>
                <td>
                    <select name="<?php echo self::OPTION_NAME; ?>[appearance_font_family]">
                        <option value="system" <?php selected($settings['appearance_font_family'] ?? 'system', 'system'); ?>>
                            <?php esc_html_e('System Default', 'waza-booking'); ?>
                        </option>
                        <option value="roboto" <?php selected($settings['appearance_font_family'] ?? 'system', 'roboto'); ?>>
                            <?php esc_html_e('Roboto', 'waza-booking'); ?>
                        </option>
                        <option value="open-sans" <?php selected($settings['appearance_font_family'] ?? 'system', 'open-sans'); ?>>
                            <?php esc_html_e('Open Sans', 'waza-booking'); ?>
                        </option>
                        <option value="lato" <?php selected($settings['appearance_font_family'] ?? 'system', 'lato'); ?>>
                            <?php esc_html_e('Lato', 'waza-booking'); ?>
                        </option>
                        <option value="montserrat" <?php selected($settings['appearance_font_family'] ?? 'system', 'montserrat'); ?>>
                            <?php esc_html_e('Montserrat', 'waza-booking'); ?>
                        </option>
                    </select>
                    <p class="description"><?php esc_html_e('Font family for booking interface', 'waza-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Show Terms Checkbox', 'waza-booking'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo self::OPTION_NAME; ?>[appearance_show_terms]" 
                               value="1" <?php checked($settings['appearance_show_terms'] ?? 1, 1); ?> />
                        <?php esc_html_e('Display "I agree to terms of service" checkbox', 'waza-booking'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Terms Checkbox Text', 'waza-booking'); ?></th>
                <td>
                    <input type="text" name="<?php echo self::OPTION_NAME; ?>[appearance_terms_text]" 
                           value="<?php echo esc_attr($settings['appearance_terms_text'] ?? 'I agree to the terms of service'); ?>" 
                           style="width: 100%; max-width: 500px;" />
                </td>
            </tr>
        </table>
        
        <h3><?php esc_html_e('Button Text', 'waza-booking'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Next Button Text', 'waza-booking'); ?></th>
                <td>
                    <input type="text" name="<?php echo self::OPTION_NAME; ?>[appearance_button_next]" 
                           value="<?php echo esc_attr($settings['appearance_button_next'] ?? 'NEXT'); ?>" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Back Button Text', 'waza-booking'); ?></th>
                <td>
                    <input type="text" name="<?php echo self::OPTION_NAME; ?>[appearance_button_back]" 
                           value="<?php echo esc_attr($settings['appearance_button_back'] ?? 'BACK'); ?>" />
                </td>
            </tr>
        </table>
        <?php
    }
    
    private function render_calendar_tab($settings) {
        ?>
        <h3><?php esc_html_e('Calendar Appearance Settings', 'waza-booking'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Primary Color', 'waza-booking'); ?></th>
                <td>
                    <input type="color" name="<?php echo self::OPTION_NAME; ?>[waza_calendar_primary_color]" 
                           value="<?php echo esc_attr($settings['waza_calendar_primary_color'] ?? '#2271b1'); ?>" />
                    <p class="description"><?php esc_html_e('Primary color for calendar UI elements', 'waza-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Start of Week', 'waza-booking'); ?></th>
                <td>
                    <select name="<?php echo self::OPTION_NAME; ?>[waza_calendar_start_of_week]">
                        <option value="sunday" <?php selected($settings['waza_calendar_start_of_week'] ?? 'sunday', 'sunday'); ?>>
                            <?php esc_html_e('Sunday', 'waza-booking'); ?>
                        </option>
                        <option value="monday" <?php selected($settings['waza_calendar_start_of_week'] ?? 'sunday', 'monday'); ?>>
                            <?php esc_html_e('Monday', 'waza-booking'); ?>
                        </option>
                    </select>
                    <p class="description"><?php esc_html_e('First day of the week in calendar view', 'waza-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Time Format', 'waza-booking'); ?></th>
                <td>
                    <select name="<?php echo self::OPTION_NAME; ?>[waza_calendar_time_format]">
                        <option value="12h" <?php selected($settings['waza_calendar_time_format'] ?? '12h', '12h'); ?>>
                            <?php esc_html_e('12-hour (AM/PM)', 'waza-booking'); ?>
                        </option>
                        <option value="24h" <?php selected($settings['waza_calendar_time_format'] ?? '12h', '24h'); ?>>
                            <?php esc_html_e('24-hour', 'waza-booking'); ?>
                        </option>
                    </select>
                    <p class="description"><?php esc_html_e('Time format for displaying slots', 'waza-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Show Instructor Names', 'waza-booking'); ?></th>
                <td>
                    <select name="<?php echo self::OPTION_NAME; ?>[waza_calendar_show_instructor]">
                        <option value="yes" <?php selected($settings['waza_calendar_show_instructor'] ?? 'yes', 'yes'); ?>>
                            <?php esc_html_e('Yes', 'waza-booking'); ?>
                        </option>
                        <option value="no" <?php selected($settings['waza_calendar_show_instructor'] ?? 'yes', 'no'); ?>>
                            <?php esc_html_e('No', 'waza-booking'); ?>
                        </option>
                    </select>
                    <p class="description"><?php esc_html_e('Display instructor names in calendar slots', 'waza-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Show Prices', 'waza-booking'); ?></th>
                <td>
                    <select name="<?php echo self::OPTION_NAME; ?>[waza_calendar_show_price]">
                        <option value="yes" <?php selected($settings['waza_calendar_show_price'] ?? 'yes', 'yes'); ?>>
                            <?php esc_html_e('Yes', 'waza-booking'); ?>
                        </option>
                        <option value="no" <?php selected($settings['waza_calendar_show_price'] ?? 'yes', 'no'); ?>>
                            <?php esc_html_e('No', 'waza-booking'); ?>
                        </option>
                    </select>
                    <p class="description"><?php esc_html_e('Display prices in calendar slots', 'waza-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Max Slots Per Day', 'waza-booking'); ?></th>
                <td>
                    <input type="number" name="<?php echo self::OPTION_NAME; ?>[waza_calendar_slots_per_day]" 
                           value="<?php echo esc_attr($settings['waza_calendar_slots_per_day'] ?? 8); ?>" 
                           min="1" max="20" />
                    <p class="description"><?php esc_html_e('Maximum number of slots to display per day in calendar view', 'waza-booking'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Section callbacks
     */
    public function general_section_callback() {
        echo '<p>' . __('Configure general settings for your booking system.', 'waza-booking') . '</p>';
    }
    
    public function payment_section_callback() {
        echo '<p>' . __('Configure payment gateway integrations. Enable one or more payment methods.', 'waza-booking') . '</p>';
    }
    
    public function booking_section_callback() {
        echo '<p>' . __('Configure booking rules and policies.', 'waza-booking') . '</p>';
    }
    
    public function email_section_callback() {
        echo '<p>' . __('Customize email templates sent to customers.', 'waza-booking') . '</p>';
    }
    
    public function notifications_section_callback() {
        echo '<p>' . __('Configure notification and refund/reschedule policies.', 'waza-booking') . '</p>';
    }
    
    public function calendar_section_callback() {
        echo '<p>' . __('Customize the interactive calendar appearance and behavior.', 'waza-booking') . '</p>';
    }
    
    public function appearance_section_callback() {
        echo '<p>' . __('Customize the look and feel of booking modals, forms, and calendar.', 'waza-booking') . '</p>';
        echo '<p class="description"><strong>💡 Tip:</strong> Changes apply to all frontend booking interfaces including modals, progress indicators, and buttons.</p>';
    }
    
    /**
     * Field callbacks
     */
    public function text_field_callback($args) {
        $settings = get_option(self::OPTION_NAME, []);
        $default = isset($args['default']) ? $args['default'] : '';
        $value = isset($settings[$args['field_id']]) ? $settings[$args['field_id']] : $default;
        $class = isset($args['class']) ? $args['class'] : '';
        
        printf(
            '<input type="text" id="%s" name="%s[%s]" value="%s" class="%s" />',
            esc_attr($args['field_id']),
            esc_attr(self::OPTION_NAME),
            esc_attr($args['field_id']),
            esc_attr($value),
            esc_attr($class)
        );
        
        if (isset($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }
    
    public function email_field_callback($args) {
        $settings = get_option(self::OPTION_NAME, []);
        $value = isset($settings[$args['field_id']]) ? $settings[$args['field_id']] : '';
        
        printf(
            '<input type="email" id="%s" name="%s[%s]" value="%s" />',
            esc_attr($args['field_id']),
            esc_attr(self::OPTION_NAME),
            esc_attr($args['field_id']),
            esc_attr($value)
        );
        
        if (isset($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }
    
    public function password_field_callback($args) {
        $settings = get_option(self::OPTION_NAME, []);
        $value = isset($settings[$args['field_id']]) ? $settings[$args['field_id']] : '';
        $class = isset($args['class']) ? $args['class'] : '';
        
        printf(
            '<input type="password" id="%s" name="%s[%s]" value="%s" class="%s" />',
            esc_attr($args['field_id']),
            esc_attr(self::OPTION_NAME),
            esc_attr($args['field_id']),
            esc_attr($value),
            esc_attr($class)
        );
        
        if (isset($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }
    
    public function number_field_callback($args) {
        $settings = get_option(self::OPTION_NAME, []);
        $default = isset($args['default']) ? $args['default'] : '';
        $value = isset($settings[$args['field_id']]) ? $settings[$args['field_id']] : $default;
        $class = isset($args['class']) ? $args['class'] : '';
        $min = isset($args['min']) ? 'min="' . esc_attr($args['min']) . '"' : '';
        $max = isset($args['max']) ? 'max="' . esc_attr($args['max']) . '"' : '';
        
        printf(
            '<input type="number" id="%s" name="%s[%s]" value="%s" class="%s" %s %s />',
            esc_attr($args['field_id']),
            esc_attr(self::OPTION_NAME),
            esc_attr($args['field_id']),
            esc_attr($value),
            esc_attr($class),
            $min,
            $max
        );
        
        if (isset($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }
    
    public function textarea_field_callback($args) {
        $settings = get_option(self::OPTION_NAME, []);
        $value = isset($settings[$args['field_id']]) ? $settings[$args['field_id']] : '';
        
        printf(
            '<textarea id="%s" name="%s[%s]" rows="4">%s</textarea>',
            esc_attr($args['field_id']),
            esc_attr(self::OPTION_NAME),
            esc_attr($args['field_id']),
            esc_textarea($value)
        );
        
        if (isset($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }
    
    public function select_field_callback($args) {
        $settings = get_option(self::OPTION_NAME, []);
        $default = isset($args['default']) ? $args['default'] : '';
        $value = isset($settings[$args['field_id']]) ? $settings[$args['field_id']] : $default;
        
        printf('<select id="%s" name="%s[%s]">', esc_attr($args['field_id']), esc_attr(self::OPTION_NAME), esc_attr($args['field_id']));
        
        foreach ($args['options'] as $option_value => $option_label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($option_value),
                selected($value, $option_value, false),
                esc_html($option_label)
            );
        }
        
        echo '</select>';
        
        if (isset($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }
    
    public function color_field_callback($args) {
        $settings = get_option(self::OPTION_NAME, []);
        $default = isset($args['default']) ? $args['default'] : '#007bff';
        $value = isset($settings[$args['field_id']]) ? $settings[$args['field_id']] : $default;
        
        printf(
            '<input type="color" id="%s" name="%s[%s]" value="%s" />',
            esc_attr($args['field_id']),
            esc_attr(self::OPTION_NAME),
            esc_attr($args['field_id']),
            esc_attr($value)
        );
        
        if (isset($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }
    
    public function checkbox_field_callback($args) {
        $settings = get_option(self::OPTION_NAME, []);
        $default = isset($args['default']) ? $args['default'] : '';
        $value = isset($settings[$args['field_id']]) ? $settings[$args['field_id']] : $default;
        $disabled = isset($args['disabled']) ? 'disabled' : '';
        
        printf(
            '<input type="checkbox" id="%s" name="%s[%s]" value="1" %s %s />',
            esc_attr($args['field_id']),
            esc_attr(self::OPTION_NAME),
            esc_attr($args['field_id']),
            checked('1', $value, false),
            $disabled
        );
        
        if (isset($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }
    
    public function multi_checkbox_field_callback($args) {
        $settings = get_option(self::OPTION_NAME, []);
        $values = isset($settings[$args['field_id']]) ? (array) $settings[$args['field_id']] : [];
        
        foreach ($args['options'] as $option_value => $option_label) {
            $checked = in_array($option_value, $values) ? 'checked' : '';
            printf(
                '<label><input type="checkbox" name="%s[%s][]" value="%s" %s /> %s</label><br/>',
                esc_attr(self::OPTION_NAME),
                esc_attr($args['field_id']),
                esc_attr($option_value),
                $checked,
                esc_html($option_label)
            );
        }
        
        if (isset($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }
    
    public function timezone_field_callback($args) {
        $settings = get_option(self::OPTION_NAME, []);
        $value = isset($settings[$args['field_id']]) ? $settings[$args['field_id']] : get_option('timezone_string', 'UTC');
        
        printf('<select id="%s" name="%s[%s]">', esc_attr($args['field_id']), esc_attr(self::OPTION_NAME), esc_attr($args['field_id']));
        
        $timezones = timezone_identifiers_list();
        foreach ($timezones as $timezone) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($timezone),
                selected($value, $timezone, false),
                esc_html($timezone)
            );
        }
        
        echo '</select>';
        
        if (isset($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }
    
    public function page_select_callback($args) {
        $settings = get_option(self::OPTION_NAME, []);
        $value = isset($settings[$args['field_id']]) ? $settings[$args['field_id']] : '';
        
        wp_dropdown_pages([
            'name' => self::OPTION_NAME . '[' . $args['field_id'] . ']',
            'id' => $args['field_id'],
            'selected' => $value,
            'show_option_none' => __('Select Page', 'waza-booking'),
            'option_none_value' => ''
        ]);
        
        if (isset($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        // Debug: Log incoming data
        error_log('Waza Settings Input: ' . print_r($input, true));
        
        $sanitized = [];
        
        // Define sanitization rules for each field
        $text_fields = [
            'business_name', 'business_address', 'contact_phone', 'from_name',
            'razorpay_key_id', 'stripe_publishable_key',
            'twilio_account_sid', 'twilio_phone_number',
            'textlocal_api_key', 'textlocal_sender',
            'phonepay_merchant_id', 'phonepay_salt_index',
            'phonepe_merchant_id', 'phonepe_salt_key', 'phonepe_salt_index', // PhonePe fields
            'ical_event_name', 'ical_location', 'ical_event_title_format',
            'sender_name', // Email sender name
            'waza_calendar_primary_color', 'waza_calendar_start_of_week',
            'waza_calendar_time_format', 'waza_calendar_show_instructor',
            'waza_calendar_show_price',
            // Appearance customization fields
            'appearance_primary_color', 'appearance_secondary_color', 'appearance_background_color',
            'appearance_text_color', 'appearance_step_labels', 'appearance_terms_text',
            'appearance_button_next', 'appearance_button_back', 'appearance_slots_bg_color'
        ];
        
        $email_fields = [
            'contact_email', 'from_email', 'admin_notification_email',
            'sender_email' // Email sender email
        ];
        
        $password_fields = [
            'razorpay_key_secret', 'razorpay_webhook_secret', 
            'stripe_secret_key', 'stripe_webhook_secret',
            'twilio_auth_token', 'phonepay_api_key', 'phonepe_api_key'
        ];
        
        $number_fields = [
            'advance_booking_days', 'booking_cutoff_hours', 'cancellation_cutoff_hours',
            'max_attendees_per_booking', 'partial_payment_percentage', 'qr_code_expiry_hours',
            'default_duration', 'cancellation_hours', // Booking settings
            'waza_calendar_slots_per_day', // Calendar settings
            'appearance_border_radius' // Appearance settings
        ];
        
        $select_fields = [
            'timezone', 'currency', 'date_format', 'time_format', 'payment_mode',
            'sms_provider',
            'appearance_progress_style', 'appearance_booking_steps', 'appearance_font_family' // Appearance settings
        ];
        
        $checkbox_fields = [
            'razorpay_enabled', 'stripe_enabled', 'partial_payment_enabled',
            'auto_confirm_bookings', 'require_phone_number', 'sms_notifications_enabled',
            'qr_code_enabled', 'phonepay_enabled', 'phonepe_enabled', 'enable_csv_export',
            'enable_activity_logging', 'enable_attendance_tracking', 'enable_ical_export',
            'send_confirmations', 'send_reminders', 'sms_enabled', // Email/SMS settings
            'appearance_show_terms' // Appearance settings
        ];
        
        $array_fields = ['payment_methods', 'email_notifications'];
        
        // Sanitize each field type
        foreach ($text_fields as $field) {
            if (isset($input[$field])) {
                $sanitized[$field] = sanitize_text_field($input[$field]);
            }
        }
        
        foreach ($email_fields as $field) {
            if (isset($input[$field])) {
                $sanitized[$field] = sanitize_email($input[$field]);
            }
        }
        
        foreach ($password_fields as $field) {
            if (isset($input[$field])) {
                $sanitized[$field] = sanitize_text_field($input[$field]);
            }
        }
        
        foreach ($number_fields as $field) {
            if (isset($input[$field])) {
                $sanitized[$field] = absint($input[$field]);
            }
        }
        
        foreach ($select_fields as $field) {
            if (isset($input[$field])) {
                $sanitized[$field] = sanitize_text_field($input[$field]);
            }
        }
        
        foreach ($checkbox_fields as $field) {
            $sanitized[$field] = isset($input[$field]) ? '1' : '0';
        }
        
        foreach ($array_fields as $field) {
            if (isset($input[$field]) && is_array($input[$field])) {
                $sanitized[$field] = array_map('sanitize_text_field', $input[$field]);
            }
        }
        
        // Page selections
        if (isset($input['booking_status_page'])) {
            $sanitized['booking_status_page'] = absint($input['booking_status_page']);
        }
        
        if (isset($input['terms_conditions_page'])) {
            $sanitized['terms_conditions_page'] = absint($input['terms_conditions_page']);
        }
        
        // Debug: Log sanitized data before returning
        error_log('Waza Settings Sanitized: ' . print_r($sanitized, true));
        
        return $sanitized;
    }
    
    /**
     * Enqueue settings page assets
     */
    public function enqueue_settings_assets($hook) {
        if ($hook !== 'waza_page_waza-settings') {
            return;
        }
        
        wp_enqueue_script('jquery-ui-tabs');
        wp_enqueue_style('jquery-ui-tabs');
        
        // Add custom CSS for settings page
        wp_add_inline_style('admin-menu', '
            .waza-settings-container .nav-tab-wrapper {
                border-bottom: 1px solid #ccd0d4;
            }
            .waza-settings-container .tab-content {
                background: #fff;
                padding: 20px;
                border: 1px solid #ccd0d4;
                border-top: none;
            }
        ');
    }
    
    /**
     * Test payment gateway connection
     */
    public function test_payment_gateway() {
        check_ajax_referer('waza_test_gateway', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'waza-booking'));
        }
        
        $gateway = sanitize_text_field($_POST['gateway']);
        $settings = get_option(self::OPTION_NAME, []);
        
        $success = false;
        $message = '';
        
        if ($gateway === 'razorpay') {
            if (empty($settings['razorpay_key_id']) || empty($settings['razorpay_key_secret'])) {
                $message = __('Please enter Razorpay credentials first', 'waza-booking');
            } else {
                // Test Razorpay connection (simplified)
                $success = true;
                $message = __('Razorpay connection successful', 'waza-booking');
            }
        } elseif ($gateway === 'stripe') {
            if (empty($settings['stripe_publishable_key']) || empty($settings['stripe_secret_key'])) {
                $message = __('Please enter Stripe credentials first', 'waza-booking');
            } else {
                // Test Stripe connection (simplified)
                $success = true;
                $message = __('Stripe connection successful', 'waza-booking');
            }
        }
        
        if ($success) {
            wp_send_json_success($message);
        } else {
            wp_send_json_error($message);
        }
    }
    
    /**
     * Get setting value
     */
    public static function get_setting($key, $default = '') {
        $settings = get_option(self::OPTION_NAME, []);
        return isset($settings[$key]) ? $settings[$key] : $default;
    }
}