# Existing Instructors Migration - Complete ✅

## Summary

All existing instructors have been successfully migrated to the new instructor system with WordPress user accounts and dashboard access.

---

## ✅ Migration Completed

### Instructors Synced
1. **Instructor 1**
   - Email: `test@jvcinst.com`
   - WordPress User: `instructor_1`
   - Status: Approved (publish)
   - User Linked: ✅

2. **Demo Instructor**
   - Email: `demo@instructor.com`
   - WordPress User: `demo_instructor`
   - Status: Approved (publish)
   - User Linked: ✅

---

## 🔧 Technical Changes

### 1. **Admin Interface Enhancement**
- **File**: [src/Admin/InstructorManager.php](src/Admin/InstructorManager.php)
- **Added**: User linkage status indicator in instructor list
- **Added**: "Sync Now" button for unlinked instructors
- **Added**: AJAX handler `waza_sync_instructor` for one-click sync
- **Display**: Shows ✓ or ⚠ icon for user linkage status

### 2. **Database Query Fixes**
- **File**: [src/Frontend/InstructorFrontend.php](src/Frontend/InstructorFrontend.php)
- **Fixed**: All queries now use correct meta key `_waza_instructor` (was `_waza_instructor_id`)
- **Fixed**: All queries use correct datetime column `start_datetime` (was `start_time`)
- **Updated Methods**:
  - `get_workshops()` - Shows instructor's assigned workshops
  - `get_instructor_overview()` - Stats (today/upcoming/students)
  - `get_instructor_schedule()` - Next 20 slots timeline
  - `get_instructor_students()` - Student roster with attendance

### 3. **Migration Tools Created**
- **[sync-instructors.php](sync-instructors.php)**: Batch sync all instructors
- **[check-instructors.php](check-instructors.php)**: Inspect instructor status
- **[update-demo-instructor.php](update-demo-instructor.php)**: Add missing emails

---

## 🔄 Sync Process

### Automatic Sync (Command Line)
```powershell
d:\xam\php\php.exe sync-instructors.php
```

**What it does**:
1. Finds all instructors without linked users
2. Checks if email exists → Link to existing user
3. Email doesn't exist → Create new WordPress user
4. Generates password reset link
5. Sends welcome email with login credentials
6. Updates instructor post with `_waza_user_id` meta

### Manual Sync (Admin UI)
1. Go to **Waza Studio → Instructors**
2. Find instructor with "⚠ Not linked" status
3. Click "Sync Now" button
4. User account created automatically
5. Email sent to instructor

---

## 📊 Data Structure

### Before Migration
```
Instructor Post (ID: 24)
├─ post_title: "Instructor 1"
├─ post_status: "publish"
├─ Meta:
   ├─ _waza_email: "test@jvcinst.com"
   ├─ _waza_phone: "123456789"
   ├─ _waza_bio: "test"
   └─ _waza_experience: "10"
└─ _waza_user_id: NOT SET ❌
```

### After Migration
```
Instructor Post (ID: 24)
├─ post_title: "Instructor 1"
├─ post_status: "publish"
├─ Meta:
   ├─ _waza_email: "test@jvcinst.com"
   ├─ _waza_phone: "123456789"
   ├─ _waza_bio: "test"
   ├─ _waza_experience: "10"
   ├─ _waza_experience_years: "10" (copied from _waza_experience)
   ├─ _waza_activity_type: "Yoga" (from assigned activity)
   └─ _waza_user_id: 15 ✅

WordPress User (ID: 15)
├─ user_login: "instructor_1"
├─ user_email: "test@jvcinst.com"
└─ user_pass: (set via password reset link)
```

---

## 🎯 Dashboard Access

### For Existing Instructors

1. **Receive Email**:
   ```
   Subject: [Site Name] Instructor Account Created - Set Your Password
   
   Dear Instructor 1,
   
   Your instructor account has been set up in our system!
   
   Please set your password to access the instructor dashboard:
   [Password Reset Link]
   
   Your username: instructor_1
   Your email: test@jvcinst.com
   
   After setting your password, you can access your dashboard here:
   [Dashboard URL]
   ```

2. **Set Password**: Click reset link → Create password

3. **Login**: Visit `/wp-login.php` → Enter credentials

4. **Access Dashboard**: Visit `/instructor-dashboard/`

5. **View Data**:
   - Workshops (if activities assigned)
   - Schedule (upcoming slots)
   - Students (booking history)
   - Stats (today, upcoming, total)

---

## 🔗 Linking Logic

### Scenario 1: Email Exists in WordPress
```php
// Check if user with email already exists
$user = get_user_by('email', 'test@jvcinst.com');

if ($user) {
    // Link to existing user
    update_post_meta($instructor_id, '_waza_user_id', $user->ID);
    // ✓ No new user created
}
```

### Scenario 2: New Email (No WordPress User)
```php
// Create new user
$username = sanitize_user('instructor_1');
$password = wp_generate_password(20);
$user_id = wp_create_user($username, $password, 'test@jvcinst.com');

// Link to instructor
update_post_meta($instructor_id, '_waza_user_id', $user_id);

// Send password reset email
$reset_key = get_password_reset_key($user);
wp_mail($email, $subject, $message);
```

---

## ✅ Verification Checklist

- [x] All instructors have `_waza_user_id` meta field set
- [x] WordPress users created for all instructors
- [x] Password reset emails sent
- [x] Dashboard queries use correct meta key (`_waza_instructor`)
- [x] Dashboard queries use correct datetime column (`start_datetime`)
- [x] Admin UI shows user linkage status
- [x] One-click sync available in admin
- [x] Email notifications functional

---

## 🚀 Next Steps for Admins

### Assign Activities to Instructors

For instructors to see data in their dashboard:

1. **Go to**: Waza Studio → Activities
2. **Edit** an activity (e.g., "Yoga Class")
3. **Instructor** dropdown → Select instructor
4. **Save** activity
5. **Create Slots** for this activity

### Instructor Will See:
- ✅ Activity in "Workshops" tab
- ✅ Slots in "Schedule" tab
- ✅ Students who booked in "Students" tab
- ✅ Stats updated (workshops today, upcoming, students)

---

## 📝 Email Template Sent

```
Subject: [Waza Studio] Instructor Account Created - Set Your Password

Dear Instructor 1,

Your instructor account has been set up in our system!

Please set your password to access the instructor dashboard:
http://yoursite.com/wp-login.php?action=rp&key=ABCD1234&login=instructor_1

Your username: instructor_1
Your email: test@jvcinst.com

After setting your password, you can access your dashboard here:
http://yoursite.com/instructor-dashboard/

In the dashboard, you can:
- View your workshops and schedule
- Generate QR codes for workshops
- Track your students
- Update your profile

Best regards,
Waza Studio Team
```

---

## 🔍 Troubleshooting

### Issue: Instructor sees "Not registered"
**Cause**: `_waza_user_id` meta not set
**Fix**:
1. Admin → Instructors
2. Click "Sync Now" for that instructor

### Issue: Dashboard shows 0 workshops
**Cause**: No activities assigned to instructor
**Fix**:
1. Admin → Activities
2. Edit activity → Assign instructor
3. Create slots for activity

### Issue: Email not received
**Cause**: WordPress mail not configured
**Fix**:
- Install WP Mail SMTP plugin
- Configure SMTP settings

### Issue: "User deleted" in admin
**Cause**: WordPress user was manually deleted
**Fix**:
- Click "Sync Now" to recreate user

---

## 📊 Migration Results

```
=== SYNC COMPLETE ===
Total Instructors: 2
Synced: 2
Skipped (already linked): 0
Errors: 0

✓ Instructor 1 → user: instructor_1
✓ Demo Instructor → user: demo_instructor
```

---

## 🎉 Benefits of Migration

### Before
- ❌ Instructors couldn't login
- ❌ No dashboard access
- ❌ No self-service management
- ❌ Admin had to manage everything

### After
- ✅ Instructors can login with WordPress credentials
- ✅ Full dashboard access
- ✅ View workshops, students, schedule
- ✅ Generate QR codes
- ✅ Update own profile
- ✅ Admin workload reduced

---

**Migration Date**: January 7, 2026
**Status**: Complete ✅
**Instructors Migrated**: 2/2 (100%)
**Email Notifications**: Sent
**Dashboard Access**: Enabled
