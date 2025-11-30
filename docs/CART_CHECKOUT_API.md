# Cart and Checkout API Documentation

## Overview
This API provides comprehensive cart management and checkout functionality with Paystack payment integration.

## Setup Instructions

### 1. Environment Configuration
Add the following to your `.env` file:

```env
# Paystack Configuration
PAYSTACK_PUBLIC_KEY=your_paystack_public_key
PAYSTACK_SECRET_KEY=your_paystack_secret_key

# Frontend URL for payment callback
FRONTEND_URL=http://localhost:3000
```

### 2. Get Paystack Keys
1. Sign up at [Paystack](https://paystack.com/)
2. Go to Settings > API Keys & Webhooks
3. Copy your Public Key and Secret Key
4. For testing, use Test Keys
5. For production, use Live Keys

### 3. Run Migrations
```bash
php artisan migrate
```

## API Endpoints

### Cart Management

#### 1. Get Cart
```http
GET /api/v1/cart
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Cart retrieved successfully",
  "data": {
    "cart_id": 1,
    "items": [
      {
        "id": 1,
        "product_id": 5,
        "product_name": "Adjustable Dumbbells Set",
        "product_slug": "adjustable-dumbbells-set",
        "product_image": "http://localhost:8000/storage/products/dumbbells.jpg",
        "vendor_name": "FitGear Pro",
        "price": 45000.00,
        "quantity": 2,
        "subtotal": 90000.00,
        "in_stock": true,
        "stock_quantity": 50
      }
    ],
    "total_items": 2,
    "total_amount": 90000.00
  }
}
```

#### 2. Add Item to Cart
```http
POST /api/v1/cart/add
Authorization: Bearer {token}
Content-Type: application/json

{
  "product_id": 5,
  "quantity": 2
}
```

**Response:**
```json
{
  "success": true,
  "message": "Item added to cart successfully",
  "data": {
    "cart_id": 1,
    "total_items": 2,
    "total_amount": 90000.00,
    "item": {
      "id": 1,
      "product_name": "Adjustable Dumbbells Set",
      "quantity": 2,
      "subtotal": 90000.00
    }
  }
}
```

**Validation Rules:**
- `product_id`: required, must exist in products table
- `quantity`: required, integer, minimum 1

**Error Responses:**
```json
{
  "success": false,
  "message": "Product is out of stock"
}
```

```json
{
  "success": false,
  "message": "Only 10 units available in stock"
}
```

#### 3. Update Cart Item Quantity
```http
PUT /api/v1/cart/items/{itemId}
Authorization: Bearer {token}
Content-Type: application/json

{
  "quantity": 3
}
```

**Response:**
```json
{
  "success": true,
  "message": "Cart item updated successfully",
  "data": {
    "item": {
      "id": 1,
      "quantity": 3,
      "subtotal": 135000.00
    },
    "total_items": 3,
    "total_amount": 135000.00
  }
}
```

#### 4. Remove Item from Cart
```http
DELETE /api/v1/cart/items/{itemId}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Item removed from cart successfully",
  "data": {
    "total_items": 0,
    "total_amount": 0.00
  }
}
```

#### 5. Clear Cart
```http
DELETE /api/v1/cart/clear
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Cart cleared successfully",
  "data": {
    "total_items": 0,
    "total_amount": 0.00
  }
}
```

### Checkout & Payment

#### 1. Get Checkout Summary
```http
GET /api/v1/checkout/summary
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Checkout summary retrieved successfully",
  "data": {
    "subtotal": 90000.00,
    "shipping_cost": 2000.00,
    "tax_amount": 6750.00,
    "total_amount": 98750.00,
    "items_count": 2
  }
}
```

#### 2. Initialize Checkout
```http
POST /api/v1/checkout/initialize
Authorization: Bearer {token}
Content-Type: application/json

{
  "shipping_address_id": 1,
  "billing_address_id": 1,
  "notes": "Please handle with care"
}
```

**Validation Rules:**
- `shipping_address_id`: required, must exist in user_addresses table
- `billing_address_id`: optional, must exist in user_addresses table
- `notes`: optional, string, max 500 characters

**Response:**
```json
{
  "success": true,
  "message": "Checkout initialized successfully",
  "data": {
    "order_id": 123,
    "payment_url": "https://checkout.paystack.com/abc123def456",
    "reference": "ORD-123-1696694400",
    "amount": 98750.00
  }
}
```

**Process Flow:**
1. Cart is validated for stock availability
2. Order is created with status "pending"
3. Order items are created from cart items
4. Payment is initialized with Paystack
5. Payment record is created with status "pending"
6. User is redirected to Paystack payment page

**Error Responses:**
```json
{
  "success": false,
  "message": "Cart is empty"
}
```

```json
{
  "success": false,
  "message": "Product 'Adjustable Dumbbells Set' is out of stock"
}
```

```json
{
  "success": false,
  "message": "Only 5 units of 'Adjustable Dumbbells Set' available"
}
```

#### 3. Verify Payment
```http
POST /api/v1/checkout/verify
Authorization: Bearer {token}
Content-Type: application/json

{
  "reference": "ORD-123-1696694400"
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "Payment verified successfully",
  "data": {
    "order_id": 123,
    "payment_status": "success",
    "order_status": "processing",
    "amount": 98750.00
  }
}
```

**Response (Failed):**
```json
{
  "success": true,
  "message": "Payment failed",
  "data": {
    "order_id": 123,
    "payment_status": "failed",
    "order_status": "failed",
    "amount": 98750.00
  }
}
```

**Process Flow:**
1. Payment is verified with Paystack
2. Payment record is updated with status
3. If successful:
   - Order status is updated to "processing"
   - Product inventory is decremented
   - User's cart is cleared
4. If failed:
   - Order status is updated to "failed"

## Payment Flow

### Frontend Integration

1. **Add items to cart**
   ```javascript
   // Add item to cart
   const response = await fetch('/api/v1/cart/add', {
     method: 'POST',
     headers: {
       'Authorization': 'Bearer ' + token,
       'Content-Type': 'application/json'
     },
     body: JSON.stringify({
       product_id: 5,
       quantity: 2
     })
   });
   ```

2. **Get checkout summary**
   ```javascript
   const summary = await fetch('/api/v1/checkout/summary', {
     headers: { 'Authorization': 'Bearer ' + token }
   });
   ```

3. **Initialize checkout**
   ```javascript
   const checkout = await fetch('/api/v1/checkout/initialize', {
     method: 'POST',
     headers: {
       'Authorization': 'Bearer ' + token,
       'Content-Type': 'application/json'
     },
     body: JSON.stringify({
       shipping_address_id: 1,
       billing_address_id: 1
     })
   });
   
   const data = await checkout.json();
   // Redirect user to payment_url
   window.location.href = data.data.payment_url;
   ```

4. **Handle payment callback**
   ```javascript
   // On your callback page (e.g., /payment/callback)
   const urlParams = new URLSearchParams(window.location.search);
   const reference = urlParams.get('reference');
   
   // Verify payment
   const verification = await fetch('/api/v1/checkout/verify', {
     method: 'POST',
     headers: {
       'Authorization': 'Bearer ' + token,
       'Content-Type': 'application/json'
     },
     body: JSON.stringify({ reference })
   });
   
   const result = await verification.json();
   if (result.data.payment_status === 'success') {
     // Redirect to order confirmation page
     window.location.href = '/orders/' + result.data.order_id;
   } else {
     // Show payment failed message
     alert('Payment failed. Please try again.');
   }
   ```

## Payment Calculations

### Subtotal
Sum of all cart items (quantity × price)

### Shipping Cost
- Flat rate: ₦2,000.00
- Can be customized based on weight, location, etc.

### Tax Amount
- VAT: 7.5% of subtotal
- Formula: `subtotal × 0.075`

### Total Amount
```
total = subtotal + shipping_cost + tax_amount
```

## Order Status Flow

1. **pending** - Order created, awaiting payment
2. **processing** - Payment successful, order being prepared
3. **shipped** - Order dispatched for delivery
4. **delivered** - Order delivered to customer
5. **failed** - Payment failed
6. **cancelled** - Order cancelled by user or admin

## Payment Status

1. **pending** - Payment initiated
2. **success** - Payment successful
3. **failed** - Payment failed
4. **refunded** - Payment refunded

## Testing with Paystack

### Test Cards

**Successful Payment:**
```
Card Number: 4084084084084081
CVV: 408
Expiry: Any future date
PIN: 0000
OTP: 123456
```

**Insufficient Funds:**
```
Card Number: 5060666666666666666
CVV: 123
Expiry: Any future date
```

**Declined:**
```
Card Number: 507850785078507812
CVV: 884
Expiry: Any future date
```

## Error Handling

All endpoints return consistent error responses:

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

Common HTTP status codes:
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

## Security Considerations

1. **Authentication Required**: All cart and checkout endpoints require authentication
2. **Stock Validation**: Stock is validated before checkout and payment verification
3. **Payment Verification**: Always verify payments with Paystack before fulfilling orders
4. **Secure Keys**: Never expose Paystack Secret Key in frontend code
5. **HTTPS Required**: Use HTTPS in production for secure transactions

## Database Schema

### carts
- `id` - Primary key
- `user_id` - Foreign key to users
- `created_at`, `updated_at` - Timestamps

### cart_items
- `id` - Primary key
- `cart_id` - Foreign key to carts
- `product_id` - Foreign key to products
- `quantity` - Integer
- `created_at`, `updated_at` - Timestamps

### orders
- `id` - Primary key
- `user_id` - Foreign key to users
- `order_date` - Timestamp
- `status` - Enum (pending, processing, shipped, delivered, failed, cancelled)
- `total_amount` - Decimal
- `shipping_address_id` - Foreign key to user_addresses
- `billing_address_id` - Foreign key to user_addresses
- `shipping_cost` - Decimal
- `tax_amount` - Decimal
- `discount_amount` - Decimal
- `notes` - Text
- `created_at`, `updated_at` - Timestamps

### order_items
- `id` - Primary key
- `order_id` - Foreign key to orders
- `product_id` - Foreign key to products
- `vendor_id` - Foreign key to vendors
- `quantity` - Integer
- `price` - Decimal
- `total` - Decimal
- `created_at`, `updated_at` - Timestamps

### payments
- `id` - Primary key
- `order_id` - Foreign key to orders
- `amount` - Decimal
- `payment_method` - Enum (card, bank_transfer, mobile_money, cash_on_delivery)
- `status` - Enum (pending, success, failed, refunded)
- `transaction_id` - String (Paystack reference)
- `payment_date` - Timestamp
- `gateway_response` - JSON
- `created_at`, `updated_at` - Timestamps

## Support

For issues or questions:
- Paystack Documentation: https://paystack.com/docs
- Paystack Support: support@paystack.com
