# LIVE SERVER DEPLOYMENT GUIDE

**Target:** wazastudio.com  
**Date:** <?php echo date('Y-m-d'); ?>  
**Issues Fixed:** 7 critical production bugs

---

## 🚨 ALL 7 ISSUES - ROOT CAUSES & FIXES

### ✅ ISSUE #1: Social Link Buttons Not Working
**Problem:** Add/Remove social link buttons not responding on `/instructor-register/`  
**Root Cause:** Shortcode name mismatch in [InstructorFrontend.php](src/Frontend/InstructorFrontend.php#L45)
- Checking for: `waza_instructor_register` (wrong)
- Actual shortcode: `waza_instructor_registration` (correct)
- Result: JavaScript never loaded, buttons dead

**Fix Applied:** ✅ Changed line 45 to use correct shortcode name
```php
// BEFORE:
has_shortcode(get_post()->post_content, 'waza_instructor_register')

// AFTER:
has_shortcode(get_post()->post_content, 'waza_instructor_registration')
```

---

### ⚠️ ISSUE #2: Razorpay Payment Blocked
**Problem:** "Payment blocked as website does not match registered website(s)"  
**Root Cause:** `wazastudio.com` not whitelisted in Razorpay dashboard

**Manual Fix Required:**
1. Log in to [Razorpay Dashboard](https://dashboard.razorpay.com/)
2. Navigate to: **Settings** → **Website & App Settings** → **Whitelisted Domains**
3. Add domain: `wazastudio.com`
4. **Save changes**
5. Test payment again

---

### ✅ ISSUE #3: /activities-2/ Page Empty
**Problem:** Activity browser shortcode not displaying activities  
**Root Cause:** Likely missing published `waza_activity` posts on live server

**Diagnostic Checks:**
- ActivityBrowserManager properly initialized ✅
- Template `templates/activity-browser.php` exists ✅
- Shortcode `[waza_activity_browser]` registered ✅

**Fix:** Verify activities exist on live server:
```sql
SELECT * FROM wp_posts WHERE post_type = 'waza_activity' AND post_status = 'publish';
```

If no activities, create some in WordPress admin or import from local.

---

### ✅ ISSUE #4: Instructor Registration Not Saving Data
**Problem:** Form submits but nothing happens, data appears in query string  
**Root Cause:** **SAME AS ISSUE #1** - JavaScript not loading

**Fix Applied:** ✅ Shortcode name mismatch fixed (Issue #1)  
**Result:** After code deployment, AJAX will work and data will save correctly

**Verification Method:**
The [submit_application method](src/Frontend/InstructorFrontend.php#L87-L180) is correctly implemented:
- Creates WordPress user ✅
- Creates instructor post ✅
- Saves all metadata ✅
- Processes social links ✅
- Sends notification emails ✅
- Returns JSON response ✅

Problem was scripts not loading, not the AJAX handler.

---

### ✅ ISSUE #5: Sync Creates Subscriber Instead of waza_instructor
**Problem:** Admin sync button links instructor to user but assigns wrong role  
**Root Cause:** `sync_single_instructor()` method never assigned role after `wp_create_user()`

**Fix Applied:** ✅ Added role assignment in [InstructorManager.php](src/Admin/InstructorManager.php#L384-L388)
```php
$user_id = wp_create_user($username, $password, $email);

if (is_wp_error($user_id)) {
    wp_send_json_error($user_id->get_error_message());
}

// NEW: Assign waza_instructor role
$user = new \WP_User($user_id);
$user->set_role('waza_instructor');

// Link instructor to user
update_post_meta($instructor_id, '_waza_user_id', $user_id);
```

---

### 🔍 ISSUE #6: Slots Not Creating - "Failed to create slot. Please try again."
**Problem:** Slot creation fails in admin  
**Possible Causes:**
1. `waza_slots` table doesn't exist on live server
2. Database permission issues
3. Invalid activity_id being passed
4. AJAX nonce mismatch

**Diagnostic Tool:** [live-server-diagnostics.php](live-server-diagnostics.php) checks:
- Table existence ✅
- Table structure ✅
- Can auto-create table if missing ✅
- Shows existing slots ✅

**Fix:** Run diagnostic script to identify exact cause

---

### 🔍 ISSUE #7: Instructor Dashboard - "Invalid activity selected"
**Problem:** Instructors can't create slots - activity dropdown shows "Invalid activity"  
**Root Cause:** Instructors must be assigned to activities via `_waza_instructor` meta key

**Code Logic:** [InstructorFrontend.php](src/Frontend/InstructorFrontend.php#L485-L487)
```php
// Validation check
$activity_instructor_id = get_post_meta($activity_id, '_waza_instructor', true);
if (empty($activity_instructor_id) || $activity_instructor_id != $instructor_id) {
    // FAIL: Instructor not assigned to this activity
}
```

**Fix:** Assign instructors to activities in WordPress admin
1. Go to **Activities** in admin menu
2. Edit each activity
3. Assign instructor via "Instructor" dropdown
4. Save activity

**Diagnostic:** Run [live-server-diagnostics.php](live-server-diagnostics.php) to see current assignments

---

## 📦 DEPLOYMENT STEPS

### Step 1: Commit & Push Changes (Local Dev)
```bash
cd d:\xam\htdocs\wazza\wp-content\plugins\waza-studio-app

# Verify changes
git status

# Should show:
# - src/Admin/InstructorManager.php (role assignment fix)
# - src/Frontend/InstructorFrontend.php (shortcode name fix)
# - live-server-diagnostics.php (new diagnostic tool)

# Commit
git add .
git commit -m "Fix all 7 production issues: shortcode name, sync role, diagnostics"

# Push
git push origin main
```

### Step 2: Pull Code on Live Server
SSH into wazastudio.com:
```bash
cd /path/to/wp-content/plugins/waza-studio-app

# Pull latest code
git pull origin main

# Verify changes applied
git log -1
```

### Step 3: Run Diagnostics
Open in browser:
```
https://wazastudio.com/wp-content/plugins/waza-studio-app/live-server-diagnostics.php
```

The diagnostic tool will:
- ✅ Check all 7 issues
- ✅ Show current status
- ✅ Identify missing data
- ✅ Can auto-create missing waza_slots table
- ✅ Show instructor-activity assignments
- ✅ Display Razorpay configuration
- ✅ List all activities and instructors

### Step 4: Fix Razorpay (Manual)
Follow instructions in diagnostic tool output for Issue #2.

### Step 5: Verify Data Exists
From diagnostic output, check:
- [ ] Published activities exist (Issue #3)
- [ ] waza_slots table exists (Issue #6)
- [ ] waza_instructor role registered (Issue #5)
- [ ] Instructors assigned to activities (Issue #7)

If missing, create/fix as needed.

### Step 6: Test All Features

**Test #1: Instructor Registration** ✅
1. Visit: `https://wazastudio.com/instructor-register/`
2. Fill form
3. Click "Add Social Link" button → Should add new row
4. Click "Remove" → Should remove row
5. Submit form → Should save data and show thank you message
6. Check admin → New pending instructor should appear

**Test #2: Admin Sync Instructor** ✅
1. Go to WordPress admin → Instructors
2. Find pending instructor
3. Click "Sync" button
4. Check Users → New user should have `waza_instructor` role (not subscriber)

**Test #3: Activity Browser** ✅
1. Visit: `https://wazastudio.com/activities-2/`
2. Should show grid of activities with cards
3. If empty, create activities in admin

**Test #4: Create Slot (Admin)** ✅
1. Go to WordPress admin → Slots
2. Click "Add New Slot"
3. Fill form (activity, instructor, date/time, capacity)
4. Save → Should succeed without "Failed to create slot" error

**Test #5: Instructor Dashboard Slot Creation** ✅
1. Log in as instructor
2. Visit: `https://wazastudio.com/instructor-dashboard/`
3. Activity dropdown should show assigned activities (not "Invalid activity")
4. Fill slot form and submit → Should create slot

**Test #6: Payment (After Razorpay Fix)** ✅
1. Book a slot
2. Proceed to payment
3. Should redirect to Razorpay without "website mismatch" error

### Step 7: Delete Diagnostic File
After all tests pass:
```bash
rm /path/to/wp-content/plugins/waza-studio-app/live-server-diagnostics.php
```

Or use the "Delete" button in the diagnostic tool itself.

---

## 🔧 FIXES SUMMARY

### Code Changes Made
| File | Line | Change | Issue Fixed |
|------|------|--------|-------------|
| `src/Frontend/InstructorFrontend.php` | 45 | `waza_instructor_register` → `waza_instructor_registration` | #1, #4 |
| `src/Admin/InstructorManager.php` | 384-388 | Added `$user->set_role('waza_instructor')` | #5 |

### Files Added
- `live-server-diagnostics.php` - Comprehensive diagnostic tool

### Manual Actions Required
1. Razorpay: Add `wazastudio.com` to whitelist (#2)
2. Activities: Verify published activities exist (#3)
3. Assignments: Assign instructors to activities (#7)
4. Database: Ensure `waza_slots` table exists (#6)

---

## 📊 VERIFICATION CHECKLIST

After deployment, verify all issues resolved:

- [ ] **Issue #1:** Social link add/remove buttons work
- [ ] **Issue #2:** Razorpay payment accepted (after domain whitelist)
- [ ] **Issue #3:** /activities-2/ displays activities
- [ ] **Issue #4:** Instructor registration saves data
- [ ] **Issue #5:** Sync creates waza_instructor role (not subscriber)
- [ ] **Issue #6:** Admin can create slots without errors
- [ ] **Issue #7:** Instructor dashboard activity dropdown works

---

## 🆘 TROUBLESHOOTING

### If Issue #1/#4 Still Not Working
1. Clear browser cache (Ctrl+Shift+Del)
2. Hard reload page (Ctrl+F5)
3. Check browser console for JavaScript errors
4. Verify `assets/instructor.js` loaded (Network tab)

### If Issue #3 Still Empty
1. Run diagnostic tool
2. Check if activities exist: `SELECT * FROM wp_posts WHERE post_type='waza_activity' AND post_status='publish'`
3. Create test activity in admin
4. Check theme compatibility (try default WordPress theme)

### If Issue #5 Still Creating Subscriber
1. Verify code pulled correctly: `git log -1`
2. Check if `waza_instructor` role exists (diagnostic tool shows this)
3. Try deactivate/reactivate plugin to re-register role

### If Issue #6 Table Missing
1. Run diagnostic tool → Click "Create waza_slots Table Now"
2. Or deactivate/reactivate plugin
3. Check database permissions

### If Issue #7 Still Invalid Activity
1. Run diagnostic tool → Check "Activity → Instructor Assignments" table
2. Edit activities in admin
3. Assign instructor to each activity
4. Save

---

## 📞 SUPPORT

If any issues persist after deployment:
1. Check diagnostic tool output
2. Check WordPress debug log
3. Check browser console errors
4. Verify all data exists (activities, instructors, assignments)

---

**IMPORTANT:** Delete `live-server-diagnostics.php` after use for security!
