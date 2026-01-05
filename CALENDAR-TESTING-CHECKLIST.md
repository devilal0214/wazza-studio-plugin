# Calendar Testing Checklist ✅

## Pre-Testing Setup

### 1. Clear Browser Cache
- [ ] Press `Ctrl + Shift + Delete`
- [ ] Select "Cached images and files"
- [ ] Click "Clear data"

### 2. Verify Plugin is Active
- [ ] Go to WordPress Admin → Plugins
- [ ] Confirm "Waza Booking" is activated
- [ ] Check for any error messages

### 3. Verify Slots Exist
- [ ] Go to WordPress Admin → Waza Slots
- [ ] Confirm at least 5-10 slots exist with future dates
- [ ] If not, run `seed_slots.php` to create test data

## Calendar Preview Testing

### Test 1: JavaScript Localization ✅
**Expected:** waza_frontend object loaded with all required properties

**Steps:**
1. Open test page with calendar shortcode `[waza_booking_calendar]`
2. Press F12 to open browser console
3. Type: `console.log(waza_frontend)`
4. Press Enter

**Success Criteria:**
- [ ] Object exists (not undefined)
- [ ] Contains `ajax_url` property
- [ ] Contains `nonce` property  
- [ ] Contains `calendar_settings` object with 6 properties

**If Failed:**
- Check that FrontendManager.php uses `waza_frontend` not `wazaBooking`
- Verify `ajax_url` is in localization array
- Clear cache and reload

### Test 2: Calendar Grid Renders ✅
**Expected:** Calendar displays current month with day numbers

**Steps:**
1. Navigate to page with `[waza_booking_calendar]` shortcode
2. Wait for page to fully load
3. Look for calendar grid

**Success Criteria:**
- [ ] Calendar header shows current month name (e.g., "January 2025")
- [ ] Grid displays 7 columns (days of week)
- [ ] All day numbers are visible
- [ ] Days with available slots have GREEN background (#D1FAE5)
- [ ] No JavaScript errors in console

**If Failed:**
- Check if `waza-calendar-grid` div exists in HTML
- Verify CSS file is loaded (check Network tab)
- Look for AJAX errors in console

### Test 3: Navigation Buttons Work ✅
**Expected:** Previous/Next buttons change the displayed month

**Steps:**
1. Note the current month displayed
2. Click the "Next" button (›)
3. Observe month changes to next month
4. Click the "Previous" button (‹) twice
5. Observe month changes to previous months

**Success Criteria:**
- [ ] Next button advances month forward
- [ ] Previous button moves month backward
- [ ] Month name updates in header
- [ ] Calendar grid updates with new dates
- [ ] Available slots show green on new month's dates
- [ ] No console errors during navigation

**If Failed:**
- Check that event handlers are attached (`$(document).on()`)
- Verify `loadCalendarMonth()` is being called
- Check AJAX requests in Network tab
- Ensure `waza_load_calendar` action is registered

### Test 4: Slots Display Correctly ✅
**Expected:** Days with available slots show green background and slot previews

**Steps:**
1. Identify days with slots (should have green background)
2. Look for slot time indicators (e.g., "10:00", "14:00")
3. Check color coding:
   - Blue = Available slots
   - Yellow = Limited availability (≤2 spots)
   - Red = Full (0 spots)

**Success Criteria:**
- [ ] Green background on days with slots
- [ ] Up to 3 slot times visible per day
- [ ] "+X" indicator if more than 3 slots exist
- [ ] Correct color coding based on availability
- [ ] Slots only appear on future dates, not past dates

**If Failed:**
- Run `test-calendar-debug.php` to check database slots
- Verify `get_day_slots()` is returning data
- Check if slots have `_waza_start_date` matching calendar dates
- Ensure slot post status is 'publish'

### Test 5: View Dropdown Changes ✅
**Expected:** Dropdown allows switching between Month/Week/Day views

**Steps:**
1. Locate the view dropdown (shows "Month" by default)
2. Click the dropdown
3. Select "Week"
4. Note the message displayed
5. Select "Day"
6. Note the message displayed
7. Select "Month" again

**Success Criteria:**
- [ ] Dropdown shows 3 options: Month, Week, Day
- [ ] Selecting "Month" reloads calendar
- [ ] Selecting "Week" shows "coming soon" message
- [ ] Selecting "Day" shows "coming soon" message
- [ ] No errors in console

**If Failed:**
- Verify `.waza-calendar-view` dropdown exists in HTML
- Check that change event handler is attached
- Look for `showAlert()` function errors

### Test 6: Slot Selection Modal ✅
**Expected:** Clicking a day with slots opens modal with slot list

**Steps:**
1. Click on a green day (day with available slots)
2. Wait for modal to appear
3. Observe slot details displayed

**Success Criteria:**
- [ ] Modal opens smoothly
- [ ] Modal shows selected date
- [ ] All slots for that day are listed
- [ ] Each slot shows:
  - Start/end time
  - Activity name
  - Instructor name (if enabled)
  - Price (if enabled)
  - Availability count
- [ ] "Book Now" button appears for available slots
- [ ] Disabled/full slots show as unavailable

**If Failed:**
- Check `waza_load_day_slots` AJAX action
- Verify `loadDaySlots()` function in frontend.js
- Look for modal HTML structure
- Check CSS for `.waza-modal` styles

## Advanced Testing

### Test 7: Activity Filtering ✅
**Expected:** Calendar filters slots by activity when activity_id is specified

**Steps:**
1. Create shortcode with activity ID: `[waza_booking_calendar activity_id="123"]`
2. Replace "123" with actual activity post ID
3. Load page
4. Verify only slots for that activity appear

**Success Criteria:**
- [ ] Only slots for specified activity show green
- [ ] Modal only shows slots for that activity
- [ ] Other activities' slots are hidden

### Test 8: Admin Settings Integration ✅
**Expected:** Calendar respects admin panel customization settings

**Steps:**
1. Go to WordPress Admin → Waza Booking → Settings → Calendar
2. Change "Primary Color" to #FF0000 (red)
3. Change "Slots Per Day" to 5
4. Save settings
5. Reload calendar page

**Success Criteria:**
- [ ] Calendar uses new primary color
- [ ] Up to 5 slot previews show per day
- [ ] Changes reflect immediately after save

### Test 9: Responsive Design ✅
**Expected:** Calendar adapts to mobile screens

**Steps:**
1. Open calendar page on desktop
2. Press F12 and toggle device toolbar
3. Select iPhone or Android device
4. Test all interactions

**Success Criteria:**
- [ ] Calendar grid adapts to screen width
- [ ] All buttons remain clickable
- [ ] Modal is readable and scrollable
- [ ] No horizontal overflow

### Test 10: Performance ✅
**Expected:** Calendar loads quickly without lag

**Steps:**
1. Open browser's Network tab (F12)
2. Reload calendar page
3. Note load times
4. Navigate between months
5. Note AJAX response times

**Success Criteria:**
- [ ] Initial page load < 2 seconds
- [ ] AJAX calendar load < 500ms
- [ ] No memory leaks when navigating
- [ ] Smooth animations

## Quick Debug Tool

### Using test-calendar-debug.php
1. Navigate to: `http://your-site/wp-content/plugins/waza-studio-app/test-calendar-debug.php`
2. Review all 6 test sections:
   - ✅ JavaScript Localization Test
   - ✅ Database Slots Test
   - ✅ AJAX Endpoint Test
   - ✅ Live Calendar Test
   - ✅ Calendar Settings Check
   - ✅ Browser Console Monitor

3. Click "Test AJAX Load Calendar" button
4. Verify all tests pass

## Common Issues & Solutions

### Issue: Calendar shows but no green days
**Solution:**
- Verify slots exist in database with future dates
- Run: `wp post list --post_type=waza_slot --format=table`
- Check `_waza_start_date` meta values
- Ensure slots are published, not drafts

### Issue: Buttons don't respond to clicks
**Solution:**
- Clear browser cache completely
- Check console for JavaScript errors
- Verify `waza_frontend.ajax_url` exists
- Ensure jQuery is loaded before frontend.js

### Issue: AJAX returns 400 Bad Request
**Solution:**
- Check nonce is being sent correctly
- Verify `waza_frontend_nonce` is valid
- Ensure AJAX action is registered in AjaxHandler.php
- Check WordPress REST API is enabled

### Issue: Modal doesn't open when clicking day
**Solution:**
- Verify `loadDaySlots()` function exists
- Check `waza_load_day_slots` AJAX action
- Look for modal HTML in ShortcodeManager.php
- Ensure modal CSS is loaded

### Issue: Admin settings not applying
**Solution:**
- Save settings again in admin panel
- Clear WordPress object cache
- Verify `waza_calendar_*` options exist in database
- Check `calendar_settings` in waza_frontend object

## Browser Console Commands

### Check localization:
```javascript
console.log(waza_frontend);
```

### Test AJAX manually:
```javascript
jQuery.ajax({
    url: waza_frontend.ajax_url,
    type: 'POST',
    data: {
        action: 'waza_load_calendar',
        year: 2025,
        month: 1,
        nonce: waza_frontend.nonce
    },
    success: function(response) {
        console.log('Success:', response);
    }
});
```

### Check event handlers:
```javascript
jQuery._data(document, 'events');
```

### Monitor all AJAX:
```javascript
jQuery(document).ajaxSend(function(event, xhr, settings) {
    console.log('AJAX Send:', settings);
});
```

## Sign-Off

### Developer Checklist
- [ ] All 10 tests passed
- [ ] No console errors
- [ ] Calendar loads in < 2 seconds
- [ ] Mobile responsive
- [ ] Admin settings work
- [ ] Documentation updated

### User Acceptance
- [ ] Calendar displays correctly
- [ ] Navigation is intuitive
- [ ] Booking flow works end-to-end
- [ ] Performance is acceptable
- [ ] No broken features

**Date Tested:** __________
**Tested By:** __________
**Browser/Version:** __________
**Result:** ☐ PASS ☐ FAIL ☐ NEEDS REVISION

**Notes:**
_____________________________________________
_____________________________________________
_____________________________________________
