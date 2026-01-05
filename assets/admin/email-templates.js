/**
 * Waza Booking Email Templates Admin JavaScript
 */

(function($) {
    'use strict';
    
    var WazaEmailTemplates = {
        
        currentTemplate: null,
        editor: null,
        
        /**
         * Initialize the email templates interface
         */
        init: function() {
            this.bindEvents();
            this.initializeEditor();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;
            
            // Template selection
            $(document).on('click', '.template-item', function(e) {
                e.preventDefault();
                var templateType = $(this).data('template');
                self.loadTemplate(templateType);
                
                // Update active state
                $('.template-item').removeClass('active');
                $(this).addClass('active');
            });
            
            // Template actions
            $(document).on('click', '.save-template', function() {
                self.saveTemplate();
            });
            
            $(document).on('click', '.preview-template', function() {
                self.previewTemplate();
            });
            
            $(document).on('click', '.test-email', function() {
                self.showTestEmailModal();
            });
            
            $(document).on('click', '.reset-template', function() {
                if (confirm(waza_email_templates.strings.confirm_reset)) {
                    self.resetTemplate();
                }
            });
            
            // Variable insertion
            $(document).on('click', '.insert-variable', function() {
                var target = $(this).data('target');
                var selectedVar = $('.variable-selector').val();
                
                if (selectedVar) {
                    self.insertVariable(selectedVar, target);
                }
            });
            
            $(document).on('change', '.variable-selector', function() {
                var selectedVar = $(this).val();
                if (selectedVar) {
                    $('.insert-variable').prop('disabled', false);
                } else {
                    $('.insert-variable').prop('disabled', true);
                }
            });
            
            // Variable tag clicks
            $(document).on('click', '.variable-tag', function() {
                var variable = $(this).data('variable');
                self.insertVariable(variable, 'content');
            });
            
            // Modal events
            $(document).on('click', '.close-modal, .email-modal', function(e) {
                if (e.target === this) {
                    self.closeModal();
                }
            });
            
            $(document).on('click', '.send-test-email', function() {
                self.sendTestEmail();
            });
            
            // Form changes
            $(document).on('input', '#template-subject', function() {
                self.enableSaveButton();
            });
            
            // ESC key to close modals
            $(document).on('keyup', function(e) {
                if (e.keyCode === 27) {
                    self.closeModal();
                }
            });
        },
        
        /**
         * Initialize WordPress editor
         */
        initializeEditor: function() {
            var self = this;
            
            // Monitor editor changes
            if (typeof tinymce !== 'undefined') {
                tinymce.on('AddEditor', function(e) {
                    if (e.editor.id === 'template-content') {
                        self.editor = e.editor;
                        e.editor.on('input change keyup', function() {
                            self.enableSaveButton();
                        });
                    }
                });
            }
            
            // Fallback for textarea
            $(document).on('input', '#template-content', function() {
                self.enableSaveButton();
            });
        },
        
        /**
         * Load template data
         */
        loadTemplate: function(templateType) {
            var self = this;
            
            if (!templateType) {
                return;
            }
            
            this.currentTemplate = templateType;
            this.showLoading();
            
            $.ajax({
                url: waza_email_templates.ajax_url,
                type: 'POST',
                data: {
                    action: 'waza_get_email_template',
                    type: templateType,
                    nonce: waza_email_templates.nonce
                },
                success: function(response) {
                    self.hideLoading();
                    
                    if (response.success) {
                        self.populateEditor(response.data);
                        self.updateTemplateTitle(templateType);
                        self.enableActions();
                    } else {
                        self.showNotice(response.data || 'Error loading template.', 'error');
                    }
                },
                error: function() {
                    self.hideLoading();
                    self.showNotice('Error loading template.', 'error');
                }
            });
        },
        
        /**
         * Populate editor with template data
         */
        populateEditor: function(templateData) {
            // Set subject
            $('#template-subject').val(templateData.subject || '');
            
            // Set content
            if (this.editor && this.editor.initialized) {
                this.editor.setContent(templateData.content || '');
            } else {
                $('#template-content').val(templateData.content || '');
            }
            
            this.disableSaveButton();
        },
        
        /**
         * Update template title
         */
        updateTemplateTitle: function(templateType) {
            var templateName = this.getTemplateName(templateType);
            $('.template-title').text('Editing: ' + templateName);
        },
        
        /**
         * Get template display name
         */
        getTemplateName: function(templateType) {
            var names = {
                'booking_confirmation': 'Booking Confirmation',
                'booking_reminder': 'Booking Reminder',
                'booking_cancellation': 'Booking Cancellation',
                'booking_rescheduled': 'Booking Rescheduled',
                'payment_confirmation': 'Payment Confirmation',
                'waitlist_notification': 'Waitlist Notification',
                'instructor_notification': 'Instructor Notification',
                'thank_you_message': 'Thank You Message',
                'welcome_email': 'Welcome Email',
                'password_reset': 'Password Reset'
            };
            
            return names[templateType] || templateType;
        },
        
        /**
         * Save template
         */
        saveTemplate: function() {
            var self = this;
            
            if (!this.currentTemplate) {
                return;
            }
            
            var subject = $('#template-subject').val();
            var content = this.getEditorContent();
            
            if (!subject.trim()) {
                this.showNotice('Please enter a subject line.', 'error');
                return;
            }
            
            if (!content.trim()) {
                this.showNotice('Please enter email content.', 'error');
                return;
            }
            
            this.showLoading();
            
            $.ajax({
                url: waza_email_templates.ajax_url,
                type: 'POST',
                data: {
                    action: 'waza_save_email_template',
                    type: this.currentTemplate,
                    subject: subject,
                    content: content,
                    nonce: waza_email_templates.nonce
                },
                success: function(response) {
                    self.hideLoading();
                    
                    if (response.success) {
                        self.showNotice(waza_email_templates.strings.save_success, 'success');
                        self.disableSaveButton();
                        self.updateTemplateStatus(self.currentTemplate, 'customized');
                    } else {
                        self.showNotice(response.data || waza_email_templates.strings.save_error, 'error');
                    }
                },
                error: function() {
                    self.hideLoading();
                    self.showNotice(waza_email_templates.strings.save_error, 'error');
                }
            });
        },
        
        /**
         * Preview template
         */
        previewTemplate: function() {
            var self = this;
            
            var subject = $('#template-subject').val();
            var content = this.getEditorContent();
            
            if (!subject.trim() || !content.trim()) {
                this.showNotice('Please enter both subject and content to preview.', 'error');
                return;
            }
            
            $.ajax({
                url: waza_email_templates.ajax_url,
                type: 'POST',
                data: {
                    action: 'waza_preview_email_template',
                    type: this.currentTemplate,
                    subject: subject,
                    content: content,
                    nonce: waza_email_templates.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showPreviewModal(response.data);
                    } else {
                        self.showNotice('Error generating preview.', 'error');
                    }
                },
                error: function() {
                    self.showNotice('Error generating preview.', 'error');
                }
            });
        },
        
        /**
         * Reset template to default
         */
        resetTemplate: function() {
            var self = this;
            
            if (!this.currentTemplate) {
                return;
            }
            
            this.showLoading();
            
            $.ajax({
                url: waza_email_templates.ajax_url,
                type: 'POST',
                data: {
                    action: 'waza_reset_email_template',
                    type: this.currentTemplate,
                    nonce: waza_email_templates.nonce
                },
                success: function(response) {
                    self.hideLoading();
                    
                    if (response.success) {
                        self.populateEditor(response.data);
                        self.showNotice('Template reset to default.', 'success');
                        self.updateTemplateStatus(self.currentTemplate, 'default');
                        self.disableSaveButton();
                    } else {
                        self.showNotice('Error resetting template.', 'error');
                    }
                },
                error: function() {
                    self.hideLoading();
                    self.showNotice('Error resetting template.', 'error');
                }
            });
        },
        
        /**
         * Show test email modal
         */
        showTestEmailModal: function() {
            $('#test-email-modal').show();
            $('#test-email-address').focus();
        },
        
        /**
         * Send test email
         */
        sendTestEmail: function() {
            var self = this;
            var email = $('#test-email-address').val();
            var subject = $('#template-subject').val();
            var content = this.getEditorContent();
            
            if (!email || !this.isValidEmail(email)) {
                this.showNotice('Please enter a valid email address.', 'error');
                return;
            }
            
            if (!subject.trim() || !content.trim()) {
                this.showNotice('Please enter both subject and content.', 'error');
                return;
            }
            
            var $button = $('.send-test-email');
            $button.addClass('loading').prop('disabled', true);
            
            $.ajax({
                url: waza_email_templates.ajax_url,
                type: 'POST',
                data: {
                    action: 'waza_test_email_template',
                    email: email,
                    subject: subject,
                    content: content,
                    nonce: waza_email_templates.nonce
                },
                success: function(response) {
                    $button.removeClass('loading').prop('disabled', false);
                    
                    if (response.success) {
                        self.showNotice(waza_email_templates.strings.test_email_sent, 'success');
                        self.closeModal();
                    } else {
                        self.showNotice(response.data || waza_email_templates.strings.test_email_error, 'error');
                    }
                },
                error: function() {
                    $button.removeClass('loading').prop('disabled', false);
                    self.showNotice(waza_email_templates.strings.test_email_error, 'error');
                }
            });
        },
        
        /**
         * Insert variable into editor
         */
        insertVariable: function(variable, target) {
            if (target === 'subject') {
                var $subject = $('#template-subject');
                var cursorPos = $subject[0].selectionStart;
                var text = $subject.val();
                var newText = text.slice(0, cursorPos) + variable + text.slice(cursorPos);
                $subject.val(newText);
                $subject.focus();
            } else if (target === 'content') {
                if (this.editor && this.editor.initialized) {
                    this.editor.insertContent(variable);
                } else {
                    var $textarea = $('#template-content');
                    var cursorPos = $textarea[0].selectionStart;
                    var text = $textarea.val();
                    var newText = text.slice(0, cursorPos) + variable + text.slice(cursorPos);
                    $textarea.val(newText);
                    $textarea.focus();
                }
            }
            
            this.enableSaveButton();
        },
        
        /**
         * Get editor content
         */
        getEditorContent: function() {
            if (this.editor && this.editor.initialized) {
                return this.editor.getContent();
            } else {
                return $('#template-content').val();
            }
        },
        
        /**
         * Show preview modal
         */
        showPreviewModal: function(previewData) {
            $('.preview-subject').text(previewData.subject);
            $('.email-body').html(previewData.content);
            $('#email-preview-modal').show();
        },
        
        /**
         * Close modal
         */
        closeModal: function() {
            $('.email-modal').hide();
        },
        
        /**
         * Enable template actions
         */
        enableActions: function() {
            $('.template-actions .button').prop('disabled', false);
        },
        
        /**
         * Enable save button
         */
        enableSaveButton: function() {
            $('.save-template').prop('disabled', false);
        },
        
        /**
         * Disable save button
         */
        disableSaveButton: function() {
            $('.save-template').prop('disabled', true);
        },
        
        /**
         * Update template status indicator
         */
        updateTemplateStatus: function(templateType, status) {
            var $item = $('.template-item[data-template="' + templateType + '"]');
            var $status = $item.find('.template-status');
            
            $status.removeClass('customized default').addClass(status);
        },
        
        /**
         * Validate email address
         */
        isValidEmail: function(email) {
            var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        },
        
        /**
         * Show loading overlay
         */
        showLoading: function() {
            $('.loading-overlay').show();
        },
        
        /**
         * Hide loading overlay
         */
        hideLoading: function() {
            $('.loading-overlay').hide();
        },
        
        /**
         * Show notice message
         */
        showNotice: function(message, type) {
            type = type || 'success';
            
            var notice = '<div class="template-notice ' + type + '"><p>' + message + '</p></div>';
            
            $('.email-templates-header').after(notice);
            
            // Auto-remove after 5 seconds
            setTimeout(function() {
                $('.template-notice').fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Scroll to notice
            $('html, body').animate({
                scrollTop: $('.template-notice').offset().top - 100
            }, 300);
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.waza-email-templates-admin').length) {
            WazaEmailTemplates.init();
            
            // Load first template by default
            setTimeout(function() {
                $('.template-item:first').trigger('click');
            }, 500);
        }
    });
    
    // Handle page unload with unsaved changes
    $(window).on('beforeunload', function() {
        if (!$('.save-template').prop('disabled')) {
            return 'You have unsaved changes. Are you sure you want to leave?';
        }
    });
    
    // Export to global scope
    window.WazaEmailTemplates = WazaEmailTemplates;
    
})(jQuery);