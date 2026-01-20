# Quick Testing Guide for Frontend Features

## 🧪 Testing Instructions

### 1. Interactive Calendar Testing

**Location:** Page with `[waza_calendar]` shortcode

**Tests:**
1. **Past Dates Disabled:**
   - Open calendar
   - Verify all dates before today are grayed out
   - Try clicking a past date → Should not be clickable

2. **Green Slots Indicator:**
   - Look for dates with available slots
   - These should have a **light green background** (#D1FAE5)
   - Hover over them → Should turn to a darker green (#A7F3D0)

3. **Month Navigation:**
   - Click "Next" button → Calendar should show next month
   - Click "Previous" button → Calendar should show previous month

4. **Slot Modal:**
   - Click on a green (available) date
   - Modal should open showing time slots
   - Each slot should show: Activity name, Time, Instructor, Availability

---

### 2. Booking Form Testing

**Location:** Click on an available slot from calendar

**Test Scenarios:**

#### A. Guest Booking (No Account)
1. Fill in name, email, phone
2. Leave "Create an account" **unchecked**
3. Select payment method
4. Submit form
5. ✅ Expected: Booking created, no user account created

#### B. Account Creation - Auto Password
1. Fill in name, email, phone
2. **Check** "Create an account"
3. Password options should appear
4. Select "Generate password automatically..."
5. Submit form
6. ✅ Expected: 
   - Account created
   - Email sent with credentials
   - Check email inbox for password

#### C. Account Creation - Manual Password
1. Fill in name, email, phone
2. **Check** "Create an account"
3. Select "Set my own password"
4. Password field should appear
5. Enter password (min 8 characters)
6. Submit form
7. ✅ Expected:
   - Account created
   - Can login with the password you set

#### D. Password Validation
1. Check "Create an account"
2. Select "Set my own password"
3. Enter password less than 8 characters (e.g., "test123")
4. Submit form
5. ✅ Expected: Error message "Password must be at least 8 characters long"

---

### 3. Payment & Confirmation Testing

**After Successful Booking:**

1. **Payment Page:**
   - Should redirect to PhonePe payment page (if amount > 0)
   - OR show success message (if free booking)

2. **QR Code:**
   - After payment success, QR code should be displayed
   - QR code format: `WAZA-BOOKING-{id}`
   - Should be 150x150px image

3. **Confirmation Email:**
   - Check email inbox
   - Should receive booking confirmation email
   - Should contain booking details

---

### 4. Instructor Registration Testing

**Location:** Page with `[waza_instructor_register]` shortcode

**Full Flow Test:**

1. **Registration:**
   - Fill in name, email, phone, password
   - Fill in skills (comma-separated, e.g., "Yoga, Pilates, Zumba")
   - Select rating (2, 3, or 5 stars)
   - Click "Register as Instructor"
   - ✅ Expected: Success message "Please check your email to verify..."

2. **Email Verification:**
   - Check email inbox
   - Should receive "Verify Your Instructor Account" email
   - Click verification link
   - ✅ Expected: 
     - Success page "Email verified successfully!"
     - Message about pending admin approval

3. **Admin Notification:**
   - Admin should receive email
   - Subject: "New Instructor Pending Approval"
   - Should contain instructor name and review link

4. **Admin Approval:**
   - Login as admin
   - Go to WP Admin → Instructors
   - Find the new instructor (status: Pending)
   - Verify email_verified meta = '1'
   - Click "Publish" to approve
   - ✅ Expected: Instructor status changes to Published

5. **Instructor Login:**
   - After approval, instructor can login
   - Should see instructor dashboard
   - Can view assigned slots and bookings

---

### 5. Email Verification Edge Cases

**Test Double Verification:**
1. Click verification link once → Success
2. Click same link again → Should show "Email already verified"

**Test Invalid Token:**
1. Modify token in URL
2. Click modified link
3. ✅ Expected: "Invalid or expired verification token"

**Test Invalid Email:**
1. Modify email in URL
2. Click modified link
3. ✅ Expected: "User not found"

---

## 🐛 Common Issues & Solutions

### Issue: Calendar not loading
**Solution:** 
- Check browser console for JavaScript errors
- Verify `waza_frontend` object is loaded
- Check AJAX URL in console

### Issue: Green color not showing
**Solution:**
- Hard refresh browser (Ctrl+F5)
- Clear browser cache
- Verify CSS file is loaded
- Check if slots exist in database

### Issue: Email not sending
**Solution:**
- Check WordPress email settings
- Install WP Mail SMTP plugin
- Check spam folder
- Enable WP_DEBUG_LOG and check logs

### Issue: Password field not appearing
**Solution:**
- Check browser console for JavaScript errors
- Hard refresh page
- Verify jQuery is loaded

### Issue: Verification link not working
**Solution:**
- Check if `template_redirect` hook is firing
- Verify URL format is correct
- Check user meta for verification token

---

## 📊 Database Verification

### Check Booking Created:
```sql
SELECT * FROM wp_waza_bookings ORDER BY id DESC LIMIT 1;
```

### Check User Account Created:
```sql
SELECT * FROM wp_users WHERE user_email = 'test@example.com';
```

### Check User Meta (Verification Token):
```sql
SELECT * FROM wp_usermeta 
WHERE meta_key IN ('waza_email_verification_token', 'waza_email_verified') 
AND user_id = {USER_ID};
```

### Check Instructor Post:
```sql
SELECT * FROM wp_posts 
WHERE post_type = 'waza_instructor' 
ORDER BY post_date DESC LIMIT 1;
```

### Check Instructor Email Verified Meta:
```sql
SELECT * FROM wp_postmeta 
WHERE meta_key = '_waza_email_verified' 
AND post_id = {INSTRUCTOR_ID};
```

---

## ✅ Feature Checklist

- [ ] Calendar shows green for available slots
- [ ] Past dates are disabled
- [ ] Slot modal opens on click
- [ ] Account creation option appears
- [ ] Password auto-generation works
- [ ] Manual password setting works
- [ ] Password validation (min 8 chars) works
- [ ] Booking creates successfully
- [ ] QR code generates
- [ ] Confirmation email received
- [ ] Credentials email received (auto-password)
- [ ] Instructor registration works
- [ ] Verification email sent
- [ ] Email verification link works
- [ ] Admin notification received
- [ ] Instructor approval workflow works

---

## 📧 Test Emails Checklist

1. [ ] Account credentials email (auto-password)
2. [ ] Instructor verification email
3. [ ] Admin notification email (new instructor)
4. [ ] Booking confirmation email

---

## 🎯 Success Criteria

**All features working if:**
1. Calendar displays correctly with green/gray colors
2. Users can book with or without account creation
3. Password options work (auto/manual)
4. Payments process successfully
5. QR codes generate after booking
6. Emails are delivered
7. Instructor registration requires email verification
8. Admin receives notification for new instructors
9. Instructor can login after approval

---

## 🚀 Go Live Checklist

Before launching:
- [ ] Test all features on staging
- [ ] Configure real payment gateway credentials
- [ ] Set up SMTP for reliable email delivery
- [ ] Test email templates and delivery
- [ ] Remove test bookings from database
- [ ] Set payment mode to "production"
- [ ] Add terms & conditions page
- [ ] Configure privacy policy
- [ ] Test mobile responsiveness
- [ ] Set up backup schedule
- [ ] Monitor error logs

---

**Happy Testing! 🎉**
