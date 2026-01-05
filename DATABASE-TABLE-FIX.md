# DATABASE TABLE FIX - Workshops & Announcements

## Problem
Both Workshops and Announcements pages were showing database errors because:
1. **Missing Tables**: Database tables weren't created during plugin activation
2. **Wrong Query**: Workshops was trying to join with non-existent `wp_waza_activities` table

## Root Causes

### 1. Missing Tables
The plugin activation should create all database tables automatically, but this may have failed if:
- Plugin was activated before all files were uploaded
- Database permissions issue
- PHP execution timeout during activation

### 2. Wrong Table Reference
Workshops query was trying to join:
```sql
LEFT JOIN {$wpdb->prefix}waza_activities a ON s.activity_id = a.ID
```

But Activities are stored as WordPress **custom post types** in `wp_posts`, not a separate table.

## Fixes Applied

### ✅ Fix #1: Enhanced Database Fix Button
Updated `src/Admin/AdminManager.php` `ajax_fix_database()` method to:
- **Create ALL missing database tables** (not just fix columns)
- Verify all 13 required tables exist
- Show detailed status for each table
- Report success/failure for each operation

**Tables Created**:
1. wp_waza_bookings
2. wp_waza_slots
3. wp_waza_qr_tokens
4. wp_waza_attendance
5. wp_waza_payments
6. wp_waza_waitlist
7. wp_waza_email_templates
8. wp_waza_workshops
9. wp_waza_workshop_students
10. wp_waza_activity_logs
11. wp_waza_announcements
12. wp_waza_qr_groups
13. wp_waza_qr_group_members

### ✅ Fix #2: Corrected Workshops Query
Changed in `src/Workshop/WorkshopManager.php` line 59:

**BEFORE**:
```php
LEFT JOIN {$wpdb->prefix}waza_activities a ON s.activity_id = a.ID
```

**AFTER**:
```php
LEFT JOIN {$wpdb->posts} a ON s.activity_id = a.ID AND a.post_type = 'waza_activity'
```

This correctly queries the WordPress posts table where activities are stored as custom post types.

### ✅ Fix #3: Manual Table Creation Script
Created `force-create-tables.php` as a backup method to manually create tables:
- Can be accessed directly at: `http://localhost/wazza/wp-content/plugins/waza-studio-app/force-create-tables.php`
- Shows which tables exist vs missing
- Creates all tables using DatabaseManager

## How to Fix Your Installation

### Method 1: Use "Fix Database" Button (EASIEST)
1. Go to **Waza Booking > Dashboard**
2. Click the **"Fix Database Issues"** button (top right)
3. Wait for success message
4. Refresh the Workshops and Announcements pages

### Method 2: Deactivate and Reactivate Plugin
1. Go to **Plugins**
2. **Deactivate** Waza Booking
3. **Activate** Waza Booking again
4. This will trigger `Installer::activate()` which creates all tables

### Method 3: Manual Script (If button doesn't work)
1. Open browser
2. Navigate to: `http://localhost/wazza/wp-content/plugins/waza-studio-app/force-create-tables.php`
3. You'll see a list of all tables and their status
4. Refresh admin pages

## Verification

After applying the fix, verify:

### Workshops Page
1. Go to **Waza Booking > Workshops**
2. Should show: "No workshops found" (not database error)
3. Should display table headers: Workshop Title, Activity, Instructor, Date/Time, etc.

### Announcements Page
1. Go to **Waza Booking > Announcements**
2. Should show: "No announcements found" (not database error)
3. "Add New Announcement" link should be visible
4. Clicking it should show the form

### Database Check (Optional)
Run this SQL query in phpMyAdmin:
```sql
SHOW TABLES LIKE 'wp_waza_%';
```

You should see 13 tables listed.

## Why This Happened

This is a **one-time setup issue**. The tables should have been created during plugin activation, but one of these scenarios likely occurred:

1. **Files uploaded during development** - Plugin activated before all files were present
2. **Manual file edits** - Plugin was already active when new features were added
3. **Database permissions** - MySQL user may not have had CREATE TABLE permissions
4. **PHP timeout** - Activation script may have timed out during table creation

## Prevention

Going forward, tables will be created automatically because:
- `Installer::activate()` creates all tables on activation
- `DatabaseManager::check_database_version()` runs on every `plugins_loaded` hook
- "Fix Database" button now creates ALL tables, not just columns

## Technical Details

### Database Schema
All tables use `CREATE TABLE IF NOT EXISTS`, so running the creation multiple times is safe.

### Activities vs Tables
- **Activities** = Custom Post Type stored in `wp_posts`
- **Bookings, Slots, Workshops** = Custom tables in database
- Queries join `wp_posts` (for activities) with custom tables (for bookings/slots)

### AJAX Endpoint
The "Fix Database" button calls:
- Action: `waza_fix_database`
- Handler: `AdminManager::ajax_fix_database()`
- Requires: `manage_options` capability (admin only)
- Returns: JSON with table status and operation details

## Support

If you still see database errors after applying all fixes:
1. Check MySQL error log
2. Verify database user has CREATE TABLE, ALTER TABLE permissions
3. Check WordPress debug log (wp-content/debug.log)
4. Manually run the SQL from `src/Database/DatabaseManager.php` create_tables() method
