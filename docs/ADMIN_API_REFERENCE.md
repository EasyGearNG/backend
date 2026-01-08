# Admin API Reference

Complete documentation for all admin-level API endpoints in the Easygear platform.

---

## Authentication

### Admin Login

**Endpoint:** `POST /api/v1/login`

**Headers:**
```
Content-Type: application/json
```

**Request Body:**
```json
{
  "email": "admin@example.com",
  "password": "your_admin_password"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "Admin User",
      "email": "admin@example.com",
      "role": "admin",
      "is_active": true
    },
    "token": "1|xyz...token"
  }
}
```

**Note:** The user must have `role = "admin"` in the users table to access admin endpoints.

---

## Admin Endpoints

**Base URL:** `/api/v1/admin`

**Required Headers for all admin endpoints:**
```
Authorization: Bearer {admin_token}
Content-Type: application/json
Accept: application/json
```

---

## 1. Dashboard & Analytics

### Get Dashboard Statistics

**Endpoint:** `GET /api/v1/admin/dashboard`

Returns overview statistics including total users, orders, revenue, products, etc.

**Response:**
```json
{
  "success": true,
  "data": {
    "total_users": 1250,
    "total_orders": 3420,
    "total_revenue": 15000000.00,
    "total_products": 450,
    "pending_orders": 23,
    "recent_orders": [...],
    "top_products": [...]
  }
}
```

---

### Get Revenue Analytics

**Endpoint:** `GET /api/v1/admin/revenue-analytics`

Returns detailed revenue and sales analytics.

**Query Parameters:**
- `period` - Time period (optional): `today`, `week`, `month`, `year`
- `start_date` - Start date (optional): `YYYY-MM-DD`
- `end_date` - End date (optional): `YYYY-MM-DD`

**Response:**
```json
{
  "success": true,
  "data": {
    "total_revenue": 15000000.00,
    "total_orders": 3420,
    "average_order_value": 4385.96,
    "revenue_by_period": [...],
    "top_selling_products": [...],
    "revenue_by_category": [...]
  }
}
```

---

## 2. User Management

### List All Users

**Endpoint:** `GET /api/v1/admin/users`

**Query Parameters:**
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 20)
- `search` - Search by name or email
- `role` - Filter by role: `admin`, `vendor`, `customer`
- `is_active` - Filter by status: `true`, `false`

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "customer",
        "is_active": true,
        "created_at": "2025-01-01T10:00:00.000000Z"
      }
    ],
    "total": 100,
    "per_page": 20
  }
}
```

---

### Get Single User

**Endpoint:** `GET /api/v1/admin/users/{id}`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "customer",
    "phone_number": "+2348012345678",
    "is_active": true,
    "orders_count": 15,
    "total_spent": 750000.00,
    "created_at": "2025-01-01T10:00:00.000000Z"
  }
}
```

---

### Update User

**Endpoint:** `PUT /api/v1/admin/users/{id}`

**Request Body:**
```json
{
  "name": "Updated Name",
  "email": "newemail@example.com",
  "phone_number": "+2348012345678",
  "role": "customer",
  "is_active": true
}
```

**Response:**
```json
{
  "success": true,
  "message": "User updated successfully",
  "data": {
    "id": 1,
    "name": "Updated Name",
    "email": "newemail@example.com",
    "role": "customer",
    "is_active": true
  }
}
```

---

### Delete User

**Endpoint:** `DELETE /api/v1/admin/users/{id}`

**Response:**
```json
{
  "success": true,
  "message": "User deleted successfully"
}
```

---

## 3. Order Management

### List All Orders

**Endpoint:** `GET /api/v1/admin/orders`

**Query Parameters:**
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 20)
- `status` - Filter by status: `pending`, `processing`, `shipped`, `delivered`, `cancelled`, `failed`
- `search` - Search by order ID or customer name
- `date_from` - Filter from date: `YYYY-MM-DD`
- `date_to` - Filter to date: `YYYY-MM-DD`

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "order_date": "2025-12-29T09:43:47.000000Z",
        "status": "processing",
        "total_amount": 95000.00,
        "user": {
          "id": 5,
          "name": "Customer Name",
          "email": "customer@example.com"
        },
        "items_count": 3
      }
    ],
    "total": 500
  }
}
```

---

### Get Single Order

**Endpoint:** `GET /api/v1/admin/orders/{id}`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "order_date": "2025-12-29T09:43:47.000000Z",
    "status": "processing",
    "total_amount": 95000.00,
    "shipping_cost": 2000.00,
    "tax_amount": 3000.00,
    "user": {
      "id": 5,
      "name": "Customer Name",
      "email": "customer@example.com"
    },
    "items": [
      {
        "id": 1,
        "product_name": "Product Name",
        "quantity": 2,
        "price_at_purchase": 45000.00,
        "subtotal": 90000.00
      }
    ],
    "shipping_address": {...},
    "payment": {
      "status": "success",
      "payment_method": "card",
      "amount": 95000.00
    }
  }
}
```

---

### Update Order Status

**Endpoint:** `PATCH /api/v1/admin/orders/{id}/status`

**Request Body:**
```json
{
  "status": "processing"
}
```

**Status Options:**
- `pending` - Order placed, awaiting processing
- `processing` - Order is being prepared
- `shipped` - Order has been shipped
- `delivered` - Order delivered to customer
- `cancelled` - Order cancelled
- `failed` - Order failed

**Response:**
```json
{
  "success": true,
  "message": "Order status updated successfully",
  "data": {
    "id": 1,
    "status": "processing",
    "updated_at": "2025-12-29T10:00:00.000000Z"
  }
}
```

---

## 4. Product Management

### List All Products

**Endpoint:** `GET /api/v1/admin/products`

**Query Parameters:**
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 20)
- `search` - Search by product name
- `status` - Filter by status: `active`, `inactive`, `draft`
- `category_id` - Filter by category ID
- `vendor_id` - Filter by vendor ID
- `is_featured` - Filter featured products: `true`, `false`

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "Product Name",
        "slug": "product-name-1234567890",
        "price": 45000.00,
        "quantity": 100,
        "status": "active",
        "is_featured": false,
        "category": {
          "id": 1,
          "name": "Electronics"
        },
        "vendor": {
          "id": 1,
          "business_name": "Vendor Name"
        }
      }
    ]
  }
}
```

---

### Get Single Product

**Endpoint:** `GET /api/v1/admin/products/{id}`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Product Name",
    "slug": "product-name-1234567890",
    "short_description": "Brief description",
    "description": "Full product description",
    "price": 45000.00,
    "quantity": 100,
    "sku": "PROD-001",
    "status": "active",
    "is_featured": false,
    "category": {...},
    "vendor": {...},
    "images": [...],
    "reviews": [...],
    "average_rating": 4.5,
    "total_reviews": 23
  }
}
```

---

### Create Product

**Endpoint:** `POST /api/v1/admin/products`

**Request Body:**
```json
{
  "vendor_id": 1,
  "category_id": 1,
  "name": "New Product",
  "short_description": "Brief description",
  "description": "Full product description",
  "price": 25000.00,
  "quantity": 100,
  "sku": "PROD-001",
  "weight": 1.5,
  "dimensions": "10x20x5",
  "brand": "Brand Name",
  "status": "active",
  "is_featured": false
}
```

**Response:**
```json
{
  "success": true,
  "message": "Product created successfully",
  "data": {
    "id": 1,
    "name": "New Product",
    "slug": "new-product-1234567890",
    "price": 25000.00,
    "status": "active"
  }
}
```

---

### Update Product

**Endpoint:** `PUT /api/v1/admin/products/{id}`

**Request Body:** Same as create product.

**Response:**
```json
{
  "success": true,
  "message": "Product updated successfully",
  "data": {...}
}
```

---

### Update Product Status

**Endpoint:** `PATCH /api/v1/admin/products/{id}/status`

**Request Body:**
```json
{
  "status": "active"
}
```

**Status Options:** `active`, `inactive`, `draft`

---

### Update Product Stock

**Endpoint:** `PATCH /api/v1/admin/products/{id}/stock`

**Request Body:**
```json
{
  "quantity": 150
}
```

**Response:**
```json
{
  "success": true,
  "message": "Stock updated successfully",
  "data": {
    "id": 1,
    "quantity": 150
  }
}
```

---

### Toggle Product Featured

**Endpoint:** `PATCH /api/v1/admin/products/{id}/featured`

**Request Body:**
```json
{
  "is_featured": true
}
```

**Response:**
```json
{
  "success": true,
  "message": "Product featured status updated",
  "data": {
    "id": 1,
    "is_featured": true
  }
}
```

---

### Delete Product

**Endpoint:** `DELETE /api/v1/admin/products/{id}`

**Response:**
```json
{
  "success": true,
  "message": "Product deleted successfully"
}
```

---

## 5. Vendor Management

### List All Vendors

**Endpoint:** `GET /api/v1/admin/vendors`

**Query Parameters:**
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 20)
- `search` - Search by business name
- `is_active` - Filter by status: `true`, `false`

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "user_id": 2,
        "business_name": "Vendor Business Name",
        "email": "vendor@example.com",
        "phone_number": "+2348012345678",
        "is_active": true,
        "products_count": 45,
        "created_at": "2025-01-01T10:00:00.000000Z"
      }
    ]
  }
}
```

---

### Get Single Vendor

**Endpoint:** `GET /api/v1/admin/vendors/{id}`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 2,
    "business_name": "Vendor Business Name",
    "business_address": "123 Business Street, Lagos",
    "email": "vendor@example.com",
    "phone_number": "+2348012345678",
    "is_active": true,
    "products_count": 45,
    "total_sales": 2500000.00,
    "user": {
      "id": 2,
      "name": "Vendor User",
      "email": "vendor@example.com"
    },
    "products": [...],
    "created_at": "2025-01-01T10:00:00.000000Z"
  }
}
```

---

### Update Vendor Status

**Endpoint:** `PATCH /api/v1/admin/vendors/{id}/status`

**Request Body:**
```json
{
  "is_active": true
}
```

**Response:**
```json
{
  "success": true,
  "message": "Vendor status updated successfully",
  "data": {
    "id": 1,
    "is_active": true
  }
}
```

---

## 6. Payment Management

### List All Payments

**Endpoint:** `GET /api/v1/admin/payments`

**Query Parameters:**
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 20)
- `status` - Filter by status: `pending`, `success`, `failed`, `refunded`
- `payment_method` - Filter by method: `card`, `bank_transfer`, `mobile_money`, `cash_on_delivery`
- `date_from` - Filter from date: `YYYY-MM-DD`
- `date_to` - Filter to date: `YYYY-MM-DD`

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "order_id": 1,
        "amount": 95000.00,
        "payment_method": "card",
        "status": "success",
        "transaction_id": "ORD-1-1735465200",
        "payment_date": "2025-12-29T09:45:00.000000Z",
        "order": {
          "id": 1,
          "user": {
            "name": "Customer Name",
            "email": "customer@example.com"
          }
        }
      }
    ]
  }
}
```

---

## 7. Category Management

### List All Categories

**Endpoint:** `GET /api/v1/admin/categories`

**Query Parameters:**
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 50)
- `search` - Search by category name

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "Electronics",
        "description": "Electronic devices and gadgets",
        "products_count": 45,
        "created_at": "2025-01-01T10:00:00.000000Z"
      }
    ]
  }
}
```

---

### Create Category

**Endpoint:** `POST /api/v1/admin/categories`

**Request Body:**
```json
{
  "name": "Electronics",
  "description": "Electronic devices and gadgets"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Category created successfully",
  "data": {
    "id": 1,
    "name": "Electronics",
    "description": "Electronic devices and gadgets",
    "created_at": "2025-01-01T10:00:00.000000Z"
  }
}
```

---

### Update Category

**Endpoint:** `PUT /api/v1/admin/categories/{id}`

**Request Body:**
```json
{
  "name": "Updated Category Name",
  "description": "Updated description"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Category updated successfully",
  "data": {
    "id": 1,
    "name": "Updated Category Name",
    "description": "Updated description"
  }
}
```

---

### Delete Category

**Endpoint:** `DELETE /api/v1/admin/categories/{id}`

**Response:**
```json
{
  "success": true,
  "message": "Category deleted successfully"
}
```

---

## How to Create an Admin User

### Option 1: Via Database

```sql
-- Update existing user to admin
UPDATE users SET role = 'admin' WHERE email = 'youremail@example.com';

-- Or create new admin user directly
INSERT INTO users (name, email, password, role, is_active, created_at, updated_at)
VALUES ('Admin Name', 'admin@example.com', '$2y$10$...hashed_password', 'admin', 1, NOW(), NOW());
```

### Option 2: Register then Update

1. Register a new user via `/api/v1/register`
2. Update the user's role in the database:
```sql
UPDATE users SET role = 'admin' WHERE email = 'newadmin@example.com';
```

---

## Error Responses

All endpoints follow consistent error response format:

**Validation Error (422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "name": ["The name field is required."]
  }
}
```

**Unauthorized (401):**
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

**Forbidden (403):**
```json
{
  "success": false,
  "message": "You do not have permission to perform this action"
}
```

**Not Found (404):**
```json
{
  "success": false,
  "message": "Resource not found"
}
```

**Server Error (500):**
```json
{
  "success": false,
  "message": "An error occurred",
  "error": "Error details..."
}
```

---

## Rate Limiting

Admin endpoints are rate-limited to prevent abuse:
- **60 requests per minute** per IP address
- **1000 requests per hour** per authenticated admin

Exceeded rate limits return:
```json
{
  "message": "Too Many Attempts.",
  "retry_after": 60
}
```

---

## Best Practices

1. **Always include proper headers** with every request
2. **Use pagination** for list endpoints to improve performance
3. **Handle errors gracefully** on the client side
4. **Store tokens securely** - never expose admin tokens in frontend code
5. **Logout when done** - revoke tokens after admin session ends
6. **Use HTTPS** in production to encrypt all communications

---

## Support

For API support or to report issues, contact the development team.
