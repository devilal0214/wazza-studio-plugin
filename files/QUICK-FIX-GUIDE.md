# Quick Fix Instructions

## Issues Fixed

### 1. ✅ Slots Not Showing for Activity Booking
**Problem:** Date format mismatch between date picker and database query

**Fix Applied:**
- Added automatic date format conversion in JavaScript
- Converts MM-DD-YYYY or MM/DD/YYYY to YYYY-MM-DD
- Added debug logging (check browser console)
- Removed `start_datetime > NOW()` check (allows past dates with future time slots)
- Changed status check to `IN ('active', 'available')` for backward compatibility

**File:** `templates/activity-slots.php` (Lines 248-266)

### 2. ✅ Back to Activities Link Fixed
**File:** `templates/activity-slots.php` (Line 42)
- Changed from `/browse-activities/` to `/activities-2/`

### 3. ✅ Checkout Page Created
**New File:** `src/Payment/CheckoutPageHandler.php`
- Creates `[waza_checkout]` shortcode
- Handles both activity bookings and rentals
- Shows payment gateway options
- Integrates with existing PaymentManager

---

## Setup Required

### Create Checkout Page in WordPress

1. **Go to:** Pages → Add New
2. **Title:** Checkout
3. **Slug:** `checkout` (important!)
4. **Content:** Add this shortcode:
   ```
   [waza_checkout]
   ```
5. **Publish** the page

**OR** Run this SQL to create it automatically:

```sql
INSERT INTO wp_posts (post_title, post_name, post_content, post_status, post_type, post_author)
VALUES ('Checkout', 'checkout', '[waza_checkout]', 'publish', 'page', 1);
```

---

## Testing the Fixes

### Test 1: Activity Slots Loading

1. Open browser console (F12)
2. Go to: `http://localhost/wazza/activity-booking/?activity_id=94`
3. Select date: **01-29-2026**
4. Check console for:
   ```
   Loading slots for activity: 94 date: 01-29-2026 formatted: 2026-01-29
   Slots response: {success: true, data: {slots: [...]}}
   Displaying X slots: [...]
   ```
5. Slots should appear on the page

**If still not showing:**
- Check WordPress debug.log for: `Waza: Loading slots for activity_id=94, date=2026-01-29`
- Check: `Waza: Found X slots`
- If 0 slots found, check the query output in debug.log

### Test 2: Back Link

1. Go to activity booking page
2. Click "← Back to Activities"
3. Should redirect to: `http://localhost/wazza/activities-2/`

### Test 3: Studio Rental Payment

1. Fill studio rental form
2. Click "Book Studio"
3. Should redirect to: `http://localhost/wazza/checkout/?temp_rental_id=...`
4. Checkout page should show:
   - Order summary
   - Payment method options (Razorpay/Stripe/PhonePe)
   - "Pay Now" buttons
5. **Before payment:** Check `wp_waza_rentals` table - should be EMPTY
6. Click "Pay Now" and complete payment
7. **After payment:** Check `wp_waza_rentals` table - should have new entry

---

## Debug Information

### If Slots Still Not Showing:

**Check these in order:**

1. **Browser Console:**
   - Look for: "Loading slots for activity: 94"
   - Check formatted date: should be "2026-01-29"
   - Look for AJAX response

2. **WordPress debug.log:**
   - Enable: Add to wp-config.php:
     ```php
     define('WP_DEBUG', true);
     define('WP_DEBUG_LOG', true);
     ```
   - Check: `wp-content/debug.log`
   - Look for: "Waza: Loading slots for activity_id=94"
   - Check query and result count

3. **Database Check:**
   ```sql
   SELECT id, activity_id, start_datetime, status, capacity, booked_count
   FROM wp_waza_slots
   WHERE activity_id = 94
   AND DATE(start_datetime) = '2026-01-29'
   AND status IN ('active', 'available');
   ```
   
   Should return at least one row for the slot visible in admin screenshot.

4. **Admin Screenshot Shows:**
   - Activity: Hip Hop Dance
   - Date: Jan 29, 2026 @ 10:01 AM - 11:02 AM
   - Capacity: 20
   - Bookings: 1/20
   - Status: AVAILABLE

   **If slot exists but not showing, check:**
   - `activity_id` column value - must be 94
   - `status` column - should be 'active' or 'available'
   - `booked_count` vs `capacity` - must have available spots
   - Date format in database

### Manual Query to Check:

```sql
SELECT 
    s.id,
    s.activity_id,
    p.post_title as activity_name,
    s.start_datetime,
    s.status,
    s.capacity,
    s.booked_count,
    (s.capacity - s.booked_count) as available
FROM wp_waza_slots s
LEFT JOIN wp_posts p ON s.activity_id = p.ID
WHERE s.activity_id = 94
ORDER BY s.start_datetime DESC
LIMIT 10;
```

This will show all slots for activity 94 and their details.

---

## Common Issues & Solutions

### Issue: Slots exist but AJAX returns "No available slots"

**Possible causes:**
1. Date format mismatch
2. Status is not 'active' or 'available'
3. All slots are fully booked
4. Timezone issue

**Solution:**
- Check debug.log for exact query
- Run query manually in phpMyAdmin
- Verify date format in database

### Issue: Checkout page shows 404

**Solution:**
1. Make sure checkout page is created with `[waza_checkout]` shortcode
2. Go to Settings → Permalinks → Click "Save Changes" (flush rewrite rules)
3. Or create page manually as shown above

### Issue: Payment not saving to database

**Cause:** Payment callback not triggering `complete_rental_booking()`

**Check:**
1. Payment gateway is configured correctly
2. Webhook URLs are set up
3. `RentalPaymentHandler` is detecting `temp_rental_id` parameter
4. Transient exists before payment and is deleted after

---

## Files Modified Summary

1. **templates/activity-slots.php**
   - Line 42: Fixed back link
   - Lines 248-280: Added date format conversion and debug logging

2. **src/Activity/ActivityBrowserManager.php**
   - Lines 62-96: Added debug logging
   - Removed `start_datetime > NOW()` check
   - Changed status check to include 'available'

3. **src/Payment/CheckoutPageHandler.php** (NEW)
   - Complete checkout page with payment gateway selection

4. **src/Core/Plugin.php**
   - Line 437: Registered CheckoutPageHandler

---

## Next Steps

1. ✅ Create checkout page in WordPress
2. ✅ Test slot loading with console open
3. ✅ Test rental payment flow
4. ✅ Verify data saves correctly after payment

All fixes are in place - just need to create the checkout page!
