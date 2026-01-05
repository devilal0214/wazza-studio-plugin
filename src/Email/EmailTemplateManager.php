<?php
/**
 * Email Template Manager
 *
 * Manages email templates for booking notifications and messages.
 *
 * @package WazaBooking
 */

namespace WazaBooking\Email;

if (!defined('ABSPATH')) {
    exit;
}

class EmailTemplateManager {
    
    /**
     * Available email template types
     */
    const TEMPLATE_TYPES = [
        'booking_confirmation' => 'Booking Confirmation',
        'booking_reminder' => 'Booking Reminder', 
        'booking_cancellation' => 'Booking Cancellation',
        'booking_rescheduled' => 'Booking Rescheduled',
        'payment_confirmation' => 'Payment Confirmation',
        'waitlist_notification' => 'Waitlist Notification',
        'instructor_notification' => 'Instructor Notification',
        'thank_you_message' => 'Thank You Message',
        'welcome_email' => 'Welcome Email',
        'password_reset' => 'Password Reset'
    ];
    
    /**
     * Template variables
     */
    const TEMPLATE_VARIABLES = [
        'general' => [
            'site_name' => 'Site Name',
            'site_url' => 'Site URL',
            'current_date' => 'Current Date',
            'current_time' => 'Current Time'
        ],
        'user' => [
            'user_name' => 'User Name',
            'user_email' => 'User Email',
            'user_phone' => 'User Phone',
            'user_first_name' => 'First Name',
            'user_last_name' => 'Last Name'
        ],
        'booking' => [
            'booking_id' => 'Booking ID',
            'booking_date' => 'Booking Date',
            'booking_time' => 'Booking Time',
            'booking_status' => 'Booking Status',
            'booking_notes' => 'Booking Notes',
            'participants' => 'Number of Participants'
        ],
        'activity' => [
            'activity_name' => 'Activity Name',
            'activity_description' => 'Activity Description',
            'activity_duration' => 'Activity Duration',
            'activity_price' => 'Activity Price',
            'activity_category' => 'Activity Category',
            'activity_location' => 'Activity Location'
        ],
        'instructor' => [
            'instructor_name' => 'Instructor Name',
            'instructor_email' => 'Instructor Email',
            'instructor_bio' => 'Instructor Bio'
        ],
        'payment' => [
            'payment_amount' => 'Payment Amount',
            'payment_method' => 'Payment Method',
            'payment_status' => 'Payment Status',
            'payment_date' => 'Payment Date',
            'transaction_id' => 'Transaction ID'
        ]
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', [$this, 'init_admin']);
        add_action('wp_ajax_waza_get_email_template', [$this, 'ajax_get_template']);
        add_action('wp_ajax_waza_save_email_template', [$this, 'ajax_save_template']);
        add_action('wp_ajax_waza_preview_email_template', [$this, 'ajax_preview_template']);
        add_action('wp_ajax_waza_reset_email_template', [$this, 'ajax_reset_template']);
        add_action('wp_ajax_waza_test_email_template', [$this, 'ajax_test_email']);
    }
    
    /**
     * Initialize admin functionality
     */
    public function init_admin() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }
    
    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_submenu_page(
            'waza-booking',
            __('Email Templates', 'waza-booking'),
            __('Email Templates', 'waza-booking'),
            'manage_options',
            'waza-email-templates',
            [$this, 'admin_page']
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'waza-email-templates') === false) {
            return;
        }
        
        // WordPress editor
        wp_enqueue_editor();
        wp_enqueue_media();
        
        // Color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        // Custom styles and scripts
        wp_enqueue_style(
            'waza-email-templates',
            WAZA_BOOKING_PLUGIN_URL . 'assets/admin/email-templates.css',
            [],
            WAZA_BOOKING_VERSION
        );
        
        wp_enqueue_script(
            'waza-email-templates',
            WAZA_BOOKING_PLUGIN_URL . 'assets/admin/email-templates.js',
            ['jquery', 'wp-color-picker', 'wp-util'],
            WAZA_BOOKING_VERSION,
            true
        );
        
        wp_localize_script('waza-email-templates', 'waza_email_templates', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('waza_email_templates'),
            'strings' => [
                'save_success' => __('Template saved successfully!', 'waza-booking'),
                'save_error' => __('Error saving template.', 'waza-booking'),
                'test_email_sent' => __('Test email sent successfully!', 'waza-booking'),
                'test_email_error' => __('Error sending test email.', 'waza-booking'),
                'confirm_reset' => __('Are you sure you want to reset this template to default?', 'waza-booking'),
                'loading' => __('Loading...', 'waza-booking')
            ],
            'template_variables' => self::TEMPLATE_VARIABLES
        ]);
    }
    
    /**
     * Admin page HTML
     */
    public function admin_page() {
        ?>
        <div class="wrap waza-email-templates-admin">
            <div class="email-templates-header">
                <h1><?php _e('Email Templates', 'waza-booking'); ?></h1>
                <p><?php _e('Customize email templates for booking notifications and messages. Use template variables to personalize emails.', 'waza-booking'); ?></p>
            </div>
            
            <div class="email-templates-container">
                <!-- Template Selection -->
                <div class="template-sidebar">
                    <h3><?php _e('Email Templates', 'waza-booking'); ?></h3>
                    <ul class="template-list">
                        <?php foreach (self::TEMPLATE_TYPES as $type => $label): ?>
                            <li>
                                <a href="#" class="template-item" data-template="<?php echo esc_attr($type); ?>">
                                    <?php echo esc_html($label); ?>
                                    <span class="template-status <?php echo $this->is_template_customized($type) ? 'customized' : 'default'; ?>"></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <!-- Template Editor -->
                <div class="template-editor">
                    <div class="template-editor-header">
                        <h3 class="template-title"><?php _e('Select a template to edit', 'waza-booking'); ?></h3>
                        <div class="template-actions">
                            <button type="button" class="button preview-template" disabled>
                                <?php _e('Preview', 'waza-booking'); ?>
                            </button>
                            <button type="button" class="button test-email" disabled>
                                <?php _e('Send Test', 'waza-booking'); ?>
                            </button>
                            <button type="button" class="button reset-template" disabled>
                                <?php _e('Reset to Default', 'waza-booking'); ?>
                            </button>
                            <button type="button" class="button button-primary save-template" disabled>
                                <?php _e('Save Template', 'waza-booking'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="template-editor-content">
                        <div class="template-settings">
                            <div class="setting-group">
                                <label for="template-subject"><?php _e('Subject Line', 'waza-booking'); ?></label>
                                <input type="text" id="template-subject" class="template-subject widefat" placeholder="<?php _e('Email subject...', 'waza-booking'); ?>">
                                <p class="description"><?php _e('Use template variables like {user_name} and {activity_name}', 'waza-booking'); ?></p>
                            </div>
                            
                            <div class="setting-group">
                                <label><?php _e('Email Content', 'waza-booking'); ?></label>
                                <div class="editor-toolbar">
                                    <button type="button" class="button insert-variable" data-target="content">
                                        <?php _e('Insert Variable', 'waza-booking'); ?>
                                    </button>
                                    <select class="variable-selector">
                                        <option value=""><?php _e('Select Variable', 'waza-booking'); ?></option>
                                        <?php foreach (self::TEMPLATE_VARIABLES as $group => $variables): ?>
                                            <optgroup label="<?php echo esc_attr(ucfirst($group)); ?>">
                                                <?php foreach ($variables as $key => $label): ?>
                                                    <option value="{<?php echo esc_attr($key); ?>}"><?php echo esc_html($label); ?></option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php
                                wp_editor('', 'template-content', [
                                    'textarea_name' => 'template_content',
                                    'textarea_rows' => 20,
                                    'media_buttons' => true,
                                    'teeny' => false,
                                    'tinymce' => [
                                        'toolbar1' => 'formatselect,bold,italic,underline,strikethrough,|,bullist,numlist,blockquote,|,link,unlink,|,spellchecker,wp_adv',
                                        'toolbar2' => 'undo,redo,|,cut,copy,paste,pastetext,|,forecolor,backcolor,|,alignleft,aligncenter,alignright,alignjustify,|,wp_more'
                                    ]
                                ]);
                                ?>
                            </div>
                        </div>
                        
                        <!-- Template Variables Reference -->
                        <div class="template-variables">
                            <h4><?php _e('Available Variables', 'waza-booking'); ?></h4>
                            <div class="variables-grid">
                                <?php foreach (self::TEMPLATE_VARIABLES as $group => $variables): ?>
                                    <div class="variable-group">
                                        <h5><?php echo esc_html(ucfirst($group)); ?></h5>
                                        <ul>
                                            <?php foreach ($variables as $key => $label): ?>
                                                <li>
                                                    <code class="variable-tag" data-variable="{<?php echo esc_attr($key); ?>}">
                                                        {<?php echo esc_html($key); ?>}
                                                    </code>
                                                    <span><?php echo esc_html($label); ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Preview Modal -->
            <div id="email-preview-modal" class="email-modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3><?php _e('Email Preview', 'waza-booking'); ?></h3>
                        <button type="button" class="close-modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="email-preview">
                            <div class="email-header">
                                <strong><?php _e('Subject:', 'waza-booking'); ?></strong>
                                <span class="preview-subject"></span>
                            </div>
                            <div class="email-body"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="button close-modal"><?php _e('Close', 'waza-booking'); ?></button>
                    </div>
                </div>
            </div>
            
            <!-- Test Email Modal -->
            <div id="test-email-modal" class="email-modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3><?php _e('Send Test Email', 'waza-booking'); ?></h3>
                        <button type="button" class="close-modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p><?php _e('Send a test email to see how your template will look in email clients.', 'waza-booking'); ?></p>
                        <label for="test-email-address"><?php _e('Email Address', 'waza-booking'); ?></label>
                        <input type="email" id="test-email-address" class="widefat" value="<?php echo esc_attr(get_option('admin_email')); ?>" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="button close-modal"><?php _e('Cancel', 'waza-booking'); ?></button>
                        <button type="button" class="button button-primary send-test-email"><?php _e('Send Test Email', 'waza-booking'); ?></button>
                    </div>
                </div>
            </div>
            
            <div class="loading-overlay" style="display: none;">
                <div class="loading-spinner"></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get email template
     */
    public function get_template($type, $format = 'array') {
        $templates = get_option('waza_email_templates', []);
        
        if (isset($templates[$type])) {
            $template = $templates[$type];
        } else {
            $template = $this->get_default_template($type);
        }
        
        if ($format === 'array') {
            return $template;
        }
        
        return $template['content'] ?? '';
    }
    
    /**
     * Save email template
     */
    public function save_template($type, $subject, $content) {
        $templates = get_option('waza_email_templates', []);
        
        $templates[$type] = [
            'subject' => sanitize_text_field($subject),
            'content' => wp_kses_post($content),
            'modified' => current_time('mysql')
        ];
        
        return update_option('waza_email_templates', $templates);
    }
    
    /**
     * Reset template to default
     */
    public function reset_template($type) {
        $templates = get_option('waza_email_templates', []);
        
        if (isset($templates[$type])) {
            unset($templates[$type]);
            update_option('waza_email_templates', $templates);
        }
        
        return true;
    }
    
    /**
     * Check if template is customized
     */
    public function is_template_customized($type) {
        $templates = get_option('waza_email_templates', []);
        return isset($templates[$type]);
    }
    
    /**
     * Replace template variables
     */
    public function replace_variables($content, $data = []) {
        // Default variables
        $variables = [
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url(),
            'current_date' => wp_date(get_option('date_format')),
            'current_time' => wp_date(get_option('time_format'))
        ];
        
        // Merge with provided data
        $variables = array_merge($variables, $data);
        
        // Replace variables in content
        foreach ($variables as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }
        
        return $content;
    }
    
    /**
     * Send email using template
     */
    public function send_email($type, $to, $data = [], $attachments = []) {
        $template = $this->get_template($type);
        
        if (!$template) {
            return false;
        }
        
        $subject = $this->replace_variables($template['subject'], $data);
        $content = $this->replace_variables($template['content'], $data);
        
        // Set email headers
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        ];
        
        return wp_mail($to, $subject, $content, $headers, $attachments);
    }
    
    /**
     * Get default templates
     */
    private function get_default_template($type) {
        $defaults = [
            'booking_confirmation' => [
                'subject' => 'Booking Confirmation - {activity_name}',
                'content' => $this->get_default_booking_confirmation_template()
            ],
            'booking_reminder' => [
                'subject' => 'Reminder: {activity_name} - {booking_date}',
                'content' => $this->get_default_booking_reminder_template()
            ],
            'booking_cancellation' => [
                'subject' => 'Booking Cancelled - {activity_name}',
                'content' => $this->get_default_booking_cancellation_template()
            ],
            'thank_you_message' => [
                'subject' => 'Thank you for booking with us!',
                'content' => $this->get_default_thank_you_template()
            ],
            'welcome_email' => [
                'subject' => 'Welcome to {site_name}!',
                'content' => $this->get_default_welcome_template()
            ]
        ];
        
        return $defaults[$type] ?? [
            'subject' => 'Notification from {site_name}',
            'content' => '<p>Hello {user_name},</p><p>This is a notification from {site_name}.</p>'
        ];
    }
    
    /**
     * AJAX: Get template
     */
    public function ajax_get_template() {
        check_ajax_referer('waza_email_templates', 'nonce');
        
        $type = sanitize_key($_POST['type']);
        $template = $this->get_template($type);
        
        wp_send_json_success($template);
    }
    
    /**
     * AJAX: Save template
     */
    public function ajax_save_template() {
        check_ajax_referer('waza_email_templates', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'waza-booking'));
        }
        
        $type = sanitize_key($_POST['type']);
        $subject = sanitize_text_field($_POST['subject']);
        $content = wp_kses_post($_POST['content']);
        
        $success = $this->save_template($type, $subject, $content);
        
        if ($success) {
            wp_send_json_success(__('Template saved successfully!', 'waza-booking'));
        } else {
            wp_send_json_error(__('Error saving template.', 'waza-booking'));
        }
    }
    
    /**
     * AJAX: Preview template
     */
    public function ajax_preview_template() {
        check_ajax_referer('waza_email_templates', 'nonce');
        
        $type = sanitize_key($_POST['type']);
        $subject = sanitize_text_field($_POST['subject']);
        $content = wp_kses_post($_POST['content']);
        
        // Sample data for preview
        $sample_data = $this->get_sample_data();
        
        $preview_subject = $this->replace_variables($subject, $sample_data);
        $preview_content = $this->replace_variables($content, $sample_data);
        
        wp_send_json_success([
            'subject' => $preview_subject,
            'content' => $preview_content
        ]);
    }
    
    /**
     * AJAX: Reset template
     */
    public function ajax_reset_template() {
        check_ajax_referer('waza_email_templates', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'waza-booking'));
        }
        
        $type = sanitize_key($_POST['type']);
        $this->reset_template($type);
        
        $default_template = $this->get_default_template($type);
        
        wp_send_json_success($default_template);
    }
    
    /**
     * AJAX: Send test email
     */
    public function ajax_test_email() {
        check_ajax_referer('waza_email_templates', 'nonce');
        
        $email = sanitize_email($_POST['email']);
        $subject = sanitize_text_field($_POST['subject']);
        $content = wp_kses_post($_POST['content']);
        
        // Sample data for test
        $sample_data = $this->get_sample_data();
        
        $test_subject = $this->replace_variables($subject, $sample_data);
        $test_content = $this->replace_variables($content, $sample_data);
        
        // Add test email notice
        $test_content = '<div style="background: #fffbf0; padding: 15px; border-left: 4px solid #ffb900; margin-bottom: 20px;"><strong>This is a test email.</strong> This message contains sample data for preview purposes.</div>' . $test_content;
        
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        ];
        
        wp_mail($email, $test_subject, $test_content, $headers);
        
        wp_send_json_success(__('Test email sent successfully!', 'waza-booking'));
    }
    

    
    /**
     * Get sample data for previews
     */
    private function get_sample_data() {
        return [
            'user_name' => 'John Doe',
            'user_email' => 'john@example.com',
            'user_first_name' => 'John',
            'user_last_name' => 'Doe',
            'user_phone' => '+1 (555) 123-4567',
            'booking_id' => 'WB-12345',
            'booking_date' => wp_date('F j, Y'),
            'booking_time' => '2:00 PM - 3:00 PM',
            'booking_status' => 'Confirmed',
            'booking_notes' => 'Looking forward to the session!',
            'participants' => '2',
            'activity_name' => 'Yoga Class',
            'activity_description' => 'A relaxing yoga session for all levels.',
            'activity_duration' => '60 minutes',
            'activity_price' => '$25.00',
            'activity_category' => 'Fitness',
            'activity_location' => 'Studio A',
            'instructor_name' => 'Sarah Johnson',
            'instructor_email' => 'sarah@example.com',
            'payment_amount' => '$25.00',
            'payment_method' => 'Credit Card',
            'payment_status' => 'Completed',
            'payment_date' => wp_date('F j, Y'),
            'transaction_id' => 'TXN-67890',
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url()
        ];
    }

    private function get_default_booking_confirmation_template() {
        return '
        <div style="font-family: \'Inter\', -apple-system, BlinkMacSystemFont, sans-serif; max-width: 600px; margin: 0 auto; padding: 40px 20px; background-color: #F9FAFB; color: #111827;">
            <div style="background: #FFFFFF; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #E5E7EB;">
                <div style="text-align: center; margin-bottom: 30px;">
                    <h2 style="color: #4F46E5; margin: 0; font-size: 24px; font-weight: 700;">Booking Confirmed!</h2>
                </div>
                
                <p style="font-size: 16px; line-height: 1.6; color: #374151; margin-bottom: 24px;">Dear {user_name},</p>
                
                <p style="font-size: 16px; line-height: 1.6; color: #374151; margin-bottom: 32px;">Thank you for your booking! We\'re excited to confirm your reservation.</p>
                
                <div style="background: #EEF2FF; padding: 24px; border-radius: 8px; margin-bottom: 32px; border: 1px solid #C7D2FE;">
                    <h3 style="margin-top: 0; color: #4F46E5; font-size: 18px; font-weight: 600; margin-bottom: 16px;">Booking Details</h3>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 8px 0; color: #6B7280; font-size: 14px;">Activity</td>
                            <td style="padding: 8px 0; color: #111827; font-weight: 600; text-align: right;">{activity_name}</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; color: #6B7280; font-size: 14px;">Date & Time</td>
                            <td style="padding: 8px 0; color: #111827; font-weight: 600; text-align: right;">{booking_date} at {booking_time}</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; color: #6B7280; font-size: 14px;">Duration</td>
                            <td style="padding: 8px 0; color: #111827; font-weight: 600; text-align: right;">{activity_duration}</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; color: #6B7280; font-size: 14px;">Participants</td>
                            <td style="padding: 8px 0; color: #111827; font-weight: 600; text-align: right;">{participants}</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; color: #6B7280; font-size: 14px;">Booking ID</td>
                            <td style="padding: 8px 0; color: #111827; font-weight: 600; text-align: right;">{booking_id}</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; color: #6B7280; font-size: 14px;">Status</td>
                            <td style="padding: 8px 0; color: #059669; font-weight: 600; text-align: right;">{booking_status}</td>
                        </tr>
                    </table>
                </div>
                
                <div style="background: #F9FAFB; padding: 24px; border-radius: 8px; margin-bottom: 32px; border: 1px solid #E5E7EB;">
                    <h4 style="margin-top: 0; color: #111827; font-size: 16px; font-weight: 600; margin-bottom: 12px;">What to Expect</h4>
                    <p style="font-size: 14px; line-height: 1.6; color: #6B7280; margin-bottom: 12px;">{activity_description}</p>
                    <p style="font-size: 14px; line-height: 1.6; color: #6B7280; margin: 0;"><strong>Location:</strong> {activity_location}</p>
                </div>
                
                <p style="font-size: 16px; line-height: 1.6; color: #374151; margin-bottom: 24px;">If you have any questions or need to make changes, please don\'t hesitate to contact us.</p>
                
                <p style="font-size: 16px; line-height: 1.6; color: #374151; margin-bottom: 32px;">We look forward to seeing you!</p>
                
                <div style="border-top: 1px solid #E5E7EB; padding-top: 24px; text-align: center;">
                    <p style="font-size: 14px; color: #6B7280; margin: 0;">Best regards,<br>The {site_name} Team</p>
                </div>
            </div>
        </div>';
    }
    
    private function get_default_booking_reminder_template() {
        return '
        <div style="font-family: \'Inter\', -apple-system, BlinkMacSystemFont, sans-serif; max-width: 600px; margin: 0 auto; padding: 40px 20px; background-color: #F9FAFB; color: #111827;">
            <div style="background: #FFFFFF; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #E5E7EB;">
                <div style="text-align: center; margin-bottom: 30px;">
                    <h2 style="color: #4F46E5; margin: 0; font-size: 24px; font-weight: 700;">Booking Reminder</h2>
                </div>
                
                <p style="font-size: 16px; line-height: 1.6; color: #374151; margin-bottom: 24px;">Hi {user_name},</p>
                
                <p style="font-size: 16px; line-height: 1.6; color: #374151; margin-bottom: 32px;">This is a friendly reminder about your upcoming booking:</p>
                
                <div style="background: #FFFBEB; padding: 24px; border-radius: 8px; margin-bottom: 32px; border: 1px solid #FCD34D;">
                    <h3 style="margin-top: 0; color: #B45309; font-size: 18px; font-weight: 600; margin-bottom: 12px;">{activity_name}</h3>
                    <p style="margin-bottom: 8px; color: #92400E; font-size: 14px;"><strong>Date:</strong> {booking_date}</p>
                    <p style="margin-bottom: 8px; color: #92400E; font-size: 14px;"><strong>Time:</strong> {booking_time}</p>
                    <p style="margin-bottom: 0; color: #92400E; font-size: 14px;"><strong>Location:</strong> {activity_location}</p>
                </div>
                
                <p style="font-size: 16px; line-height: 1.6; color: #374151; margin-bottom: 24px;">Please arrive 10 minutes early to ensure a smooth start to your session.</p>
                
                <p style="font-size: 16px; line-height: 1.6; color: #374151; margin-bottom: 32px;">Looking forward to seeing you soon!</p>
                
                <div style="border-top: 1px solid #E5E7EB; padding-top: 24px; text-align: center;">
                    <p style="font-size: 14px; color: #6B7280; margin: 0;">Best regards,<br>{site_name}</p>
                </div>
            </div>
        </div>';
    }

    private function get_default_booking_cancellation_template() {
        return '
        <div style="font-family: \'Inter\', -apple-system, BlinkMacSystemFont, sans-serif; max-width: 600px; margin: 0 auto; padding: 40px 20px; background-color: #F9FAFB; color: #111827;">
            <div style="background: #FFFFFF; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #E5E7EB;">
                <div style="text-align: center; margin-bottom: 30px;">
                    <h2 style="color: #EF4444; margin: 0; font-size: 24px; font-weight: 700;">Booking Cancelled</h2>
                </div>
                
                <p style="font-size: 16px; line-height: 1.6; color: #374151; margin-bottom: 24px;">Dear {user_name},</p>
                
                <p style="font-size: 16px; line-height: 1.6; color: #374151; margin-bottom: 32px;">We\'re sorry to inform you that your booking has been cancelled.</p>
                
                <div style="background: #FEF2F2; padding: 24px; border-radius: 8px; margin-bottom: 32px; border: 1px solid #FECACA;">
                    <h3 style="margin-top: 0; color: #991B1B; font-size: 18px; font-weight: 600; margin-bottom: 12px;">Cancelled Booking</h3>
                    <p style="margin-bottom: 8px; color: #7F1D1D; font-size: 14px;"><strong>Activity:</strong> {activity_name}</p>
                    <p style="margin-bottom: 8px; color: #7F1D1D; font-size: 14px;"><strong>Original Date:</strong> {booking_date}</p>
                    <p style="margin-bottom: 0; color: #7F1D1D; font-size: 14px;"><strong>Booking ID:</strong> {booking_id}</p>
                </div>
                
                <p style="font-size: 16px; line-height: 1.6; color: #374151; margin-bottom: 24px;">If this cancellation was unexpected or if you have any questions, please contact us immediately.</p>
                
                <p style="font-size: 16px; line-height: 1.6; color: #374151; margin-bottom: 32px;">We apologize for any inconvenience and hope to serve you again soon.</p>
                
                <div style="border-top: 1px solid #E5E7EB; padding-top: 24px; text-align: center;">
                    <p style="font-size: 14px; color: #6B7280; margin: 0;">Best regards,<br>{site_name}</p>
                </div>
            </div>
        </div>';
    }

    private function get_default_thank_you_template() {
        return '
        <div style="font-family: \'Inter\', -apple-system, BlinkMacSystemFont, sans-serif; max-width: 600px; margin: 0 auto; padding: 40px 20px; background-color: #F9FAFB; color: #111827;">
            <div style="background: #FFFFFF; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #E5E7EB; text-align: center;">
                <h1 style="color: #10B981; margin-bottom: 24px; font-size: 32px; font-weight: 700;">Thank You!</h1>
                
                <p style="font-size: 18px; line-height: 1.6; color: #374151; margin-bottom: 24px;">Dear {user_name},</p>
                
                <p style="font-size: 16px; line-height: 1.6; color: #374151; margin-bottom: 32px;">Thank you for choosing {site_name} for your {activity_name} experience!</p>
                
                <div style="background: #ECFDF5; padding: 24px; border-radius: 8px; margin-bottom: 32px; border: 1px solid #A7F3D0;">
                    <p style="margin: 0; font-size: 16px; color: #065F46; font-weight: 500;">
                        We hope you had an amazing time and we\'d love to welcome you back soon!
                    </p>
                </div>
                
                <p style="font-size: 16px; line-height: 1.6; color: #374151; margin-bottom: 24px;">Your feedback is important to us. If you have a moment, we\'d appreciate if you could share your experience.</p>
                
                <p style="font-size: 16px; line-height: 1.6; color: #374151; margin-bottom: 32px;">Thank you again for being part of our community!</p>
                
                <div style="border-top: 1px solid #E5E7EB; padding-top: 24px;">
                    <p style="font-size: 14px; color: #6B7280; margin: 0;">Warm regards,<br>The {site_name} Team</p>
                </div>
            </div>
        </div>';
    }

    private function get_default_welcome_template() {
        return '
        <div style="font-family: \'Inter\', -apple-system, BlinkMacSystemFont, sans-serif; max-width: 600px; margin: 0 auto; padding: 40px 20px; background-color: #F9FAFB; color: #111827;">
            <div style="background: #FFFFFF; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #E5E7EB;">
                <div style="text-align: center; margin-bottom: 30px;">
                    <h1 style="color: #4F46E5; margin: 0; font-size: 32px; font-weight: 700;">Welcome to {site_name}!</h1>
                </div>
                
                <p style="font-size: 16px; line-height: 1.6; color: #374151; margin-bottom: 24px;">Hello {user_name},</p>
                
                <p style="font-size: 16px; line-height: 1.6; color: #374151; margin-bottom: 32px;">Welcome to {site_name}! We\'re thrilled to have you join our community.</p>
                
                <div style="background: #EEF2FF; padding: 24px; border-radius: 8px; margin-bottom: 32px; border: 1px solid #C7D2FE;">
                    <h3 style="margin-top: 0; color: #4F46E5; font-size: 18px; font-weight: 600; margin-bottom: 12px;">Getting Started</h3>
                    <p style="font-size: 14px; line-height: 1.6; color: #4338CA; margin-bottom: 12px;">You can now browse and book our activities, manage your bookings, and much more!</p>
                    <p style="margin: 0;"><a href="{site_url}" style="color: #4F46E5; text-decoration: none; font-weight: 600;">Visit our website</a> to explore available activities.</p>
                </div>
                
                <p style="font-size: 16px; line-height: 1.6; color: #374151; margin-bottom: 24px;">If you have any questions or need assistance, don\'t hesitate to reach out to our support team.</p>
                
                <p style="font-size: 16px; line-height: 1.6; color: #374151; margin-bottom: 32px;">We look forward to providing you with amazing experiences!</p>
                
                <div style="border-top: 1px solid #E5E7EB; padding-top: 24px; text-align: center;">
                    <p style="font-size: 14px; color: #6B7280; margin: 0;">Best regards,<br>The {site_name} Team</p>
                </div>
            </div>
        </div>';
    }
}