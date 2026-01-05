# Email Template System

The Waza Booking plugin includes a comprehensive email template system that allows you to customize all booking-related notifications sent to users and instructors.

## Features

- **10 Built-in Template Types**: Covering all booking scenarios
- **Visual Editor**: WordPress-integrated editor with rich text formatting
- **Variable Replacement**: Dynamic content using booking data
- **Template Preview**: See how emails will look before sending
- **Test Email Sending**: Send test emails to verify templates
- **Import/Export**: Backup and share template configurations
- **Reset to Defaults**: Easily restore original templates

## Available Templates

### User Templates
1. **Booking Confirmation** - Sent when a booking is confirmed
2. **Booking Reminder** - Sent before the scheduled activity
3. **Booking Cancellation** - Sent when a booking is cancelled
4. **Booking Rescheduled** - Sent when a booking is moved to a different slot
5. **Payment Confirmation** - Sent when payment is received
6. **Waitlist Notification** - Sent when added to or removed from waitlist
7. **Thank You Message** - Sent after activity completion

### System Templates
8. **Instructor Notification** - Sent to instructors about new bookings
9. **Welcome Email** - Sent to new users after registration
10. **Password Reset** - Sent when users request password reset

## Available Variables

### General Variables
- `{{site_name}}` - Your website name
- `{{site_url}}` - Your website URL
- `{{admin_email}}` - Site administrator email
- `{{current_date}}` - Current date
- `{{current_time}}` - Current time
- `{{current_year}}` - Current year

### User Variables
- `{{user_name}}` - User's display name
- `{{user_email}}` - User's email address
- `{{user_first_name}}` - User's first name
- `{{user_last_name}}` - User's last name
- `{{user_phone}}` - User's phone number
- `{{user_id}}` - User ID number

### Booking Variables
- `{{booking_id}}` - Unique booking ID
- `{{booking_status}}` - Current booking status
- `{{booking_date}}` - Date when booking was made
- `{{booking_notes}}` - Special notes from user
- `{{qr_code}}` - QR code for check-in
- `{{cancellation_reason}}` - Reason for cancellation (if applicable)

### Activity Variables
- `{{activity_name}}` - Name of the booked activity
- `{{activity_description}}` - Activity description
- `{{activity_duration}}` - How long the activity lasts
- `{{activity_category}}` - Activity category/type
- `{{activity_requirements}}` - Special requirements or equipment needed
- `{{slot_date}}` - Scheduled date for the activity
- `{{slot_time}}` - Scheduled time for the activity
- `{{slot_location}}` - Where the activity takes place

### Instructor Variables
- `{{instructor_name}}` - Instructor's name
- `{{instructor_email}}` - Instructor's email address
- `{{instructor_phone}}` - Instructor's phone number
- `{{instructor_bio}}` - Instructor's biography

### Payment Variables
- `{{payment_amount}}` - Total amount paid
- `{{payment_method}}` - How payment was made
- `{{payment_date}}` - When payment was processed
- `{{payment_id}}` - Payment transaction ID
- `{{refund_amount}}` - Refund amount (if applicable)

## Using the Email Template Editor

### Accessing Templates
1. Go to your WordPress admin dashboard
2. Navigate to **Waza > Email Templates**
3. Select a template from the sidebar to edit

### Editing Templates
1. **Subject Line**: Click on the subject field to edit the email subject
2. **Content Editor**: Use the visual editor to format your email content
3. **Insert Variables**: Click "Insert Variable" to add dynamic content
4. **Preview**: Click "Preview" to see how the email will look
5. **Save**: Click "Save Template" to store your changes

### Testing Templates
1. Edit any template
2. Click "Send Test Email"
3. Enter your email address
4. Check your inbox for the test email

### Resetting Templates
If you want to restore a template to its default:
1. Open the template you want to reset
2. Click "Reset to Default"
3. Confirm the action

## Template Best Practices

### Design Guidelines
- Keep emails under 600px wide for better mobile compatibility
- Use web-safe fonts (Arial, Helvetica, Georgia, Times New Roman)
- Include your brand colors but ensure good contrast for readability
- Test templates in multiple email clients

### Content Guidelines
- Keep subject lines under 50 characters
- Include all essential information (date, time, location)
- Add clear call-to-action buttons when needed
- Provide contact information for questions

### Variable Usage
- Always include `{{user_name}}` for personalization
- Use `{{booking_id}}` for reference in support communications
- Include `{{qr_code}}` in confirmation emails for easy check-in
- Add `{{site_name}}` to reinforce your brand

## Advanced Customization

### HTML Templates
The email templates support full HTML formatting. You can:
- Add custom CSS styles (inline styles recommended)
- Include images (use absolute URLs)
- Create responsive layouts
- Add custom branding elements

### Template Structure
```html
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <header style="background: #your-brand-color; padding: 20px; text-align: center;">
        <h1 style="color: white; margin: 0;">{{site_name}}</h1>
    </header>
    
    <main style="padding: 30px;">
        <h2>Hello {{user_name}}!</h2>
        <p>Your booking for {{activity_name}} has been confirmed.</p>
        
        <div style="background: #f8f9fa; padding: 20px; border-radius: 5px;">
            <h3>Booking Details</h3>
            <p><strong>Date:</strong> {{slot_date}}</p>
            <p><strong>Time:</strong> {{slot_time}}</p>
            <p><strong>Location:</strong> {{slot_location}}</p>
        </div>
    </main>
    
    <footer style="background: #f8f9fa; padding: 20px; text-align: center;">
        <p>Questions? Contact us at {{admin_email}}</p>
    </footer>
</div>
```

## Troubleshooting

### Templates Not Saving
- Check that you have the correct permissions (manage_options capability)
- Ensure the database tables were created during plugin activation
- Verify there are no PHP errors in your WordPress error log

### Variables Not Replacing
- Make sure variables are enclosed in double curly braces: `{{variable_name}}`
- Check that the variable name is spelled correctly
- Ensure the booking data includes the variable you're trying to use

### Emails Not Sending
- Verify your WordPress site can send emails (test with password reset)
- Check your spam folder
- Consider using an SMTP plugin for better email delivery
- Review the notification manager settings

### Styling Issues
- Use inline CSS styles for better email client compatibility
- Test templates in multiple email clients (Gmail, Outlook, Apple Mail)
- Keep layouts simple and avoid complex positioning

## Integration with Booking System

The email template system automatically integrates with the booking workflow:

1. **Booking Created** → Booking Confirmation email sent
2. **Payment Received** → Payment Confirmation email sent  
3. **24 Hours Before** → Booking Reminder email sent
4. **After Activity** → Thank You Message email sent
5. **Booking Cancelled** → Cancellation email sent
6. **Slot Changed** → Rescheduled email sent

## Support

For additional help with email templates:
- Check the plugin documentation
- Contact plugin support
- Review WordPress email best practices
- Test thoroughly before going live

Remember to always test your email templates with real data before using them with actual customers!