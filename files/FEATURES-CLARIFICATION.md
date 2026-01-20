# Waza Booking System - Feature Clarification & Implementation Plan

## 1. Multi-Seat Booking Logic (e.g., 10 seats)

### Current Implementation:
- When a user books 10 seats, ONE booking record is created with `attendees_count = 10`
- ONE user account is created for the person who made the booking
- ONE QR code is generated for the entire booking

### Recommended Implementation:

**Option A: Single Booking, Multiple Attendees (Current - Best for workshops)**
```
Booking #123
  - Main User: John Doe (john@example.com)
  - Attendees Count: 10
  - QR Code: One master QR with booking details
  - Attendee Names: Stored as JSON/separate table
```

**Advantages:**
- Simple for group bookings (families, corporate teams)
- One person manages the entire booking
- Easy refund/cancellation handling

**Disadvantages:**
- All attendees use same QR code
- Cannot track individual attendance

**Option B: Individual Seat Bookings (Recommended for better tracking)**
```
Booking #123 (Parent)
  ├── Seat 1: John Doe (primary, john@example.com)
  ├── Seat 2: Jane Doe
  ├── Seat 3: Bob Smith
  └── ... (10 seats total)
  
Each seat has:
  - Individual QR code
  - Individual attendance tracking
  - Optional individual account creation
```

## 2. Student Attendance System

### Complete Flow:

**Step 1: Student Arrives at Venue**
- Student shows QR code (from email/app/my-account page)
- QR contains: booking_id, qr_token, user_email, seat_number

**Step 2: Admin/Manager Scans QR Code**
- Scanner page decodes QR data
- System validates token and booking
- Displays student details:
  * Name, Photo, Email, Phone
  * Activity Name, Slot Time
  * Attendee Count
  * Previous attendance history

**Step 3: Admin Marks Entry**
- Admin clicks "Mark Entry" button
- System records:
  * `check_in_time` = current timestamp
  * `attended` = true
  * `attendance_log` = JSON with entry details

**Step 4: Automatic Exit**
- Cron job runs every 15 minutes
- Checks for slots that have ended
- Auto-marks exit for all attendees still "logged in"
- Records `check_out_time` = slot end_datetime

**Step 5: Student Views Attendance**
- In My Account > Attendance History
- Shows all bookings with:
  * Entry Time: 7:45 AM (Jan 6, 2026)
  * Exit Time: 9:30 AM (Auto)
  * Duration: 1h 45m
  * Status: Completed

## 3. Instructor Workshop Management

### Instructor Capabilities:

**A. Create Workshop/Activity**
- Instructor dashboard > Create New Activity
- Fill in: Title, Description, Category, Price, Duration
- Set availability schedule
- Submit for admin approval (if required)

**B. Manage Slots**
- View all slots for their activities
- Create recurring slots (e.g., every Monday 6-8 PM)
- Set capacity limits
- Enable/disable bookings

**C. View Students**
- See all students enrolled in their workshops
- Filter by: upcoming slots, past slots, specific activity
- Export student list

**D. Attendance Tracking**
- Real-time view of who checked in
- Mark attendance manually (backup to QR)
- Download attendance reports

## 4. Instructor Features Implementation

### Missing Features to Implement:

1. **Instructor Registration Flow**
   - Public registration form
   - Email verification
   - Admin approval workflow
   - Profile completion

2. **Instructor Dashboard**
   - My Activities (workshops)
   - My Slots (schedule)
   - Students & Attendance
   - Earnings Report

3. **Activity Management**
   - Create/Edit activities
   - Upload photos, videos
   - Set pricing tiers
   - Manage descriptions

4. **Slot Management**
   - Create individual slots
   - Create recurring patterns
   - Set capacity & pricing
   - View bookings per slot

5. **Student Management**
   - View enrolled students
   - Contact students
   - Download student lists
   - View attendance records

6. **Attendance Scanner**
   - QR scanner interface
   - Student detail popup
   - Quick check-in/check-out
   - Manual attendance entry

## Database Schema Changes Needed

### Add Attendance Tracking Table:
```sql
CREATE TABLE wp_waza_attendance (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    booking_id bigint(20) NOT NULL,
    slot_id bigint(20) NOT NULL,
    user_id bigint(20) NOT NULL,
    check_in_time datetime DEFAULT NULL,
    check_out_time datetime DEFAULT NULL,
    marked_by bigint(20) DEFAULT NULL,
    entry_method varchar(20) DEFAULT 'qr',
    exit_method varchar(20) DEFAULT 'auto',
    notes text DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY booking_id (booking_id),
    KEY slot_id (slot_id),
    KEY user_id (user_id)
);
```

### Add Attendees Table (for multi-seat bookings):
```sql
CREATE TABLE wp_waza_booking_attendees (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    booking_id bigint(20) NOT NULL,
    attendee_name varchar(100) NOT NULL,
    attendee_email varchar(100) DEFAULT NULL,
    attendee_phone varchar(20) DEFAULT NULL,
    seat_number int(11) NOT NULL,
    qr_token varchar(255) NOT NULL,
    user_id bigint(20) DEFAULT NULL,
    PRIMARY KEY (id),
    KEY booking_id (booking_id),
    UNIQUE KEY qr_token (qr_token)
);
```

## Implementation Priority

### Phase 1: Attendance System (Critical)
1. Add attendance table
2. Create QR scanner page
3. Implement check-in/check-out logic
4. Add attendance view for students

### Phase 2: Multi-Seat Booking
1. Add attendees table
2. Modify booking form for attendee details
3. Generate individual QR codes
4. Update email notifications

### Phase 3: Instructor Features
1. Fix instructor registration
2. Build instructor dashboard
3. Add activity management
4. Implement slot roster view
5. Add attendance tracking for instructors

### Phase 4: Enhancements
1. Auto-logout cron job
2. Attendance reports
3. Student analytics
4. Email reminders
