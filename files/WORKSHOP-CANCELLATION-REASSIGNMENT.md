# Workshop Cancellation & Reassignment System

## Overview
Complete workflow implementation for workshop cancellation requests and admin reassignment using modal popups.

## Features Implemented

### 1. Instructor Cancellation Request
- **Modal Popup**: Replace alert/prompt with professional modal interface
- **Cancellation Reason**: Textarea field for instructors to provide reason
- **Status**: Workshop marked as `pending_cancellation`
- **Email Notification**: Admin receives email when cancellation requested

### 2. Admin Approval Workflow
- **Approve Cancellation**: Sets status to `cancelled` (does NOT delete workshop)
- **Reject Cancellation**: Returns workshop to `active` status
- **Preserved Data**: All workshop and slot data retained for reassignment

### 3. Reassignment System
- **Modal with Dropdown**: Select available instructors from dropdown
- **Conflict Check**: Only shows instructors without time conflicts
- **Auto-Update**: Updates activity instructor and slot status to `active`
- **Email Notification**: New instructor receives notification

### 4. Admin Panel Display
- **Pending Workshops Table**: Shows workshops awaiting approval + cancellation requests
- **Cancelled Workshops Table**: Separate section for cancelled workshops
- **Action Buttons**: 
  - Pending approval: Approve / Reject
  - Pending cancellation: Approve Cancel / Reject Cancel / Reassign
  - Cancelled: Reassign

### 5. Calendar Integration
- **Excluded Status**: Cancelled workshops don't appear in:
  - Frontend calendar ([waza_calendar] shortcode)
  - Instructor dashboard workshop list
  - Available slots for booking
- **Active Only**: Only workshops with status != 'cancelled' shown to users

## Database Schema

### Slot Status Values
- `pending_approval` - New workshop awaiting admin approval
- `active` - Approved and visible in calendar
- `pending_cancellation` - Instructor requested cancellation
- `cancelled` - Admin approved cancellation, available for reassignment

### Meta Fields
- `_waza_cancellation_reason_{slot_id}` - Stores cancellation reason text

## Modified Files

### Templates
- `templates/instructor-dashboard.php`
  - Added cancel-workshop-modal (lines 314-348)

### JavaScript
- `assets/instructor.js`
  - Cancel button opens modal instead of prompt
  - Form submission handler with AJAX

### CSS
- `assets/instructor.css`
  - Modal styles with black/white theme
  - .waza-btn-danger class for cancel buttons

### PHP
- `src/Frontend/InstructorFrontend.php`
  - request_workshop_cancellation() method
  - notify_admin_cancellation_request() email
  - Workshop query excludes cancelled status (line 321)

- `src/Admin/AdminManager.php`
  - approve_cancellation() - UPDATE instead of DELETE
  - ajax_get_available_instructors() - conflict checking
  - ajax_reassign_workshop() - reassignment logic
  - pending_workshops_page() - two separate queries and tables
  - showReassignModal() - dynamic modal with dropdown

- `src/Frontend/InteractiveCalendarManager.php`
  - Already filters by status = 'active' (line 462)

## Workflow

### Cancellation Flow
1. Instructor clicks "Cancel" button on workshop
2. Modal opens with textarea for reason
3. Instructor submits cancellation request
4. Status changes to `pending_cancellation`
5. Admin receives email notification
6. Admin approves or rejects:
   - **Approve**: Status → `cancelled`, workshop preserved
   - **Reject**: Status → `active`, workshop continues

### Reassignment Flow
1. Admin clicks "Reassign" button on cancelled workshop
2. Modal opens with dropdown of available instructors
3. System checks for time conflicts:
   - Query finds instructors WITHOUT overlapping slots
   - Only conflict-free instructors shown in dropdown
4. Admin selects instructor and submits
5. Activity instructor updated
6. Slot status → `active`
7. New instructor receives email notification
8. Workshop appears in new instructor's dashboard

## Testing Checklist

- [ ] Instructor can request workshop cancellation
- [ ] Modal shows with textarea for reason
- [ ] Admin receives cancellation request email
- [ ] Admin sees cancellation in pending table
- [ ] Approve sets status to 'cancelled' (not delete)
- [ ] Cancelled workshop appears in separate table
- [ ] Cancelled workshop NOT in calendar
- [ ] Cancelled workshop NOT in instructor dashboard
- [ ] Reassign modal shows available instructors only
- [ ] Reassignment updates instructor and status
- [ ] New instructor receives notification email
- [ ] Reassigned workshop shows in new instructor dashboard

## Version
Updated in version 2.5.0

## Notes
- All modals use black/white theme matching site logo
- Currency format: ₹ (Indian Rupee) without decimals
- Email notifications sent for all workflow transitions
- Cancelled workshops retained for potential future use
