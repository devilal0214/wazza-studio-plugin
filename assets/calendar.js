/**
 * Waza Calendar JavaScript
 * 
 * Interactive calendar functionality
 */

(function($) {
    'use strict';
    
    let currentDate = new Date();
    let currentView = 'month';
    let filters = {
        activity_id: 0,
        instructor_id: 0
    };
    
    $(document).ready(function() {
        initCalendar();
        bindEvents();
    });
    
    function initCalendar() {
        currentView = $('.waza-calendar-wrapper').data('view') || 'month';
        
        // Set initial filter values
        filters.activity_id = $('#waza-filter-activity').val() || 0;
        filters.instructor_id = $('#waza-filter-instructor').val() || 0;
        
        renderCalendar();
    }
    
    function bindEvents() {
        // Navigation buttons
        $('.waza-calendar-nav').on('click', function() {
            const nav = $(this).data('nav');
            if (nav === 'prev') {
                navigateCalendar(-1);
            } else {
                navigateCalendar(1);
            }
        });
        
        // Today button
        $('.waza-calendar-today').on('click', function() {
            currentDate = new Date();
            renderCalendar();
        });
        
        // View switcher
        $('#waza-calendar-view').on('change', function() {
            currentView = $(this).val();
            renderCalendar();
        });
        
        // Filters
        $('.waza-filter').on('change', function() {
            filters.activity_id = $('#waza-filter-activity').val() || 0;
            filters.instructor_id = $('#waza-filter-instructor').val() || 0;
            renderCalendar();
        });
        
        // Modal close
        $('.waza-modal-close, .waza-modal').on('click', function(e) {
            if (e.target === this) {
                $('#waza-slot-modal').hide();
            }
        });
        
        // Slot click delegation
        $(document).on('click', '.waza-slot-item', function(e) {
            e.stopPropagation();
            const slotId = $(this).data('slot-id');
            showSlotDetails(slotId);
        });
    }
    
    function navigateCalendar(direction) {
        if (currentView === 'month') {
            currentDate.setMonth(currentDate.getMonth() + direction);
        } else if (currentView === 'week') {
            currentDate.setDate(currentDate.getDate() + (direction * 7));
        } else {
            currentDate.setDate(currentDate.getDate() + direction);
        }
        renderCalendar();
    }
    
    function renderCalendar() {
        const month = currentDate.getMonth() + 1;
        const year = currentDate.getFullYear();
        
        // Update header
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                          'July', 'August', 'September', 'October', 'November', 'December'];
        $('#waza-current-month').text(monthNames[currentDate.getMonth()]);
        $('#waza-current-year').text(year);
        
        // Show loading
        $('.waza-calendar-loading').show();
        $('#waza-calendar-days').empty();
        
        // Fetch slots
        $.ajax({
            url: wazaCalendar.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_get_calendar_slots',
                nonce: wazaCalendar.nonce,
                month: month,
                year: year,
                activity_id: filters.activity_id,
                instructor_id: filters.instructor_id
            },
            success: function(response) {
                $('.waza-calendar-loading').hide();
                
                if (response.success) {
                    renderMonthView(response.data.slots_by_date, month, year);
                }
            },
            error: function() {
                $('.waza-calendar-loading').hide();
                alert('Failed to load calendar. Please try again.');
            }
        });
    }
    
    function renderMonthView(slotsByDate, month, year) {
        const firstDay = new Date(year, month - 1, 1);
        const lastDay = new Date(year, month, 0);
        const daysInMonth = lastDay.getDate();
        
        // Get start of week setting
        const startOfWeek = wazaCalendar.settings.start_of_week === 'sunday' ? 0 : 1;
        
        // Calculate offset for first day
        let firstDayOfWeek = firstDay.getDay();
        if (startOfWeek === 1) {
            firstDayOfWeek = firstDayOfWeek === 0 ? 6 : firstDayOfWeek - 1;
        }
        
        // Previous month days
        const prevMonthDays = new Date(year, month - 1, 0).getDate();
        const prevMonthStart = prevMonthDays - firstDayOfWeek + 1;
        
        const $daysContainer = $('#waza-calendar-days');
        $daysContainer.empty();
        
        const today = new Date();
        const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
        
        // Render previous month days
        for (let i = 0; i < firstDayOfWeek; i++) {
            const day = prevMonthStart + i;
            $daysContainer.append(createDayElement(day, 'other-month'));
        }
        
        // Render current month days
        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const isToday = dateStr === todayStr;
            const slots = slotsByDate[dateStr] || [];
            
            $daysContainer.append(createDayElement(day, isToday ? 'today' : '', slots));
        }
        
        // Render next month days to fill grid
        const totalCells = $daysContainer.children().length;
        const remainingCells = 42 - totalCells; // 6 weeks * 7 days
        
        for (let day = 1; day <= remainingCells; day++) {
            $daysContainer.append(createDayElement(day, 'other-month'));
        }
    }
    
    function createDayElement(dayNumber, className, slots) {
        slots = slots || [];
        
        const $day = $('<div>', {
            class: 'waza-calendar-day ' + className
        });
        
        $day.append($('<div>', {
            class: 'waza-day-number',
            text: dayNumber
        }));
        
        if (slots.length > 0) {
            const $slotsContainer = $('<div>', {
                class: 'waza-day-slots'
            });
            
            const maxSlots = parseInt(wazaCalendar.settings.slots_per_day) || 5;
            const displaySlots = slots.slice(0, maxSlots);
            
            displaySlots.forEach(function(slot) {
                const showInstructor = wazaCalendar.settings.show_instructor === 'yes';
                const showPrice = wazaCalendar.settings.show_price === 'yes';
                
                let slotText = slot.time;
                if (slot.activity) {
                    slotText += ' - ' + slot.activity;
                }
                if (showInstructor && slot.instructor) {
                    slotText += ' (' + slot.instructor + ')';
                }
                if (showPrice && slot.price > 0) {
                    slotText += ' - ₹' + slot.price;
                }
                
                const $slotItem = $('<div>', {
                    class: 'waza-slot-item waza-slot-' + slot.availability_class,
                    'data-slot-id': slot.id,
                    html: slotText
                });
                
                $slotsContainer.append($slotItem);
            });
            
            if (slots.length > maxSlots) {
                $slotsContainer.append($('<div>', {
                    class: 'waza-more-slots',
                    text: '+' + (slots.length - maxSlots) + ' more',
                    style: 'font-size: 11px; color: #666; padding: 4px;'
                }));
            }
            
            $day.append($slotsContainer);
        }
        
        return $day;
    }
    
    function showSlotDetails(slotId) {
        // This will be populated from the SlotDetailsManager
        // For now, redirect to slot details page
        window.location.href = wazaCalendar.ajax_url.replace('admin-ajax.php', '') + 'slot/' + slotId;
    }
    
})(jQuery);
