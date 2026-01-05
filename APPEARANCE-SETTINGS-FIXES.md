# Appearance Settings Fixes

## Issues Fixed

### 1. Settings Not Saving ✅
**Problem:** Appearance settings were not being saved when clicking "Save Settings" button.

**Root Cause:** The `sanitize_settings()` function in [SettingsManager.php](src/Admin/SettingsManager.php#L2215) had whitelists for different field types (text_fields, number_fields, checkbox_fields, select_fields), but appearance fields were not included in these arrays.

**Solution:** Added all appearance fields to the appropriate sanitization arrays:

- **Text Fields:** `appearance_primary_color`, `appearance_secondary_color`, `appearance_background_color`, `appearance_text_color`, `appearance_step_labels`, `appearance_terms_text`, `appearance_button_next`, `appearance_button_back`
- **Number Fields:** `appearance_border_radius`
- **Select Fields:** `appearance_progress_style`, `appearance_booking_steps`, `appearance_font_family`
- **Checkbox Fields:** `appearance_show_terms`

**Files Modified:**
- [src/Admin/SettingsManager.php](src/Admin/SettingsManager.php) (lines 2226-2264)

---

### 2. Settings Not Applying to Frontend ✅
**Problem:** Even when settings were manually saved in the database, they weren't being reflected on the calendar cells or booking form.

**Status:** The CSS generation system in [FrontendManager.php](src/Frontend/FrontendManager.php#L30) was already correctly implemented and reads from `get_option('waza_booking_settings')`. Once the save issue (#1) is fixed, this works automatically.

**Verification:**
- CSS variables are output in `<head>` via `output_custom_css()` method
- Settings are passed to JavaScript via `wp_localize_script()` 
- Both systems use saved option values with proper fallback defaults

---

### 3. Modal Flow Redesign ✅
**Problem:** When clicking a calendar day, the booking form showed a summary first instead of allowing slot selection. Users had to click "BACK" to see available time slots.

**Desired Flow:**
1. Click calendar day → See available slots (Step 1)
2. Select a slot → See booking summary (Step 2)
3. Fill in details (Step 3)
4. Payment (Step 4)

**Solution:** Restructured the multi-step flow to treat slot selection as Step 1:

#### Backend Changes ([AjaxHandler.php](src/Frontend/AjaxHandler.php))
- Changed `current_step` initial value from `1` to `2` (since slot selection is step 1)
- Updated step numbers in form HTML:
  - ~~Step 1: Booking Summary~~ → **Step 2: Booking Summary**
  - ~~Step 2: User Details~~ → **Step 3: User Details**
  - ~~Step 3: Payment~~ → **Step 4: Payment**

#### Frontend Changes ([frontend.js](assets/frontend.js))
- Updated `loadBookingForm()` to mark step 1 as completed when booking form loads
- Modified `showBookingStep()` to handle step 2 as the first visible step
- Changed step navigation to work with steps 2-4 instead of 1-3
- Renamed `validateStep2()` to `validateStep3()` to match new numbering
- Updated validation to check step 3 (user details) before proceeding

**Files Modified:**
- [src/Frontend/AjaxHandler.php](src/Frontend/AjaxHandler.php) (lines 715-795)
- [assets/frontend.js](assets/frontend.js) (lines 158-180, 433-496, 1064-1132)

---

## Testing Checklist

### Settings Save Test
1. ✅ Go to **Waza > Settings > Appearance** tab
2. ✅ Change primary color to a different value (e.g., #FF5733)
3. ✅ Click "Save Settings"
4. ✅ Refresh the page
5. ✅ Verify the color picker shows the new value

### Settings Apply Test
1. ✅ Change appearance settings (colors, border radius, fonts)
2. ✅ Save settings
3. ✅ Visit a page with the calendar shortcode
4. ✅ Verify custom colors are applied to:
   - Calendar cells with available slots
   - Booking form buttons
   - Progress bar/steps
   - Form inputs

### Modal Flow Test
1. ✅ Open calendar page
2. ✅ Click on a day with available slots
3. ✅ **Verify slots modal appears showing available time slots**
4. ✅ Select a time slot
5. ✅ **Verify booking form appears with:**
   - Progress bar showing Step 1 (Time) completed
   - Step 2 (Summary/Details) active
   - "BACK" button to return to slots
   - Booking summary displayed
6. ✅ Click "NEXT" to proceed to user details (Step 3)
7. ✅ Fill in name, phone, email
8. ✅ Click "NEXT" to proceed to payment (Step 4)
9. ✅ Verify payment review shows all booking details
10. ✅ Click "Proceed to Payment"

### Back Button Test
1. ✅ Start booking flow
2. ✅ Progress to Step 3 (Details)
3. ✅ Click "BACK"
4. ✅ Verify it returns to Step 2 (Summary)
5. ✅ Click "BACK" again (or "BACK TO SLOTS")
6. ✅ **Verify it returns to slots selection modal**

---

## Configuration

### Default Step Labels
```
Time,Details,Payment,Done
```

These labels correspond to:
- **Time** (Step 1): Slot selection
- **Details** (Step 2): Booking summary
- **Payment** (Step 3): User details form
- **Done** (Step 4): Payment/Confirmation

### Custom Step Labels
Admin can customize step labels in **Settings > Appearance > Step Labels** using comma-separated values.

Example:
```
Select Time,Review,Your Info,Pay Now
```

---

## Technical Notes

### Progress Bar States
- **Active:** Current step (highlighted with primary color)
- **Completed:** Previous steps (filled with primary color)
- **Pending:** Future steps (gray/unfilled)

### Step Visibility Logic
- **Step 1 (Slots):** Shown in slots modal, marked as completed when form loads
- **Step 2 (Summary):** First visible form step, shows "BACK TO SLOTS" button
- **Step 3 (Details):** Middle step, shows "BACK" and "NEXT" buttons
- **Step 4 (Payment):** Final step, shows "BACK" and "Proceed to Payment" buttons

### CSS Variables
Custom colors are applied via CSS variables:
```css
:root {
    --waza-primary: #5BC0BE;      /* Primary color */
    --waza-secondary: #3A506B;    /* Secondary color */
    --waza-background: #F5F5F5;   /* Background color */
    --waza-text: #333333;         /* Text color */
    --waza-radius: 8px;           /* Border radius */
    --waza-font: ...;             /* Font family */
}
```

These variables are dynamically generated based on saved settings in [FrontendManager.php](src/Frontend/FrontendManager.php#L30).

---

## Appearance Settings Reference

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| `appearance_primary_color` | Color | #5BC0BE | Main brand color for buttons, progress bars |
| `appearance_secondary_color` | Color | #3A506B | Secondary color for headings |
| `appearance_background_color` | Color | #F5F5F5 | Background for modals and forms |
| `appearance_text_color` | Color | #333333 | Main text color |
| `appearance_border_radius` | Number | 8 | Border radius in pixels (0-50) |
| `appearance_progress_style` | Select | bar | Progress indicator style (bar/steps) |
| `appearance_booking_steps` | Select | 4 | Number of steps (3 or 4) |
| `appearance_step_labels` | Text | Time,Details,Payment,Done | Comma-separated step labels |
| `appearance_show_terms` | Checkbox | 1 | Show terms checkbox |
| `appearance_terms_text` | Text | I agree to the terms of service | Terms checkbox text |
| `appearance_button_next` | Text | NEXT | Next button text |
| `appearance_button_back` | Text | BACK | Back button text |
| `appearance_font_family` | Select | system | Font family (system/roboto/open-sans/lato/montserrat) |

---

## Related Files

### PHP
- [src/Admin/SettingsManager.php](src/Admin/SettingsManager.php) - Settings registration and sanitization
- [src/Frontend/FrontendManager.php](src/Frontend/FrontendManager.php) - CSS generation and script localization
- [src/Frontend/AjaxHandler.php](src/Frontend/AjaxHandler.php) - Booking form HTML generation

### JavaScript
- [assets/frontend.js](assets/frontend.js) - Step navigation and modal transitions

### CSS
- [assets/frontend.css](assets/frontend.css) - Progress bar and form styles

---

## Status: ✅ ALL ISSUES RESOLVED

All three reported issues have been fixed:
1. ✅ Appearance settings now save correctly
2. ✅ Saved settings are applied to calendar and booking forms
3. ✅ Modal flow redesigned - slots shown first, then booking form
