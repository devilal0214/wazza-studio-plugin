/**
 * Waza Instructor Dashboard JavaScript
 * 
 * @package WazaBooking
 */

(function ($) {
    'use strict';

    // Global functions accessible throughout
    let loadWorkshops, loadStats, loadSchedule, loadStudents, showWorkshopQR, showWorkshopStudents;

    $(document).ready(function () {
        initInstructorRegistration();
        initInstructorDashboard();
        initSocialLinksManager();
    });
    
    /**
     * Initialize social links add/remove functionality
     */
    function initSocialLinksManager() {
        // Add social link
        $(document).on('click', '#add-social-link', function() {
            var newRow = $('.social-link-row:first').clone();
            newRow.find('input').val('');
            $('#social-links-container').append(newRow);
        });
        
        // Remove social link
        $(document).on('click', '.remove-social-link', function() {
            if ($('.social-link-row').length > 1) {
                $(this).closest('.social-link-row').remove();
            } else {
                alert('At least one social link field must remain. You can leave it empty if not needed.');
            }
        });
    }

    /**
     * Initialize instructor registration form
     */
    function initInstructorRegistration() {
        $('#waza-instructor-registration-form').on('submit', function (e) {
            e.preventDefault();

            const form = $(this);
            const submitBtn = form.find('button[type=submit]');
            const buttonText = submitBtn.find('.button-text');
            const buttonLoader = submitBtn.find('.button-loader');
            const messageDiv = $('#instructor-registration-message');

            // Disable submit button
            submitBtn.prop('disabled', true);
            buttonText.hide();
            buttonLoader.show();

            // Collect social links
            var socialPlatforms = [];
            var socialUrls = [];
            $('select[name="social_platform[]"]').each(function(index) {
                var url = $('input[name="social_url[]"]').eq(index).val();
                if (url) {
                    socialPlatforms.push($(this).val());
                    socialUrls.push(url);
                }
            });
            
            $.ajax({
                url: wazaInstructor.ajax_url,
                type: 'POST',
                data: {
                    action: 'waza_submit_instructor_application',
                    nonce: wazaInstructor.nonce,
                    instructor_name: $('#instructor_name').val(),
                    instructor_email: $('#instructor_email').val(),
                    instructor_phone: $('#instructor_phone').val(),
                    activity_type: $('#activity_type').val(),
                    experience_years: $('#experience_years').val(),
                    instructor_bio: $('#instructor_bio').val(),
                    certifications: $('#certifications').val(),
                    instructor_rating: $('#instructor_rating').val(),
                    social_platform: socialPlatforms,
                    social_url: socialUrls,
                    accept_terms: $('#accept_terms').is(':checked') ? 1 : 0
                },
                success: function (response) {
                    if (response.success) {
                        messageDiv
                            .removeClass('error')
                            .addClass('success')
                            .html('<p>✅ ' + response.data.message + '</p>')
                            .fadeIn();
                        
                        form[0].reset();
                        
                        // Redirect after 3 seconds
                        setTimeout(function() {
                            window.location.href = '/';
                        }, 3000);
                    } else {
                        messageDiv
                            .removeClass('success')
                            .addClass('error')
                            .html('<p>❌ ' + response.data.message + '</p>')
                            .fadeIn();
                    }
                },
                error: function () {
                    messageDiv
                        .removeClass('success')
                        .addClass('error')
                        .html('<p>❌ An error occurred. Please try again.</p>')
                        .fadeIn();
                },
                complete: function () {
                    submitBtn.prop('disabled', false);
                    buttonText.show();
                    buttonLoader.hide();
                }
            });
        });
    }

    /**
     * Initialize instructor dashboard
     */
    function initInstructorDashboard() {
        if ($('.waza-instructor-dashboard').length === 0) {
            return;
        }

        // Tab navigation
        $('.waza-tab-btn').on('click', function () {
            const tab = $(this).data('tab');
            
            $('.waza-tab-btn').removeClass('active');
            $(this).addClass('active');
            
            $('.waza-tab-panel').removeClass('active');
            $('[data-panel="' + tab + '"]').addClass('active');
            
            // Load tab data
            loadTabData(tab);
        });

        // Load initial data
        loadTabData('workshops');
        loadStats();

        // Workshop filters
        $(document).on('click', '.waza-filter-tab', function () {
            console.log('Filter clicked:', $(this).data('filter'));
            $('.waza-filter-tab').removeClass('active');
            $(this).addClass('active');
            
            const filter = $(this).data('filter');
            loadWorkshops(filter);
        });

        // Create workshop button
        $('.waza-create-workshop-btn, .waza-fab').on('click', function () {
            console.log('Create workshop button clicked');
            
            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            $('#workshop-date').attr('min', today);
            
            $('#create-workshop-modal').fadeIn();
        });

        // Close modal handlers
        $(document).on('click', '.waza-modal-close, .waza-modal-overlay', function () {
            console.log('Closing modal');
            $(this).closest('.waza-modal').fadeOut();
        });

        // Create workshop form submission
        $('#create-workshop-form').on('submit', function (e) {
            e.preventDefault();
            
            const form = $(this);
            const submitBtn = $('#submit-workshop-btn');
            const messageDiv = $('#workshop-form-message');
            
            submitBtn.prop('disabled', true).text('Creating...');
            messageDiv.hide();
            
            $.ajax({
                url: wazaInstructor.ajax_url,
                type: 'POST',
                data: {
                    action: 'waza_create_workshop',
                    nonce: wazaInstructor.nonce,
                    activity_id: $('#workshop-activity').val(),
                    capacity: $('#workshop-capacity').val(),
                    price: $('#workshop-price').val(),
                    location: $('#workshop-location').val(),
                    date: $('#workshop-date').val(),
                    time: $('#workshop-time').val(),
                    end_time: $('#workshop-end-time').val()
                },
                success: function (response) {
                    if (response.success) {
                        messageDiv
                            .removeClass('error')
                            .addClass('success')
                            .html('<p>✅ ' + response.data.message + '</p>')
                            .fadeIn();
                        
                        form[0].reset();
                        
                        // Reload workshops list
                        setTimeout(function () {
                            $('#create-workshop-modal').fadeOut();
                            loadWorkshops('upcoming');
                            loadStats();
                        }, 1500);
                    } else {
                        messageDiv
                            .removeClass('success')
                            .addClass('error')
                            .html('<p>❌ ' + (response.data || 'Failed to create workshop') + '</p>')
                            .fadeIn();
                    }
                },
                error: function () {
                    messageDiv
                        .removeClass('success')
                        .addClass('error')
                        .html('<p>❌ An error occurred. Please try again.</p>')
                        .fadeIn();
                },
                complete: function () {
                    submitBtn.prop('disabled', false).text('Create Workshop');
                }
            });
        });

        // Workshop QR code
        $(document).on('click', '.view-workshop-qr', function () {
            console.log('View QR clicked');
            const slotId = $(this).data('slot-id');
            showWorkshopQR(slotId);
        });

        // View students
        $(document).on('click', '.view-workshop-students', function () {
            console.log('View students clicked');
            const slotId = $(this).data('slot-id');
            showWorkshopStudents(slotId);
        });
        
        // Cancel workshop
        $(document).on('click', '.cancel-workshop', function () {
            const slotId = $(this).data('slot-id');
            $('#cancel-slot-id').val(slotId);
            $('#cancel-reason').val('');
            $('#cancel-form-message').hide();
            $('#cancel-workshop-modal').fadeIn();
        });
        
        // Submit cancellation request
        $('#cancel-workshop-form').on('submit', function(e) {
            e.preventDefault();
            
            const slotId = $('#cancel-slot-id').val();
            const reason = $('#cancel-reason').val();
            const submitBtn = $('#submit-cancel-btn');
            const messageDiv = $('#cancel-form-message');
            
            submitBtn.prop('disabled', true).text('<?php esc_html_e("Submitting...", "waza-booking"); ?>');
            messageDiv.hide();
            
            $.ajax({
                url: wazaInstructor.ajax_url,
                type: 'POST',
                data: {
                    action: 'waza_request_workshop_cancellation',
                    nonce: wazaInstructor.nonce,
                    slot_id: slotId,
                    reason: reason
                },
                success: function (response) {
                    if (response.success) {
                        messageDiv.removeClass('error').addClass('success')
                            .html('<p>✅ ' + response.data.message + '</p>')
                            .fadeIn();
                        
                        setTimeout(function() {
                            $('#cancel-workshop-modal').fadeOut();
                            loadWorkshops('upcoming');
                        }, 1500);
                    } else {
                        messageDiv.removeClass('success').addClass('error')
                            .html('<p>❌ ' + (response.data.message || 'Failed to submit request') + '</p>')
                            .fadeIn();
                    }
                    submitBtn.prop('disabled', false).text('Submit Request');
                }
            });
        });
    }

    /**
     * Load tab data based on active tab
     */
    function loadTabData(tab) {
        switch (tab) {
            case 'workshops':
                loadWorkshops('upcoming');
                break;
            case 'schedule':
                loadSchedule();
                break;
            case 'students':
                loadStudents();
                break;
        }
    }

    /**
     * Load dashboard stats
     */
    loadStats = function() {
        $.ajax({
            url: wazaInstructor.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_get_instructor_overview',
                nonce: wazaInstructor.nonce
            },
            success: function (response) {
                if (response.success) {
                    const stats = response.data;
                    $('#stat-workshops-today').text(stats.workshops_today || 0);
                    $('#stat-upcoming').text(stats.upcoming_workshops || 0);
                    $('#stat-students').text(stats.total_students || 0);
                } else {
                    console.error('Failed to load stats:', response.data?.message || 'Unknown error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error loading stats:', status, error, xhr.responseText);
            }
        });
    };

    /**
     * Load workshops list
     */
    loadWorkshops = function(filter) {
        $('#workshops-list').html('<div class="waza-loading"><span class="spinner"></span><p>Loading workshops...</p></div>');

        $.ajax({
            url: wazaInstructor.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_get_instructor_workshops',
                nonce: wazaInstructor.nonce,
                filter: filter || 'upcoming'
            },
            success: function (response) {
                if (response.success) {
                    displayWorkshops(response.data.workshops);
                } else {
                    $('#workshops-list').html('<div class="waza-empty-state"><p>' + (response.data.message || 'No workshops found') + '</p></div>');
                }
            },
            error: function () {
                $('#workshops-list').html('<div class="waza-error"><p>Error loading workshops</p></div>');
            }
        });
    }

    /**
     * Display workshops
     */
    function displayWorkshops(workshops) {
        if (!workshops || workshops.length === 0) {
            $('#workshops-list').html('<div class="waza-empty-state"><p>No workshops found. Create your first workshop!</p></div>');
            return;
        }

        let html = '<div class="waza-workshops-grid">';
        
        workshops.forEach(function (workshop) {
            const fillPercentage = (workshop.booked / workshop.capacity) * 100;
            const isPending = workshop.approval_status === 'pending_approval';
            const isCancelling = workshop.approval_status === 'pending_cancellation';
            const statusClass = isPending || isCancelling ? 'pending' : (workshop.status === 'past' ? 'past' : 'upcoming');
            const statusText = isPending ? '⏳ Pending Approval' : (isCancelling ? '🚫 Cancellation Pending' : workshop.status);
            const statusBadgeClass = isPending || isCancelling ? 'warning' : '';
            const canCancel = !isPending && !isCancelling && workshop.status !== 'past';
            
            html += `
                <div class="waza-workshop-card ${statusClass}">
                    <div class="workshop-header">
                        <h3>${workshop.activity_title}</h3>
                        <span class="workshop-status status-badge ${statusBadgeClass}">${statusText}</span>
                    </div>
                    <div class="workshop-details">
                        <div class="detail-row">
                            <span class="icon">📅</span>
                            <span>${workshop.date} (${workshop.day})</span>
                        </div>
                        <div class="detail-row">
                            <span class="icon">🕐</span>
                            <span>${workshop.time}</span>
                        </div>
                        <div class="detail-row">
                            <span class="icon">👥</span>
                            <span>${workshop.booked}/${workshop.capacity} students</span>
                        </div>
                    </div>
                    <div class="workshop-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${fillPercentage}%"></div>
                        </div>
                        <span class="progress-text">${isPending ? 'Awaiting approval' : (isCancelling ? 'Cancellation pending' : workshop.available + ' slots available')}</span>
                    </div>
                    <div class="workshop-actions">
                        <button class="waza-btn waza-btn-sm waza-btn-primary view-workshop-qr" data-slot-id="${workshop.id}" ${isPending || isCancelling ? 'disabled title="Not available"' : ''}>
                            📱 View QR
                        </button>
                        <button class="waza-btn waza-btn-sm waza-btn-secondary view-workshop-students" data-slot-id="${workshop.id}" ${isPending || isCancelling ? 'disabled title="Not available"' : ''}>
                            👥 Students (${workshop.booked})
                        </button>
                        ${canCancel ? `<button class="waza-btn waza-btn-sm waza-btn-danger cancel-workshop" data-slot-id="${workshop.id}">🗑️ Cancel</button>` : ''}
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        $('#workshops-list').html(html);
    }

    /**
     * Show workshop QR code
     */
    showWorkshopQR = function(slotId) {
        console.log('Showing QR for slot:', slotId);
        
        $.ajax({
            url: wazaInstructor.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_get_workshop_qr',
                nonce: wazaInstructor.nonce,
                slot_id: slotId
            },
            success: function (response) {
                console.log('QR response:', response);
                if (response.success) {
                    // Clone the modal from template
                    const modalHtml = $('#qr-modal-template > .waza-modal').clone();
                    $('body').append(modalHtml);
                    
                    const modal = $('.waza-qr-modal:last');
                    modal.find('#workshop-qr-image').attr('src', response.data.qr_image);
                    modal.fadeIn(200);
                    
                    // Download QR
                    modal.find('#download-qr-btn').off('click').on('click', function() {
                        const link = document.createElement('a');
                        link.href = response.data.qr_image;
                        link.download = `Workshop-${slotId}-QR.png`;
                        link.click();
                    });
                    
                    // Close modal
                    modal.find('.waza-modal-close, .waza-modal-overlay').off('click').on('click', function () {
                        modal.fadeOut(200, function() {
                            modal.remove();
                        });
                    });
                } else {
                    alert('Failed to load QR code');
                }
            },
            error: function(xhr, status, error) {
                console.error('QR error:', error);
                alert('Error loading QR code');
            }
        });
    };

    /**
     * Load schedule
     */
    loadSchedule = function() {
        $('#schedule-calendar').html('<div class="waza-loading"><span class="spinner"></span><p>Loading schedule...</p></div>');
        
        $.ajax({
            url: wazaInstructor.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_get_instructor_schedule',
                nonce: wazaInstructor.nonce
            },
            success: function (response) {
                if (response.success) {
                    displaySchedule(response.data.schedule);
                }
            }
        });
    }

    /**
     * Display schedule
     */
    function displaySchedule(schedule) {
        if (!schedule || schedule.length === 0) {
            $('#schedule-calendar').html('<div class="waza-empty-state"><p>No scheduled workshops</p></div>');
            return;
        }

        let html = '<div class="waza-schedule-list">';
        
        schedule.forEach(function (item) {
            html += `
                <div class="schedule-item">
                    <div class="schedule-time">
                        <span class="time">${item.time}</span>
                        <span class="date">${item.date}</span>
                    </div>
                    <div class="schedule-info">
                        <h4>${item.activity_title}</h4>
                        <p>${item.booked}/${item.capacity} students</p>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        $('#schedule-calendar').html(html);
    }

    /**
     * Show workshop students modal
     */
    showWorkshopStudents = function(slotId) {
        console.log('Loading students for slot:', slotId);
        
        $.ajax({
            url: wazaInstructor.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_get_workshop_students',
                nonce: wazaInstructor.nonce,
                slot_id: slotId
            },
            success: function(response) {
                console.log('Workshop students response:', response);
                if (response.success) {
                    displayWorkshopStudentsModal(response.data);
                } else {
                    alert(response.data?.message || 'Failed to load students');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading workshop students:', status, error);
                alert('Failed to load students. Please try again.');
            }
        });
    };

    /**
     * Display workshop students modal
     */
    function displayWorkshopStudentsModal(data) {
        const modal = $('<div class="waza-modal waza-students-modal" style="display: none;">');
        
        let studentsHtml = '';
        if (!data.students || data.students.length === 0) {
            studentsHtml = '<div class="waza-empty-state"><p>No students booked yet</p></div>';
        } else {
            studentsHtml = '<div class="waza-students-table"><table>';
            studentsHtml += '<thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Attendees</th><th>Status</th><th>Attended</th></tr></thead>';
            studentsHtml += '<tbody>';
            
            data.students.forEach(function(student) {
                const statusBadge = student.status === 'confirmed' ? '<span class="status-badge success">Confirmed</span>' : 
                                   student.status === 'pending' ? '<span class="status-badge warning">Pending</span>' :
                                   '<span class="status-badge error">' + student.status + '</span>';
                const attendedBadge = student.attended ? '<span class="status-badge success">✓ Yes</span>' : 
                                     '<span class="status-badge">No</span>';
                
                studentsHtml += `
                    <tr>
                        <td><strong>${student.name}</strong></td>
                        <td>${student.email}</td>
                        <td>${student.phone || 'N/A'}</td>
                        <td>${student.attendees}</td>
                        <td>${statusBadge}</td>
                        <td>${attendedBadge}</td>
                    </tr>
                `;
            });
            
            studentsHtml += '</tbody></table></div>';
        }
        
        modal.html(`
            <div class="waza-modal-overlay"></div>
            <div class="waza-modal-content">
                <button class="waza-modal-close">&times;</button>
                <div class="waza-modal-header">
                    <h3>Workshop Students</h3>
                    <div class="workshop-details">
                        <p><strong>${data.activity_title}</strong></p>
                        <p>${data.slot_time}</p>
                        <p>Capacity: ${data.booked}/${data.capacity}</p>
                    </div>
                </div>
                <div class="waza-modal-body">
                    ${studentsHtml}
                </div>
            </div>
        `);
        
        $('body').append(modal);
        modal.fadeIn(200);
        
        // Close modal handlers
        modal.find('.waza-modal-close, .waza-modal-overlay').on('click', function() {
            console.log('Closing students modal');
            modal.fadeOut(200, function() {
                modal.remove();
            });
        });
    }

    /**
     * Load students
     */
    loadStudents = function() {
        $('#students-list').html('<div class="waza-loading"><span class="spinner"></span><p>Loading students...</p></div>');
        
        $.ajax({
            url: wazaInstructor.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_get_instructor_students',
                nonce: wazaInstructor.nonce
            },
            success: function (response) {
                if (response.success) {
                    displayStudents(response.data.students);
                }
            }
        });
    }

    /**
     * Display students
     */
    function displayStudents(students) {
        if (!students || students.length === 0) {
            $('#students-list').html('<div class="waza-empty-state"><p>No students yet</p></div>');
            return;
        }

        let html = '<div class="waza-students-table"><table><thead><tr><th>Name</th><th>Email</th><th>Bookings</th><th>Attendance</th></tr></thead><tbody>';
        
        students.forEach(function (student) {
            html += `
                <tr>
                    <td>${student.name}</td>
                    <td>${student.email}</td>
                    <td>${student.total_bookings}</td>
                    <td>${student.total_attendance}</td>
                </tr>
            `;
        });
        
        html += '</tbody></table></div>';
        $('#students-list').html(html);
    }

})(jQuery);
