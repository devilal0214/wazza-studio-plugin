# Waza Booking Plugin

A modern, secure WordPress plugin for managing activities, bookings, payments, and instructor workflows with QR verification.

## Features

- 🎯 **Activity Management**: Dance, Yoga, Zumba, Photography, Influencer Showcases
- 📅 **Smart Scheduling**: One-time and recurring slots with capacity management
- 💳 **Payment Integration**: Razorpay (India) and Stripe support
- 📱 **QR Code System**: Generate and verify attendance with secure tokens
- 👨‍🏫 **Instructor Workflows**: Workshop creation, student invites, bulk import
- ⏰ **Automated Notifications**: Email/SMS confirmations and reminders
- 📊 **Admin Dashboard**: Comprehensive booking and attendance management
- 🔒 **Security First**: Role-based access, input sanitization, rate limiting

## Requirements

- WordPress 6.0+
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.2+
- cURL extension for payment gateways
- GD extension for QR code generation

## Installation

### Method 1: Upload Plugin (Recommended)

1. Download the plugin zip file
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Upload the zip file and activate

**🎉 That's it! The plugin automatically sets up everything:**
- ✅ Creates database tables
- ✅ Creates default pages with shortcodes
- ✅ Sets up user roles and permissions
- ✅ Creates sample activities and data
- ✅ Configures upload directories
- ✅ Schedules background tasks

### Method 2: Manual Installation

1. Upload the plugin folder to `/wp-content/plugins/waza-booking/`
2. Run `composer install --no-dev` in the plugin directory
3. Activate the plugin through WordPress admin

### Method 3: Development Setup

```bash
# Clone and setup
git clone <repository-url> wp-content/plugins/waza-booking
cd wp-content/plugins/waza-booking

# Install dependencies
composer install
npm install  # If frontend assets need building

# Run tests
composer test
```

## Automatic Setup

Upon activation, the plugin creates these pages with pre-configured shortcodes:

| Page | URL | Shortcode | Purpose |
|------|-----|-----------|---------|
| **Activities** | `/activities` | `[waza_activities_list]` | Browse all available activities |
| **Calendar** | `/calendar` | `[waza_booking_calendar]` | View calendar and book slots |
| **My Account** | `/my-account` | `[waza_user_dashboard]` | User dashboard and profile |
| **My Bookings** | `/my-bookings` | `[waza_my_bookings]` | Manage user bookings |
| **Login** | `/login` | `[waza_user_login]` | User login form |
| **Register** | `/register` | `[waza_user_register]` | User registration |
| **Book Activity** | `/book` | `[waza_booking_form]` | Activity booking form |
| **Booking Confirmation** | `/booking-confirmation` | `[waza_booking_confirmation]` | Payment and confirmation |

## Database Tables

The plugin automatically creates these custom tables:
- `wp_waza_bookings` - Booking records with concurrency support
- `wp_waza_slots` - Activity time slots and scheduling  
- `wp_waza_qr_tokens` - Secure QR token management
- `wp_waza_attendance` - Detailed attendance tracking
- `wp_waza_payments` - Payment transaction logs
- `wp_waza_waitlist` - Waitlist management

## Sample Data

The plugin creates sample content to get you started:
- ✅ **Sample Instructor**: Sarah Johnson (instructor@waza.studio)
- ✅ **Sample Activities**: Hip Hop, Yoga, Zumba, Photography
- ✅ **Time Slots**: Next 4 weeks of scheduled classes
- ✅ **User Roles**: Instructor and Student roles with capabilities

### 2. Configure Settings

Go to **Waza → Settings** in WordPress admin:

#### Payment Gateways

**Razorpay (Recommended for India)**
```
Key ID: rzp_test_xxxxxxxxxx
Key Secret: xxxxxxxxxx
Webhook Secret: whsec_xxxxxxxxxx
```

**Stripe**
```
Publishable Key: pk_test_xxxxxxxxxx
Secret Key: sk_test_xxxxxxxxxx
Webhook Endpoint Secret: whsec_xxxxxxxxxx
```

#### Notification Settings
```
SMS Provider: [Twilio/TextLocal/MSG91]
Email Template: [Enable HTML emails]
Reminder Schedule: 24h, 1h before slot
```

### 3. Webhook Configuration

#### Razorpay Webhooks
- URL: `https://yoursite.com/wp-json/waza/v1/payment/webhook/razorpay`
- Events: `payment.captured`, `payment.failed`, `refund.created`

#### Stripe Webhooks  
- URL: `https://yoursite.com/wp-json/waza/v1/payment/webhook/stripe`
- Events: `payment_intent.succeeded`, `payment_intent.payment_failed`, `charge.dispute.created`

### 4. Scanner Device Setup

Create API keys for QR scanners:
1. Go to **Waza → Scanner Devices**
2. Add new device with name and location
3. Copy the generated API key
4. Configure scanner app with:
   - Endpoint: `https://yoursite.com/wp-json/waza/v1/qr/verify`
   - API Key: Generated key
   - Header: `X-API-Key: your_api_key`

## REST API Endpoints

### Calendar & Slots

```http
# Get calendar data
GET /wp-json/waza/v1/calendar?from=2024-01-01&to=2024-01-31&activity=123

# Get slot details
GET /wp-json/waza/v1/slots/456
```

### Booking Management

```http
# Create booking
POST /wp-json/waza/v1/book
Content-Type: application/json

{
  "slot_id": 123,
  "user_name": "John Doe",
  "user_email": "john@example.com", 
  "user_phone": "+91 9876543210",
  "attendees_count": 2,
  "coupon_code": "SAVE10"
}

# Get user bookings
GET /wp-json/waza/v1/bookings?user_email=john@example.com

# Cancel booking
POST /wp-json/waza/v1/bookings/789/cancel
```

### QR Verification

```http
# Verify QR token
POST /wp-json/waza/v1/qr/verify
X-API-Key: scanner_api_key
Content-Type: application/json

{
  "token": "uuid-token-string",
  "scanner_device": "entrance_scanner_1"
}
```

### Instructor Workflows

```http
# Get instructor bookings
GET /wp-json/waza/v1/instructor/123/bookings

# Create workshop invite
POST /wp-json/waza/v1/instructor/workshops
```

## Frontend Integration

### Calendar Widget

```html
<!-- Add to page/post -->
[waza_calendar]

<!-- With parameters -->
[waza_calendar activity="123" instructor="456" theme="dark"]
```

### Booking Modal

```html
<!-- Single slot booking -->
[waza_booking_modal slot_id="123"]

<!-- Generic booking button -->
[waza_booking_button text="Book Now" class="btn-primary"]
```

### JavaScript API

```javascript
// Initialize calendar
const calendar = new WazaCalendar({
  container: '#waza-calendar',
  apiUrl: '/wp-json/waza/v1/',
  theme: 'modern'
});

// Book slot
const booking = await waza.bookSlot({
  slotId: 123,
  userData: {
    name: 'John Doe',
    email: 'john@example.com',
    phone: '+91 9876543210'
  },
  attendeeCount: 2
});
```

## Admin Usage

### Managing Activities

1. **Waza → Activities**
2. Create activities (Dance, Yoga, etc.)
3. Set default capacity, duration, skill level
4. Add description and equipment requirements

### Creating Slots

1. **Waza → Slots → Add New**
2. Select activity and instructor
3. Set date, time, capacity, and price
4. Enable recurring for regular classes

### Booking Management

1. **Waza → Bookings**
2. View all bookings with filters
3. Process cancellations and refunds
4. Export attendance reports (CSV)

### Instructor Dashboard

Instructors can:
- Create workshops and slots
- Generate invite links for students
- Bulk import student lists (CSV)
- View attendance and earnings

## Advanced Features

### Concurrency Control

The plugin uses database transactions with `SELECT FOR UPDATE` to prevent overbooking:

```php
// Atomic booking process
$wpdb->query('START TRANSACTION');
$slot = $wpdb->get_row("SELECT ... FOR UPDATE");
// Check availability and create booking
$wpdb->query('COMMIT');
```

### Waitlist Management

When slots are full:
1. Users automatically added to waitlist
2. Notified when seats become available  
3. 30-minute booking window
4. Automatic priority queue management

### Security Features

- **Input Sanitization**: All inputs sanitized with WordPress functions
- **Capability Checks**: Role-based access control
- **Rate Limiting**: Scanner endpoint rate limiting
- **Secure Tokens**: SHA-256 hashed QR tokens
- **Nonce Verification**: CSRF protection

## Development & Testing

### Running Tests

```bash
# Unit tests
composer test

# Integration tests
./vendor/bin/phpunit tests/integration/

# Concurrency test simulation
php tests/concurrency-test.php
```

### Code Standards

```bash
# Check coding standards
composer phpcs

# Fix coding standards
composer phpcbf
```

### Local Development

```bash
# Start local environment
docker-compose up -d

# Watch for changes
npm run watch

# Build for production
npm run build
```

## Troubleshooting

### Common Issues

**QR Codes Not Generating**
- Check GD extension is installed
- Verify write permissions in uploads directory
- Check error logs for Endroid QR library issues

**Payment Webhooks Failing**
- Verify webhook URLs are accessible
- Check SSL certificate validity
- Confirm webhook secrets match

**Booking Concurrency Issues**
- Enable MySQL query logging
- Check for deadlocks in slow query log
- Verify InnoDB engine is used

**Notifications Not Sending**
- Test SMTP configuration
- Check Action Scheduler queue
- Verify SMS provider API keys

### Debug Mode

Enable debug mode in `wp-config.php`:

```php
define('WAZA_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check logs in `/wp-content/debug.log`

### Performance Optimization

**Database Indexing**
```sql
-- Add indexes for better performance
ALTER TABLE wp_waza_bookings ADD INDEX idx_slot_date (slot_id, created_at);
ALTER TABLE wp_waza_qr_tokens ADD INDEX idx_expires (expires_at, is_active);
```

**Caching**
```php
// Enable object caching
define('WP_CACHE', true);

// Use Redis for session storage
ini_set('session.save_handler', 'redis');
```

## API Documentation

Full OpenAPI specification available at:
`/wp-json/waza/v1/docs`

Postman collection:
`/wp-content/plugins/waza-booking/docs/waza-booking.postman_collection.json`

## Support

- **Documentation**: [GitHub Wiki](wiki-url)
- **Issues**: [GitHub Issues](issues-url)  
- **Discussions**: [GitHub Discussions](discussions-url)
- **Email**: dev@waza.studio

## License

GPL v2 or later. See [LICENSE](LICENSE) file for details.

## Changelog

### Version 1.0.0 (Current)
- Initial release
- Complete booking system with payment integration
- QR verification system
- Instructor workflows
- Admin dashboard
- REST API endpoints
- Frontend widgets and shortcodes

### Upcoming Features
- Mobile app support
- Advanced reporting dashboard
- Multi-language support
- Custom email templates
- Integration with popular calendar apps
- Advanced coupon system