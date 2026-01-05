# Waza Booking - Complete Admin Features Guide

## Overview
This guide documents all admin features available in the Waza Booking Plugin v2.1.0, including the newly implemented features.

---

## 📋 Admin Menu Structure

After activating the plugin, you'll see the following menu items under **Waza Booking**:

### Main Menu Items:
1. **Dashboard** - Overview with stats and quick actions
2. **Time Slots** - Create and manage activity slots
3. **Instructors** - Manage instructor profiles
4. **Activities** - Manage activity types
5. **Bookings** - View all bookings
6. **Workshops** - View instructor-led workshops
7. **Announcements** - Create studio announcements
8. **Email Templates** - Customize email templates
9. **Customization** - Customize appearance
10. **Settings** - Configure all plugin settings
11. **QR Scanner** - Scan attendance QR codes

---

## 🎯 Feature 1: Time Slots with Instructor & Pricing

### Location: **Waza Booking > Time Slots**

### What's New:
- ✅ **Instructor Assignment**: Assign instructors to specific slots
- ✅ **Per-Slot Pricing**: Set custom prices for each slot (supports free slots)
- ✅ **Bulk Create Enhancement**: Instructor and price fields now in bulk creation

### How to Use:

#### Add Single Slot:
1. Go to **Waza Booking > Time Slots**
2. Click **Add Single Slot** tab
3. Fill in the form:
   - **Activity**: Select an activity type
   - **Date**: Choose the slot date
   - **Start Time / End Time**: Set time range
   - **Capacity**: Maximum attendees
   - **Instructor**: ⭐ Select an instructor (optional)
   - **Price**: ⭐ Set price (0.00 for free slots)
   - **Location**: Physical location
   - **Notes**: Internal notes
4. Click **Save Slot**

#### Bulk Create Slots:
1. Go to **Waza Booking > Time Slots**
2. Click **Bulk Create** tab
3. Configure:
   - **Activity**: Choose activity
   - **Date Range**: Start and end dates
   - **Days of Week**: Select days
   - **Time Slots**: Add multiple time ranges
   - **Capacity**: Default capacity
   - **Instructor**: ⭐ Assign to all slots
   - **Price**: ⭐ Set price for all slots
4. Click **Create Slots**

### Database Field:
- `waza_slots.instructor_id` (nullable, references wp_users.ID)
- `waza_slots.price` (decimal 10,2, default 0.00)

---

## 💼 Feature 2: Workshops Management

### Location: **Waza Booking > Workshops**

### What's New:
- ✅ Instructors can create workshops from frontend
- ✅ Unique invite links for student enrollment
- ✅ Master QR code for instructor verification
- ✅ Student roster viewing
- ✅ Free and paid workshop support

### Admin Features:
1. **View All Workshops**:
   - Workshop title and description
   - Assigned instructor
   - Activity and date/time
   - Max students
   - Pricing (Free/Paid)
   - Invite link

2. **Workshop Data**:
   - Created via AJAX endpoint `waza_create_workshop`
   - Stored in `waza_workshops` table
   - Linked to `waza_bookings` table

### Frontend Integration:
- Use shortcode: `[waza_workshop_invite]`
- Students join via invite URL: `/workshop/{token}`
- Instructors manage rosters via AJAX

### Database Tables:
- `waza_workshops` - Workshop metadata
- `waza_workshop_students` - Student enrollments

---

## 📢 Feature 3: Studio Announcements

### Location: **Waza Booking > Announcements**

### What's New:
- ✅ Create targeted announcements
- ✅ Priority levels (0-10)
- ✅ Announcement types: General, Important, Urgent
- ✅ Target audiences: All, Instructors, Students
- ✅ Start/expiry date scheduling
- ✅ Active/inactive status

### Admin Features:
1. **View Announcements**:
   - See all announcements with filters
   - Check status (Active/Inactive)
   - View target audience
   - See expiry dates

2. **Create Announcement**:
   - Title and message
   - Type (General/Important/Urgent)
   - Target (All/Instructors/Students)
   - Priority level
   - Start date (optional)
   - Expiry date (optional)

### Frontend Display:
- Use shortcode: `[waza_announcements]`
- Automatically shows active announcements
- Respects target audience

### Database Table:
- `waza_announcements`

---

## 💳 Feature 4: PhonePe Payment Gateway

### Location: **Waza Booking > Settings > Payment Settings**

### What's New:
- ✅ PhonePe integration (India's leading payment platform)
- ✅ Support for UPI, Cards, Wallets, Net Banking
- ✅ Sandbox and Production modes
- ✅ Automatic payment verification
- ✅ Refund support

### Configuration:
1. Go to **Settings > Payment Settings**
2. Find **PhonePe Settings** section
3. Configure:
   - ☑️ **Enable PhonePe**: Check to activate
   - **Merchant ID**: Your PhonePe merchant ID
   - **Salt Key**: Your PhonePe salt key
   - **Salt Index**: Usually "1"
   - **Payment Mode**: Sandbox or Live

### Features:
- Payment creation with SHA256 signature
- X-VERIFY header validation
- Webhook support
- Automatic refund processing

### Payment Flow:
1. User selects PhonePe at checkout
2. System creates payment order
3. User completes payment on PhonePe
4. System verifies payment callback
5. Booking confirmed

---

## 📱 Feature 5: SMS Notifications

### Location: **Waza Booking > Settings > Notifications**

### What's New:
- ✅ SMS via Twilio or TextLocal
- ✅ Booking confirmation SMS
- ✅ 24-hour reminder SMS
- ✅ 1-hour reminder SMS
- ✅ Cancellation notifications

### Configuration:

#### Twilio Setup:
1. Go to **Settings > Notifications**
2. Select **Twilio** as SMS Provider
3. Enter:
   - **Account SID**: From Twilio dashboard
   - **Auth Token**: From Twilio dashboard
   - **Phone Number**: Your Twilio number

#### TextLocal Setup (India):
1. Select **TextLocal** as SMS Provider
2. Enter:
   - **API Key**: From TextLocal account
   - **Sender ID**: Your approved sender ID

### SMS Templates:
- **Booking Confirmation**: Immediate notification
- **24h Reminder**: Sent 24 hours before activity
- **1h Reminder**: Sent 1 hour before activity
- **Cancellation**: Sent when booking cancelled

---

## 💸 Feature 6: Refund Management

### Location: **Waza Booking > Settings > Booking Policies**

### What's New:
- ✅ Automated refund eligibility checking
- ✅ Full refund window (configurable hours)
- ✅ Partial refund window (configurable hours + percentage)
- ✅ Gateway integration (Razorpay, Stripe, PhonePe)

### Configuration:
1. Go to **Settings > Booking Policies**
2. Set refund policies:
   - **Full Refund Hours**: Hours before activity for 100% refund (default: 48)
   - **Partial Refund Hours**: Hours before activity for partial refund (default: 24)
   - **Partial Refund Percentage**: Percentage refunded (default: 50%)

### Processing Refunds:
1. View booking in admin
2. System checks eligibility automatically
3. Admin can process:
   - Full refund
   - Partial refund
   - Custom amount refund
4. Refund sent to payment gateway
5. Booking status updated

### AJAX Endpoints:
- `waza_process_refund`
- `waza_calculate_refund`
- `waza_check_refund_eligibility`

---

## 🔄 Feature 7: Reschedule Functionality

### Location: **Booking management (frontend/backend)**

### What's New:
- ✅ Customer-initiated rescheduling
- ✅ Maximum reschedule limit per booking
- ✅ Deadline enforcement
- ✅ Automatic seat management
- ✅ Transactional updates

### Configuration:
1. Go to **Settings > Booking Policies**
2. Set:
   - **Reschedule Deadline Hours**: Minimum notice (default: 24)
   - **Max Reschedules Per Booking**: Maximum allowed (default: 2)

### How It Works:
1. Customer requests reschedule
2. System checks eligibility
3. Shows available alternative slots
4. Customer selects new slot
5. System updates:
   - Releases old slot seat
   - Reserves new slot seat
   - Updates booking record
   - Logs activity
6. Notifications sent

---

## 📊 Feature 8: CSV Export

### Location: **Various admin pages**

### What's New:
- ✅ Export attendance records
- ✅ Export slot rosters
- ✅ Export bookings report
- ✅ Date range filtering
- ✅ UTF-8 BOM (Excel compatible)

### Export Types:

#### 1. Attendance Export:
- URL: `admin-post.php?action=waza_export_attendance`
- Filters: Date range, slot
- Columns: Booking ID, User, Slot, Check-in time, Scanner

#### 2. Slot Roster Export:
- URL: `admin-post.php?action=waza_export_roster&slot_id={id}`
- Filters: Specific slot
- Columns: Attendee, Booking status, QR token, Attendance

#### 3. Bookings Export:
- URL: `admin-post.php?action=waza_export_bookings`
- Filters: Date range, status
- Columns: Booking #, User, Activity, Slot, Amount, Payment status

---

## 🔍 Feature 9: Master QR & Group QR

### Location: **QR generation (automatic)**

### What's New:
- ✅ Master QR for instructors (9999 uses)
- ✅ Group QR for choreographers (50+ members)
- ✅ Individual member QR codes
- ✅ Attendance tracking

### QR Token Types:
1. **Single** (1 use) - Regular booking
2. **Group** (50 uses) - Choreographer group
3. **Multi** (999 uses) - Multiple scans
4. **Master** (9999 uses) - Instructor verification

### Group QR Features:
- Choreographer gets master group QR
- Each student gets individual QR
- Automatic member numbering
- Group attendance percentage

### Database Tables:
- `waza_qr_groups` - Group master records
- `waza_qr_group_members` - Member QRs

---

## 📅 Feature 10: Interactive Calendar View

### Location: **Settings > Calendar Settings** + Frontend

### What's New:
- ✅ Month/Week/Day view switcher
- ✅ Customizable colors
- ✅ Show/hide instructor
- ✅ Show/hide price
- ✅ Configurable week start day
- ✅ Time format selection
- ✅ Maximum slots per day

### Configuration:
1. Go to **Settings > Calendar Settings**
2. Customize:
   - **Primary Color**: Calendar theme color
   - **Start of Week**: Monday or Sunday
   - **Time Format**: 12-hour or 24-hour
   - ☑️ **Show Instructor**: Display instructor names
   - ☑️ **Show Price**: Display slot prices
   - **Max Slots Per Day**: Limit displayed slots

### Frontend Display:
- Use shortcode: `[waza_calendar]`
- Features:
  - View switching (Month/Week/Day)
  - Slot filtering by activity
  - Click slot for details
  - Responsive design

---

## 📝 Feature 11: Activity Logs

### Location: **Database logging (automatic)**

### What's New:
- ✅ Complete audit trail
- ✅ User action tracking
- ✅ IP and user agent logging
- ✅ Metadata storage (JSON)
- ✅ Searchable logs

### Logged Actions:
- Booking created/cancelled/rescheduled
- Refunds processed
- QR code scans
- User logins (instructors/admins)
- Payment transactions
- All critical actions

### Database Table:
- `waza_activity_logs`

### AJAX Endpoint:
- `waza_get_logs` (filtered retrieval)

---

## 🛠️ Quick Setup Checklist

### Initial Setup:
- [ ] Configure Payment Gateways (Settings > Payment Settings)
  - [ ] Razorpay (optional)
  - [ ] Stripe (optional)
  - [ ] PhonePe (optional)
- [ ] Setup SMS Notifications (Settings > Notifications)
  - [ ] Choose provider (Twilio/TextLocal)
  - [ ] Enter credentials
- [ ] Configure Refund Policies (Settings > Booking Policies)
- [ ] Setup Calendar Settings (Settings > Calendar Settings)

### Content Creation:
- [ ] Create Activities (Activities menu)
- [ ] Add Instructors (Instructors menu)
- [ ] Create Time Slots (Time Slots > Add Slot)
- [ ] Create Announcements (Announcements menu)

### Frontend Integration:
- [ ] Add `[waza_calendar]` shortcode to a page
- [ ] Add `[waza_announcements]` shortcode where needed
- [ ] Add `[waza_workshop_invite]` for instructor access
- [ ] Add `[waza_booking_confirmation]` for confirmation page

---

## 🔐 Permissions & Roles

### Custom Capabilities:
- `manage_waza` - Full admin access
- `edit_waza_slots` - Create/edit slots
- `view_waza_bookings` - View bookings

### User Roles:
- **Administrator** - Full access
- **Waza Instructor** - Can create workshops, manage assigned slots
- **Customer** - Can make bookings

---

## 📞 Support & Documentation

### Shortcodes:
- `[waza_calendar]` - Interactive calendar view
- `[waza_announcements]` - Display active announcements
- `[waza_workshop_invite]` - Workshop creation form
- `[waza_booking_confirmation]` - Booking confirmation page

### AJAX Endpoints:
All AJAX actions prefixed with `waza_`:
- Slot management: `waza_save_slot`, `waza_update_slot`, `waza_bulk_create_slots`
- Workshops: `waza_create_workshop`, `waza_get_workshop_roster`
- Announcements: `waza_create_announcement`, `waza_get_announcements`
- Refunds: `waza_process_refund`, `waza_check_refund_eligibility`
- Reschedule: `waza_process_reschedule`, `waza_get_available_slots`

### Database Tables (New):
- `waza_workshops` - Workshop details
- `waza_workshop_students` - Student enrollments
- `waza_announcements` - Studio announcements
- `waza_activity_logs` - Audit trail
- `waza_qr_groups` - Group QR master
- `waza_qr_group_members` - Group members

### Database Changes:
- `waza_slots.instructor_id` (added)
- `waza_slots.price` (added)

---

## 🎉 Summary

**All Features Implemented:**
- ✅ Time slots with instructor assignment and pricing
- ✅ Interactive calendar view with customization
- ✅ Workshop management system
- ✅ Studio announcements
- ✅ PhonePe payment gateway
- ✅ SMS notifications (Twilio/TextLocal)
- ✅ Refund management
- ✅ Reschedule functionality
- ✅ CSV exports
- ✅ Master QR for instructors
- ✅ Group QR for choreographers
- ✅ Activity logging

**Admin Menus Available:**
1. Dashboard ✅
2. Time Slots ✅
3. Workshops ✅
4. Announcements ✅
5. Settings (with all new sections) ✅
6. QR Scanner ✅

**Version:** 2.1.0
**Last Updated:** January 2, 2026
**Status:** All Features Complete ✅
