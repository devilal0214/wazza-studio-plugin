/**
 * Waza Booking Admin Customization JavaScript
 */

(function ($) {
    'use strict';

    var WazaCustomization = {

        /**
         * Initialize the customization interface
         */
        init: function () {
            this.bindEvents();
            this.initColorPickers();
            this.initRangeSliders();
            this.loadSettings();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function () {
            var self = this;

            // Customization specific events (Tab logic moved to admin.js)

            // Theme preset selection
            $(document).on('click', '.theme-preset', function () {
                self.selectThemePreset($(this).data('preset'));
            });

            // Control changes
            $(document).on('change', '.control-input, .control-select, .control-textarea', function () {
                self.handleControlChange($(this));
            });

            // Color picker changes
            $(document).on('change', '.color-picker', function () {
                var colorValue = $(this).val();
                $(this).siblings('.color-value').val(colorValue);
                self.updatePreview();
            });

            // Manual color input
            $(document).on('input', '.color-value', function () {
                var colorValue = $(this).val();
                if (self.isValidColor(colorValue)) {
                    $(this).siblings('.color-picker').val(colorValue);
                    self.updatePreview();
                }
            });

            // Font selection change
            $(document).on('change', 'select[data-setting^="font"]', function () {
                self.updateFontPreview($(this));
            });

            // Range slider changes
            $(document).on('input', '.range-slider', function () {
                $(this).siblings('.range-value').text($(this).val() + $(this).data('unit'));
                self.updatePreview();
            });

            // Toggle switches
            $(document).on('change', '.toggle-switch input', function () {
                self.updatePreview();
            });

            // Save settings
            $(document).on('click', '.save-settings', function () {
                self.saveSettings();
            });

            // Preview changes
            $(document).on('click', '.preview-changes', function () {
                self.previewChanges();
            });

            // Reset settings
            $(document).on('click', '.reset-settings', function () {
                if (confirm(waza_customization.strings.confirm_reset)) {
                    self.resetSettings();
                }
            });

            // Import/Export
            $(document).on('click', '.export-settings', function () {
                self.exportSettings();
            });

            $(document).on('click', '.import-settings', function () {
                self.importSettings();
            });
        },

        /**
         * Initialize WordPress color pickers
         */
        initColorPickers: function () {
            $('.waza-color-picker').wpColorPicker({
                change: function (event, ui) {
                    var color = ui.color.toString();
                    $(event.target).val(color).trigger('change');
                    WazaCustomization.updatePreview();
                },
                clear: function (event) {
                    $(event.target).val('').trigger('change');
                    WazaCustomization.updatePreview();
                }
            });
        },

        /**
         * Initialize range sliders
         */
        initRangeSliders: function () {
            $('.range-slider').each(function () {
                var $slider = $(this);
                var value = $slider.val();
                var unit = $slider.data('unit') || '';
                $slider.siblings('.range-value').text(value + unit);
            });
        },

        /**
         * Switch between tabs
         */
        // switchTab moved to admin.js

        /**
         * Select a theme preset
         */
        selectThemePreset: function (preset) {
            var self = this;

            // Update UI
            $('.theme-preset').removeClass('active');
            $('.theme-preset[data-preset="' + preset + '"]').addClass('active');
            $('#calendar_theme').val(preset).trigger('change');

            // Apply preset values
            if (waza_customization.presets[preset]) {
                var presetData = waza_customization.presets[preset];

                $.each(presetData, function (key, value) {
                    var $control = $('[data-setting="' + key + '"]');

                    if ($control.hasClass('color-picker')) {
                        $control.iris('color', value);
                        $control.siblings('.color-value').val(value);
                    } else if ($control.is('select')) {
                        $control.val(value);
                    } else if ($control.hasClass('range-slider')) {
                        $control.val(value);
                        var unit = $control.data('unit') || '';
                        $control.siblings('.range-value').text(value + unit);
                    } else if ($control.is('input[type="checkbox"]')) {
                        $control.prop('checked', value);
                    } else {
                        $control.val(value);
                    }
                });

                self.updatePreview();
            }
        },

        /**
         * Handle control changes
         */
        handleControlChange: function ($control) {
            var setting = $control.data('setting');
            var value = $control.val();

            // Store the change
            if (!this.pendingChanges) {
                this.pendingChanges = {};
            }
            this.pendingChanges[setting] = value;

            // Update preview
            this.updatePreview();
        },

        /**
         * Update font preview
         */
        updateFontPreview: function ($select) {
            var font = $select.val();
            var $preview = $select.siblings('.font-preview');

            if ($preview.length) {
                $preview.css('font-family', font);

                // Load Google Font if needed
                if (waza_customization.google_fonts.includes(font)) {
                    this.loadGoogleFont(font);
                }
            }
        },

        /**
         * Load Google Font
         */
        loadGoogleFont: function (font) {
            var fontUrl = 'https://fonts.googleapis.com/css2?family=' +
                font.replace(/\s+/g, '+') + ':wght@300;400;500;600;700&display=swap';

            if (!$('link[href="' + fontUrl + '"]').length) {
                $('<link>').attr({
                    rel: 'stylesheet',
                    href: fontUrl
                }).appendTo('head');
            }
        },

        /**
         * Update live preview
         */
        updatePreview: function () {
            var self = this;
            var settings = this.getAllSettings();

            // Generate CSS
            var customCSS = this.generateCustomCSS(settings);

            // Update preview frame
            var $preview = $('#live-preview');
            if ($preview.length) {
                // Remove existing custom styles
                $preview.find('#waza-custom-styles').remove();

                // Add new custom styles
                $preview.append('<style id="waza-custom-styles">' + customCSS + '</style>');
            }

            // Update frontend preview if available
            if (window.opener && window.opener.WazaBookingFrontend) {
                window.opener.WazaBookingFrontend.updateCustomStyles(customCSS);
            }
        },

        /**
         * Get all current settings
         */
        getAllSettings: function () {
            var settings = {};
            // According to user request: only submit data from currently active tab
            var $activeTab = $('.waza-tab-content.active');

            // If no active tab (shouldn't happen), fallback to first one or search all as last resort
            if (!$activeTab.length) {
                $activeTab = $('.waza-tab-content').first();
            }

            // Collect all inputs with data-setting within the active tab
            $activeTab.find('[data-setting]').each(function () {
                var $control = $(this);
                var setting = $control.data('setting');

                if (setting) {
                    if ($control.is('input[type="checkbox"]')) {
                        settings[setting] = $control.is(':checked') ? 1 : 0;
                    } else {
                        settings[setting] = $control.val();
                    }
                }
            });

            return settings;
        },

        /**
         * Generate custom CSS from settings
         */
        generateCustomCSS: function (settings) {
            var css = '';

            // Calendar styles
            if (settings.calendar_primary_color) {
                css += '.waza-calendar .calendar-header { background-color: ' + settings.calendar_primary_color + '; }';
            }

            if (settings.calendar_secondary_color) {
                css += '.waza-calendar .calendar-day.available { background-color: ' + settings.calendar_secondary_color + '; }';
            }

            if (settings.calendar_accent_color) {
                css += '.waza-calendar .calendar-day.today { background-color: ' + settings.calendar_accent_color + '; }';
            }

            if (settings.calendar_text_color) {
                css += '.waza-calendar { color: ' + settings.calendar_text_color + '; }';
            }

            if (settings.calendar_background) {
                css += '.waza-calendar { background-color: ' + settings.calendar_background + '; }';
            }

            // Typography
            if (settings.primary_font && settings.primary_font !== 'inherit') {
                css += '.waza-booking-container { font-family: "' + settings.primary_font + '", sans-serif; }';
            }

            if (settings.heading_font && settings.heading_font !== 'inherit') {
                css += '.waza-booking-container h1, .waza-booking-container h2, .waza-booking-container h3 { font-family: "' + settings.heading_font + '", sans-serif; }';
            }

            if (settings.font_size) {
                css += '.waza-booking-container { font-size: ' + settings.font_size + 'px; }';
            }

            // Layout
            if (settings.border_radius) {
                css += '.waza-booking-container .card, .waza-booking-container .btn { border-radius: ' + settings.border_radius + 'px; }';
            }

            if (settings.spacing) {
                css += '.waza-booking-container .grid { gap: ' + settings.spacing + 'px; }';
            }

            // Custom CSS
            if (settings.custom_css) {
                css += settings.custom_css;
            }

            return css;
        },

        /**
         * Save settings via AJAX
         */
        saveSettings: function () {
            var self = this;
            var settings = this.getAllSettings();

            this.showLoading();

            $.ajax({
                url: waza_customization.ajax_url,
                type: 'POST',
                data: {
                    action: 'waza_save_customization',
                    nonce: waza_customization.nonce,
                    settings: JSON.stringify(settings)
                },
                success: function (response) {
                    self.hideLoading();

                    if (response.success) {
                        self.showNotice('Settings saved successfully!', 'success');
                        self.pendingChanges = {};
                    } else {
                        self.showNotice('Failed to save settings: ' + response.data, 'error');
                    }
                },
                error: function () {
                    self.hideLoading();
                    self.showNotice('An error occurred while saving settings.', 'error');
                }
            });
        },

        /**
         * Load settings from server
         */
        loadSettings: function () {
            var self = this;

            $.ajax({
                url: waza_customization.ajax_url,
                type: 'POST',
                data: {
                    action: 'waza_load_customization',
                    nonce: waza_customization.nonce
                },
                success: function (response) {
                    if (response.success && response.data) {
                        self.applySettings(response.data);
                    }
                }
            });
        },

        /**
         * Apply settings to controls
         */
        applySettings: function (settings) {
            var self = this;

            $.each(settings, function (key, value) {
                var $control = $('[data-setting="' + key + '"]');

                if ($control.hasClass('waza-color-picker') && value) {
                    $control.iris('color', value);
                } else if ($control.is('select')) {
                    $control.val(value);
                } else if ($control.hasClass('range-slider')) {
                    $control.val(value);
                    var unit = $control.data('unit') || '';
                    $control.siblings('.range-value').text(value + unit);
                } else if ($control.is('input[type="checkbox"]')) {
                    $control.prop('checked', value);
                } else if ($control.length) {
                    $control.val(value);
                }
            });

            this.updatePreview();
        },

        /**
         * Reset settings to defaults
         */
        resetSettings: function () {
            var self = this;

            this.showLoading();

            $.ajax({
                url: waza_customization.ajax_url,
                type: 'POST',
                data: {
                    action: 'waza_reset_customization',
                    nonce: waza_customization.nonce
                },
                success: function (response) {
                    self.hideLoading();

                    if (response.success) {
                        self.showNotice('Settings reset to defaults!', 'success');
                        location.reload();
                    } else {
                        self.showNotice('Failed to reset settings.', 'error');
                    }
                },
                error: function () {
                    self.hideLoading();
                    self.showNotice('An error occurred while resetting settings.', 'error');
                }
            });
        },

        /**
         * Export settings
         */
        exportSettings: function () {
            var settings = this.getAllSettings();
            var dataStr = JSON.stringify(settings, null, 2);
            var dataUri = 'data:application/json;charset=utf-8,' + encodeURIComponent(dataStr);

            var exportFileDefaultName = 'waza-booking-settings.json';

            var linkElement = document.createElement('a');
            linkElement.setAttribute('href', dataUri);
            linkElement.setAttribute('download', exportFileDefaultName);
            linkElement.click();
        },

        /**
         * Import settings
         */
        importSettings: function () {
            var self = this;
            var input = document.createElement('input');
            input.type = 'file';
            input.accept = '.json';

            input.onchange = function (e) {
                var file = e.target.files[0];
                if (!file) return;

                var reader = new FileReader();
                reader.onload = function (event) {
                    try {
                        var settings = JSON.parse(event.target.result);
                        self.applySettings(settings);
                        self.showNotice('Settings imported successfully!', 'success');
                    } catch (error) {
                        self.showNotice('Invalid settings file.', 'error');
                    }
                };
                reader.readAsText(file);
            };

            input.click();
        },

        /**
         * Validate color value
         */
        isValidColor: function (color) {
            return /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(color);
        },

        /**
         * Show loading overlay
         */
        showLoading: function () {
            if (!$('#waza-loading-overlay').length) {
                $('body').append('<div id="waza-loading-overlay" class="loading-overlay"><div class="loading-spinner"></div></div>');
            }
        },

        /**
         * Hide loading overlay
         */
        hideLoading: function () {
            $('#waza-loading-overlay').remove();
        },

        /**
         * Show notice message
         */
        showNotice: function (message, type) {
            type = type || 'success';

            var notice = '<div class="notice ' + type + '">' + message + '</div>';
            $('.customization-header').after(notice);

            setTimeout(function () {
                $('.notice').fadeOut(function () {
                    $(this).remove();
                });
            }, 4000);
        }
    };

    // Initialize when document is ready
    $(document).ready(function () {
        if ($('.waza-customization-admin').length) {
            WazaCustomization.init();
        }
    });

    // Export to global scope
    window.WazaCustomization = WazaCustomization;

})(jQuery);