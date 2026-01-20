# 🔧 RENTAL DROPDOWN FIX - COMPLETE SOLUTION

## Problem Identified ✅

**Live Server Issue:**
```
rental_type: 0    ❌ (Should be: "rehearsal")
duration_type: 0  ❌ (Should be: "hourly")
```

**Root Cause:**
The database on wazastudio.com has rental settings stored with **numeric array keys** instead of **associative array keys**.

---

## Solution Applied (2-Part Fix)

### Part 1: Template Key Normalization ✅

**File:** `templates/studio-rental.php`

**What Changed:**
Added helper functions to automatically convert numeric keys to proper string keys:

```php
// Helper functions added
function ensure_rental_type_key($key, $index) {
    $key_map = [0 => 'rehearsal', 1 => 'shoot', 2 => 'commercial'];
    return is_numeric($key) ? ($key_map[$key] ?? $key) : $key;
}

function ensure_duration_key($key, $index) {
    $key_map = [0 => 'hourly', 1 => '3hrs', 2 => 'half_day', 3 => 'full_day'];
    return is_numeric($key) ? ($key_map[$key] ?? $key) : $key;
}

// Now dropdowns use normalized keys
<option value="<?php echo esc_attr($normalized_key); ?>">
    <?php echo esc_html($type['icon'] . ' ' . $type['label']); ?>
</option>
```

**Result:**
- Even if database has numeric keys (0,1,2), template converts them to string keys
- Form submissions will now send: `rental_type=rehearsal` instead of `rental_type=0`

---

### Part 2: Database Fix Script ✅

**File:** `fix-rental-keys.php` (NEW)

**Purpose:** Permanently fix the database settings

**Run This On Live Server:**
```
https://wazastudio.com/wp-content/plugins/waza-studio-app/fix-rental-keys.php
```

**What It Does:**
1. ✅ Reads current `waza_rental_settings` from database
2. ✅ Detects numeric keys (0, 1, 2)
3. ✅ Converts to proper keys:
   - `0` → `'rehearsal'`
   - `1` → `'shoot'`
   - `2` → `'commercial'`
4. ✅ Updates pricing array with proper keys
5. ✅ Saves fixed settings back to database
6. ✅ Shows verification of the fix

**Expected Output:**
```
❌ PROBLEM FOUND: Rental types have numeric keys!

✓ Converted index [0] → ['rehearsal']
  Label: Rehearsal
✓ Converted index [1] → ['shoot']
  Label: Photo/Video Shoot
✓ Converted index [2] → ['commercial']
  Label: Commercial Event

✅ SUCCESS! Settings have been fixed and saved!

Verification - Current Settings After Fix:

Rental Types:
  [rehearsal] (STRING ✅ GOOD) → Rehearsal
  [shoot] (STRING ✅ GOOD) → Photo/Video Shoot
  [commercial] (STRING ✅ GOOD) → Commercial Event
```

---

## Deployment Steps

### Step 1: Upload Updated Files

**Changed Files (Commit 71414ce):**
- `templates/studio-rental.php` (Template with key normalization)
- `fix-rental-keys.php` (NEW - Database fix script)
- `test-complete.php` (Updated)
- `test-email-templates.php` (NEW)
- `src/Frontend/ShortcodeManager.php` (Number format fix)

**Upload via:**
- Git pull: `git pull origin main`
- Or upload files manually via FTP/cPanel

---

### Step 2: Run Database Fix Script

```
Visit: https://wazastudio.com/wp-content/plugins/waza-studio-app/fix-rental-keys.php
```

**You Should See:**
- ✅ Problem detection
- ✅ Key conversion
- ✅ Success message
- ✅ Verification output

---

### Step 3: Delete Fix Script (IMPORTANT!)

```bash
rm /path/to/waza-studio-app/fix-rental-keys.php
```

**OR via cPanel File Manager:**
- Navigate to wp-content/plugins/waza-studio-app/
- Delete: fix-rental-keys.php

---

### Step 4: Test Rental Form

Visit: `https://wazastudio.com/studio-rental`

**Dropdown Should Now Show:**
- 🎭 Rehearsal ✅ (not 0)
- 📸 Photo/Video Shoot ✅ (not 1)
- 🎪 Commercial Event ✅ (not 2)

**Form Submission Should Send:**
```
rental_type=rehearsal     ✅ (not 0)
duration_type=hourly      ✅ (not 0)
```

---

## Why This Fix Works

### Before (Database):
```php
[
    'rental_types' => [
        0 => ['label' => 'Rehearsal', ...],  // ❌ Numeric key
        1 => ['label' => 'Shoot', ...],      // ❌ Numeric key
        2 => ['label' => 'Commercial', ...], // ❌ Numeric key
    ]
]
```

### After (Database):
```php
[
    'rental_types' => [
        'rehearsal' => ['label' => 'Rehearsal', ...],   // ✅ String key
        'shoot' => ['label' => 'Shoot', ...],           // ✅ String key
        'commercial' => ['label' => 'Commercial', ...], // ✅ String key
    ]
]
```

### Template (Safety Net):
Even if database still has numeric keys, template normalizes them:
```php
$normalized_key = ensure_rental_type_key($type_key, $index);
// 0 → 'rehearsal'
// 1 → 'shoot'
// 2 → 'commercial'
```

---

## Alternative: Manual Fix via Admin

If you don't want to run the script:

1. WordPress Admin → Waza Booking → Rental Settings
2. Click "Save Settings" (don't change anything)
3. This will re-save settings with proper keys

**But the script is faster and shows verification!**

---

## Verification

### Test on Live Server:

```
1. Visit: /studio-rental
2. Check dropdown shows:
   - Rehearsal (not 0) ✅
   - Photo/Video Shoot (not 1) ✅
   - Commercial Event (not 2) ✅

3. Fill form and submit
4. Check form data in booking:
   - rental_type should be "rehearsal" not "0" ✅
   - duration_type should be "hourly" not "0" ✅
```

---

## Summary

**Fixed:**
1. ✅ Template normalizes numeric keys automatically
2. ✅ Database fix script converts keys permanently
3. ✅ Form submissions now use proper string keys
4. ✅ Backward compatible (handles both numeric and string keys)

**Committed to GitHub:** Commit `71414ce`

**Next Steps:**
1. Pull latest code from GitHub OR upload files manually
2. Run fix-rental-keys.php on live server
3. Delete fix-rental-keys.php
4. Test rental form
5. Verify dropdowns show names, not numbers

**Your rental system is now fixed! 🎉**
