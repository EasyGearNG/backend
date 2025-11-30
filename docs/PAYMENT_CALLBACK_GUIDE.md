# 💳 Payment Callback Simulation Guide

## ✅ What's Been Created

A complete payment callback system has been implemented to **simulate the frontend callback URL** directly in your Laravel backend. This allows you to test the entire payment flow without needing a separate frontend application!

---

## 🎯 How It Works

```
User → Checkout API → Paystack Payment Page → Callback Route → Verify Payment → Success/Error Page
```

### Flow Diagram
```
1. POST /api/v1/checkout/initialize
   ↓
2. User redirected to Paystack
   ↓
3. User completes payment
   ↓
4. Paystack redirects to: http://localhost:8000/payment/callback?reference=XXX
   ↓
5. Backend verifies payment automatically
   ↓
6. Shows success or error page
```

---

## 📁 Files Created

### Controllers
✅ `app/Http/Controllers/PaymentCallbackController.php` - Handles callback and verification

### Views
✅ `resources/views/payment/success.blade.php` - Beautiful success page
✅ `resources/views/payment/error.blade.php` - Beautiful error page

### Routes
✅ `routes/web.php` - Added payment callback routes

---

## 🚀 Usage

### Method 1: Complete Flow (Recommended)

1. **Login and get token:**
```bash
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"login":"john@example.com","password":"password123"}'
```

2. **Add items to cart:**
```bash
curl -X POST http://localhost:8000/api/v1/cart/add \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"product_id":1,"quantity":2}'
```

3. **Initialize checkout:**
```bash
curl -X POST http://localhost:8000/api/v1/checkout/initialize \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"shipping_address_id":1}'
```

4. **Response will include payment_url:**
```json
{
  "data": {
    "order_id": 1,
    "payment_url": "https://checkout.paystack.com/abc123",
    "reference": "ORD-1-1697626800",
    "amount": 98750.00
  }
}
```

5. **Visit the payment_url in your browser**

6. **Complete payment with test card:**
   - Card: 4084084084084081
   - CVV: 408
   - Expiry: 12/25
   - PIN: 0000
   - OTP: 123456

7. **Paystack automatically redirects to:**
```
http://localhost:8000/payment/callback?reference=ORD-1-1697626800
```

8. **Backend automatically verifies and shows result page! ✅**

---

### Method 2: Direct Callback Testing

If you already have a reference from a previous payment:

```
http://localhost:8000/payment/callback?reference=ORD-1-1697626800
```

Or use the test route:

```
http://localhost:8000/payment/test?reference=ORD-1-1697626800
```

---

## 🌐 Routes Available

### Payment Callback Route
```
GET /payment/callback?reference={reference}
```
**Purpose:** Receives redirect from Paystack, verifies payment, shows result

**Parameters:**
- `reference` (required): Payment reference from Paystack
- `status` (optional): Payment status from Paystack

**Response:** HTML page (success or error)

---

### Test Verification Route
```
GET /payment/test?reference={reference}
```
**Purpose:** Manually test payment verification with a reference

**Parameters:**
- `reference` (required): Payment reference to verify

**Response:** Redirects to callback route

---

## 🎨 Success Page Features

✅ **Animated checkmark**
✅ **Order details display**
✅ **Amount paid**
✅ **Reference number**
✅ **Continue shopping button**
✅ **Beautiful gradient design**

### Success Page Preview
```
┌────────────────────────────────────┐
│         ✓ (animated)               │
│                                    │
│   Payment Successful! 🎉          │
│                                    │
│   Your order is being processed.   │
│                                    │
│   Order ID:        #123            │
│   Amount Paid:     ₦98,750.00     │
│   Status:          Confirmed       │
│                                    │
│   [Continue Shopping] [Go Home]    │
│                                    │
│   Reference: ORD-123-1697626800    │
└────────────────────────────────────┘
```

---

## 🚨 Error Page Features

✅ **Animated error icon**
✅ **Error message display**
✅ **Reference number**
✅ **Help text**
✅ **Return to cart button**
✅ **Beautiful red gradient design**

### Error Page Preview
```
┌────────────────────────────────────┐
│         ✗ (animated)               │
│                                    │
│     Payment Failed                 │
│         [ERROR]                    │
│                                    │
│   Payment verification failed.     │
│   Please contact support.          │
│                                    │
│   Reference: ORD-123-1697626800    │
│                                    │
│   [Return to Cart] [Go Home]       │
│                                    │
│   Need Help?                       │
│   Contact support with reference   │
└────────────────────────────────────┘
```

---

## ⚙️ Configuration

### Use Backend Callback (Default)
Leave `FRONTEND_URL` empty or unset in `.env`:
```env
# FRONTEND_URL not set - uses backend callback
```

Callback URL: `http://localhost:8000/payment/callback`

### Use Frontend Callback
Set `FRONTEND_URL` in `.env`:
```env
FRONTEND_URL=http://localhost:3000
```

Callback URL: `http://localhost:3000/payment/callback`

---

## 🧪 Testing Scenarios

### Test Case 1: Successful Payment
1. Initialize checkout
2. Go to payment URL
3. Use test card: 4084084084084081
4. Complete payment
5. See success page ✅

### Test Case 2: Failed Payment
1. Initialize checkout
2. Go to payment URL
3. Use declined card: 507850785078507812
4. Complete payment
5. See error page ❌

### Test Case 3: Manual Verification
1. Get reference from previous payment
2. Visit: `/payment/callback?reference=YOUR_REF`
3. See result page

---

## 📊 What Happens Behind the Scenes

### On Callback:

1. **Receive Reference**
   ```php
   $reference = $request->query('reference');
   ```

2. **Find Payment Record**
   ```php
   $payment = Payment::where('transaction_id', $reference)->first();
   ```

3. **Get User Token**
   ```php
   $user = $payment->order->user;
   $token = $user->createToken('verification')->plainTextToken;
   ```

4. **Call Verification API**
   ```php
   POST /api/v1/checkout/verify
   Authorization: Bearer {token}
   Body: { "reference": "..." }
   ```

5. **Process Result**
   - Success → Show success page + clear cart
   - Failed → Show error page

---

## 🔧 Customization

### Customize Success Page
Edit: `resources/views/payment/success.blade.php`

```php
// Add more order details
<div class="detail-row">
    <span class="detail-label">Shipping Address</span>
    <span class="detail-value">{{ $shipping_address }}</span>
</div>
```

### Customize Error Page
Edit: `resources/views/payment/error.blade.php`

```php
// Add retry button
<a href="/retry-payment/{{ $reference }}" class="button">
    Retry Payment
</a>
```

### Add Email Notification
Edit: `PaymentCallbackController.php`

```php
if ($data['success']) {
    // Send success email
    Mail::to($user->email)->send(new OrderConfirmation($order));
}
```

---

## 📱 Mobile Responsive

Both pages are **fully responsive** and look great on:
- 📱 Mobile phones
- 💻 Tablets
- 🖥️ Desktop computers

---

## 🔒 Security Notes

1. **Token Generation:** Temporary token is created for verification only
2. **Reference Validation:** Reference is validated before processing
3. **Payment Verification:** Always verified with Paystack API
4. **User Authentication:** Uses user's actual authentication context

---

## 🐛 Troubleshooting

### Error: "Payment record not found"
**Cause:** Invalid reference or payment doesn't exist
**Solution:** Check that reference matches a payment in database

### Error: "Payment verification failed"
**Cause:** Paystack API returned error
**Solution:** Check Paystack credentials, internet connection

### Callback not triggered
**Cause:** Paystack can't reach your local server
**Solution:** 
- Use ngrok for local testing: `ngrok http 8000`
- Or test manually with `/payment/test?reference=XXX`

---

## 🎯 Production Considerations

### For Production:

1. **Use HTTPS:**
```env
APP_URL=https://yourdomain.com
FRONTEND_URL=https://yourdomain.com
```

2. **Remove Test Route:**
```php
// Comment out in production
// Route::get('/payment/test', ...);
```

3. **Add Webhooks:**
Create webhook handler for automated notifications

4. **Add Email Notifications:**
Send confirmation emails on success

5. **Add Order Tracking:**
Redirect to order tracking page

---

## 📖 Example Complete Test

### Step-by-Step Test:

```bash
# 1. Login
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"login":"john@example.com","password":"password123"}' \
  | jq -r '.data.token'

# Output: 1|abc123...

# 2. Add to cart
curl -X POST http://localhost:8000/api/v1/cart/add \
  -H "Authorization: Bearer 1|abc123..." \
  -H "Content-Type: application/json" \
  -d '{"product_id":1,"quantity":2}'

# 3. Initialize checkout
curl -X POST http://localhost:8000/api/v1/checkout/initialize \
  -H "Authorization: Bearer 1|abc123..." \
  -H "Content-Type: application/json" \
  -d '{"shipping_address_id":1}'

# Output includes payment_url

# 4. Open payment_url in browser

# 5. Complete payment with test card

# 6. Automatically redirected to success page! ✅
```

---

## 🎉 Benefits

✅ **No Frontend Required** - Test complete flow in backend
✅ **Beautiful UI** - Professional success/error pages
✅ **Automatic Verification** - No manual API calls needed
✅ **Real Flow Testing** - Tests exactly like production
✅ **Easy Debugging** - See errors in nice UI

---

## 📚 Related Documentation

- **CART_CHECKOUT_API.md** - Complete API reference
- **AUTHENTICATION_GUIDE.md** - Authentication guide
- **IMPLEMENTATION_COMPLETE.md** - Full system overview

---

## ✅ Ready to Test!

Everything is set up and ready to go! Just:
1. Add Paystack credentials to `.env`
2. Clear config cache: `php artisan config:clear`
3. Follow the "Complete Flow" steps above
4. Enjoy seamless payment testing! 🚀

---

**Happy Testing! 💳✨**
