/**
 * Rental Settings Admin JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Tab Switching
        $('.waza-tab-btn').on('click', function() {
            var tabId = $(this).data('tab');
            
            $('.waza-tab-btn').removeClass('active');
            $(this).addClass('active');
            
            $('.waza-tab-content').removeClass('active');
            $('#tab-' + tabId).addClass('active');
        });
        
        // Add New Rental Type
        $('#add-rental-type').on('click', function() {
            var timestamp = Date.now();
            var newKey = 'custom_' + timestamp;
            
            var template = `
                <div class="rental-type-item" data-type-key="${newKey}">
                    <div class="rental-type-header">
                        <h3>
                            <span class="type-icon">🏢</span>
                            <span class="type-label">New Rental Type</span>
                        </h3>
                        <label class="toggle-switch">
                            <input type="checkbox" 
                                   name="waza_rental_settings[rental_types][${newKey}][enabled]" 
                                   value="1" checked>
                            <span class="toggle-slider"></span>
                            Enabled
                        </label>
                    </div>
                    
                    <table class="form-table">
                        <tr>
                            <th><label>Label</label></th>
                            <td>
                                <input type="text" 
                                       name="waza_rental_settings[rental_types][${newKey}][label]" 
                                       value="New Rental Type" 
                                       class="regular-text" required>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Icon/Emoji</label></th>
                            <td>
                                <input type="text" 
                                       name="waza_rental_settings[rental_types][${newKey}][icon]" 
                                       value="🏢" 
                                       class="small-text">
                                <p class="description">Single emoji or icon character</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Includes</label></th>
                            <td>
                                <textarea name="waza_rental_settings[rental_types][${newKey}][includes]" 
                                          rows="3" 
                                          class="large-text"></textarea>
                                <p class="description">Comma-separated list of included items</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Excludes</label></th>
                            <td>
                                <textarea name="waza_rental_settings[rental_types][${newKey}][excludes]" 
                                          rows="3" 
                                          class="large-text"></textarea>
                                <p class="description">Comma-separated list of excluded items</p>
                            </td>
                        </tr>
                    </table>
                    
                    <button type="button" class="button delete-rental-type" data-type-key="${newKey}">
                        Delete Type
                    </button>
                </div>
            `;
            
            $('#rental-types-container').append(template);
            
            // Scroll to new item
            $('html, body').animate({
                scrollTop: $('[data-type-key="' + newKey + '"]').offset().top - 100
            }, 500);
        });
        
        // Delete Rental Type
        $(document).on('click', '.delete-rental-type', function() {
            if (confirm(wazaRentalSettings.strings.confirm_delete)) {
                $(this).closest('.rental-type-item').fadeOut(300, function() {
                    $(this).remove();
                });
            }
        });
        
        // Add New Duration
        $('#add-duration').on('click', function() {
            var timestamp = Date.now();
            var newKey = 'custom_' + timestamp;
            
            var template = `
                <div class="duration-item" data-duration-key="${newKey}">
                    <div class="duration-header">
                        <h3>New Duration</h3>
                        <label class="toggle-switch">
                            <input type="checkbox" 
                                   name="waza_rental_settings[durations][${newKey}][enabled]" 
                                   value="1" checked>
                            <span class="toggle-slider"></span>
                            Enabled
                        </label>
                    </div>
                    
                    <table class="form-table">
                        <tr>
                            <th><label>Label</label></th>
                            <td>
                                <input type="text" 
                                       name="waza_rental_settings[durations][${newKey}][label]" 
                                       value="New Duration" 
                                       class="regular-text" required>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Hours</label></th>
                            <td>
                                <input type="number" 
                                       name="waza_rental_settings[durations][${newKey}][hours]" 
                                       value="1" 
                                       min="0.5" 
                                       step="0.5" 
                                       class="small-text" required>
                                <p class="description">Number of hours for this duration</p>
                            </td>
                        </tr>
                    </table>
                    
                    <button type="button" class="button delete-duration" data-duration-key="${newKey}">
                        Delete Duration
                    </button>
                </div>
            `;
            
            $('#durations-container').append(template);
            
            // Scroll to new item
            $('html, body').animate({
                scrollTop: $('[data-duration-key="' + newKey + '"]').offset().top - 100
            }, 500);
        });
        
        // Delete Duration
        $(document).on('click', '.delete-duration', function() {
            if (confirm(wazaRentalSettings.strings.confirm_delete)) {
                $(this).closest('.duration-item').fadeOut(300, function() {
                    $(this).remove();
                });
            }
        });
        
        // Update type label when icon or label changes
        $(document).on('input', '.rental-type-item input[name*="[label]"], .rental-type-item input[name*="[icon]"]', function() {
            var $item = $(this).closest('.rental-type-item');
            var label = $item.find('input[name*="[label]"]').val();
            var icon = $item.find('input[name*="[icon]"]').val();
            
            $item.find('.type-icon').text(icon);
            $item.find('.type-label').text(label);
        });
        
        // Update duration label when changed
        $(document).on('input', '.duration-item input[name*="[label]"]', function() {
            var $item = $(this).closest('.duration-item');
            var label = $(this).val();
            
            $item.find('.duration-header h3').text(label);
        });
        
        // Form validation
        $('#waza-rental-settings-form').on('submit', function(e) {
            var valid = true;
            
            // Check if at least one rental type is enabled
            var hasEnabledType = false;
            $('.rental-type-item input[name*="[enabled]"]:checked').each(function() {
                hasEnabledType = true;
            });
            
            if (!hasEnabledType) {
                alert('Please enable at least one rental type.');
                valid = false;
            }
            
            // Check if at least one duration is enabled
            var hasEnabledDuration = false;
            $('.duration-item input[name*="[enabled]"]:checked').each(function() {
                hasEnabledDuration = true;
            });
            
            if (!hasEnabledDuration) {
                alert('Please enable at least one duration option.');
                valid = false;
            }
            
            if (!valid) {
                e.preventDefault();
            }
        });
        
    });
    
})(jQuery);
