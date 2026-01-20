# Booking Flows - Modal Popup vs Activity Browser

## Two Independent Booking Systems

Your plugin now has **TWO separate booking flows** that work independently:

---

## 1. **Modal Popup Booking** (Original System)
**Used for:** Existing calendar-based booking flow

### How It Works:
1. User visits a page with activity calendar
2. Clicks on a date to see available slots
3. Slots appear in a modal popup
4. Clicks "Book Now" on a slot
5. Multi-step booking form appears in the same modal
6. Completes booking and payment

### Files Involved:
- **AJAX Handler:** `src/Frontend/AjaxHandler.php`
  - `load_day_slots()` - Loads slots for selected date
  - `load_booking_form()` - Shows booking form modal
  - `process_booking()` - Processes booking
- **Frontend JS:** `assets/frontend.js` - Modal interactions
- **Shortcode:** Uses existing `[waza_calendar]` shortcode

### Slot Status: 
- Checks for both `active` and `available` status
- Uses `capacity` and `booked_count` from slots table

---

## 2. **Activity Browser Booking** (New Feature)
**Used for:** BookMyShow-style activity browsing experience

### How It Works:
1. User visits `/browse-activities/` page
2. Sees grid of all activities with filters
3. Searches/filters by category, rating, etc.
4. Clicks "Book Now" on an activity
5. Redirected to `/activity-booking/?activity_id=XX`
6. Sees 4-step booking wizard (Select Date → Select Slot → Details → Payment)
7. Completes booking

### Files Involved:
- **Manager:** `src/Activity/ActivityBrowserManager.php`
  - `filter_activities()` - AJAX handler for filtering
  - `get_activity_slots()` - AJAX handler for slot loading
- **Templates:**
  - `templates/activity-browser.php` - Activity grid page
  - `templates/activity-slots.php` - 4-step booking wizard
- **Frontend JS:** Built into templates with AJAX calls

### Slot Status:
- Checks for `active` status only
- Uses `capacity` and `booked_count` from slots table
- Links slots via `activity_id` column

---

## Key Differences

| Feature | Modal Popup | Activity Browser |
|---------|-------------|------------------|
| **Entry Point** | Calendar shortcode | Browse activities page |
| **UI Style** | Modal/Overlay | Full page wizard |
| **Flow** | Date → Slots → Book | Activity → Date → Slot → Book |
| **AJAX Endpoint** | `waza_load_day_slots` | `waza_get_activity_slots` |
| **Use Case** | Quick booking for known date | Browse & discover activities |
| **Steps** | 5 steps in modal | 4 steps in page |

---

## Important Notes

### They Don't Conflict:
- **Different AJAX actions** - No endpoint overlap
- **Different templates** - Separate UI files
- **Same booking table** - Both create records in `wp_waza_bookings`
- **Same payment flow** - Both use PaymentManager

### Shared Components:
- ✅ Both use `wp_waza_slots` table
- ✅ Both check availability same way
- ✅ Both create bookings in `wp_waza_bookings`
- ✅ Both generate QR codes
- ✅ Both send confirmation emails
- ✅ Both work with same payment gateways

---

## Recent Fixes Applied

### Modal Popup Issues Fixed:
1. ✅ **Slot availability check** - Now accepts both 'active' and 'available' status
2. ✅ **"Slot not available" error** - Fixed status mismatch in queries
3. ✅ **get_slot_details()** - Updated to check IN ('active', 'available')
4. ✅ **check_slot_availability()** - Updated to check IN ('active', 'available')
5. ✅ **process_booking()** - Updated slot query status check

### Activity Browser Fixed:
1. ✅ Uses `activity_id` column (not `activity_type`)
2. ✅ Uses `capacity` field (not `max_participants`)
3. ✅ Properly queries existing slots table structure
4. ✅ Booking count calculated from actual `booked_count`

### Sample Slots Creator Fixed:
1. ✅ Gets ALL instructors (not just one)
2. ✅ Rotates through instructors for variety
3. ✅ Creates slots with `activity_id` and `instructor_id`
4. ✅ Sets status as 'active'
5. ✅ Shows error if no instructors exist

---

## Testing Instructions

### Test Modal Popup:
1. Add `[waza_calendar]` shortcode to a page
2. Click on a future date
3. Verify slots appear in modal
4. Click "Book Now" - should show booking form (NOT "Slot not available")
5. Complete booking process

### Test Activity Browser:
1. Visit `/browse-activities/`
2. See all activities in grid
3. Use filters/search
4. Click "Book Now" on an activity
5. Should redirect to booking wizard
6. Select date, then slot
7. Complete booking

### Create Sample Data:
1. Visit: `http://localhost/wazza/wp-content/plugins/waza-studio-app/create-sample-slots.php`
2. Should create 21 slots per activity (7 days × 3 slots)
3. Each slot will have a different instructor (rotated)
4. Check slots table - should see new records with:
   - `activity_id` pointing to your activities
   - `instructor_id` pointing to instructors
   - `status` = 'active'
   - `capacity` = 20
   - `booked_count` = 0

---

## Usage Recommendations

Use **Modal Popup** when:
- Customer knows their preferred date
- Quick booking is priority
- Embedded in activity detail pages
- Mobile-friendly overlay needed

Use **Activity Browser** when:
- Customer wants to explore options
- Discovery and browsing is important
- Desktop/tablet experience
- Want to showcase all activities

---

## Need Help?

If bookings aren't working:
1. Check slot status in database (should be 'active' or 'available')
2. Verify `capacity > booked_count` for slots
3. Check browser console for AJAX errors
4. Ensure nonce is valid
5. Check if payment gateway is enabled
