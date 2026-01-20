# 🚀 INSTALLATION & GO-LIVE CHECKLIST

## Pre-Installation Verification ✓

- [ ] WordPress version 5.8 or higher
- [ ] PHP version 7.4 or higher
- [ ] MySQL version 5.7 or higher
- [ ] Write permissions on `wp-content/uploads/`
- [ ] Existing Waza Booking plugin is active and working
- [ ] Backup of database completed
- [ ] Backup of plugin files completed

---

## Installation Steps 📦

### Step 1: File Upload
- [ ] All new files uploaded to plugin directory
- [ ] Verify file structure matches documentation
- [ ] Check file permissions (644 for files, 755 for folders)

**New Files to Verify:**
```
✓ src/Activity/ActivityBrowserManager.php
✓ src/Rental/RentalManager.php
✓ src/Admin/RentalAdminManager.php
✓ templates/activity-browser.php
✓ templates/activity-slots.php
✓ templates/studio-rental.php
✓ setup-new-features.php
```

**Updated Files:**
```
✓ src/Core/Plugin.php
```

### Step 2: Database Setup
- [ ] Access WordPress admin
- [ ] Navigate to any page (this triggers database table creation)
- [ ] Verify tables exist:
  - [ ] `wp_waza_rentals`
  - [ ] `wp_waza_slots` (existing)
  - [ ] `wp_waza_bookings` (existing)

**Verify Database:**
```sql
SHOW TABLES LIKE 'wp_waza_rentals';
DESCRIBE wp_waza_rentals;
```

### Step 3: Run Setup Wizard
- [ ] Access: `yoursite.com/wp-content/plugins/waza-studio-app/setup-new-features.php`
- [ ] Click "Create Pages" button
- [ ] Click "Create Sample Activities" button
- [ ] Click "Verify Database" button
- [ ] Confirm all checks pass

**Pages Created:**
- [ ] `/activities/` - Browse Activities
- [ ] `/activity-booking/` - Book Activity
- [ ] `/studio-rental/` - Studio Rental

### Step 4: WordPress Configuration
- [ ] Add new pages to main navigation menu
- [ ] Set page templates (if your theme requires)
- [ ] Test permalink structure (visit pages)
- [ ] Verify shortcodes render properly

---

## Feature Configuration ⚙️

### Activity System

#### Create Real Activities (Replace Samples)
- [ ] Go to Dashboard → Activities
- [ ] Delete sample activities (or keep for testing)
- [ ] Create your actual activities:
  - [ ] Add title and description
  - [ ] Set featured image (recommended 800x600px)
  - [ ] Add price in meta field
  - [ ] Set duration in minutes
  - [ ] Assign category (taxonomy)
  - [ ] Set activity slug (URL-friendly)
  - [ ] Publish

**Activity Meta Fields to Fill:**
```
_waza_activity_price = 500
_waza_activity_duration = 60
_waza_activity_slug = zumba
_waza_activity_rating = 0 (will update automatically)
_waza_booking_count = 0 (will update automatically)
```

#### Link Activities to Slots
- [ ] Ensure existing slots have `activity_type` field
- [ ] Match slot `activity_type` to activity `_waza_activity_slug`
- [ ] Verify slots appear on activity booking page

**Example:**
```
Activity: Zumba Dance Class
Meta: _waza_activity_slug = 'zumba'

Slot:
activity_type = 'zumba' ✓ MATCHES
```

### Rental System

#### Pricing Configuration
- [ ] Review default pricing in `RentalManager.php`
- [ ] Adjust if needed (lines 19-35)
- [ ] Confirm studio hours (10 AM - 10 PM)
- [ ] Set advance payment policy

#### Email Configuration
- [ ] Verify admin email in Settings → General
- [ ] Test rental notification emails
- [ ] Check spam folders
- [ ] Consider email SMTP plugin for reliability

#### QR Code Setup
- [ ] Check folder exists: `wp-content/uploads/waza-qr/`
- [ ] Verify write permissions (755)
- [ ] Test QR generation with sample rental
- [ ] Ensure QR scanner app/system ready

---

## Integration Testing 🧪

### Activity Booking Flow
1. [ ] **Browse Page**
   - Visit `/activities/`
   - Verify activities display
   - Test search function
   - Test category filter
   - Test sort options
   - Check responsive design

2. [ ] **Activity Selection**
   - Click on activity card
   - Verify redirect to booking page
   - Check activity details display
   - Confirm pricing shows

3. [ ] **Date Selection**
   - Pick future date
   - Verify slots load
   - Check "no slots" message if none available
   - Test different dates

4. [ ] **Slot Selection**
   - Click on available slot
   - Verify summary updates
   - Check time displayed correctly
   - Confirm instructor name shows

5. [ ] **Booking Details**
   - Fill in all fields
   - Test form validation
   - Check summary accuracy
   - Verify special notes field works

6. [ ] **Payment Redirect**
   - Submit form
   - Verify redirect to payment page
   - Check parameters passed correctly
   - Confirm booking created in database

### Studio Rental Flow
1. [ ] **Pricing Display**
   - Visit `/studio-rental/`
   - Verify all 3 pricing cards show
   - Check icons display (🎭 🎥 📢)
   - Confirm rates match specification
   - Test responsive layout

2. [ ] **Form Functionality**
   - Fill personal information
   - Select rental type
   - Choose duration
   - Pick date (must be future)
   - Set time (10 AM - 10 PM only)
   - Verify amount calculates correctly

3. [ ] **Availability Check**
   - Click "Check Availability"
   - Test with available time → Should pass ✅
   - Test with booked time → Should fail ❌
   - Test with class time → Should fail ❌
   - Verify clear error messages

4. [ ] **Booking Submission**
   - Submit form after availability check
   - Verify success message
   - Check QR code generated
   - Confirm admin email received
   - Test payment redirect

5. [ ] **Conflict Detection**
   - Create slot at 2 PM - 3 PM
   - Try booking rental 2:30 PM - 4:30 PM
   - Should block rental ✅
   - Try different time → Should allow ✅

### Admin Management
1. [ ] **Rentals Dashboard**
   - Go to Dashboard → Studio Rentals
   - Verify list displays
   - Check all columns show data
   - Test status filters
   - Try search function

2. [ ] **Rental Actions**
   - Update rental status
   - Download QR code
   - View rental details
   - Check timestamps correct

3. [ ] **Activities Management**
   - Go to Dashboard → Activities
   - Create new activity
   - Edit existing activity
   - Set meta fields
   - Add featured image

---

## Security Checklist 🔒

- [ ] Delete `setup-new-features.php` after setup
- [ ] Verify AJAX nonces working
- [ ] Test as non-admin user
- [ ] Check SQL injection protection
- [ ] Verify XSS protection on outputs
- [ ] Test file upload permissions
- [ ] Review error logs for issues

---

## Performance Testing ⚡

- [ ] Test page load times
- [ ] Check AJAX response times
- [ ] Verify image optimization
- [ ] Test with 50+ activities
- [ ] Check mobile performance
- [ ] Test concurrent bookings
- [ ] Monitor database queries

---

## Mobile Responsiveness 📱

- [ ] Test on iPhone (Safari)
- [ ] Test on Android (Chrome)
- [ ] Verify forms work on mobile
- [ ] Check date/time pickers
- [ ] Test payment flow on mobile
- [ ] Verify QR codes scan properly

---

## Browser Compatibility 🌐

- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] Mobile browsers

---

## Documentation Review 📚

- [ ] Read `NEW-FEATURES-GUIDE.md`
- [ ] Review `QUICK-REFERENCE.md`
- [ ] Check `IMPLEMENTATION-SUMMARY.md`
- [ ] Keep docs accessible for team

---

## Training & Handoff 👥

### Admin Training
- [ ] Show how to manage rentals
- [ ] Demonstrate activity creation
- [ ] Explain status updates
- [ ] Review email notifications
- [ ] Practice QR code download
- [ ] Walk through reports/data

### User Communication
- [ ] Announce new features
- [ ] Create user guide/video
- [ ] Update website navigation
- [ ] Add call-to-actions
- [ ] Promote on social media

---

## Go-Live Checklist ✈️

### Final Verifications
- [ ] All sample data removed (or kept intentionally)
- [ ] Real activities created
- [ ] All pages published
- [ ] Menu links active
- [ ] SSL certificate valid
- [ ] Error logs clean
- [ ] Payment gateway tested
- [ ] Email notifications working

### Monitoring Setup
- [ ] Enable error logging
- [ ] Set up analytics tracking
- [ ] Monitor first bookings
- [ ] Check admin emails
- [ ] Watch for user feedback

### Backup & Rollback Plan
- [ ] Final pre-launch backup
- [ ] Document rollback steps
- [ ] Keep previous version accessible
- [ ] Test restore process

---

## Post-Launch Tasks 📊

### Week 1
- [ ] Monitor all bookings closely
- [ ] Check for errors daily
- [ ] Respond to user questions
- [ ] Gather initial feedback
- [ ] Review email deliverability

### Week 2-4
- [ ] Analyze booking patterns
- [ ] Review popular activities
- [ ] Check rental utilization
- [ ] Optimize if needed
- [ ] Plan improvements

### Monthly
- [ ] Review revenue reports
- [ ] Update pricing if needed
- [ ] Add new activities
- [ ] Archive old bookings
- [ ] Clean up test data

---

## Troubleshooting Quick Reference 🔧

### Activities Not Showing
```bash
# Check post type
SELECT * FROM wp_posts WHERE post_type = 'waza_activity';

# Verify meta fields
SELECT * FROM wp_postmeta WHERE meta_key LIKE '_waza_activity_%';
```

### Slots Not Loading
```bash
# Check slot activity types
SELECT DISTINCT activity_type FROM wp_waza_slots;

# Match with activity slugs
SELECT meta_value FROM wp_postmeta WHERE meta_key = '_waza_activity_slug';
```

### Rentals Table Missing
```bash
# Check table exists
SHOW TABLES LIKE 'wp_waza_rentals';

# Create manually if needed
# See NEW-FEATURES-GUIDE.md for SQL
```

### QR Codes Not Working
```bash
# Check folder
ls -la wp-content/uploads/waza-qr/

# Set permissions
chmod 755 wp-content/uploads/waza-qr/
```

---

## Support Contacts 📞

**Technical Issues:**
- Check: `wp-content/debug.log`
- Review: Browser console (F12)
- Search: Error in `NEW-FEATURES-GUIDE.md`

**Documentation:**
- Full Guide: `NEW-FEATURES-GUIDE.md`
- Quick Ref: `QUICK-REFERENCE.md`
- Summary: `IMPLEMENTATION-SUMMARY.md`

---

## Success Criteria ✨

### Must Have
- ✅ All pages accessible
- ✅ Activities browseable
- ✅ Bookings work end-to-end
- ✅ Rentals process successfully
- ✅ QR codes generate
- ✅ Admin can manage bookings
- ✅ No console errors
- ✅ Mobile responsive

### Nice to Have
- ⭐ Featured images on all activities
- ⭐ Custom pricing for special events
- ⭐ Email templates customized
- ⭐ Analytics integrated
- ⭐ User testimonials

---

## Launch Announcement Template 📣

```
🎉 Exciting News! 

We're thrilled to announce TWO amazing new features at Waza Studio:

🎯 Browse & Book Activities
Explore our full range of classes - from Zumba to Yoga, Martial Arts to Aerobics. 
Book your perfect session in just a few clicks!
👉 [Link to /activities/]

🏢 Studio Rental
Need our space for your rehearsal, shoot, or event? 
Check out our competitive rates and book instantly!
👉 [Link to /studio-rental/]

Experience the new booking flow today! 🚀
```

---

**Status:** Ready for Launch ✅  
**Version:** 1.0.0  
**Date:** January 16, 2026

**Final Check:** Have you completed ALL items above? ☑️
