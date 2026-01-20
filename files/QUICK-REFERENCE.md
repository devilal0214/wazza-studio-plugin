# NEW FEATURES QUICK REFERENCE

## 🎯 FEATURE 1: ACTIVITY BROWSER & BOOKING

### User Flow
```
Browse Activities → Select → Pick Date → Choose Slot → Details → Payment
```

### Pages to Create
1. **Activities** (`/activities/`)
   - Shortcode: `[waza_activity_browser]`
   
2. **Activity Booking** (`/activity-booking/`)
   - Shortcode: `[waza_activity_slots]`

### Key Files
- `src/Activity/ActivityBrowserManager.php`
- `templates/activity-browser.php`
- `templates/activity-slots.php`

### AJAX Actions
- `waza_filter_activities` - Search/filter
- `waza_get_activity_slots` - Get available slots

---

## 🏢 FEATURE 2: STUDIO RENTAL SYSTEM

### Pricing (Quick Ref)
| Type | Hourly | 3hrs | 6hrs | 12hrs |
|------|--------|------|------|-------|
| Rehearsal 🎭 | ₹1K | ₹2.7K | ₹5.5K | ₹10K |
| Shoot 🎥 | ₹1.7K | ₹4.5K | ₹9K | ₹15K |
| Commercial 📢 | ₹3.5K | - | ₹18K | ₹25K |

### Pages to Create
1. **Studio Rental** (`/studio-rental/`)
   - Shortcode: `[waza_studio_rental]`

2. **Rental Payment** (`/rental-payment/`)
   - Your payment gateway shortcode

### Key Files
- `src/Rental/RentalManager.php`
- `src/Admin/RentalAdminManager.php`
- `templates/studio-rental.php`

### AJAX Actions
- `waza_check_rental_availability` - Check availability
- `waza_submit_rental_booking` - Submit booking
- `waza_update_rental_status` - Admin: Update status

### Admin Page
**Dashboard → Studio Rentals**
- View all rentals
- Filter by status (pending, confirmed, completed, cancelled)
- Update statuses
- Download QR codes

---

## 🚀 QUICK SETUP (5 Steps)

1. **Run Setup Script**
   ```
   yoursite.com/wp-content/plugins/waza-studio-app/setup-new-features.php
   ```

2. **Create Pages** - Click "Create Pages" button in setup

3. **Add Sample Activities** - Click "Create Sample Activities"

4. **Add to Menu** - Add pages to your site navigation

5. **Test** - Browse activities and try rental booking

---

## 📊 DATABASE

### New Table: `wp_waza_rentals`
Key columns:
- `customer_name`, `customer_email`, `customer_phone`
- `rental_type`, `duration_type`
- `rental_date`, `start_time`, `end_time`
- `total_amount`, `payment_status`, `booking_status`
- `qr_code_path`

### Activity Meta Fields
- `_waza_activity_price`
- `_waza_activity_duration`
- `_waza_activity_slug`
- `_waza_activity_rating`
- `_waza_booking_count`

---

## 🎨 CUSTOMIZATION

### Override Templates (in theme)
```
your-theme/
└── waza-booking/
    ├── activity-browser.php
    ├── activity-slots.php
    └── studio-rental.php
```

### CSS Classes
```css
/* Activities */
.waza-activity-browser { }
.activity-card { }
.activity-filters { }

/* Rentals */
.waza-rental-container { }
.pricing-card { }
.rental-amount-display { }
```

---

## ⚡ TROUBLESHOOTING

### Activities Not Showing
✓ Check post type: `waza_activity`
✓ Verify posts are published
✓ Check meta fields exist

### Slots Not Loading
✓ Match `activity_type` in slots with `_waza_activity_slug`
✓ Ensure future dates only
✓ Check console for AJAX errors

### Rental Availability Fails
✓ Verify tables exist
✓ Check time format: `HH:MM:SS`
✓ Date format: `YYYY-MM-DD`

### QR Not Generating
✓ Check folder permissions: `wp-content/uploads/waza-qr/`
✓ Ensure QRManager is loaded

---

## 📞 ADMIN TASKS

### Daily
- Review pending rental requests
- Confirm paid rentals
- Check activity bookings

### Weekly
- Update activity ratings
- Review rental revenue
- Export booking data

### Monthly
- Analyze popular activities
- Adjust pricing if needed
- Clean up old pending bookings

---

## 🔗 USEFUL LINKS

**Frontend:**
- Activities: `/activities/`
- Rental: `/studio-rental/`

**Admin:**
- Rentals: `Dashboard → Studio Rentals`
- Activities: `Dashboard → Activities`
- Bookings: `Dashboard → All Bookings`

**Docs:**
- Full Guide: `NEW-FEATURES-GUIDE.md`
- This File: `QUICK-REFERENCE.md`

---

## 💡 PRO TIPS

1. **Set Featured Images** - Add images to activities for better engagement
2. **Test Workflow** - Complete a booking end-to-end before launch
3. **Email Testing** - Verify admin notifications work
4. **Mobile Check** - Test on mobile devices
5. **Payment Gateway** - Ensure integration handles both activity & rental bookings
6. **Backup** - Backup database before making changes
7. **Security** - Delete `setup-new-features.php` after setup
8. **Performance** - Use caching for activity listings

---

## ✅ POST-SETUP CHECKLIST

- [ ] All 3 pages created
- [ ] Pages added to menu
- [ ] Sample activities visible
- [ ] Activity booking flow works
- [ ] Rental form submits
- [ ] Availability check works
- [ ] QR codes generate
- [ ] Admin notifications received
- [ ] Payment integration tested
- [ ] Mobile responsiveness checked
- [ ] Setup script deleted

---

**Last Updated:** January 16, 2026  
**Support:** Check WordPress debug.log for errors
