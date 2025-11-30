# Cart & Checkout Setup Guide

## Quick Setup (5 Minutes)

### Step 1: Add Paystack Credentials to .env
```bash
# Open your .env file and add these lines:
PAYSTACK_PUBLIC_KEY=pk_test_your_public_key_here
PAYSTACK_SECRET_KEY=sk_test_your_secret_key_here
FRONTEND_URL=http://localhost:3000
```

**Get your Paystack keys:**
1. Go to https://paystack.com and sign up/login
2. Navigate to Settings > API Keys & Webhooks
3. Copy your Test Public Key and Test Secret Key
4. Paste them in your .env file

### Step 2: Clear Configuration Cache
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/afiemo/backend
php artisan config:clear
php artisan cache:clear
```

### Step 3: Test the Endpoints

**Option A: Using cURL**

1. **Get Cart** (should be empty initially)
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json" \
     http://localhost:8000/api/v1/cart
```

2. **Add Item to Cart**
```bash
curl -X POST http://localhost:8000/api/v1/cart/add \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     -d '{"product_id": 1, "quantity": 2}'
```

3. **Get Checkout Summary**
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json" \
     http://localhost:8000/api/v1/checkout/summary
```

**Option B: Using Postman**
1. Import `postman_cart_checkout_collection.json`
2. Set the `token` variable to your auth token
3. Run the requests in order

### Step 4: Test Complete Payment Flow

1. **Add products to cart**
2. **Initialize checkout** - you'll get a payment URL
3. **Visit the payment URL** and complete payment with test card:
   - Card: 4084084084084081
   - CVV: 408
   - Expiry: Any future date
   - PIN: 0000
   - OTP: 123456
4. **Verify payment** with the reference returned

## API Endpoints Summary

### Cart Management (Authenticated)
```
GET    /api/v1/cart              - View cart
POST   /api/v1/cart/add          - Add item
PUT    /api/v1/cart/items/{id}   - Update quantity
DELETE /api/v1/cart/items/{id}   - Remove item
DELETE /api/v1/cart/clear        - Clear cart
```

### Checkout (Authenticated)
```
GET    /api/v1/checkout/summary     - Get totals
POST   /api/v1/checkout/initialize  - Start checkout
POST   /api/v1/checkout/verify      - Verify payment
```

## Payment Calculation

```
Subtotal:      Sum of (price × quantity) for all items
Shipping:      ₦2,000.00 (flat rate)
Tax (VAT):     7.5% of subtotal
Total:         Subtotal + Shipping + Tax
```

Example:
```
Product A: ₦45,000 × 2 = ₦90,000
Subtotal: ₦90,000
Shipping: ₦2,000
Tax: ₦6,750 (7.5% of ₦90,000)
Total: ₦98,750
```

## Testing Paystack Cards

**Success:**
- Card: 4084084084084081
- CVV: 408
- Expiry: 12/25
- PIN: 0000
- OTP: 123456

**Insufficient Funds:**
- Card: 5060666666666666666
- CVV: 123
- Expiry: 12/25

**Declined:**
- Card: 507850785078507812
- CVV: 884
- Expiry: 12/25

## Important Notes

1. **Authentication Required**: All cart and checkout endpoints require a valid Bearer token
2. **Stock Validation**: System checks stock availability before checkout and payment
3. **Auto Cart Clear**: Cart is automatically cleared after successful payment
4. **Inventory Update**: Product quantities are decremented after successful payment
5. **Order Tracking**: Orders are created with tracking through order status

## Order Status Flow
```
pending → processing → shipped → delivered
   ↓
 failed (if payment fails)
```

## Troubleshooting

**Error: "Cart is empty"**
- Add items to cart first using POST /api/v1/cart/add

**Error: "Product is out of stock"**
- Check product quantity in database
- Ensure product.quantity > 0

**Error: "Failed to initialize payment"**
- Check Paystack credentials in .env
- Ensure PAYSTACK_SECRET_KEY is correct
- Clear config cache: `php artisan config:clear`

**Error: "Unauthenticated"**
- Include Authorization header: `Bearer YOUR_TOKEN`
- Ensure token is valid (not expired)

## Files Created

1. **Controllers:**
   - `app/Http/Controllers/Api/CartController.php`
   - `app/Http/Controllers/Api/CheckoutController.php`

2. **Models Updated:**
   - `app/Models/Cart.php`
   - `app/Models/CartItem.php`
   - `app/Models/OrderItem.php`

3. **Routes:**
   - `routes/api.php` (updated with cart & checkout routes)

4. **Documentation:**
   - `CART_CHECKOUT_API.md` (complete API documentation)
   - `SETUP_GUIDE.md` (this file)
   - `.env.paystack.example` (environment variables template)
   - `postman_cart_checkout_collection.json` (Postman collection)

## Next Steps

1. **Frontend Integration**: Use the API endpoints in your React/Vue/Angular app
2. **Customize Shipping**: Update `calculateShipping()` method in CheckoutController
3. **Customize Tax**: Update `calculateTax()` method in CheckoutController
4. **Add Webhooks**: Set up Paystack webhooks for automated payment notifications
5. **Add Order Management**: Create endpoints for viewing and managing orders

## Support

- Full documentation: `CART_CHECKOUT_API.md`
- Paystack docs: https://paystack.com/docs
- Test your integration: https://dashboard.paystack.com/#/test
