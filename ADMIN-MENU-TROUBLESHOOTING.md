# Waza Admin Menu Not Showing - Troubleshooting Guide

## Issue Description
The Waza menu is not appearing in the WordPress admin dashboard after plugin activation.

## Root Causes & Solutions

### 1. **Missing Capabilities** (Most Common)
The user doesn't have the required `manage_waza` capability.

**Quick Fix:**
```php
// Add this to your theme's functions.php temporarily
add_action('init', function() {
    $admin = get_role('administrator');
    if ($admin) {
        $admin->add_cap('manage_waza');
        $admin->add_cap('edit_waza_slots');
        $admin->add_cap('view_waza_bookings');
        $admin->add_cap('manage_waza_instructors');
        $admin->add_cap('scan_waza_qr');
        $admin->add_cap('export_waza_data');
    }
});
```

### 2. **Plugin Not Fully Activated**
The activation hooks didn't run properly.

**Solution:** 
- Deactivate the plugin
- Reactivate the plugin
- Check WordPress error logs for any activation errors

### 3. **User Role Issues**
Current user is not an administrator.

**Check:** Go to Users > Your Profile and verify you have Administrator role.

### 4. **Caching Issues**
WordPress or plugin caching is preventing menu updates.

**Solution:**
- Clear all caches (WordPress, object cache, page cache)
- Log out and log back in to WordPress admin

## Diagnostic Steps

### Step 1: Check Current User Capabilities
```php
// Run this in WordPress admin or via WP-CLI
$user = wp_get_current_user();
echo "User: " . $user->user_login . "\n";
echo "Roles: " . implode(', ', $user->roles) . "\n";
echo "Has manage_waza: " . (current_user_can('manage_waza') ? 'Yes' : 'No') . "\n";
```

### Step 2: Verify Plugin Activation
```php
// Check if plugin was properly activated
$activated = get_option('waza_booking_activated');
$version = get_option('waza_booking_version');
echo "Activated: " . ($activated ? 'Yes' : 'No') . "\n";
echo "Version: " . $version . "\n";
```

### Step 3: Check Admin Menu Hooks
Look for these entries in WordPress error log:
```
Waza Booking: Created user role 'Waza Instructor'
Waza Booking: Added capability 'manage_waza' to administrator role
Waza Booking: Plugin activation completed successfully
```

## Manual Fix Commands

### Via WP-CLI:
```bash
# Add capabilities
wp user add-cap admin manage_waza
wp user add-cap admin edit_waza_slots
wp user add-cap admin view_waza_bookings

# Check current user capabilities
wp user list-caps admin
```

### Via MySQL (Advanced):
```sql
-- Check capabilities in database
SELECT * FROM wp_options WHERE option_name = 'wp_user_roles';

-- Check if user has admin role
SELECT * FROM wp_usermeta WHERE meta_key = 'wp_capabilities' AND user_id = 1;
```

## Debug Scripts

### 1. Run Debug Script
Upload `debug-admin-menu.php` to your plugin directory and run it:
```
https://yoursite.com/wp-content/plugins/waza-studio-app/debug-admin-menu.php
```

### 2. Run Fix Script  
Upload `fix-admin-menu.php` to your plugin directory and run it:
```
https://yoursite.com/wp-content/plugins/waza-studio-app/fix-admin-menu.php
```

## Expected Admin Menu Structure

After successful setup, you should see:

```
WordPress Admin Menu:
├── Dashboard
├── Posts
├── Media
├── Pages
├── Comments
├── **Waza** 👈 This should appear here
│   ├── Dashboard
│   ├── Activities
│   ├── Slots  
│   ├── Bookings
│   ├── Instructors
│   ├── Email Templates
│   ├── Customization
│   └── Settings
├── Appearance
├── Plugins
└── ...
```

## Prevention for Future Updates

Add this to your theme's functions.php to ensure capabilities persist:

```php
// Ensure Waza capabilities are always available
add_action('after_setup_theme', function() {
    $admin = get_role('administrator');
    if ($admin && !$admin->has_cap('manage_waza')) {
        // Re-add capabilities if missing
        $caps = ['manage_waza', 'edit_waza_slots', 'view_waza_bookings', 'manage_waza_instructors', 'scan_waza_qr', 'export_waza_data'];
        foreach ($caps as $cap) {
            $admin->add_cap($cap);
        }
    }
});
```

## Still Not Working?

### Last Resort Fixes:

1. **Reset User Capabilities:**
   ```php
   delete_user_meta(get_current_user_id(), 'wp_capabilities');
   $admin = get_role('administrator');
   wp_update_user(['ID' => get_current_user_id()]);
   ```

2. **Force Menu Registration:**
   Add to functions.php:
   ```php
   add_action('admin_menu', function() {
       if (current_user_can('manage_options')) {
           add_menu_page('Waza', 'Waza', 'manage_options', 'waza-booking', function() {
               echo '<h1>Waza Booking</h1><p>Plugin is working!</p>';
           }, 'dashicons-calendar-alt', 30);
       }
   }, 99);
   ```

3. **Check for Plugin Conflicts:**
   - Deactivate all other plugins
   - Test if Waza menu appears
   - Reactivate plugins one by one to find conflicts

## Support Information

If the menu still doesn't appear:

1. Check WordPress error logs: `/wp-content/debug.log`
2. Check server error logs
3. Verify WordPress version compatibility (6.0+)
4. Verify PHP version compatibility (8.0+)
5. Test with default WordPress theme
6. Contact support with:
   - WordPress version
   - PHP version  
   - Active plugins list
   - Error log contents
   - User role information

---

**Quick Test:** After trying any fix, refresh your WordPress admin page and look for "Waza" in the left sidebar menu.