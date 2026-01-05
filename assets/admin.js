console.log('Waza Booking: admin.js is loading...');
jQuery(document).ready(function ($) {
    console.log('Waza Booking: admin.js ready');
    
    // Fix Database Button Handler
    $('#waza-fix-database-btn').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var $message = $('#waza-db-fix-message');
        
        if (!confirm('This will check and fix missing database columns. Continue?')) {
            return;
        }
        
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spinning"></span> Fixing...');
        $message.removeClass('notice notice-success notice-error').hide();
        
        $.ajax({
            url: wazaAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_fix_database',
                nonce: wazaAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    var detailsList = response.data.details ? '<ul><li>' + response.data.details.join('</li><li>') + '</li></ul>' : '';
                    $message.addClass('notice notice-success')
                           .html('<p><strong>' + response.data.message + '</strong></p>' + detailsList)
                           .slideDown();
                } else {
                    $message.addClass('notice notice-error')
                           .html('<p><strong>Error:</strong> ' + (response.data.message || 'Unknown error') + '</p>')
                           .slideDown();
                }
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-admin-tools"></span> Fix Database Issues');
            },
            error: function() {
                $message.addClass('notice notice-error')
                       .html('<p><strong>Error:</strong> Network request failed</p>')
                       .slideDown();
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-admin-tools"></span> Fix Database Issues');
            }
        });
    });
    
    // Add spinning animation for dashicons
    if (!$('style:contains(".dashicons.spinning")').length) {
        $('<style>.dashicons.spinning { animation: spin 1s linear infinite; } @keyframes spin { 100% { transform: rotate(360deg); } }</style>').appendTo('head');
    }
    
    // Unified Tab Switching Functionality
    function switchTab(containerSelector, tabId) {
        console.log('Waza Booking: Switching to tab', tabId, 'in', containerSelector);

        var $container = $(containerSelector);
        if (!$container.length) {
            $container = $('.waza-settings-container, .waza-customization-admin, .wrap').first();
        }

        // Update active tab link
        $container.find('.nav-tab').removeClass('nav-tab-active active');
        $container.find('.nav-tab[data-tab="' + tabId + '"], .nav-tab[href$="#' + tabId + '"]').addClass('nav-tab-active active');

        // Show/hide tab content - use classes instead of .show()/.hide() to preserve form data
        $container.find('.waza-tab-content, .tab-panel').removeClass('active waza-tab-active');

        // Find the specific panel
        var $targetContent = $container.find('#' + tabId);
        if (!$targetContent.length) $targetContent = $container.find('#' + tabId + '-panel');
        if (!$targetContent.length) $targetContent = $container.find('[data-tab-content="' + tabId + '"]');

        if ($targetContent.length) {
            console.log('Waza Booking: Found target content', $targetContent.attr('id'));
            $targetContent.addClass('active waza-tab-active');
        } else {
            console.warn('Waza Booking: Could not find content for tab', tabId);
        }

        // Update URL hash without reload
        if (history.pushState) {
            var newUrl = window.location.pathname + window.location.search + '#' + tabId;
            history.pushState(null, null, newUrl);
        } else {
            location.hash = tabId;
        }

        $(window).trigger('resize');
    }

    $(document).on('click', '.nav-tab', function (e) {
        var $this = $(this);
        var href = $this.attr('href') || '';
        var targetTab = $this.data('tab');

        if (targetTab || href.indexOf('#') !== -1) {
            e.preventDefault();

            if (!targetTab && href.indexOf('#') !== -1) {
                targetTab = href.split('#')[1];
            }

            if (targetTab) {
                var $parent = $this.closest('.waza-settings-container, .waza-customization-admin, .wrap');
                var containerSelector = $parent.attr('id') ? '#' + $parent.attr('id') : '';

                switchTab(containerSelector, targetTab);
                e.stopImmediatePropagation();
            }
        }
    });

    // Handle initial tab from URL hash
    var initialHash = window.location.hash.substring(1);
    if (initialHash) {
        $('.nav-tab[href="#' + initialHash + '"], .nav-tab[data-tab="' + initialHash + '"]').first().trigger('click');
    }

    // REMOVED: Do NOT disable hidden tab inputs for settings forms
    // Settings forms need ALL tabs' data to be submitted, not just the active tab
    // The form uses WordPress Settings API which expects all fields to be present

    // Form submission with loading states
    $('.waza-form').on('submit', function () {
        var submitButton = $(this).find('button[type="submit"], input[type="submit"]');
        var originalText = submitButton.val() || submitButton.text();

        submitButton.prop('disabled', true);
        if (submitButton.is('button')) {
            submitButton.text('Saving...');
        } else {
            submitButton.val('Saving...');
        }

        // Re-enable after 5 seconds as fallback
        setTimeout(function () {
            submitButton.prop('disabled', false);
            if (submitButton.is('button')) {
                submitButton.text(originalText);
            } else {
                submitButton.val(originalText);
            }
        }, 5000);
    });

    // Payment gateway test functionality
    $('.waza-gateway-test-button').on('click', function (e) {
        e.preventDefault();

        var button = $(this);
        var gateway = button.data('gateway');
        var resultContainer = button.siblings('.waza-gateway-test-result');

        if (!resultContainer.length) {
            resultContainer = $('<div class="waza-gateway-test-result"></div>');
            button.after(resultContainer);
        }

        button.prop('disabled', true).text('Testing...');
        resultContainer.html('').hide();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'waza_test_payment_gateway',
                gateway: gateway,
                nonce: wazaAdmin.nonce
            },
            success: function (response) {
                if (response.success) {
                    resultContainer.removeClass('error').addClass('success')
                        .html('<strong>Success:</strong> ' + response.data.message)
                        .slideDown();
                } else {
                    resultContainer.removeClass('success').addClass('error')
                        .html('<strong>Error:</strong> ' + (response.data || 'Test failed'))
                        .slideDown();
                }
            },
            error: function () {
                resultContainer.removeClass('success').addClass('error')
                    .html('<strong>Error:</strong> Connection failed')
                    .slideDown();
            },
            complete: function () {
                button.prop('disabled', false).text('Test Connection');
            }
        });
    });

    // Color picker enhancement
    if ($.fn.wpColorPicker) {
        $('.waza-color-picker').wpColorPicker({
            change: function (event, ui) {
                $(this).trigger('change');
            },
            clear: function (event) {
                $(this).trigger('change');
            }
        });
    }

    // Tooltip functionality
    $('.waza-tooltip').on('mouseenter', function () {
        var tooltip = $(this).attr('title') || $(this).data('tooltip');
        if (tooltip) {
            var tooltipElement = $('<div class="waza-tooltip-content">' + tooltip + '</div>');
            $('body').append(tooltipElement);

            var offset = $(this).offset();
            tooltipElement.css({
                position: 'absolute',
                top: offset.top - tooltipElement.outerHeight() - 5,
                left: offset.left + ($(this).outerWidth() / 2) - (tooltipElement.outerWidth() / 2),
                zIndex: 9999,
                background: '#333',
                color: '#fff',
                padding: '5px 8px',
                borderRadius: '3px',
                fontSize: '12px',
                whiteSpace: 'nowrap'
            }).fadeIn(200);
        }
    }).on('mouseleave', function () {
        $('.waza-tooltip-content').fadeOut(200, function () {
            $(this).remove();
        });
    });

    // Confirm dialogs for destructive actions
    $('.waza-confirm').on('click', function (e) {
        var confirmMessage = $(this).data('confirm') || 'Are you sure?';
        if (!confirm(confirmMessage)) {
            e.preventDefault();
            return false;
        }
    });

    // Auto-save functionality for settings
    var autoSaveTimer;
    $('.waza-autosave').on('input change', function () {
        clearTimeout(autoSaveTimer);
        var form = $(this).closest('form');

        autoSaveTimer = setTimeout(function () {
            if (form.length) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: form.serialize() + '&action=waza_autosave_settings&nonce=' + (wazaAdmin.nonce || ''),
                    success: function (response) {
                        if (response.success) {
                            showNotice('Settings saved automatically', 'success', 2000);
                        }
                    }
                });
            }
        }, 2000);
    });

    // Notice system
    function showNotice(message, type, duration) {
        type = type || 'info';
        duration = duration || 5000;

        var noticeClass = 'notice-' + type;
        var notice = $('<div class="notice ' + noticeClass + ' is-dismissible waza-auto-notice">' +
            '<p>' + message + '</p>' +
            '<button type="button" class="notice-dismiss">' +
            '<span class="screen-reader-text">Dismiss this notice.</span>' +
            '</button>' +
            '</div>');

        $('.wrap > h1').after(notice);

        // Auto-dismiss
        setTimeout(function () {
            notice.fadeOut(function () {
                $(this).remove();
            });
        }, duration);

        // Manual dismiss
        notice.find('.notice-dismiss').on('click', function () {
            notice.fadeOut(function () {
                $(this).remove();
            });
        });
    }

    // Dashboard widget interactions
    $('.waza-dashboard-widget').on('click', function () {
        var link = $(this).data('link');
        if (link) {
            window.location.href = link;
        }
    });

    // Responsive table enhancement
    $('.waza-table-responsive table').each(function () {
        var table = $(this);
        var wrapper = table.closest('.waza-table-responsive');

        if (table.outerWidth() > wrapper.width()) {
            wrapper.addClass('scrollable');
        }

        $(window).on('resize', function () {
            if (table.outerWidth() > wrapper.width()) {
                wrapper.addClass('scrollable');
            } else {
                wrapper.removeClass('scrollable');
            }
        });
    });

    // Form validation enhancement
    $('.waza-form input[required], .waza-form select[required], .waza-form textarea[required]').each(function () {
        $(this).on('blur', function () {
            if (!this.value.trim()) {
                $(this).addClass('error');
            } else {
                $(this).removeClass('error');
            }
        });
    });

    // Bulk actions
    $('.waza-bulk-action').on('click', function (e) {
        var checkedItems = $('.waza-bulk-checkbox:checked');
        if (checkedItems.length === 0) {
            e.preventDefault();
            alert('Please select items to perform this action.');
            return false;
        }

        var actionName = $(this).text() || $(this).val();
        if (!confirm('Are you sure you want to ' + actionName.toLowerCase() + ' ' + checkedItems.length + ' item(s)?')) {
            e.preventDefault();
            return false;
        }
    });

    // Select all checkbox functionality
    $('.waza-select-all').on('change', function () {
        $('.waza-bulk-checkbox').prop('checked', this.checked);
    });

    $('.waza-bulk-checkbox').on('change', function () {
        var totalCheckboxes = $('.waza-bulk-checkbox').length;
        var checkedCheckboxes = $('.waza-bulk-checkbox:checked').length;

        $('.waza-select-all').prop('checked', totalCheckboxes === checkedCheckboxes);
        $('.waza-select-all').prop('indeterminate', checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);
    });
});