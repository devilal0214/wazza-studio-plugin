/**
 * Student Dashboard JavaScript - Table View with Pagination
 */

(function($) {
    'use strict';

    let bookingsData = [];
    let filteredBookings = [];
    let currentPage = 1;
    const itemsPerPage = 10;
    let currentFilter = 'all';
    let searchTerm = '';

    $(document).ready(function() {
        if ($('.waza-my-bookings').length) {
            initDashboard();
        }
    });

    function initDashboard() {
        loadBookings();
        setupEventListeners();
    }

    function setupEventListeners() {
        // Filter buttons
        $(document).on('click', '.waza-filter-btn', function() {
            $('.waza-filter-btn').removeClass('active');
            $(this).addClass('active');
            currentFilter = $(this).data('filter');
            currentPage = 1;
            filterAndRenderBookings();
        });

        // Search
        $(document).on('input', '.waza-search-input', function() {
            searchTerm = $(this).val().toLowerCase();
            currentPage = 1;
            filterAndRenderBookings();
        });

        // Pagination
        $(document).on('click', '.waza-pagination-btn[data-page]', function() {
            currentPage = parseInt($(this).data('page'));
            renderBookingsTable();
        });

        // View details button
        $(document).on('click', '.waza-btn-view-details', function(e) {
            e.preventDefault();
            const bookingId = $(this).data('booking-id');
            showBookingDetailModal(bookingId);
        });

        // Download QR button
        $(document).on('click', '.waza-btn-download-qr', function() {
            const bookingId = $(this).data('booking-id');
            downloadQRCode(bookingId);
        });
    }

    function loadBookings() {
        $('#waza-bookings-list').html('<div class="waza-loading" style="text-align: center; padding: 3rem;"><div class="spinner" style="border: 3px solid #f3f3f3; border-top: 3px solid #6366f1; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto;"></div><p style="margin-top: 1rem; color: #64748b;">Loading bookings...</p></div>');
        
        $.ajax({
            url: waza_account.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_get_my_bookings',
                nonce: waza_account.nonce
            },
            success: function(response) {
                if (response.success) {
                    bookingsData = response.data.bookings;
                    filterAndRenderBookings();
                    renderStats();
                } else {
                    showError(response.data.message || 'Failed to load bookings');
                }
            },
            error: function() {
                showError('An error occurred while loading bookings');
            }
        });
    }

    function filterAndRenderBookings() {
        // Apply filters
        filteredBookings = bookingsData.filter(booking => {
            // Filter by status
            let statusMatch = true;
            if (currentFilter === 'upcoming') {
                statusMatch = booking.is_upcoming;
            } else if (currentFilter === 'past') {
                statusMatch = !booking.is_upcoming;
            }

            // Filter by search term
            let searchMatch = true;
            if (searchTerm) {
                searchMatch = 
                    booking.activity_title.toLowerCase().includes(searchTerm) ||
                    booking.id.toString().includes(searchTerm) ||
                    booking.formatted_date.toLowerCase().includes(searchTerm);
            }

            return statusMatch && searchMatch;
        });

        renderBookingsTable();
    }

    function renderStats() {
        const totalBookings = bookingsData.length;
        const upcomingBookings = bookingsData.filter(b => b.is_upcoming).length;
        const attendedBookings = bookingsData.filter(b => b.attended).length;

        const statsHtml = `
            <div class="waza-dashboard-stats">
                <div class="waza-stat-card">
                    <div class="waza-stat-label">Total Bookings</div>
                    <div class="waza-stat-value">${totalBookings}</div>
                </div>
                <div class="waza-stat-card secondary">
                    <div class="waza-stat-label">Upcoming Sessions</div>
                    <div class="waza-stat-value">${upcomingBookings}</div>
                </div>
                <div class="waza-stat-card tertiary">
                    <div class="waza-stat-label">Sessions Attended</div>
                    <div class="waza-stat-value">${attendedBookings}</div>
                </div>
            </div>
        `;

        $('.waza-dashboard-stats').replaceWith(statsHtml);
    }

    function renderBookingsTable() {
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        const paginatedBookings = filteredBookings.slice(startIndex, endIndex);
        const totalPages = Math.ceil(filteredBookings.length / itemsPerPage);

        if (paginatedBookings.length === 0) {
            $('#waza-bookings-list').html(`
                <div class="waza-empty-state">
                    <div class="waza-empty-icon">📅</div>
                    <h3>No bookings found</h3>
                    <p>${currentFilter === 'upcoming' ? 'You have no upcoming bookings' : currentFilter === 'past' ? 'You have no past bookings' : 'You haven\'t made any bookings yet'}</p>
                </div>
            `);
            return;
        }

        let tableHtml = `
            <div class="waza-bookings-table-wrapper">
                <table class="waza-bookings-table">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Activity</th>
                            <th>Date & Time</th>
                            <th>Seats</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Attendance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        paginatedBookings.forEach(booking => {
            const statusClass = booking.booking_status.toLowerCase();
            const attendanceStatus = booking.attended ? 
                '<span class="waza-attendance-badge attended">✓ Attended</span>' : 
                '<span class="waza-attendance-badge not-attended">Not Attended</span>';

            tableHtml += `
                <tr>
                    <td><strong>#${booking.id}</strong></td>
                    <td>
                        <div><strong>${booking.activity_title}</strong></div>
                        <div style="font-size: 0.875rem; color: var(--waza-text-secondary);">Booked on ${booking.created_at}</div>
                    </td>
                    <td>
                        <div>${booking.formatted_date}</div>
                        <div style="font-size: 0.875rem; color: var(--waza-text-secondary);">${booking.formatted_time}</div>
                    </td>
                    <td>${booking.quantity}</td>
                    <td>₹${booking.total_amount}</td>
                    <td><span class="waza-status-badge ${statusClass}">${booking.booking_status}</span></td>
                    <td>${booking.is_upcoming ? '<span class="waza-attendance-badge not-attended">Upcoming</span>' : attendanceStatus}</td>
                    <td>
                        <div class="waza-action-btns">
                            <button class="waza-btn-sm waza-btn-view waza-btn-view-details" data-booking-id="${booking.id}">
                                👁️ View
                            </button>
                            ${booking.qr_token ? `
                                <button class="waza-btn-sm waza-btn-download waza-btn-download-qr" data-booking-id="${booking.id}">
                                    📥 QR
                                </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `;
        });

        tableHtml += `
                    </tbody>
                </table>
            </div>
        `;

        // Add pagination
        if (totalPages > 1) {
            const paginationHtml = renderPagination(currentPage, totalPages, filteredBookings.length);
            tableHtml += paginationHtml;
        }

        $('#waza-bookings-list').html(tableHtml);
    }

    function renderPagination(currentPage, totalPages, totalItems) {
        const startItem = ((currentPage - 1) * itemsPerPage) + 1;
        const endItem = Math.min(currentPage * itemsPerPage, totalItems);

        let paginationHtml = `
            <div class="waza-pagination">
                <div class="waza-pagination-info">
                    Showing ${startItem}-${endItem} of ${totalItems} bookings
                </div>
                <div class="waza-pagination-btns">
                    <button class="waza-pagination-btn" data-page="${currentPage - 1}" ${currentPage === 1 ? 'disabled' : ''}>
                        ← Previous
                    </button>
        `;

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                paginationHtml += `
                    <button class="waza-pagination-btn ${i === currentPage ? 'active' : ''}" data-page="${i}">
                        ${i}
                    </button>
                `;
            } else if (i === currentPage - 2 || i === currentPage + 2) {
                paginationHtml += `<span style="padding: 0 0.5rem;">...</span>`;
            }
        }

        paginationHtml += `
                    <button class="waza-pagination-btn" data-page="${currentPage + 1}" ${currentPage === totalPages ? 'disabled' : ''}>
                        Next →
                    </button>
                </div>
            </div>
        `;

        return paginationHtml;
    }

    function showBookingDetailModal(bookingId) {
        // Convert to integer for comparison (data attribute returns number, DB returns string)
        const booking = bookingsData.find(b => parseInt(b.id) === parseInt(bookingId));
        if (!booking) {
            alert('Booking not found');
            return;
        }

        $.ajax({
            url: waza_account.ajax_url,
            type: 'POST',
            data: {
                action: 'waza_get_booking_details',
                booking_id: bookingId,
                nonce: waza_account.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderBookingDetailModal(response.data);
                } else {
                    alert('Error: ' + (response.data.message || 'Failed to load booking details'));
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred while loading booking details: ' + error);
            }
        });
    }

    function renderBookingDetailModal(data) {
        const modalHtml = `
            <div class="waza-modal waza-booking-detail-modal" style="display: flex;">
                <div class="waza-modal-content">
                    <span class="waza-modal-close">&times;</span>
                    <div class="waza-booking-detail-header">
                        <div>
                            <h3 class="waza-booking-detail-title">${data.activity_title}</h3>
                            <p style="color: var(--waza-text-secondary); margin: 0.5rem 0 0;">Booking #${data.id}</p>
                        </div>
                        <span class="waza-status-badge ${data.booking_status ? data.booking_status.toLowerCase() : 'pending'}">${data.booking_status_display || data.booking_status || 'Pending'}</span>
                    </div>
                    
                    <div class="waza-booking-detail-grid">
                        <div class="waza-detail-item">
                            <div class="waza-detail-label">Date</div>
                            <div class="waza-detail-value">${data.formatted_date}</div>
                        </div>
                        <div class="waza-detail-item">
                            <div class="waza-detail-label">Time</div>
                            <div class="waza-detail-value">${data.formatted_time}</div>
                        </div>
                        <div class="waza-detail-item">
                            <div class="waza-detail-label">Total Seats</div>
                            <div class="waza-detail-value">${data.quantity}</div>
                        </div>
                        <div class="waza-detail-item">
                            <div class="waza-detail-label">Total Amount</div>
                            <div class="waza-detail-value">₹${data.total_amount}</div>
                        </div>
                        <div class="waza-detail-item">
                            <div class="waza-detail-label">Payment Status</div>
                            <div class="waza-detail-value"><span class="waza-status-badge ${data.payment_status ? data.payment_status.toLowerCase() : 'pending'}">${data.payment_status_display || data.payment_status || 'Pending'}</span></div>
                        </div>
                        <div class="waza-detail-item">
                            <div class="waza-detail-label">Booked On</div>
                            <div class="waza-detail-value">${data.created_at}</div>
                        </div>
                    </div>

                    ${data.attendees && data.attendees.length > 0 ? `
                        <div class="waza-attendees-list">
                            <h4>Attendees (${data.attended_count}/${data.quantity} attended)</h4>
                            ${data.attendees.map((attendee, index) => `
                                <div class="waza-attendee-item">
                                    <div class="waza-attendee-info">
                                        <div class="waza-seat-number">${index + 1}</div>
                                        <div>
                                            <div><strong>${attendee.name}</strong></div>
                                            <div style="font-size: 0.875rem; color: var(--waza-text-secondary);">${attendee.email}</div>
                                        </div>
                                    </div>
                                    ${attendee.attended ? 
                                        '<span class="waza-attendance-badge attended">✓ Attended</span>' : 
                                        '<span class="waza-attendance-badge not-attended">Not Attended</span>'
                                    }
                                </div>
                            `).join('')}
                        </div>
                    ` : ''}

                    ${data.qr_code_url ? `
                        <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 2px solid var(--waza-border);">
                            <h4>QR Code</h4>
                            <img src="${data.qr_code_url}" alt="Booking QR Code" style="max-width: 200px; margin: 1rem auto; display: block; border: 2px solid var(--waza-border); padding: 10px; border-radius: var(--waza-radius);">
                            <button class="waza-btn-sm waza-btn-download" onclick="window.downloadQRCode('${data.qr_code_url}', 'booking-${data.id}-qr')">
                                📥 Download QR Code
                            </button>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;

        $('body').append(modalHtml);

        $('.waza-modal-close').on('click', function() {
            $(this).closest('.waza-modal').fadeOut(300, function() {
                $(this).remove();
            });
        });

        $('.waza-modal').on('click', function(e) {
            if ($(e.target).hasClass('waza-modal')) {
                $(this).fadeOut(300, function() {
                    $(this).remove();
                });
            }
        });
    }

    function downloadQRCode(bookingId) {
        window.location.href = waza_account.ajax_url + '?action=waza_download_booking_qr&booking_id=' + bookingId;
    }

    function showError(message) {
        $('#waza-bookings-list').html(`
            <div class="waza-alert waza-alert-error">
                ${message}
            </div>
        `);
    }

})(jQuery);
