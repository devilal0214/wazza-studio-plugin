# Quick Setup Guide - Create WordPress Pages

## 🚀 5-Minute Setup

### Page 1: Instructor Registration

1. **WordPress Admin** → **Pages** → **Add New**

2. **Page Settings**:
   - **Title**: `Instructor Registration`
   - **Slug**: `instructor-registration` (click Edit next to title)
   - **Template**: Default Template

3. **Content** (paste in editor):
   ```
   [waza_instructor_register]
   ```

4. **Publish**

5. **Test**: Visit `http://yoursite.com/instructor-registration/`

---

### Page 2: Instructor Dashboard

1. **WordPress Admin** → **Pages** → **Add New**

2. **Page Settings**:
   - **Title**: `Instructor Dashboard`
   - **Slug**: `instructor-dashboard`
   - **Template**: Default Template

3. **Content** (paste in editor):
   ```
   [waza_instructor_dashboard]
   ```

4. **Publish**

5. **Test**: Visit `http://yoursite.com/instructor-dashboard/` (must be logged in as instructor)

---

## 📋 Quick Test

### Test Registration (2 minutes)

1. **Visit**: `/instructor-registration/`
2. **Fill form**:
   - Name: Test Instructor
   - Email: test@example.com
   - Phone: 555-1234
   - Activity: Yoga
   - Experience: 5 years
   - Bio: Test bio
   - Terms: ✓
3. **Submit** → Look for success message
4. **Check email**: Should receive "Application Received" email

### Test Approval (1 minute)

1. **WordPress Admin** → **Waza Studio** → **Instructors**
2. **Find**: "Test Instructor" (Pending status)
3. **Click**: "Approve" toggle
4. **Status**: Changes to "Approved"
5. **Check email**: Instructor receives approval email with password reset link

### Test Dashboard (2 minutes)

1. **Click**: Password reset link from email
2. **Set**: New password
3. **Login**: WordPress login
4. **Visit**: `/instructor-dashboard/`
5. **Verify**:
   - ✅ Dashboard loads (no "pending" message)
   - ✅ Stats show 0 (no workshops yet)
   - ✅ Tabs switch correctly
   - ✅ Profile tab shows instructor data

---

## 🎨 Optional: Add to Navigation Menu

### Add "Become an Instructor" Link

1. **WordPress Admin** → **Appearance** → **Menus**
2. **Select**: Primary menu (or create new)
3. **Custom Links** → **Add**:
   - URL: `/instructor-registration/`
   - Link Text: `Become an Instructor`
4. **Save Menu**

### Add Conditional Dashboard Link (Advanced)

**Option A: Simple Link**
1. **Custom Links** → **Add**:
   - URL: `/instructor-dashboard/`
   - Link Text: `My Dashboard`
2. **CSS Class**: `menu-item-instructor-only`
3. Use CSS to hide for non-instructors

**Option B: PHP in Theme (functions.php)**
```php
add_filter('wp_nav_menu_items', function($items, $args) {
    if ($args->theme_location == 'primary' && is_user_logged_in()) {
        // Check if user is approved instructor
        $user_id = get_current_user_id();
        $instructors = get_posts([
            'post_type' => 'waza_instructor',
            'meta_key' => '_waza_user_id',
            'meta_value' => $user_id,
            'post_status' => 'publish',
            'posts_per_page' => 1
        ]);
        
        if (!empty($instructors)) {
            $items .= '<li class="menu-item"><a href="/instructor-dashboard/">My Dashboard</a></li>';
        }
    }
    return $items;
}, 10, 2);
```

---

## ⚡ Troubleshooting

### "Page not found" error
**Fix**: Go to **Settings** → **Permalinks** → Click **Save Changes** (flush rewrite rules)

### Dashboard shows blank
**Fix**: Check browser console for JavaScript errors. Ensure `instructor.js` and `instructor.css` are loading.

### Form doesn't submit
**Fix**: 
1. Check browser console for errors
2. Verify AJAX URL is correct: `wp_localize_script()` in InstructorFrontend.php
3. Test with browser developer tools Network tab

### Email not received
**Fix**: Install WP Mail SMTP plugin and configure email settings

---

## ✅ Checklist

- [ ] Page created: Instructor Registration
- [ ] Page created: Instructor Dashboard
- [ ] Permalinks flushed (Settings → Permalinks → Save)
- [ ] Test registration form submitted
- [ ] Test email received (application confirmation)
- [ ] Test admin approval (toggle in Instructors panel)
- [ ] Test approval email received (password reset link)
- [ ] Test password reset
- [ ] Test dashboard access (logged in as instructor)
- [ ] Test all dashboard tabs (workshops, schedule, students, profile)
- [ ] Test mobile view (320px width)
- [ ] Test tablet view (768px width)
- [ ] Test desktop view (1024px+ width)
- [ ] (Optional) Add navigation menu items

---

## 🎯 Next: Assign Workshops to Instructor

Once an instructor is approved, you need to assign them to activities:

1. **WordPress Admin** → **Waza Studio** → **Activities**
2. **Edit** an existing activity (or create new)
3. **Instructor** field → Select approved instructor
4. **Save**
5. **Create slots** for the activity (Admin → Slots)

Now the instructor will see workshops in their dashboard!

---

**Setup Time**: ~5 minutes
**Testing Time**: ~5 minutes
**Total**: 10 minutes to fully functional instructor system ✅
