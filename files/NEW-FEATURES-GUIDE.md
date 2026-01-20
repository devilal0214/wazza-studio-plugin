# NEW FEATURES IMPLEMENTATION GUIDE

## Overview
This document covers the implementation of two major new features for Waza Studio:
1. **Activity Browser & Booking Flow** - BookMyShow-style activity selection with slot booking
2. **Studio Rental System** - Complete rental booking system with pricing tiers

---

## FEATURE 1: ACTIVITY BROWSER & BOOKING FLOW

### Purpose
Provides a user-friendly way to browse available activities, view available slots, and complete bookings - similar to BookMyShow's movie selection flow.

### Flow Diagram
```
Browse Activities → Select Activity → Choose Date → Pick Time Slot → Fill Details → Payment → Confirmation
```

### Components Created

#### 1. **ActivityBrowserManager.php** (`src/Activity/ActivityBrowserManager.php`)
- Handles activity browsing, filtering, and slot selection
- AJAX endpoints for dynamic loading

**Key Methods:**
- `activity_browser_shortcode()` - Main activities listing page
- `activity_slots_shortcode()` - Slot selection page for specific activity
- `get_activity_slots()` - AJAX: Fetch available slots for date/activity
- `filter_activities()` - AJAX: Filter/search activities

#### 2. **activity-browser.php** (Template)
Main activity browsing page with:
- Search functionality
- Category filtering
- Sort options (Popular, Newest, Price)
- Grid display of activity cards
- Real-time AJAX filtering

**Shortcode:** `[waza_activity_browser]`

**Attributes:**
- `per_page` - Number of activities per page (default: 12)
- `show_filters` - Show/hide filters (yes/no)

#### 3. **activity-slots.php** (Template)
4-step booking wizard:
1. **Select Date** - Date picker with minimum date validation
2. **Choose Slot** - Available time slots for selected date
3. **Your Details** - Booking form with summary
4. **Payment** - Integration with existing payment system

**Shortcode:** `[waza_activity_slots activity_id="123"]`

### Database Requirements

**Activities Post Type:** `waza_activity`
Required meta fields:
- `_waza_activity_price` - Activity price
- `_waza_activity_duration` - Duration in minutes
- `_waza_activity_slug` - URL-friendly slug
- `_waza_activity_rating` - Average rating (0-5)
- `_waza_booking_count` - Total bookings (for popularity)

**Taxonomy:** `waza_instructor_specialty` (for categories)

### Setup Instructions

1. **Create Activities Page:**
   ```
   Page Title: Activities
   Slug: /activities/
   Content: [waza_activity_browser]
   ```

2. **Create Activity Booking Page:**
   ```
   Page Title: Book Activity
   Slug: /activity-booking/
   Content: [waza_activity_slots]
   ```

3. **Create Sample Activities:**
   ```php
   // In WordPress admin, create custom posts of type 'waza_activity'
   // Or use this code snippet:
   
   $activity_id = wp_insert_post([
       'post_type' => 'waza_activity',
       'post_title' => 'Zumba Dance Class',
       'post_content' => 'High-energy dance workout combining Latin rhythms...',
       'post_status' => 'publish'
   ]);
   
   update_post_meta($activity_id, '_waza_activity_price', 500);
   update_post_meta($activity_id, '_waza_activity_duration', 60);
   update_post_meta($activity_id, '_waza_activity_slug', 'zumba');
   update_post_meta($activity_id, '_waza_activity_rating', 4.5);
   update_post_meta($activity_id, '_waza_booking_count', 0);
   
   wp_set_object_terms($activity_id, ['dance'], 'waza_instructor_specialty');
   ```

4. **Link Time Slots to Activities:**
   Ensure your slots table has an `activity_type` column matching `_waza_activity_slug`

### AJAX Endpoints

**Filter Activities:**
```javascript
$.ajax({
    url: wazaBooking.ajax_url,
    data: {
        action: 'waza_filter_activities',
        nonce: wazaBooking.nonce,
        category: 'dance',
        search: 'zumba',
        sort: 'popular',
        paged: 1
    }
});
```

**Get Activity Slots:**
```javascript
$.ajax({
    url: wazaBooking.ajax_url,
    data: {
        action: 'waza_get_activity_slots',
        nonce: wazaBooking.nonce,
        activity_id: 123,
        selected_date: '2026-01-20'
    }
});
```

---

## FEATURE 2: STUDIO RENTAL SYSTEM

### Purpose
Allow individuals and businesses to rent the studio space for rehearsals, shoots, or commercial events with automated availability checking and QR code generation.

### Pricing Structure

| Rental Type | Hourly | 3 Hours | Half Day (6h) | Full Day (12h) |
|-------------|--------|---------|---------------|----------------|
| **🎭 Rehearsal** | ₹1,000 | ₹2,700 | ₹5,500 | ₹10,000 |
| **🎥 Shoot** | ₹1,700 | ₹4,500 | ₹9,000 | ₹15,000 |
| **📢 Commercial** | ₹3,500 | - | ₹18,000 | ₹25,000 |

### Components Created

#### 1. **RentalManager.php** (`src/Rental/RentalManager.php`)
Main rental management class

**Key Methods:**
- `create_rental_table()` - Creates `wp_waza_rentals` table
- `rental_form_shortcode()` - Displays rental booking form
- `check_availability()` - AJAX: Validates slot availability
- `submit_rental_booking()` - AJAX: Creates rental booking
- `calculate_rental_amount()` - Calculates price based on type/duration
- `generate_rental_qr()` - Generates QR code for rental
- `notify_admin_rental()` - Sends email notification to admin

#### 2. **studio-rental.php** (Template)
Complete rental booking form with:
- Pricing cards for all 3 rental types
- Personal information section
- Rental details (type, duration, date, time)
- Real-time amount calculation
- Availability checking
- Special requirements textarea

**Shortcode:** `[waza_studio_rental]`

#### 3. **RentalAdminManager.php** (`src/Admin/RentalAdminManager.php`)
Admin interface for managing rentals

**Features:**
- List all rentals with filters (pending, confirmed, completed, cancelled)
- Search by customer name, email, phone
- Update rental status
- View rental details
- Download QR codes
- Payment status tracking

**Admin Page:** `Dashboard → Studio Rentals`

### Database Table

**Table:** `wp_waza_rentals`

```sql
CREATE TABLE wp_waza_rentals (
    id bigint(20) AUTO_INCREMENT PRIMARY KEY,
    user_id bigint(20) DEFAULT NULL,
    customer_name varchar(255) NOT NULL,
    customer_email varchar(255) NOT NULL,
    customer_phone varchar(50) NOT NULL,
    rental_type varchar(50) NOT NULL,        -- rehearsal, shoot, commercial
    duration_type varchar(50) NOT NULL,      -- hourly, 3hrs, half_day, full_day
    rental_date date NOT NULL,
    start_time time NOT NULL,
    end_time time NOT NULL,
    total_amount decimal(10,2) NOT NULL,
    payment_status varchar(50) DEFAULT 'pending',
    booking_status varchar(50) DEFAULT 'pending',
    qr_code_path varchar(255) DEFAULT NULL,
    special_requirements text,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    KEY user_id (user_id),
    KEY rental_date (rental_date),
    KEY booking_status (booking_status)
);
```

### Setup Instructions

1. **Create Studio Rental Page:**
   ```
   Page Title: Studio Rental
   Slug: /studio-rental/
   Content: [waza_studio_rental]
   ```

2. **Create Rental Payment Page:**
   ```
   Page Title: Rental Payment
   Slug: /rental-payment/
   Content: [Your payment gateway shortcode]
   ```

3. **Configure Studio Hours:**
   Default: 10:00 AM - 10:00 PM
   (Can be modified in template or settings)

### Availability Logic

The system checks BOTH:
1. **Regular Slots Table** - Ensures no class/workshop conflicts
2. **Rentals Table** - Ensures no overlapping rental bookings

```php
// Checks if any slot exists in the time range
SELECT COUNT(*) FROM wp_waza_slots
WHERE start_datetime BETWEEN 'date start_time' AND 'date end_time'
OR end_datetime BETWEEN 'date start_time' AND 'date end_time'
OR (start_datetime <= 'date start_time' AND end_datetime >= 'date end_time')

// Checks if any rental exists in the time range
SELECT COUNT(*) FROM wp_waza_rentals
WHERE rental_date = 'date'
AND booking_status != 'cancelled'
AND (time overlap conditions...)
```

### QR Code Integration

QR codes are automatically generated for each rental containing:
```json
{
    "type": "rental",
    "rental_id": 123,
    "timestamp": 1642678400
}
```

Stored in: `wp-content/uploads/waza-qr/rental-{id}.png`

### Email Notifications

**To Admin:**
```
Subject: [Site Name] New Studio Rental Booking

New studio rental booking received:

Customer: John Doe
Email: john@example.com
Phone: 9876543210
Rental Type: Shoot
Duration: Half Day
Date: Jan 20, 2026
Time: 10:00 AM - 4:00 PM
Amount: ₹9,000

Review and approve: [Admin Link]
```

### AJAX Endpoints

**Check Availability:**
```javascript
$.ajax({
    url: wazaBooking.ajax_url,
    data: {
        action: 'waza_check_rental_availability',
        nonce: wazaBooking.nonce,
        date: '2026-01-20',
        start_time: '10:00:00',
        end_time: '16:00:00'
    }
});
```

**Submit Rental:**
```javascript
$.ajax({
    url: wazaBooking.ajax_url,
    data: {
        action: 'waza_submit_rental_booking',
        nonce: wazaBooking.nonce,
        customer_name: 'John Doe',
        customer_email: 'john@example.com',
        customer_phone: '9876543210',
        rental_type: 'shoot',
        duration_type: 'half_day',
        rental_date: '2026-01-20',
        start_time: '10:00:00',
        end_time: '16:00:00',
        special_requirements: 'Need extra lighting'
    }
});
```

**Update Rental Status (Admin):**
```javascript
$.ajax({
    url: ajaxurl,
    data: {
        action: 'waza_update_rental_status',
        rental_id: 123,
        status: 'confirmed'
    }
});
```

---

## INTEGRATION NOTES

### Payment Integration
Both features redirect to payment pages with booking data:
- Activities: `/booking-payment/?slot_id=123&activity_id=456`
- Rentals: `/rental-payment/?rental_id=789`

Ensure your payment gateway processes these parameters.

### JavaScript Dependencies
Both features require:
```javascript
wazaBooking.ajax_url  // WordPress AJAX URL
wazaBooking.nonce     // Security nonce
```

Enqueued in `FrontendManager.php`

### Styling
Both templates include embedded CSS. For customization, override in your theme:
```css
/* Activity Browser */
.waza-activity-browser { }
.activity-card { }

/* Studio Rental */
.waza-rental-container { }
.pricing-card { }
```

---

## TESTING CHECKLIST

### Activity Browser
- [ ] Activities page displays properly
- [ ] Filters work (category, search, sort)
- [ ] Clicking activity navigates to booking page
- [ ] Date selection loads slots
- [ ] Slot selection updates summary
- [ ] Booking form validates required fields
- [ ] Payment redirect includes correct parameters

### Studio Rental
- [ ] Pricing cards display correctly
- [ ] Rental type/duration updates amount
- [ ] Availability check prevents conflicts
- [ ] Form validation works
- [ ] QR code generates successfully
- [ ] Admin receives email notification
- [ ] Admin can view/manage rentals
- [ ] Status updates work
- [ ] Payment integration functions

---

## TROUBLESHOOTING

### Activities Not Showing
1. Check if `waza_activity` post type is registered
2. Ensure posts are published
3. Verify meta fields exist
4. Check taxonomy terms assigned

### Slots Not Loading
1. Verify `activity_type` column in slots table matches `_waza_activity_slug`
2. Check date is future date
3. Ensure slots have available capacity
4. Check AJAX endpoint errors in console

### Rental Availability Always Fails
1. Verify both tables exist (`wp_waza_slots`, `wp_waza_rentals`)
2. Check time format (HH:MM:SS)
3. Ensure date format (YYYY-MM-DD)
4. Check for conflicting bookings manually

### QR Codes Not Generating
1. Check QRManager is loaded
2. Verify write permissions: `wp-content/uploads/waza-qr/`
3. Check error logs for QR library issues

---

## FILE STRUCTURE

```
waza-studio-app/
├── src/
│   ├── Activity/
│   │   └── ActivityBrowserManager.php      [NEW]
│   ├── Rental/
│   │   └── RentalManager.php               [NEW]
│   ├── Admin/
│   │   └── RentalAdminManager.php          [NEW]
│   └── Core/
│       └── Plugin.php                       [UPDATED]
└── templates/
    ├── activity-browser.php                 [NEW]
    ├── activity-slots.php                   [NEW]
    └── studio-rental.php                    [NEW]
```

---

## SHORTCODES SUMMARY

| Shortcode | Purpose | Parameters |
|-----------|---------|------------|
| `[waza_activity_browser]` | Browse all activities | `per_page`, `show_filters` |
| `[waza_activity_slots]` | Book specific activity | `activity_id` |
| `[waza_studio_rental]` | Studio rental form | None |

---

## NEXT STEPS

1. **Create Required Pages** - Add pages with shortcodes
2. **Add Sample Data** - Create activities and set up pricing
3. **Test Workflow** - Complete end-to-end booking flow
4. **Configure Payment** - Ensure payment gateway integration
5. **Train Admin** - Show how to manage rentals and bookings

---

## SUPPORT

For issues or questions:
1. Check WordPress debug.log for errors
2. Verify all files are uploaded correctly
3. Ensure database tables are created
4. Check JavaScript console for AJAX errors
5. Review admin email settings for notifications

---

**Version:** 1.0.0  
**Date:** January 16, 2026  
**Author:** Waza Studio Development Team
