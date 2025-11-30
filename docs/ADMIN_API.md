# Admin API Documentation

Complete guide for admin endpoints in the EasyGear API.

## Table of Contents
- [Authentication](#authentication)
- [Dashboard & Analytics](#dashboard--analytics)
- [User Management](#user-management)
- [Order Management](#order-management)
- [Product Management](#product-management)
- [Vendor Management](#vendor-management)
- [Payment Management](#payment-management)
- [Category Management](#category-management)

## Authentication

All admin endpoints require:
- Valid authentication token
- User role: `admin`

### Admin Login
```bash
POST /api/v1/login
Content-Type: application/json

{
  "login": "admin@easygear.ng",
  "password": "admin123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "1|xxxxxxxxxxxxxxxxxxxxx",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "name": "EasyGear Admin",
      "email": "admin@easygear.ng",
      "role": "admin"
    }
  }
}
```

Use the token in all subsequent requests:
```
Authorization: Bearer {token}
```

---

## Dashboard & Analytics

### Get Dashboard Statistics
Get comprehensive overview of platform statistics.

**Endpoint:** `GET /api/v1/admin/dashboard`

**Response:**
```json
{
  "success": true,
  "data": {
    "users": {
      "total": 150,
      "customers": 120,
      "vendors": 25,
      "admins": 5,
      "active": 140,
      "new_this_month": 15
    },
    "orders": {
      "total": 500,
      "pending": 20,
      "processing": 30,
      "completed": 400,
      "cancelled": 50,
      "revenue_total": 5000000.00,
      "revenue_this_month": 800000.00
    },
    "products": {
      "total": 200,
      "active": 180,
      "out_of_stock": 10,
      "low_stock": 15
    },
    "vendors": {
      "total": 25,
      "active": 20,
      "pending": 5
    },
    "payments": {
      "total": 5000000.00,
      "pending": 50000.00,
      "completed": 4800000.00,
      "failed": 15
    }
  }
}
```

### Get Revenue Analytics
Get revenue breakdown by period.

**Endpoint:** `GET /api/v1/admin/revenue-analytics`

**Query Parameters:**
- `period` (optional): `day`, `week`, `month`, `year` (default: `month`)

**Example:**
```bash
GET /api/v1/admin/revenue-analytics?period=month
```

**Response:**
```json
{
  "success": true,
  "data": {
    "period": "month",
    "summary": {
      "total_revenue": 800000.00,
      "total_orders": 120,
      "average_order_value": 6666.67
    },
    "daily_revenue": [
      {
        "date": "2025-11-01",
        "total": 25000.00
      },
      {
        "date": "2025-11-02",
        "total": 30000.00
      }
    ]
  }
}
```

---

## User Management

### List All Users
Get paginated list of users with filtering.

**Endpoint:** `GET /api/v1/admin/users`

**Query Parameters:**
- `role` (optional): Filter by role (`customer`, `vendor`, `admin`)
- `is_active` (optional): Filter by status (true/false)
- `search` (optional): Search by name or email
- `sort_by` (optional): Sort field (default: `created_at`)
- `sort_order` (optional): `asc` or `desc` (default: `desc`)
- `per_page` (optional): Items per page (default: 15)

**Example:**
```bash
GET /api/v1/admin/users?role=customer&is_active=true&per_page=20
```

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 5,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "customer",
        "is_active": true,
        "phone_number": "+2348012345678",
        "created_at": "2025-11-01T10:00:00.000000Z"
      }
    ],
    "total": 120
  }
}
```

### Get User Details
Get detailed information about a specific user.

**Endpoint:** `GET /api/v1/admin/users/{id}`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 5,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "customer",
    "is_active": true,
    "phone_number": "+2348012345678",
    "vendor": null,
    "orders": [],
    "addresses": []
  }
}
```

### Update User
Update user information or status.

**Endpoint:** `PUT /api/v1/admin/users/{id}`

**Request Body:**
```json
{
  "name": "John Smith",
  "email": "johnsmith@example.com",
  "phone_number": "+2348087654321",
  "role": "vendor",
  "is_active": false
}
```

**Response:**
```json
{
  "success": true,
  "message": "User updated successfully",
  "data": {
    "id": 5,
    "name": "John Smith",
    "email": "johnsmith@example.com",
    "role": "vendor",
    "is_active": false
  }
}
```

### Delete User
Delete a user account.

**Endpoint:** `DELETE /api/v1/admin/users/{id}`

**Response:**
```json
{
  "success": true,
  "message": "User deleted successfully"
}
```

---

## Order Management

### List All Orders
Get paginated list of orders with filtering.

**Endpoint:** `GET /api/v1/admin/orders`

**Query Parameters:**
- `status` (optional): Filter by status (`pending`, `processing`, `shipped`, `delivered`, `completed`, `cancelled`)
- `start_date` (optional): Filter from date (YYYY-MM-DD)
- `end_date` (optional): Filter to date (YYYY-MM-DD)
- `search` (optional): Search by order ID or user details
- `per_page` (optional): Items per page (default: 15)

**Example:**
```bash
GET /api/v1/admin/orders?status=pending&per_page=10
```

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "user_id": 5,
        "status": "pending",
        "total_amount": 50000.00,
        "order_date": "2025-11-26T10:00:00.000000Z",
        "user": {
          "id": 5,
          "name": "John Doe",
          "email": "john@example.com"
        },
        "items": [],
        "payment": {}
      }
    ],
    "total": 20
  }
}
```

### Get Order Details
Get detailed information about a specific order.

**Endpoint:** `GET /api/v1/admin/orders/{id}`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 5,
    "status": "pending",
    "total_amount": 50000.00,
    "shipping_cost": 2000.00,
    "tax_amount": 3750.00,
    "order_date": "2025-11-26T10:00:00.000000Z",
    "user": {},
    "items": [
      {
        "id": 1,
        "order_id": 1,
        "product_id": 10,
        "quantity": 2,
        "price": 22500.00,
        "total": 45000.00,
        "product": {}
      }
    ],
    "payment": {},
    "tracking": []
  }
}
```

### Update Order Status
Update the status of an order.

**Endpoint:** `PATCH /api/v1/admin/orders/{id}/status`

**Request Body:**
```json
{
  "status": "processing",
  "notes": "Order is being prepared for shipment"
}
```

**Valid Statuses:**
- `pending` - Order placed, awaiting processing
- `processing` - Order is being prepared
- `shipped` - Order has been shipped
- `delivered` - Order delivered to customer
- `completed` - Order completed successfully
- `cancelled` - Order cancelled

**Response:**
```json
{
  "success": true,
  "message": "Order status updated successfully",
  "data": {
    "id": 1,
    "status": "processing",
    "notes": "Order is being prepared for shipment"
  }
}
```

---

## Product Management

### List All Products
Get paginated list of products with filtering.

**Endpoint:** `GET /api/v1/admin/products`

**Query Parameters:**
- `status` (optional): Filter by status (`active`, `inactive`, `draft`)
- `category_id` (optional): Filter by category
- `vendor_id` (optional): Filter by vendor
- `search` (optional): Search by name or description
- `per_page` (optional): Items per page (default: 15)

**Example:**
```bash
GET /api/v1/admin/products?status=active&category_id=2
```

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 10,
        "name": "Adjustable Dumbbell Set",
        "slug": "adjustable-dumbbell-set",
        "price": 22500.00,
        "quantity": 50,
        "status": "active",
        "vendor": {},
        "category": {}
      }
    ],
    "total": 180
  }
}
```

### Update Product Status
Update product status (activate/deactivate).

**Endpoint:** `PATCH /api/v1/admin/products/{id}/status`

**Request Body:**
```json
{
  "status": "inactive"
}
```

**Valid Statuses:**
- `active` - Product is visible and available
- `inactive` - Product is hidden from customers
- `draft` - Product not yet published

**Response:**
```json
{
  "success": true,
  "message": "Product status updated successfully",
  "data": {
    "id": 10,
    "status": "inactive"
  }
}
```

### Delete Product
Delete a product from the system.

**Endpoint:** `DELETE /api/v1/admin/products/{id}`

**Response:**
```json
{
  "success": true,
  "message": "Product deleted successfully"
}
```

---

## Vendor Management

### List All Vendors
Get paginated list of vendors.

**Endpoint:** `GET /api/v1/admin/vendors`

**Query Parameters:**
- `status` (optional): Filter by status (`active`, `inactive`, `pending`, `suspended`)
- `search` (optional): Search by business name or email
- `per_page` (optional): Items per page (default: 15)

**Example:**
```bash
GET /api/v1/admin/vendors?status=active
```

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "user_id": 3,
        "business_name": "FitGear Store",
        "business_email": "info@fitgear.com",
        "business_phone": "+2348012345678",
        "status": "active",
        "user": {}
      }
    ],
    "total": 20
  }
}
```

### Get Vendor Details
Get detailed information about a specific vendor.

**Endpoint:** `GET /api/v1/admin/vendors/{id}`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 3,
    "business_name": "FitGear Store",
    "business_email": "info@fitgear.com",
    "business_phone": "+2348012345678",
    "business_address": "123 Main St, Lagos",
    "status": "active",
    "user": {},
    "products": []
  }
}
```

### Update Vendor Status
Update vendor account status.

**Endpoint:** `PATCH /api/v1/admin/vendors/{id}/status`

**Request Body:**
```json
{
  "status": "suspended"
}
```

**Valid Statuses:**
- `active` - Vendor can sell products
- `inactive` - Vendor temporarily disabled
- `pending` - Vendor awaiting approval
- `suspended` - Vendor suspended (violation)

**Response:**
```json
{
  "success": true,
  "message": "Vendor status updated successfully",
  "data": {
    "id": 1,
    "status": "suspended"
  }
}
```

---

## Payment Management

### List All Payments
Get paginated list of payment transactions.

**Endpoint:** `GET /api/v1/admin/payments`

**Query Parameters:**
- `status` (optional): Filter by status (`pending`, `completed`, `failed`)
- `payment_method` (optional): Filter by method (`card`, `bank_transfer`, etc.)
- `start_date` (optional): Filter from date (YYYY-MM-DD)
- `end_date` (optional): Filter to date (YYYY-MM-DD)
- `per_page` (optional): Items per page (default: 15)

**Example:**
```bash
GET /api/v1/admin/payments?status=completed&start_date=2025-11-01
```

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
        "amount": 50000.00,
        "payment_method": "card",
        "status": "completed",
        "transaction_id": "ORD-1-1732608000",
        "created_at": "2025-11-26T10:00:00.000000Z",
        "order": {
          "id": 1,
          "user": {}
        }
      }
    ],
    "total": 400
  }
}
```

---

## Category Management

### List All Categories
Get paginated list of product categories.

**Endpoint:** `GET /api/v1/admin/categories`

**Query Parameters:**
- `search` (optional): Search by category name
- `per_page` (optional): Items per page (default: 50)

**Example:**
```bash
GET /api/v1/admin/categories
```

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "Cardio Equipment",
        "description": "Treadmills, bikes, and more",
        "products_count": 25
      }
    ],
    "total": 10
  }
}
```

### Create Category
Create a new product category.

**Endpoint:** `POST /api/v1/admin/categories`

**Request Body:**
```json
{
  "name": "Yoga Equipment",
  "description": "Mats, blocks, straps, and accessories"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Category created successfully",
  "data": {
    "id": 11,
    "name": "Yoga Equipment",
    "description": "Mats, blocks, straps, and accessories"
  }
}
```

### Update Category
Update an existing category.

**Endpoint:** `PUT /api/v1/admin/categories/{id}`

**Request Body:**
```json
{
  "name": "Yoga & Pilates Equipment",
  "description": "Complete yoga and pilates gear"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Category updated successfully",
  "data": {
    "id": 11,
    "name": "Yoga & Pilates Equipment",
    "description": "Complete yoga and pilates gear"
  }
}
```

### Delete Category
Delete a category (only if no products assigned).

**Endpoint:** `DELETE /api/v1/admin/categories/{id}`

**Response:**
```json
{
  "success": true,
  "message": "Category deleted successfully"
}
```

**Error (if category has products):**
```json
{
  "success": false,
  "message": "Cannot delete category with existing products"
}
```

---

## Error Responses

### Unauthorized
```json
{
  "success": false,
  "message": "Unauthorized"
}
```

### Forbidden
```json
{
  "success": false,
  "message": "Forbidden: Insufficient permissions"
}
```

### Validation Error
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "name": ["The name field is required."]
  }
}
```

### Not Found
```json
{
  "success": false,
  "message": "User not found"
}
```

---

## Quick Reference

### All Admin Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| **Dashboard** |
| GET | `/api/v1/admin/dashboard` | Dashboard statistics |
| GET | `/api/v1/admin/revenue-analytics` | Revenue analytics |
| **Users** |
| GET | `/api/v1/admin/users` | List all users |
| GET | `/api/v1/admin/users/{id}` | Get user details |
| PUT | `/api/v1/admin/users/{id}` | Update user |
| DELETE | `/api/v1/admin/users/{id}` | Delete user |
| **Orders** |
| GET | `/api/v1/admin/orders` | List all orders |
| GET | `/api/v1/admin/orders/{id}` | Get order details |
| PATCH | `/api/v1/admin/orders/{id}/status` | Update order status |
| **Products** |
| GET | `/api/v1/admin/products` | List all products |
| PATCH | `/api/v1/admin/products/{id}/status` | Update product status |
| DELETE | `/api/v1/admin/products/{id}` | Delete product |
| **Vendors** |
| GET | `/api/v1/admin/vendors` | List all vendors |
| GET | `/api/v1/admin/vendors/{id}` | Get vendor details |
| PATCH | `/api/v1/admin/vendors/{id}/status` | Update vendor status |
| **Payments** |
| GET | `/api/v1/admin/payments` | List all payments |
| **Categories** |
| GET | `/api/v1/admin/categories` | List categories |
| POST | `/api/v1/admin/categories` | Create category |
| PUT | `/api/v1/admin/categories/{id}` | Update category |
| DELETE | `/api/v1/admin/categories/{id}` | Delete category |

---

## Testing

### Postman Collection
Import the complete Postman collection for easy testing of all admin endpoints.

### Admin Account
Use these credentials for testing:
- **Email:** `admin@easygear.ng`
- **Password:** `admin123`

### Example Workflow
1. Login as admin to get token
2. Get dashboard statistics
3. List and manage users
4. View and update orders
5. Manage vendors and products
6. View payment transactions
7. Manage categories
