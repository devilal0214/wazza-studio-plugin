jQuery(document).ready(function ($) {
    // Client-side tab navigation
    $('.nav-tab').on('click', function (e) {
        var href = $(this).attr('href');

        // If it has a tab parameter, use client-side switching
        if (href.indexOf('tab=') !== -1) {
            e.preventDefault();

            var tab = href.split('tab=')[1];

            // Update active tab
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');

            // Show/hide tab content
            $('.tab-content > div').hide();
            $('#tab-' + tab).show();

            // Update URL without reload
            history.pushState(null, '', href);
        }
    });

    // Show active tab on page load
    var urlParams = new URLSearchParams(window.location.search);
    var activeTab = urlParams.get('tab') || 'list';
    $('#tab-' + activeTab).show();

    // Add slot form submission
    $('#add-slot-form').on('submit', function (e) {
        e.preventDefault();

        var formData = {};
        $(this).serializeArray().forEach(function (field) {
            formData[field.name] = field.value;
        });

        formData.action = 'waza_save_slot';
        formData.nonce = wazaSlots.nonce;

        $.ajax({
            url: wazaSlots.ajaxUrl,
            type: 'POST',
            data: formData,
            beforeSend: function () {
                $('#add-slot-form button[type="submit"]').prop('disabled', true).text('Creating...');
            },
            success: function (response) {
                if (response.success) {
                    alert(wazaSlots.strings.saved);
                    window.location.href = 'admin.php?page=waza-slots&tab=list';
                } else {
                    alert(response.data || wazaSlots.strings.error);
                }
            },
            error: function () {
                alert(wazaSlots.strings.error);
            },
            complete: function () {
                $('#add-slot-form button[type="submit"]').prop('disabled', false).text('Create Slot');
            }
        });
    });

    // Edit slot - show modal
    $('.edit-slot').on('click', function (e) {
        e.preventDefault();

        var slotId = $(this).data('slot-id');

        // Get slot data via AJAX
        $.ajax({
            url: wazaSlots.ajaxUrl,
            type: 'POST',
            data: {
                action: 'waza_get_slot',
                nonce: wazaSlots.nonce,
                slot_id: slotId
            },
            success: function (response) {
                if (response.success) {
                    showEditModal(response.data);
                } else {
                    alert(response.data || 'Failed to load slot data');
                }
            },
            error: function () {
                alert('Failed to load slot data');
            }
        });
    });

    // Show edit modal
    function showEditModal(slot) {
        var modal = $('<div id="edit-slot-modal" class="waza-modal">' +
            '<div class="waza-modal-content">' +
            '<span class="waza-modal-close">&times;</span>' +
            '<h2>Edit Time Slot</h2>' +
            '<form id="edit-slot-form">' +
            '<input type="hidden" name="slot_id" value="' + slot.id + '">' +
            '<table class="form-table">' +
            '<tr>' +
            '<th><label>Activity</label></th>' +
            '<td><strong>' + (slot.activity_title || 'Unknown') + '</strong></td>' +
            '</tr>' +
            '<tr>' +
            '<th><label for="edit_start_date">Start Date</label></th>' +
            '<td><input type="date" id="edit_start_date" name="start_date" value="' + slot.start_date + '" required></td>' +
            '</tr>' +
            '<tr>' +
            '<th><label for="edit_start_time">Start Time</label></th>' +
            '<td><input type="time" id="edit_start_time" name="start_time" value="' + slot.start_time + '" required></td>' +
            '</tr>' +
            '<tr>' +
            '<th><label for="edit_end_time">End Time</label></th>' +
            '<td><input type="time" id="edit_end_time" name="end_time" value="' + slot.end_time + '" required></td>' +
            '</tr>' +
            '<tr>' +
            '<th><label for="edit_instructor_id">Instructor</label></th>' +
            '<td><select id="edit_instructor_id" name="instructor_id">' +
            '<option value="">No Instructor</option>' +
            (slot.instructors_options || '') +
            '</select></td>' +
            '</tr>' +
            '<tr>' +
            '<th><label for="edit_capacity">Capacity</label></th>' +
            '<td><input type="number" id="edit_capacity" name="capacity" value="' + slot.capacity + '" min="1" max="1000" required></td>' +
            '</tr>' +
            '<tr>' +
            '<th><label for="edit_price">Price</label></th>' +
            '<td><input type="number" id="edit_price" name="price" value="' + (slot.price || 0) + '" min="0" step="0.01" class="regular-text"></td>' +
            '</tr>' +
            '<tr>' +
            '<th><label for="edit_location">Location</label></th>' +
            '<td><input type="text" id="edit_location" name="location" value="' + (slot.location || '') + '" class="regular-text"></td>' +
            '</tr>' +
            '<tr>' +
            '<th><label for="edit_notes">Notes</label></th>' +
            '<td><textarea id="edit_notes" name="notes" rows="3" class="large-text">' + (slot.notes || '') + '</textarea></td>' +
            '</tr>' +
            '</table>' +
            '<p class="submit">' +
            '<button type="submit" class="button button-primary">Update Slot</button>' +
            '<button type="button" class="button waza-modal-close">Cancel</button>' +
            '</p>' +
            '</form>' +
            '</div>' +
            '</div>');

        $('body').append(modal);
        modal.fadeIn();
    }

    // Close modal
    $(document).on('click', '.waza-modal-close', function () {
        $('#edit-slot-modal').fadeOut(function () {
            $(this).remove();
        });
    });

    // Update slot via AJAX
    $(document).on('submit', '#edit-slot-form', function (e) {
        e.preventDefault();

        var formData = {};
        $(this).serializeArray().forEach(function (field) {
            formData[field.name] = field.value;
        });

        formData.action = 'waza_update_slot';
        formData.nonce = wazaSlots.nonce;

        $.ajax({
            url: wazaSlots.ajaxUrl,
            type: 'POST',
            data: formData,
            beforeSend: function () {
                $('#edit-slot-form button[type="submit"]').prop('disabled', true).text('Updating...');
            },
            success: function (response) {
                if (response.success) {
                    alert('Slot updated successfully!');
                    location.reload();
                } else {
                    alert(response.data || wazaSlots.strings.error);
                }
            },
            error: function () {
                alert(wazaSlots.strings.error);
            },
            complete: function () {
                $('#edit-slot-form button[type="submit"]').prop('disabled', false).text('Update Slot');
            }
        });
    });

    // Bulk create form submission
    $('#bulk-create-form').on('submit', function (e) {
        e.preventDefault();

        var formData = new FormData(this);
        formData.append('action', 'waza_bulk_create_slots');
        formData.append('nonce', wazaSlots.nonce);

        $.ajax({
            url: wazaSlots.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function () {
                $('#bulk-create-form button[type="submit"]').prop('disabled', true).text('Creating...');
            },
            success: function (response) {
                if (response.success) {
                    alert(response.data.message);
                    window.location.href = 'admin.php?page=waza-slots&tab=list';
                } else {
                    alert(response.data || wazaSlots.strings.error);
                }
            },
            error: function () {
                alert(wazaSlots.strings.error);
            },
            complete: function () {
                $('#bulk-create-form button[type="submit"]').prop('disabled', false).text('Create Slots');
            }
        });
    });

    // Delete slot
    $('.delete-slot').on('click', function (e) {
        e.preventDefault();

        if (!confirm(wazaSlots.strings.confirmDelete)) {
            return;
        }

        var slotId = $(this).data('slot-id');
        var row = $(this).closest('tr');

        $.ajax({
            url: wazaSlots.ajaxUrl,
            type: 'POST',
            data: {
                action: 'waza_delete_slot',
                nonce: wazaSlots.nonce,
                slot_id: slotId
            },
            beforeSend: function () {
                row.css('opacity', '0.5');
            },
            success: function (response) {
                if (response.success) {
                    row.fadeOut(function () {
                        $(this).remove();
                    });
                } else {
                    alert(response.data || wazaSlots.strings.error);
                    row.css('opacity', '1');
                }
            },
            error: function () {
                alert(wazaSlots.strings.error);
                row.css('opacity', '1');
            }
        });
    });

    // Dynamic time slot management for bulk create
    var timeSlotIndex = 1;

    $('#add-time-slot').on('click', function () {
        var newSlot = $('<div class="time-slot-row">' +
            '<input type="time" name="time_slots[' + timeSlotIndex + '][start]" required />' +
            '<span> - </span>' +
            '<input type="time" name="time_slots[' + timeSlotIndex + '][end]" required />' +
            '<button type="button" class="button remove-time-slot" style="margin-left: 10px;">Remove</button>' +
            '</div>');

        $('#time-slots-container').append(newSlot);
        timeSlotIndex++;
    });

    $(document).on('click', '.remove-time-slot', function () {
        var container = $('#time-slots-container');
        if (container.find('.time-slot-row').length > 1) {
            $(this).parent().remove();
        } else {
            alert('At least one time slot is required.');
        }
    });

    // Form validation
    $('input[name="start_date"], input[name="date_range_start"]').on('change', function () {
        var selectedDate = new Date($(this).val());
        var today = new Date();
        today.setHours(0, 0, 0, 0);

        if (selectedDate < today) {
            alert('Please select a future date.');
            $(this).val('');
        }
    });

    $('input[name="start_time"], input[name="end_time"]').on('change', function () {
        var startTime = $('input[name="start_time"]').val();
        var endTime = $('input[name="end_time"]').val();

        if (startTime && endTime && startTime >= endTime) {
            alert('End time must be after start time.');
            $(this).val('');
        }
    });

    // Modal styles
    var modalStyles = $('<style>' +
        '.waza-modal { display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }' +
        '.waza-modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 90%; max-width: 600px; border-radius: 8px; position: relative; }' +
        '.waza-modal-close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; line-height: 20px; }' +
        '.waza-modal-close:hover, .waza-modal-close:focus { color: #000; }' +
        '.waza-modal h2 { margin-top: 0; clear: both; }' +
        '</style>');
    $('head').append(modalStyles);
});