# Waza Booking System - Complete Testing Guide

This guide will help you test all the implemented functionality of the Waza Booking system.

## 🎯 What We've Implemented

The plugin now has ALL the missing functionality that was causing blank pages:

### ✅ Completed Features

1. **Settings Page** (`src/Admin/SettingsManager.php`)
   - Complete tabbed interface with 5 sections
   - Payment gateway configuration (Razorpay & Stripe)
   - Business information settings
   - Booking rules and limitations
   - Email and notification preferences

2. **Payment Integration** (`src/Payment/PaymentManager.php`)
   - Full Razorpay and Stripe API integration
   - Payment order creation and verification
   - Webhook handling for payment confirmations
   - Refund processing
   - Mock APIs for development environment

3. **Frontend Booking System**
   - Interactive calendar (`assets/frontend.js`)
   - Time slot selection
   - Booking forms with validation
   - AJAX-powered interactions
   - Professional responsive styling (`assets/frontend.css`)

4. **Shortcode System** (`src/Frontend/ShortcodeManager.php`)
   - `[waza_activities_list]` - Display all activities
   - `[waza_activity_grid]` - Grid layout for activities
   - `[waza_booking_calendar]` - Interactive booking calendar
   - `[waza_featured_activities]` - Highlight featured activities
   - `[waza_instructors_list]` - Display instructor profiles
   - `[waza_activity_search]` - Search functionality
   - `[waza_activity_filters]` - Filter options

5. **AJAX Handlers** (`src/Frontend/AjaxHandler.php`)
   - Calendar loading and navigation
   - Time slot retrieval
   - Booking form processing
   - Payment confirmation
   - Discount code application

## 🚀 Testing Instructions

### Step 1: Activate the Plugin

1. Go to **WordPress Admin > Plugins**
2. Activate "Waza Booking" if not already active
3. Check for any activation errors in the WordPress debug log

### Step 2: Check Admin Menu

1. Go to **WordPress Admin**
2. Look for "Waza" in the admin menu (should appear with calendar icon)
3. Click through each submenu:
   - **Dashboard** - Overview and statistics
   - **Settings** - Complete configuration interface
   - **Email Templates** - Template management (already working)
   - **Customization** - Styling and layout options

### Step 3: Configure Settings

1. Go to **Waza > Settings**
2. Configure each tab:
   - **General**: Business name, address, contact info
   - **Payment**: Set up Razorpay/Stripe (use test credentials)
   - **Booking**: Set default duration, advance booking limits
   - **Email**: Configure SMTP settings
   - **Notifications**: Set notification preferences

### Step 4: Test Frontend Booking

#### Option A: Use Test Page
1. Upload `test-booking-system.php` to your active theme folder
2. Create a new WordPress page
3. Set the page template to "Test Booking System"
4. Visit the page to see all shortcodes in action

#### Option B: Use Shortcodes Directly
1. Create a new WordPress page or post
2. Add shortcodes to test specific functionality:

```
[waza_booking_calendar]

[waza_activities_list]

[waza_activity_grid]

[waza_featured_activities]

[waza_instructors_list]

[waza_activity_search]
```

### Step 5: Test Booking Flow

1. **Calendar Display**: Should show current month with available dates
2. **Date Selection**: Click on an available date
3. **Time Slots**: Should load available time slots for selected date
4. **Booking Form**: Click on a time slot to open booking form
5. **Form Validation**: Try submitting with missing information
6. **Payment**: Complete form and proceed to payment
7. **Confirmation**: Verify booking confirmation and email

### Step 6: Test AJAX Functionality

1. Open browser developer tools (F12)
2. Go to Network tab
3. Interact with the booking calendar
4. Check for AJAX requests to:
   - `admin-ajax.php?action=waza_load_calendar`
   - `admin-ajax.php?action=waza_load_day_slots`
   - `admin-ajax.php?action=waza_process_booking`

### Step 7: Test Payment Processing

#### Development Mode (Mock APIs)
- The system uses mock payment APIs for development
- Payments will be simulated without real charges
- Check payment logs in WordPress admin

#### Production Mode
1. Add real Razorpay/Stripe API credentials in Settings
2. Test with real payment methods
3. Verify webhook endpoints are accessible

## 🛠️ Troubleshooting

### Common Issues

1. **Blank Pages Still Showing**
   - Check WordPress error log for PHP errors
   - Verify all plugin files were uploaded correctly
   - Ensure proper file permissions (755 for directories, 644 for files)

2. **JavaScript Not Working**
   - Check browser console for JavaScript errors
   - Verify jQuery is loaded
   - Check that frontend.js and frontend.css are being loaded

3. **AJAX Requests Failing**
   - Verify nonce generation in `wp_localize_script`
   - Check AJAX handler registration in AjaxHandler.php
   - Ensure WordPress AJAX endpoint is accessible

4. **Shortcodes Not Displaying**
   - Verify ShortcodeManager is being initialized
   - Check for PHP errors in shortcode callback functions
   - Ensure proper escaping of shortcode attributes

### Debug Information

Enable WordPress debugging by adding to `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check the debug log at `/wp-content/debug.log` for any errors.

### Performance Optimization

1. **Database Queries**: All database operations use prepared statements
2. **Asset Loading**: Scripts/styles only load on pages with shortcodes
3. **Caching**: Consider implementing object caching for frequent queries

## 📋 Verification Checklist

- [ ] Plugin activates without errors
- [ ] Admin menu appears with all submenus
- [ ] Settings page loads with all 5 tabs
- [ ] Payment gateways can be configured
- [ ] Frontend shortcodes display content
- [ ] Calendar shows and is interactive
- [ ] Time slots load when date is selected
- [ ] Booking form opens and validates
- [ ] AJAX requests complete successfully
- [ ] Payment processing works (mock or real)
- [ ] Email notifications are sent
- [ ] Booking data is saved to database

## 🎉 Success Indicators

When everything is working correctly, you should see:

1. **Admin Area**: Fully functional settings with tabbed interface
2. **Frontend**: Interactive booking calendar with professional styling
3. **User Experience**: Smooth booking flow from calendar to payment
4. **Technical**: No JavaScript errors, successful AJAX requests
5. **Data**: Bookings and payments properly recorded in database

## 🔄 Next Steps

Once basic functionality is confirmed:

1. **Customize Styling**: Modify `assets/frontend.css` to match your theme
2. **Add Content**: Create activities, instructors, and booking slots
3. **Configure Payments**: Set up real payment gateway credentials
4. **Test Workflows**: Book activities end-to-end with real payments
5. **User Training**: Document the booking process for end users

The plugin now has complete functionality from calendar display through payment processing. All the missing pieces have been implemented!