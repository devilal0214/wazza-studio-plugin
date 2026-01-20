# Razorpay Payment Setup Guide

## ✅ Razorpay Integration Status: READY TO USE

Razorpay is **fully implemented** and ready for production use. Follow these steps to activate it:

---

## 🚀 Step 1: Get Razorpay Credentials

### 1.1 Login to Razorpay Dashboard
- Go to https://dashboard.razorpay.com/
- Login with your approved account

### 1.2 Navigate to API Keys
- Click on **Settings** (gear icon)
- Select **API Keys** from the left menu
- Click **Generate Test Keys** or **Generate Live Keys**

### 1.3 Copy Your Credentials
You'll get two keys:
- **Key ID**: Starts with `rzp_test_` (test) or `rzp_live_` (live)
- **Key Secret**: Keep this secure, never share publicly

---

## ⚙️ Step 2: Configure in WordPress

### 2.1 Go to Settings Page
Navigate to: **WordPress Admin → Wazza Studio → Settings**

### 2.2 Find Payment Gateway Section
Scroll to **Payment Gateway Settings**

### 2.3 Enter Razorpay Details

**Enable Razorpay:**
- Check the "Enable Razorpay" checkbox

**Razorpay Key ID:**
```
rzp_test_xxxxxxxxxxxxxxx (for testing)
rzp_live_xxxxxxxxxxxxxxx (for production)
```

**Razorpay Key Secret:**
```
Your secret key (keep confidential)
```

**Razorpay Webhook Secret:** (Optional for now)
```
Leave blank initially
Can be configured later for advanced features
```

### 2.4 Save Settings
Click **Save Changes** button at the bottom

---

## 🔐 Step 3: Test Mode vs Live Mode

### Test Mode (Development)
- Use keys starting with `rzp_test_`
- No real money is charged
- Use test cards for payments
- Perfect for testing the flow

### Live Mode (Production)
- Use keys starting with `rzp_live_`
- Real money transactions
- Requires KYC verification on Razorpay
- Only use after thorough testing

---

## 💳 Step 4: Test Payment Flow

### 4.1 Create a Test Booking

1. Go to frontend as a student
2. Select an activity and slot
3. Fill booking form
4. Select **Razorpay** as payment method
5. Click **Book Now**

### 4.2 Razorpay Payment Modal Opens

You'll see a popup with:
- Booking details
- Amount to pay
- Payment options (cards, UPI, wallets)

### 4.3 Use Test Cards (Test Mode Only)

**Successful Payment:**
```
Card Number: 4111 1111 1111 1111
CVV: Any 3 digits
Expiry: Any future date
Name: Any name
```

**Failed Payment (to test error handling):**
```
Card Number: 4000 0000 0000 0002
```

### 4.4 Verify Payment Success

After successful payment:
- ✅ Booking status becomes "Confirmed"
- ✅ Payment status becomes "Completed"
- ✅ User receives confirmation email
- ✅ QR code generated for attendance
- ✅ Booking appears in admin panel

---

## 📊 Step 5: View Razorpay Dashboard

### In Razorpay Dashboard You Can See:

- **Payments**: All transactions
- **Orders**: Booking orders created
- **Customers**: Student information
- **Settlement**: Money transfers to your bank

---

## 🔧 Technical Details

### Payment Flow

```
1. Student fills booking form
   ↓
2. JavaScript calls: wp_ajax_waza_create_payment_order
   ↓
3. PHP creates Razorpay order via API
   ↓
4. Razorpay order ID returned to frontend
   ↓
5. Razorpay checkout modal opens
   ↓
6. Student completes payment
   ↓
7. Razorpay returns: payment_id, order_id, signature
   ↓
8. JavaScript calls: wp_ajax_waza_verify_payment
   ↓
9. PHP verifies signature using secret key
   ↓
10. If valid: Update booking to "confirmed", payment to "completed"
    ↓
11. Send confirmation email, generate QR code
    ↓
12. Student sees success message
```

### Database Updates

**When Order Created:**
```sql
INSERT INTO wp_waza_payments (
    booking_id,
    payment_method = 'razorpay',
    gateway_order_id = 'order_xxxxx',
    amount,
    currency = 'INR',
    status = 'pending'
)
```

**When Payment Verified:**
```sql
UPDATE wp_waza_payments 
SET status = 'completed',
    gateway_payment_id = 'pay_xxxxx',
    paid_at = NOW()
WHERE gateway_order_id = 'order_xxxxx'

UPDATE wp_waza_bookings
SET booking_status = 'confirmed',
    payment_status = 'completed'
WHERE id = <booking_id>
```

---

## 🌐 Webhook Setup (Advanced - Optional)

Webhooks notify your site automatically when payment status changes, even if user closes browser.

### 5.1 Get Webhook URL

Your webhook URL:
```
https://yourdomain.com/wp-json/waza/v1/razorpay/webhook
```

### 5.2 Configure in Razorpay

1. Razorpay Dashboard → Settings → Webhooks
2. Click **Add New Webhook**
3. Enter your webhook URL
4. Select events:
   - ✅ payment.authorized
   - ✅ payment.captured
   - ✅ payment.failed
5. Click **Create Webhook**
6. Copy the **Webhook Secret**

### 5.3 Add Secret to WordPress

- Go to WordPress Settings → Payment Gateway
- Paste secret in **Razorpay Webhook Secret** field
- Save changes

---

## 🔍 Troubleshooting

### Issue: "Razorpay not configured" Error

**Solution:**
- Verify Key ID and Secret are entered correctly
- Check if "Enable Razorpay" is checked
- Clear WordPress cache
- Try saving settings again

### Issue: Payment Modal Doesn't Open

**Solution:**
- Check browser console for JavaScript errors
- Verify Razorpay SDK is loading (check network tab)
- Ensure no ad-blockers blocking Razorpay domain
- Test in incognito mode

### Issue: "Signature verification failed"

**Solution:**
- Verify Key Secret is correct
- Check for extra spaces in credentials
- Ensure using matching test/live key pairs

### Issue: Payment Succeeds but Booking Not Confirmed

**Solution:**
- Check WordPress error logs
- Verify webhook endpoint is accessible
- Test manual verification API call
- Check database `wp_waza_payments` table

---

## 📱 Frontend Payment UI

### Payment Modal Features

The Razorpay checkout modal includes:
- **Multiple Payment Methods**: Cards, UPI, Wallets, Net Banking
- **Saved Cards**: For returning customers
- **Auto-retry**: If payment fails
- **Mobile Optimized**: Works on all devices
- **Secure**: PCI DSS compliant
- **Real-time Validation**: Card number, CVV checks

### Student Experience

1. Student selects slot and fills form
2. Clicks "Book Now"
3. Beautiful Razorpay modal appears
4. Chooses payment method (UPI is popular in India)
5. Completes payment via app/card
6. Returns to site with confirmation
7. Receives email with booking details + QR code
8. Can view booking in "My Account"

---

## 💡 Best Practices

### Security
- ✅ Never commit Key Secret to version control
- ✅ Use environment variables in production
- ✅ Enable webhook signature verification
- ✅ Use HTTPS for your website
- ✅ Regularly rotate API keys

### Testing
- ✅ Always test with test mode first
- ✅ Test all payment methods (card, UPI, wallet)
- ✅ Test failure scenarios
- ✅ Verify email notifications work
- ✅ Check QR code generation

### Production
- ✅ Complete Razorpay KYC verification
- ✅ Set up automatic settlements to bank
- ✅ Monitor Razorpay dashboard daily
- ✅ Set up payment alerts
- ✅ Keep WordPress plugin updated

---

## 📊 Currency Support

Razorpay supports **100+ currencies**, but primarily:

### Indian Rupees (INR)
- Default and recommended for Indian businesses
- No currency conversion fees
- Fastest settlements

### International Currencies
- USD, EUR, GBP, etc.
- Requires international payment activation
- Additional fees may apply
- Contact Razorpay support to enable

---

## 🎯 Quick Setup Checklist

- [ ] Razorpay account approved and active
- [ ] API keys (Key ID + Secret) obtained
- [ ] Keys entered in WordPress settings
- [ ] "Enable Razorpay" checkbox enabled
- [ ] Settings saved
- [ ] Test booking created
- [ ] Test payment completed successfully
- [ ] Booking confirmed in admin
- [ ] Email received with QR code
- [ ] Payment visible in Razorpay dashboard

---

## 📞 Support Resources

### Razorpay Support
- Website: https://razorpay.com/support/
- Email: support@razorpay.com
- Phone: +91-80-61919888 (India)
- Docs: https://razorpay.com/docs/

### Plugin Support
- Check WordPress Admin → Wazza Studio → Settings
- Review error logs: wp-content/debug.log
- Check database: wp_waza_payments table
- Contact plugin developer if issues persist

---

## 🚀 You're All Set!

Once configured, Razorpay will:
- ✅ Automatically handle all payments
- ✅ Process refunds when needed
- ✅ Send payment notifications
- ✅ Update booking statuses
- ✅ Generate QR codes for attendance

**Start accepting payments in minutes!** 💰
