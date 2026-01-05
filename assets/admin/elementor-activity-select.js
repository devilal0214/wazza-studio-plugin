/**
 * Waza Activity Select Control for Elementor
 */

(function($) {
    'use strict';
    
    /**
     * Activity Select Control
     */
    var ActivitySelectControl = elementor.modules.controls.BaseData.extend({
        
        onReady: function() {
            var self = this;
            var $select = this.$el.find('select');
            
            // Initialize the control
            this.initializeControl($select);
            
            // Handle value changes
            $select.on('change', function() {
                self.saveValue();
            });
        },
        
        initializeControl: function($select) {
            // Add search functionality if there are many activities
            if ($select.find('option').length > 10) {
                this.makeSearchable($select);
            }
            
            // Add activity preview
            this.addActivityPreview($select);
        },
        
        makeSearchable: function($select) {
            var self = this;
            
            // Create search input
            var $searchInput = $('<input type="text" class="activity-search" placeholder="Search activities...">');
            $select.before($searchInput);
            
            // Store original options
            var originalOptions = $select.find('option').clone();
            
            $searchInput.on('input', function() {
                var searchTerm = $(this).val().toLowerCase();
                
                $select.empty();
                
                originalOptions.each(function() {
                    var $option = $(this);
                    var text = $option.text().toLowerCase();
                    
                    if (text.includes(searchTerm) || $option.val() === '') {
                        $select.append($option.clone());
                    }
                });
            });
        },
        
        addActivityPreview: function($select) {
            var $preview = $('<div class="activity-preview"></div>');
            $select.after($preview);
            
            $select.on('change', function() {
                var activityId = $(this).val();
                
                if (activityId) {
                    // Load activity preview
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'waza_get_activity_preview',
                            activity_id: activityId,
                            nonce: waza_elementor_activity_select.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                $preview.html(response.data);
                            }
                        }
                    });
                } else {
                    $preview.empty();
                }
            });
        },
        
        saveValue: function() {
            var value = this.$el.find('select').val();
            this.setValue(value);
        },
        
        onBeforeDestroy: function() {
            this.$el.find('select').off('change');
            this.$el.find('.activity-search').off('input');
        }
    });
    
    // Register the control
    elementor.addControlView('waza_activity_select', ActivitySelectControl);
    
})(jQuery);

/**
 * Activity Preview Styles
 */
jQuery(document).ready(function($) {
    // Add custom styles for the activity select control
    if (!$('#waza-activity-select-styles').length) {
        var styles = `
            <style id="waza-activity-select-styles">
                .activity-search {
                    width: 100%;
                    padding: 8px 12px;
                    margin-bottom: 8px;
                    border: 1px solid #d5dadf;
                    border-radius: 3px;
                    font-size: 13px;
                    background: #fff;
                }
                
                .activity-search:focus {
                    outline: none;
                    border-color: #a4b1cd;
                    box-shadow: 0 0 0 1px #a4b1cd;
                }
                
                .activity-preview {
                    margin-top: 10px;
                    padding: 12px;
                    background: #f9f9f9;
                    border: 1px solid #e6e9ec;
                    border-radius: 3px;
                    font-size: 12px;
                    max-height: 200px;
                    overflow-y: auto;
                }
                
                .activity-preview.empty {
                    display: none;
                }
                
                .activity-preview-title {
                    font-weight: 600;
                    color: #495157;
                    margin-bottom: 5px;
                }
                
                .activity-preview-meta {
                    display: flex;
                    gap: 15px;
                    margin-bottom: 8px;
                    color: #6c757d;
                }
                
                .activity-preview-description {
                    color: #495157;
                    line-height: 1.4;
                }
                
                .activity-preview-description p {
                    margin: 0 0 8px 0;
                }
                
                .activity-preview-description p:last-child {
                    margin-bottom: 0;
                }
                
                .activity-preview-loading {
                    text-align: center;
                    color: #6c757d;
                    padding: 20px;
                }
                
                .activity-preview-loading::after {
                    content: '';
                    display: inline-block;
                    width: 16px;
                    height: 16px;
                    border: 2px solid #f3f3f3;
                    border-top: 2px solid #a4b1cd;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                    margin-left: 8px;
                    vertical-align: middle;
                }
                
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            </style>
        `;
        $('head').append(styles);
    }
});