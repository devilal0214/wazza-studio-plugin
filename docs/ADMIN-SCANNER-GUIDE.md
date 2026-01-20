# Enhanced Attendance Scanner - Admin Guide

## 📱 Overview

The Enhanced Attendance Scanner is a comprehensive QR code scanning and manual booking system designed for marking student attendance. It provides multiple validation layers and detailed error messages to ensure accurate attendance tracking.

---

## 🚀 Access the Scanner

**Location**: WordPress Admin → Wazza Studio → 📱 Scanner

The scanner page displays real-time statistics and provides three modes of operation:
1. **QR Scanner** - Camera-based QR code scanning
2. **Manual Search** - Booking ID or token lookup
3. **Recent Scans** - Today's attendance history

---

## 📊 Dashboard Statistics

The top of the scanner page shows real-time stats:

- **✅ Checked In Today** - Total students marked present
- **👥 Expected Today** - Total bookings for today's slots
- **📊 Attendance Rate** - Percentage of expected vs checked in
- **🎯 Active Slots** - Number of workshops scheduled today

*Stats refresh automatically every 30 seconds*

---

## 🎯 QR Scanner Tab

### Starting the Scanner

1. Click the **Start Scanner** button
2. Allow camera permissions when prompted
3. Position the student's QR code within the frame
4. Scanner will automatically detect and process the code

### What Happens When Scanning

The system performs **5 comprehensive validations**:

#### ✅ Validation 1: Booking Exists
- Checks if the QR code or booking ID is valid
- **Error Message**: "❌ Booking Not Found"
- **Help**: Verify the booking ID or try scanning again

#### ✅ Validation 2: Payment Status
- Ensures payment is completed or marked as paid
- **Error Message**: "💳 Payment Not Completed"
- **Shows**: Payment status, amount due
- **Help**: Direct student to complete payment first

#### ✅ Validation 3: Booking Confirmation
- Verifies booking status is "confirmed"
- **Error Message**: "⚠️ Booking Not Confirmed"
- **Shows**: Current booking status
- **Help**: Admin needs to confirm the booking

#### ✅ Validation 4: Slot Timing
Checks if the slot is happening today:

**Past Slot:**
- **Error**: "⏰ This is a Past Slot"
- **Shows**: Scheduled date and time
- **Help**: Attendance can only be marked on the day of activity

**Future Slot:**
- **Error**: "📅 This is an Upcoming Slot"
- **Shows**: Scheduled date, time, days remaining
- **Help**: Come back on the scheduled day

**Current Slot (Today):**
- ✅ Proceeds to next validation

#### ✅ Validation 5: Attendance Capacity
For group bookings (multiple seats):

- **Already Full**: "✅ Attendance Already Completed"
- **Shows**: X of Y attendees marked, first check-in time
- **Help**: This booking has reached maximum capacity

- **Capacity Available**: Proceeds to mark attendance

---

## 🔍 Manual Search Tab

Use this when:
- QR code is damaged or unreadable
- Student doesn't have their QR code
- Camera is not available

### How to Use

1. Switch to "Manual Search" tab
2. Enter either:
   - **Booking ID** (e.g., 123)
   - **QR Token** (UUID string)
3. Click **Search** button
4. System performs same validations as QR scan

---

## ✅ Marking Attendance

### Success Screen Display

When all validations pass, you'll see:

#### Student Information
- Full name
- Email address
- Phone number

#### Booking Details
- Booking ID
- Activity name
- Slot date and time
- Payment status badge (green)
- Total amount paid

#### Group Booking Progress (if applicable)
- Progress bar showing completion
- "X of Y attendees checked in"
- "Y remaining" counter

### Confirming Attendance

1. Review all displayed information
2. Click **"Mark Attendance (X remaining)"** button
3. Wait for confirmation message

### Success Confirmation

After marking attendance:
- ✅ Green success message
- Student name displayed
- Check-in time recorded
- Group progress updated (if applicable)

**For Group Bookings:**
```
✅ Attendance Marked Successfully!
John Doe
Checked in at 9:15 AM

1 user checked in, 4 remaining for this booking
[Progress Bar: 20% filled]
```

**Auto-close**: Success screen disappears after 5 seconds

---

## 👥 Group Booking Example

**Scenario**: Booking for 5 seats (e.g., dance class)

### First Scan
```
Valid Booking - Ready to Mark Attendance

Group Booking Progress:
0 of 5 attendees checked in
[Progress Bar: 0%]
5 remaining

[Mark Attendance (5 remaining)]
```

### Second Scan (Same QR Code)
```
Group Booking Progress:
1 of 5 attendees checked in
[Progress Bar: 20%]
4 remaining

[Mark Attendance (4 remaining)]
```

### Process Repeats
Continue scanning the same QR code 5 times, each time marking one attendee.

### Final Scan (6th attempt)
```
❌ Attendance Already Completed

All 5 attendee(s) for this booking have been marked present.

Attended Count: 5 of 5
First Check-in: 9:15 AM
```

---

## ❌ Error Scenarios & Solutions

### 1. Payment Not Completed

**Display:**
```
💳 Payment Not Completed

Payment status: pending
Amount due: ₹500

Please complete payment before attending the activity.
```

**Action**: 
- Direct student to payment gateway
- Verify payment in bookings panel
- Rescan after payment confirmation

---

### 2. Past Slot

**Display:**
```
⏰ This is a Past Slot

This booking was for January 10, 2026 at 9:00 AM

Activity: Zumba Fitness
Scheduled Date: January 10, 2026
Scheduled Time: 9:00 AM

Attendance can only be marked on the day of the activity.
```

**Action**: 
- No attendance can be marked
- Student missed the class
- Check refund policy if applicable

---

### 3. Upcoming Slot

**Display:**
```
📅 This is an Upcoming Slot

This booking is for January 20, 2026 at 2:00 PM (7 days from now)

Activity: Photography Workshop
Scheduled Date: January 20, 2026
Scheduled Time: 2:00 PM
Days Remaining: 7

Please come back on the scheduled day to mark attendance.
```

**Action**: 
- Inform student to return on scheduled day
- Booking is valid, just too early

---

### 4. Booking Not Confirmed

**Display:**
```
⚠️ Booking Not Confirmed

Booking status: pending

Please contact admin to confirm this booking.
```

**Action**:
1. Go to Bookings panel
2. Find booking by ID
3. Change status to "Confirmed"
4. Rescan QR code

---

### 5. Invalid QR Code

**Display:**
```
❌ Booking Not Found

This QR code is invalid or the booking does not exist in our system.

Please verify the booking ID or try scanning again.
```

**Action**:
- QR code might be corrupted
- Use Manual Search with booking ID
- Verify booking exists in system

---

## 📋 Recent Scans Tab

View recent attendance activity:

**Table Columns:**
- Student name
- Activity name
- Check-in time
- Time ago (human-readable)

**Features:**
- Shows last 10 scans
- Auto-refreshes when tab is opened
- Helps track scanning activity

---

## 🎯 Best Practices

### For Single Bookings
1. Scan QR code once
2. Verify student details match
3. Click "Mark Attendance"
4. Wait for confirmation
5. Next student

### For Group Bookings
1. First student scans
2. Note "X remaining" count
3. Second student scans **same QR code**
4. Continue until all marked
5. System prevents over-marking

### Troubleshooting
- **Camera won't start**: Check browser permissions
- **QR won't scan**: Use Manual Search instead
- **Wrong student**: Cancel and rescan correct QR
- **Slow scanning**: Ensure good lighting

---

## 🔐 Security Features

✅ **Admin-only Access**: Requires `manage_waza_bookings` capability
✅ **Nonce Verification**: All AJAX requests validated
✅ **SQL Injection Prevention**: Prepared statements used
✅ **Duplicate Prevention**: 3-second cooldown between scans
✅ **Capacity Limits**: Can't exceed booking attendee count

---

## 📱 Technical Details

### AJAX Actions Used

1. **waza_scan_qr_code**
   - Validates scanned QR code
   - Returns booking details or error

2. **waza_search_booking**
   - Manual booking lookup
   - Same validation as QR scan

3. **waza_mark_single_attendance**
   - Records attendance in database
   - Updates booking status
   - Handles group booking count

4. **waza_get_today_stats**
   - Fetches real-time statistics
   - Returns recent scans list

### Database Updates

When attendance is marked:

**waza_attendance table:**
```sql
INSERT INTO wp_waza_attendance (
    booking_id,
    slot_id,
    user_id,
    attendance_status = 'present',
    check_in_time = NOW(),
    scanner_user_id = <admin_id>,
    entry_method = 'admin_scanner',
    scanner_device = 'admin_dashboard',
    ip_address,
    user_agent
)
```

**waza_bookings table:**
```sql
UPDATE wp_waza_bookings 
SET attended = 1, 
    attended_at = NOW()
WHERE id = <booking_id>
```

---

## 🚀 Quick Start Guide

**First Time Setup:**
1. Navigate to **Wazza Studio → 📱 Scanner**
2. Allow camera permissions
3. Click "Start Scanner"

**Daily Workflow:**
1. Open scanner page
2. Check today's stats
3. Start scanner
4. Students present QR codes
5. Review details before marking
6. Confirm attendance
7. Monitor recent scans

**End of Day:**
1. Check attendance rate
2. Review recent scans for accuracy
3. Export attendance report if needed

---

## ❓ FAQ

**Q: Can I mark attendance without QR code?**
A: Yes, use the Manual Search tab with booking ID.

**Q: What if I accidentally mark wrong student?**
A: Currently, attendance can't be unmarked. Contact developer for database edit.

**Q: How many times can I scan same QR for group booking?**
A: Up to the `attendees_count` value (e.g., 5 seats = 5 scans max).

**Q: Can students scan their own QR codes?**
A: No, scanner requires admin privileges for security.

**Q: Does it work offline?**
A: No, requires internet for database validation.

---

## 🎨 UI Color Codes

- **Green (#4caf50)**: Success, valid, present
- **Red (#f44336)**: Error, denied, failed
- **Blue (#2271b1)**: Information, action buttons
- **Orange (#FF9800)**: Warning, attention needed

---

## 📞 Support

For technical issues:
1. Check browser console for errors
2. Verify camera permissions
3. Test with manual search
4. Check booking exists and is confirmed
5. Verify payment status
6. Contact plugin developer if issues persist

---

**Last Updated**: January 13, 2026
**Version**: 1.0.0
**File**: EnhancedAttendanceScanner.php
