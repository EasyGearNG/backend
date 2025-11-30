# 🎯 Cart & Checkout - Quick Reference

## Setup (1 Minute)
```bash
# Add to .env
PAYSTACK_PUBLIC_KEY=pk_test_xxxxx
PAYSTACK_SECRET_KEY=sk_test_xxxxx
FRONTEND_URL=http://localhost:3000

# Clear cache
php artisan config:clear
```

## API Endpoints

### Cart
```http
GET    /api/v1/cart                    # View cart
POST   /api/v1/cart/add                # Add item
PUT    /api/v1/cart/items/{id}         # Update
DELETE /api/v1/cart/items/{id}         # Remove
DELETE /api/v1/cart/clear              # Clear
```

### Checkout
```http
GET    /api/v1/checkout/summary        # Summary
POST   /api/v1/checkout/initialize     # Start
POST   /api/v1/checkout/verify         # Verify
```

## Quick Test

### 1. Add to Cart
```bash
curl -X POST http://localhost:8000/api/v1/cart/add \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"product_id":1,"quantity":2}'
```

### 2. View Cart
```bash
curl http://localhost:8000/api/v1/cart \
  -H "Authorization: Bearer TOKEN"
```

### 3. Checkout
```bash
curl -X POST http://localhost:8000/api/v1/checkout/initialize \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"shipping_address_id":1}'
```

## Test Card
```
Card: 4084084084084081
CVV: 408
Expiry: 12/25
PIN: 0000
OTP: 123456
```

## Response Structure
```json
{
  "success": true,
  "message": "Success message",
  "data": { }
}
```

## Pricing
```
Subtotal = Σ(price × quantity)
Shipping = ₦2,000
Tax = Subtotal × 7.5%
Total = Subtotal + Shipping + Tax
```

## Order Status
`pending` → `processing` → `shipped` → `delivered`

## Payment Status
`pending` → `success` / `failed`

## Common Errors
- **401**: Missing/invalid token
- **400**: Cart empty or out of stock
- **422**: Validation failed
- **500**: Server error

## Documentation
- Full API: `CART_CHECKOUT_API.md`
- Setup: `SETUP_GUIDE.md`
- Overview: `README_CART_CHECKOUT.md`
- Postman: `postman_cart_checkout_collection.json`
