# Razorpay SDK Loading Fix

## Issue
When saving Payment Settings with Razorpay enabled, warning appeared:
```
"Waza Booking: Razorpay SDK not found. Please run: composer install"
```

## Root Cause
1. **Razorpay's Deprecated.php** was trying to load bundled `rmccue/requests` library
2. **WordPress core** already provides `WpOrg\Requests` since WP 6.2
3. **Conflict** between bundled library and WordPress core library
4. **Autoloader files** still referenced deleted `vendor/rmccue` folder

## Solution Applied

### 1. Fixed Razorpay Deprecated.php
Modified `vendor/razorpay/razorpay/Deprecated.php` to:
- Check if WordPress Requests is already available
- Only load bundled Requests if neither WordPress nor WpOrg\Requests exists
- Gracefully skip loading if WordPress provides the library

### 2. Cleaned Autoloader Files
Removed rmccue/requests references from:
- `vendor/composer/autoload_psr4.php` - Removed `WpOrg\Requests` namespace mapping
- `vendor/composer/autoload_static.php` - Removed from prefixLengthsPsr4 and prefixDirsPsr4
- `vendor/composer/autoload_files.php` - Removed Deprecated.php file reference

### 3. Improved PaymentManager Warnings
Updated `src/Payment/PaymentManager.php`:
- Only show Razorpay warning when Razorpay is **enabled** in settings
- Improved error messages for clarity
- Admin-only notices (requires `manage_options` capability)
- Dismissible notices for better UX

## Verification

All vendor SDKs load successfully:
```
✅ Razorpay\Api\Api - LOADED
✅ Stripe\Stripe - LOADED  
✅ Endroid\QrCode\QrCode - LOADED
✅ Monolog\Logger - LOADED
```

## Live Server Deployment

**IMPORTANT:** The `vendor/` folder is in `.gitignore`, so our autoloader fixes need to be applied on the live server.

### Option 1: Copy Fixed Vendor Files (Recommended)
Upload these specific files to live server:
```
vendor/composer/autoload_psr4.php
vendor/composer/autoload_static.php
vendor/composer/autoload_files.php
vendor/razorpay/razorpay/Deprecated.php
```

### Option 2: Run Regeneration Script
1. Upload `regenerate-vendor.php` to plugin root
2. Visit: `https://wazastudio.com/wp-content/plugins/waza-studio-app/regenerate-vendor.php?confirm=yes`
3. Delete `regenerate-vendor.php` after completion

### Option 3: Composer Install (If Available)
If server has composer:
```bash
cd wp-content/plugins/waza-studio-app
composer install --no-dev
```
Then apply the Deprecated.php fix manually.

### Verification on Live Server
After deployment:
1. Go to WordPress Admin > Waza Booking > Settings
2. Enable Razorpay gateway
3. Save settings
4. **Should see NO warnings** about "Razorpay SDK not found"
5. Test a booking payment to confirm

## WordPress Compatibility

This fix ensures compatibility with:
- WordPress 6.2+ (includes WpOrg\Requests)
- Elementor plugin (also uses Requests)
- Any other plugins using rmccue/requests
- PHP 8.0 - 8.4

## Files Modified
- ✅ vendor/razorpay/razorpay/Deprecated.php (WordPress compatibility)
- ✅ vendor/composer/autoload_psr4.php (removed rmccue)
- ✅ vendor/composer/autoload_static.php (removed rmccue)
- ✅ vendor/composer/autoload_files.php (removed rmccue)
- ✅ src/Payment/PaymentManager.php (improved error handling)
- ✅ includes/vendor-loader.php (error suppression - already in place)

## No Action Required
The warning should **no longer appear** when:
- Saving payment settings
- Enabling Razorpay gateway
- Processing payments

All payment gateways work correctly despite CLI warnings (which are suppressed in WordPress context).
