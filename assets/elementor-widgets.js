/**
 * Waza Booking Elementor Widgets JavaScript
 */

(function($) {
    'use strict';
    
    var WazaElementorWidgets = {
        
        /**
         * Initialize Elementor widgets
         */
        init: function() {
            this.bindEvents();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Initialize widgets when Elementor loads
            $(window).on('elementor/frontend/init', function() {
                elementorFrontend.hooks.addAction('frontend/element_ready/waza-activities-grid.default', WazaElementorWidgets.initActivitiesGrid);
                elementorFrontend.hooks.addAction('frontend/element_ready/waza-booking-calendar.default', WazaElementorWidgets.initBookingCalendar);
                elementorFrontend.hooks.addAction('frontend/element_ready/waza-booking-form.default', WazaElementorWidgets.initBookingForm);
                elementorFrontend.hooks.addAction('frontend/element_ready/waza-user-dashboard.default', WazaElementorWidgets.initUserDashboard);
                elementorFrontend.hooks.addAction('frontend/element_ready/waza-instructors-list.default', WazaElementorWidgets.initInstructorsList);
            });
        },
        
        /**
         * Initialize Activities Grid widget
         */
        initActivitiesGrid: function($scope) {
            var $widget = $scope.find('.elementor-widget-waza-activities-grid');
            
            if ($widget.length) {
                // Initialize masonry layout if needed
                WazaElementorWidgets.initMasonryLayout($widget.find('.activity-grid'));
                
                // Handle AJAX loading
                WazaElementorWidgets.handleAjaxPagination($widget);
                
                // Initialize lazy loading for images
                WazaElementorWidgets.initLazyLoading($widget);
            }
        },
        
        /**
         * Initialize Booking Calendar widget
         */
        initBookingCalendar: function($scope) {
            var $widget = $scope.find('.elementor-widget-waza-booking-calendar');
            
            if ($widget.length) {
                var $calendar = $widget.find('.waza-calendar');
                
                // Initialize calendar functionality
                if (typeof WazaBookingCalendar !== 'undefined') {
                    new WazaBookingCalendar($calendar);
                }
                
                // Handle responsive calendar
                WazaElementorWidgets.makeCalendarResponsive($calendar);
            }
        },
        
        /**
         * Initialize Booking Form widget
         */
        initBookingForm: function($scope) {
            var $widget = $scope.find('.elementor-widget-waza-booking-form');
            
            if ($widget.length) {
                var $form = $widget.find('.waza-booking-form');
                
                // Initialize form functionality
                if (typeof WazaBookingForm !== 'undefined') {
                    new WazaBookingForm($form);
                }
                
                // Handle form validation
                WazaElementorWidgets.initFormValidation($form);
                
                // Handle conditional fields
                WazaElementorWidgets.initConditionalFields($form);
            }
        },
        
        /**
         * Initialize User Dashboard widget
         */
        initUserDashboard: function($scope) {
            var $widget = $scope.find('.elementor-widget-waza-user-dashboard');
            
            if ($widget.length) {
                // Initialize tab functionality
                WazaElementorWidgets.initDashboardTabs($widget);
                
                // Initialize AJAX content loading
                WazaElementorWidgets.initDashboardAjax($widget);
                
                // Initialize pagination
                WazaElementorWidgets.initDashboardPagination($widget);
            }
        },
        
        /**
         * Initialize Instructors List widget
         */
        initInstructorsList: function($scope) {
            var $widget = $scope.find('.elementor-widget-waza-instructors-list');
            
            if ($widget.length) {
                // Initialize masonry layout
                WazaElementorWidgets.initMasonryLayout($widget.find('.instructors-grid'));
                
                // Initialize filtering
                WazaElementorWidgets.initInstructorFiltering($widget);
                
                // Initialize modal popups
                WazaElementorWidgets.initInstructorModals($widget);
            }
        },
        
        /**
         * Initialize masonry layout
         */
        initMasonryLayout: function($grid) {
            if ($grid.length && typeof Masonry !== 'undefined') {
                var masonry = new Masonry($grid[0], {
                    itemSelector: '.grid-item',
                    columnWidth: '.grid-sizer',
                    percentPosition: true,
                    gutter: 20
                });
                
                // Re-layout after images load
                $grid.imagesLoaded(function() {
                    masonry.layout();
                });
            }
        },
        
        /**
         * Handle AJAX pagination
         */
        handleAjaxPagination: function($widget) {
            $widget.on('click', '.pagination a', function(e) {
                e.preventDefault();
                
                var $link = $(this);
                var page = $link.data('page');
                var $container = $link.closest('.elementor-widget-waza-activities-grid');
                
                WazaElementorWidgets.loadActivitiesPage($container, page);
            });
        },
        
        /**
         * Load activities page via AJAX
         */
        loadActivitiesPage: function($container, page) {
            var $grid = $container.find('.activity-grid');
            var settings = $container.data('settings') || {};
            
            $grid.addClass('loading');
            
            $.ajax({
                url: waza_elementor.ajax_url,
                type: 'POST',
                data: {
                    action: 'waza_load_activities_page',
                    nonce: waza_elementor.nonce,
                    page: page,
                    settings: JSON.stringify(settings)
                },
                success: function(response) {
                    if (response.success) {
                        $grid.html(response.data.html);
                        $container.find('.pagination').replaceWith(response.data.pagination);
                        
                        // Re-initialize masonry
                        WazaElementorWidgets.initMasonryLayout($grid);
                    }
                    $grid.removeClass('loading');
                },
                error: function() {
                    $grid.removeClass('loading');
                }
            });
        },
        
        /**
         * Initialize lazy loading for images
         */
        initLazyLoading: function($widget) {
            if ('IntersectionObserver' in window) {
                var imageObserver = new IntersectionObserver(function(entries, observer) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            var img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.remove('lazy');
                            imageObserver.unobserve(img);
                        }
                    });
                });
                
                $widget.find('img[data-src]').each(function() {
                    imageObserver.observe(this);
                });
            }
        },
        
        /**
         * Make calendar responsive
         */
        makeCalendarResponsive: function($calendar) {
            function adjustCalendar() {
                var containerWidth = $calendar.width();
                var dayWidth = containerWidth / 7;
                
                $calendar.find('.calendar-day').css('height', dayWidth + 'px');
                
                if (containerWidth < 500) {
                    $calendar.addClass('mobile-view');
                } else {
                    $calendar.removeClass('mobile-view');
                }
            }
            
            adjustCalendar();
            $(window).on('resize', adjustCalendar);
        },
        
        /**
         * Initialize form validation
         */
        initFormValidation: function($form) {
            $form.on('submit', function(e) {
                var isValid = true;
                
                $form.find('[required]').each(function() {
                    var $field = $(this);
                    var value = $field.val().trim();
                    
                    if (!value) {
                        $field.addClass('error');
                        isValid = false;
                    } else {
                        $field.removeClass('error');
                    }
                    
                    // Email validation
                    if ($field.attr('type') === 'email' && value) {
                        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailRegex.test(value)) {
                            $field.addClass('error');
                            isValid = false;
                        }
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    WazaElementorWidgets.showFormMessage($form, 'Please fill in all required fields correctly.', 'error');
                }
            });
            
            // Clear errors on input
            $form.find('input, select, textarea').on('input change', function() {
                $(this).removeClass('error');
            });
        },
        
        /**
         * Initialize conditional fields
         */
        initConditionalFields: function($form) {
            $form.find('[data-condition]').each(function() {
                var $field = $(this);
                var condition = $field.data('condition');
                
                if (condition.field && condition.value) {
                    var $trigger = $form.find('[name="' + condition.field + '"]');
                    
                    function toggleField() {
                        var triggerValue = $trigger.val();
                        if (triggerValue === condition.value) {
                            $field.show();
                        } else {
                            $field.hide();
                        }
                    }
                    
                    toggleField();
                    $trigger.on('change', toggleField);
                }
            });
        },
        
        /**
         * Initialize dashboard tabs
         */
        initDashboardTabs: function($widget) {
            $widget.on('click', '.nav-tab', function(e) {
                e.preventDefault();
                
                var $tab = $(this);
                var tabId = $tab.data('tab');
                var $dashboard = $tab.closest('.waza-user-dashboard');
                
                // Update tab navigation
                $dashboard.find('.nav-tab').removeClass('active');
                $tab.addClass('active');
                
                // Update tab content
                $dashboard.find('.tab-content').removeClass('active');
                $dashboard.find('#' + tabId + '-content').addClass('active');
                
                // Load content if needed
                WazaElementorWidgets.loadDashboardTab($dashboard, tabId);
            });
        },
        
        /**
         * Initialize dashboard AJAX loading
         */
        initDashboardAjax: function($widget) {
            // Implement AJAX loading for dashboard content
        },
        
        /**
         * Initialize dashboard pagination
         */
        initDashboardPagination: function($widget) {
            $widget.on('click', '.dashboard-pagination a', function(e) {
                e.preventDefault();
                
                var $link = $(this);
                var page = $link.data('page');
                var tab = $link.closest('.tab-content').attr('id').replace('-content', '');
                var $dashboard = $link.closest('.waza-user-dashboard');
                
                WazaElementorWidgets.loadDashboardPage($dashboard, tab, page);
            });
        },
        
        /**
         * Load dashboard tab content
         */
        loadDashboardTab: function($dashboard, tabId) {
            var $content = $dashboard.find('#' + tabId + '-content');
            
            if ($content.hasClass('loaded')) {
                return;
            }
            
            $content.addClass('loading');
            
            $.ajax({
                url: waza_elementor.ajax_url,
                type: 'POST',
                data: {
                    action: 'waza_load_dashboard_tab',
                    nonce: waza_elementor.nonce,
                    tab: tabId
                },
                success: function(response) {
                    if (response.success) {
                        $content.html(response.data);
                        $content.addClass('loaded');
                    }
                    $content.removeClass('loading');
                },
                error: function() {
                    $content.removeClass('loading');
                }
            });
        },
        
        /**
         * Load dashboard page
         */
        loadDashboardPage: function($dashboard, tab, page) {
            var $content = $dashboard.find('#' + tab + '-content');
            
            $content.addClass('loading');
            
            $.ajax({
                url: waza_elementor.ajax_url,
                type: 'POST',
                data: {
                    action: 'waza_load_dashboard_page',
                    nonce: waza_elementor.nonce,
                    tab: tab,
                    page: page
                },
                success: function(response) {
                    if (response.success) {
                        $content.html(response.data);
                    }
                    $content.removeClass('loading');
                },
                error: function() {
                    $content.removeClass('loading');
                }
            });
        },
        
        /**
         * Initialize instructor filtering
         */
        initInstructorFiltering: function($widget) {
            var $filters = $widget.find('.instructor-filters');
            var $grid = $widget.find('.instructors-grid');
            
            if ($filters.length) {
                $filters.on('change', 'select, input', function() {
                    var filters = {};
                    
                    $filters.find('select, input[type="checkbox"]:checked').each(function() {
                        var name = $(this).attr('name');
                        var value = $(this).val();
                        
                        if (name && value) {
                            filters[name] = value;
                        }
                    });
                    
                    WazaElementorWidgets.filterInstructors($grid, filters);
                });
            }
        },
        
        /**
         * Filter instructors
         */
        filterInstructors: function($grid, filters) {
            $grid.addClass('loading');
            
            $.ajax({
                url: waza_elementor.ajax_url,
                type: 'POST',
                data: {
                    action: 'waza_filter_instructors',
                    nonce: waza_elementor.nonce,
                    filters: JSON.stringify(filters)
                },
                success: function(response) {
                    if (response.success) {
                        $grid.html(response.data);
                        WazaElementorWidgets.initMasonryLayout($grid);
                    }
                    $grid.removeClass('loading');
                },
                error: function() {
                    $grid.removeClass('loading');
                }
            });
        },
        
        /**
         * Initialize instructor modals
         */
        initInstructorModals: function($widget) {
            $widget.on('click', '.instructor-card[data-modal]', function(e) {
                e.preventDefault();
                
                var instructorId = $(this).data('instructor-id');
                WazaElementorWidgets.openInstructorModal(instructorId);
            });
        },
        
        /**
         * Open instructor modal
         */
        openInstructorModal: function(instructorId) {
            // Create modal overlay
            var $modal = $('<div class="instructor-modal-overlay"><div class="instructor-modal"><div class="modal-content loading">Loading...</div></div></div>');
            $('body').append($modal);
            
            // Load instructor details
            $.ajax({
                url: waza_elementor.ajax_url,
                type: 'POST',
                data: {
                    action: 'waza_get_instructor_details',
                    nonce: waza_elementor.nonce,
                    instructor_id: instructorId
                },
                success: function(response) {
                    if (response.success) {
                        $modal.find('.modal-content').removeClass('loading').html(response.data);
                    }
                },
                error: function() {
                    $modal.find('.modal-content').removeClass('loading').html('Error loading instructor details.');
                }
            });
            
            // Close modal handlers
            $modal.on('click', '.close-modal, .instructor-modal-overlay', function(e) {
                if (e.target === this) {
                    $modal.remove();
                }
            });
            
            $(document).on('keyup.instructor-modal', function(e) {
                if (e.keyCode === 27) { // ESC key
                    $modal.remove();
                    $(document).off('keyup.instructor-modal');
                }
            });
        },
        
        /**
         * Show form message
         */
        showFormMessage: function($form, message, type) {
            var $message = $('<div class="form-message ' + type + '">' + message + '</div>');
            
            $form.find('.form-message').remove();
            $form.prepend($message);
            
            setTimeout(function() {
                $message.fadeOut(function() {
                    $message.remove();
                });
            }, 5000);
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        WazaElementorWidgets.init();
    });
    
    // Export to global scope
    window.WazaElementorWidgets = WazaElementorWidgets;
    
})(jQuery);