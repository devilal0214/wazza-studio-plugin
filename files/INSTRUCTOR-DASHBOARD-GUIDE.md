# Instructor Dashboard Implementation Guide

## ✅ What Has Been Implemented

### 1. **Instructor Registration System**
- **File**: `templates/instructor-registration.php`
- Self-registration form with:
  - Personal info (name, email, phone)
  - Professional details (activity type, experience, bio)
  - Social links (Instagram, portfolio)
  - Terms & conditions checkbox
- **Handler**: `src/Frontend/InstructorFrontend.php` → `submit_application()`
- Creates WordPress user + instructor post with `pending` status
- Sends confirmation email to applicant
- Notifies admin for approval

### 2. **Instructor Dashboard Template**
- **File**: `templates/instructor-dashboard.php`
- Mobile-first design with tabs:
  - **Workshops**: View/manage workshops, get QR codes
  - **Schedule**: Calendar view of upcoming sessions
  - **Students**: List of enrolled students
  - **Profile**: Edit bio, contact, social links
- Stats cards: Today's workshops, upcoming count, total students
- Floating action button for mobile

### 3. **Backend Class**
- **File**: `src/Frontend/InstructorFrontend.php`
- Shortcodes:
  - `[waza_instructor_register]` - Registration form
  - `[waza_instructor_dashboard]` - Dashboard (overrides existing)
- AJAX Handlers:
  - `waza_submit_instructor_application` - Process registration
  - `waza_get_instructor_workshops` - Get instructor's workshops
  - `waza_get_workshop_qr` - Generate workshop QR code
  - `waza_get_workshop_students` - Get workshop attendees

### 4. **JavaScript**
- **File**: `assets/instructor.js`
- Registration form validation & submission
- Dashboard tab navigation
- Workshop listing with filters
- QR code modal display & download
- Schedule & students loading

### 5. **Integration**
- Registered `InstructorFrontend` in `src/Core/Plugin.php`
- Auto-loads on pages with instructor shortcodes

---

## 🚧 What Needs To Be Completed

### 1. **CSS Styling**
**Create**: `assets/instructor.css`

```css
/* Mobile-first instructor dashboard styles */
.waza-instructor-dashboard {
    /* Dark theme, large touch targets */
}
.waza-workshop-card {
    /* Card design for workshops */
}
.waza-mobile-header {
    /* Sticky mobile header */
}
.waza-fab {
    /* Floating action button */
}
```

### 2. **Missing AJAX Handlers**

Add to `InstructorFrontend.php`:

```php
// Get instructor overview stats
public function ajax_get_instructor_overview() {
    // Count today's workshops
    // Count upcoming workshops  
    // Count total unique students
}

// Get instructor schedule
public function ajax_get_instructor_schedule() {
    // Return calendar data
}

// Get instructor students
public function ajax_get_instructor_students() {
    // Return student list with booking/attendance counts
}
```

Register in constructor:
```php
add_action('wp_ajax_waza_get_instructor_overview', [$this, 'ajax_get_instructor_overview']);
add_action('wp_ajax_waza_get_instructor_schedule', [$this, 'ajax_get_instructor_schedule']);
add_action('wp_ajax_waza_get_instructor_students', [$this, 'ajax_get_instructor_students']);
```

### 3. **Admin Approval Workflow**

Already exists in `src/Admin/InstructorManager.php`:
- Column showing approval status
- Toggle approve/disapprove link
- Email sent on approval

**Enhancement needed**: Send credentials email when approved

Add to `InstructorManager.php`:
```php
public function handle_status_transition($new_status, $old_status, $post) {
    if ($post->post_type !== 'waza_instructor') return;
    
    if ($new_status === 'publish' && $old_status === 'pending') {
        // Get user
        $user_id = get_post_meta($post->ID, '_waza_user_id', true);
        $user = get_userdata($user_id);
        
        // Send approval email with login credentials
        $reset_key = get_password_reset_key($user);
        $reset_url = network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($user->user_login));
        
        $message = sprintf(
            __('Congratulations! Your instructor application has been approved.

Set your password: %s

Dashboard: %s

Best regards,
%s Team', 'waza-booking'),
            $reset_url,
            home_url('/instructor-dashboard'),
            get_bloginfo('name')
        );
        
        wp_mail($user->user_email, __('Instructor Application Approved', 'waza-booking'), $message);
    }
}
```

### 4. **Workshop QR System**

The QR code generation is partially implemented. Need to:

1. **Instructor Master QR**:
   - Contains: `{ type: 'instructor_workshop', slot_id, user_id, timestamp }`
   - Scanned at studio entrance → authenticates instructor
   - Enables student check-in mode

2. **Scanner Integration**:
   Update `src/Admin/AttendanceScanner.php` to handle instructor QR:
   ```php
   if ($qr_data['type'] === 'instructor_workshop') {
       // Verify instructor owns this workshop
       // Mark instructor as present
       // Enable student scanning for this workshop
   }
   ```

### 5. **Workshop Creation**

Currently redirects to `post-new.php?post_type=waza_activity`.

**Better approach**: Create custom workshop creation form in dashboard

Add to `InstructorFrontend.php`:
```php
public function create_workshop() {
    // Get available slots
    // Let instructor choose slot
    // Create workshop with auto-assigned instructor
}
```

### 6. **Student Registration for Workshops**

Need to implement:
- Generate unique workshop join link
- Student registration via link → auto-creates booking
- Workshop QR code for students to scan (links to registration)

---

## 📋 Setup Checklist

### Pages to Create (WordPress Admin)

1. **Instructor Registration** (`/instructor-registration`)
   - Add shortcode: `[waza_instructor_register]`

2. **Instructor Dashboard** (`/instructor-dashboard`)
   - Add shortcode: `[waza_instructor_dashboard]`
   - Make it private (visible only to logged-in instructors)

### User Roles

WordPress already has roles. For instructors:
- Create user → assign `subscriber` or custom `instructor` role
- Instructor post links user via `_waza_user_id` meta

**Optional**: Create custom `instructor` role
```php
add_role('instructor', 'Instructor', [
    'read' => true,
    'edit_posts' => false,
    'delete_posts' => false
]);
```

### Email Templates

Already handled in `InstructorFrontend.php`:
- Application submitted → Confirmation to applicant
- Application submitted → Notification to admin  
- Approved → Login credentials to instructor

---

## 🎯 Next Steps (Priority Order)

1. **Create `assets/instructor.css`** (Mobile-first, dark theme)
2. **Add missing AJAX handlers** (overview, schedule, students)
3. **Test registration flow** (register → admin approves → receive email)
4. **Test dashboard** (login as approved instructor → view workshops)
5. **Implement workshop creation** (custom form in dashboard)
6. **Enhance QR system** (instructor master QR + student scanning)
7. **Add workshop join links** (students can register via link)

---

## 🔧 Quick Fixes Needed

### Fix Dashboard Data Loading

The existing dashboard in `UserAccountManager.php` has AJAX handlers but they're not loading data. The new `InstructorFrontend` class fixes this, but need to:

1. **Remove duplicate shortcode** in `UserAccountManager.php` line 29
2. **Keep only** `InstructorFrontend::instructor_dashboard()`

Or: Update existing handlers in `UserAccountManager.php` to return actual data.

---

## 📱 Mobile-First CSS Structure

```css
/* Base: Mobile (320px+) */
.waza-mobile-header { position: sticky; top: 0; }
.waza-tab-navigation { display: flex; overflow-x: auto; }
.waza-tab-btn { min-width: 80px; font-size: 12px; }

/* Tablet (768px+) */
@media (min-width: 768px) {
    .waza-tab-navigation { justify-content: center; }
    .waza-workshops-grid { grid-template-columns: repeat(2, 1fr); }
}

/* Desktop (1024px+) */
@media (min-width: 1024px) {
    .waza-instructor-dashboard { max-width: 1200px; margin: 0 auto; }
    .waza-workshops-grid { grid-template-columns: repeat(3, 1fr); }
    .waza-fab { display: none; } /* Hide FAB on desktop */
}
```

---

## ✅ Testing Checklist

- [ ] Instructor can register via form
- [ ] Admin receives notification email
- [ ] Instructor receives confirmation email
- [ ] Admin can approve/reject from WordPress admin
- [ ] Approved instructor receives login email
- [ ] Instructor can login and access dashboard
- [ ] Dashboard shows correct stats
- [ ] Workshops tab loads instructor's workshops
- [ ] QR button generates and displays QR code
- [ ] QR code can be downloaded
- [ ] Schedule tab shows calendar
- [ ] Students tab shows enrolled students
- [ ] Profile tab allows editing bio/contact
- [ ] Mobile navigation works (tabs, FAB)
- [ ] Responsive design on all devices

---

This implementation provides the foundation. Complete the missing pieces above for a fully functional instructor dashboard system.
