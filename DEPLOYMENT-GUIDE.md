# Waza Booking Plugin - Deployment Guide

## Live Server Installation Steps

### Option 1: Clean Installation (Recommended)

1. **Delete vendor folder conflicts:**
   ```bash
   cd /home/wazastudio.com/public_html/wp-content/plugins/waza-studio-app
   rm -rf vendor/rmccue
   ```

2. **Regenerate autoloader:**
   ```bash
   composer dump-autoload --optimize
   ```

3. **If composer not available on server:**
   - Delete entire `vendor` folder from server
   - Run `composer install --no-dev --optimize-autoloader` locally
   - Upload fresh `vendor` folder to server

### Option 2: Use Pre-built Vendor (Fastest)

1. **On your local machine:**
   ```bash
   cd D:\xam\htdocs\wazza\wp-content\plugins\waza-studio-app
   composer install --no-dev --optimize-autoloader
   ```

2. **Upload to server:**
   - Zip the entire `vendor` folder
   - Upload and extract on server at `/public_html/wp-content/plugins/waza-studio-app/vendor`

### Option 3: Exclude Vendor and Use CDN/WordPress Libraries

If composer issues persist, remove vendor dependencies entirely:

1. **Delete composer.json and vendor folder**
2. **Use WordPress HTTP API instead of external libraries**
3. **Load payment SDKs via CDN in payment templates**

## Installation Script

After uploading plugin files, run:

**URL:** `https://wazastudio.com/wp-content/plugins/waza-studio-app/install-plugin.php`

This will:
- Create all database tables
- Create required pages
- Add sample activities and slots
- Configure default settings

**Important:** Delete `install-plugin.php` after successful installation!

## Required PHP Extensions

Ensure these are enabled on your server:
- `php-curl`
- `php-mbstring`
- `php-gd` (for QR codes)
- `php-json`
- `php-mysqli`

## File Permissions

Set correct permissions:
```bash
chmod 755 /home/wazastudio.com/public_html/wp-content/plugins/waza-studio-app
chmod 644 /home/wazastudio.com/public_html/wp-content/plugins/waza-studio-app/*.php
```

## Configuration

After installation, configure in WordPress Admin:

1. **Payment Gateway:**
   - Go to: Waza Booking → Settings
   - Add Razorpay Key ID and Secret
   - Add Stripe API keys (if using)

2. **Rental Settings:**
   - Go to: Waza Booking → Rental Settings
   - Update pricing for rental types
   - Configure tax and advance payment percentages

3. **Email Templates:**
   - Customize email templates in Settings
   - Set sender name and email

## Troubleshooting

### Error: "Failed to open stream: No such file or directory"

**Cause:** Vendor autoloader conflicts with Elementor

**Solution:**
```bash
cd /home/wazastudio.com/public_html/wp-content/plugins/waza-studio-app
rm -rf vendor
composer install --no-dev --optimize-autoloader
```

Or manually delete `vendor/rmccue` folder.

### Error: "Class not found"

**Cause:** Autoloader not loaded properly

**Solution:**
1. Check if `vendor/autoload.php` exists
2. Verify file permissions (644)
3. Run `composer dump-autoload`

### Database errors

**Cause:** Tables not created

**Solution:**
Run install-plugin.php again or manually execute SQL from `database/migration-1.0.0.sql`

### Payment gateway errors

**Cause:** API credentials not configured

**Solution:**
1. Go to WordPress Admin → Waza Booking → Settings
2. Add correct API keys
3. Test in sandbox mode first

## Security Checklist

After deployment:

- [ ] Delete `install-plugin.php`
- [ ] Delete `add-rental-column.php`
- [ ] Set strong API keys
- [ ] Enable HTTPS
- [ ] Set proper file permissions
- [ ] Test booking flow
- [ ] Test payment processing
- [ ] Test QR scanner
- [ ] Test email notifications

## Support

For issues, check:
1. WordPress debug.log: `/wp-content/debug.log`
2. PHP error log: Check cPanel → Error Log
3. Browser console for JavaScript errors

Enable WordPress debugging:
```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```
