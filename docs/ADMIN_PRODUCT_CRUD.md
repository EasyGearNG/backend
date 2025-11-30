# Admin Product Management API

Complete guide for managing products as an administrator in the EasyGear API.

## Table of Contents
- [Authentication](#authentication)
- [List Products](#list-products)
- [Get Product Details](#get-product-details)
- [Create Product](#create-product)
- [Update Product](#update-product)
- [Update Product Status](#update-product-status)
- [Update Product Stock](#update-product-stock)
- [Toggle Featured Status](#toggle-featured-status)
- [Delete Product](#delete-product)
- [Quick Reference](#quick-reference)

---

## Authentication

All admin product endpoints require:
- Valid authentication token (Bearer token)
- User role: `admin`

### Get Admin Token
```bash
POST /api/v1/login
Content-Type: application/json

{
  "login": "admin@easygear.ng",
  "password": "admin123"
}
```

Include the token in all requests:
```
Authorization: Bearer {your_token}
```

---

## List Products

Get a paginated list of all products with filtering and search capabilities.

**Endpoint:** `GET /api/v1/admin/products`

### Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `status` | string | Filter by status: `active`, `inactive`, `draft` |
| `category_id` | integer | Filter by category ID |
| `vendor_id` | integer | Filter by vendor ID |
| `search` | string | Search by name, description, or SKU |
| `per_page` | integer | Items per page (default: 15) |
| `page` | integer | Page number (default: 1) |

### Example Request

```bash
GET /api/v1/admin/products?status=active&per_page=20&page=1
Authorization: Bearer {token}
```

### Response

```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "vendor_id": 1,
        "category_id": 2,
        "name": "Adjustable Dumbbell Set",
        "slug": "adjustable-dumbbell-set-1732608000",
        "short_description": "Professional quality adjustable dumbbells",
        "description": "Complete adjustable dumbbell set with weight range...",
        "price": 22500.00,
        "quantity": 50,
        "weight": 2.5,
        "dimensions": "30cm x 15cm x 15cm",
        "sku": "SKU-ABC12345",
        "brand": "FitPro",
        "image_url": "https://example.com/image.jpg",
        "size_options": ["5kg", "10kg", "15kg"],
        "color_options": ["Black", "Silver"],
        "status": "active",
        "is_featured": true,
        "average_rating": 4.5,
        "total_reviews": 23,
        "total_sales": 145,
        "view_count": 890,
        "created_at": "2025-11-01T10:00:00.000000Z",
        "updated_at": "2025-11-26T15:30:00.000000Z",
        "formatted_price": "₦22,500.00",
        "is_in_stock": true,
        "is_low_stock": false,
        "stock_status": "In Stock",
        "primary_image": "https://api.easygear.ng/storage/products/dumbbell.jpg",
        "vendor": {
          "id": 1,
          "business_name": "FitGear Store"
        },
        "category": {
          "id": 2,
          "name": "Strength Training"
        }
      }
    ],
    "first_page_url": "http://api.easygear.ng/api/v1/admin/products?page=1",
    "last_page_url": "http://api.easygear.ng/api/v1/admin/products?page=10",
    "next_page_url": "http://api.easygear.ng/api/v1/admin/products?page=2",
    "prev_page_url": null,
    "per_page": 20,
    "total": 180
  }
}
```

---

## Get Product Details

Get detailed information about a specific product including vendor, category, images, and reviews.

**Endpoint:** `GET /api/v1/admin/products/{id}`

### Example Request

```bash
GET /api/v1/admin/products/1
Authorization: Bearer {token}
```

### Response

```json
{
  "success": true,
  "data": {
    "id": 1,
    "vendor_id": 1,
    "category_id": 2,
    "name": "Adjustable Dumbbell Set",
    "slug": "adjustable-dumbbell-set-1732608000",
    "short_description": "Professional quality adjustable dumbbells",
    "description": "Complete adjustable dumbbell set with weight range from 5kg to 25kg per dumbbell. Perfect for home gym and fitness enthusiasts.",
    "price": 22500.00,
    "quantity": 50,
    "weight": 2.5,
    "dimensions": "30cm x 15cm x 15cm",
    "sku": "SKU-ABC12345",
    "brand": "FitPro",
    "status": "active",
    "is_featured": true,
    "average_rating": 4.5,
    "total_reviews": 23,
    "vendor": {
      "id": 1,
      "business_name": "FitGear Store",
      "business_email": "info@fitgear.com"
    },
    "category": {
      "id": 2,
      "name": "Strength Training"
    },
    "images": [
      {
        "id": 1,
        "image_path": "products/dumbbell-1.jpg",
        "sort_order": 1
      },
      {
        "id": 2,
        "image_path": "products/dumbbell-2.jpg",
        "sort_order": 2
      }
    ],
    "reviews": [
      {
        "id": 1,
        "user_id": 5,
        "rating": 5,
        "comment": "Excellent quality!",
        "created_at": "2025-11-20T10:00:00.000000Z"
      }
    ]
  }
}
```

---

## Create Product

Create a new product (admin can create products for any vendor).

**Endpoint:** `POST /api/v1/admin/products`

### Request Body

```json
{
  "vendor_id": 1,
  "category_id": 2,
  "name": "Professional Yoga Mat",
  "short_description": "Non-slip yoga mat with excellent cushioning",
  "description": "Premium quality yoga mat made from eco-friendly materials. Features excellent grip and cushioning for comfortable practice. Dimensions: 183cm x 61cm x 6mm thick.",
  "price": 8500.00,
  "quantity": 100,
  "weight": 1.2,
  "dimensions": "183cm x 61cm x 6mm",
  "brand": "YogaPro",
  "image_url": "https://example.com/yoga-mat.jpg",
  "size_options": ["Standard", "Extra Large"],
  "color_options": ["Purple", "Blue", "Green", "Pink"],
  "is_featured": false,
  "status": "active"
}
```

### Field Descriptions

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `vendor_id` | integer | Yes | ID of the vendor selling this product |
| `category_id` | integer | Yes | ID of the product category |
| `name` | string | Yes | Product name (max 255 chars) |
| `short_description` | string | No | Brief product description (max 500 chars) |
| `description` | string | Yes | Full product description |
| `price` | decimal | Yes | Product price (must be >= 0) |
| `quantity` | integer | Yes | Stock quantity (must be >= 0) |
| `weight` | decimal | No | Product weight in kg |
| `dimensions` | string | No | Product dimensions (max 100 chars) |
| `brand` | string | No | Brand name (max 100 chars) |
| `image_url` | string | No | Primary image URL |
| `size_options` | array | No | Available sizes |
| `color_options` | array | No | Available colors |
| `is_featured` | boolean | No | Featured product flag (default: false) |
| `status` | string | No | Status: `active`, `inactive`, `draft` (default: active) |

### Example Request

```bash
POST /api/v1/admin/products
Authorization: Bearer {token}
Content-Type: application/json

{
  "vendor_id": 1,
  "category_id": 3,
  "name": "Professional Yoga Mat",
  "description": "Premium quality yoga mat...",
  "price": 8500.00,
  "quantity": 100
}
```

### Response

```json
{
  "success": true,
  "message": "Product created successfully",
  "data": {
    "id": 21,
    "vendor_id": 1,
    "category_id": 3,
    "name": "Professional Yoga Mat",
    "slug": "professional-yoga-mat-1732790400",
    "sku": "SKU-XYZ98765",
    "price": 8500.00,
    "quantity": 100,
    "status": "active",
    "created_at": "2025-11-30T12:00:00.000000Z"
  }
}
```

---

## Update Product

Update an existing product. You can update any field (admin can update any product).

**Endpoint:** `PUT /api/v1/admin/products/{id}`

### Request Body

```json
{
  "name": "Professional Yoga Mat - Premium Edition",
  "price": 9500.00,
  "quantity": 150,
  "is_featured": true,
  "status": "active"
}
```

### Notes
- All fields are optional (use only the fields you want to update)
- If you update the `name`, the slug will be automatically regenerated
- SKU cannot be updated (it's auto-generated)

### Example Request

```bash
PUT /api/v1/admin/products/21
Authorization: Bearer {token}
Content-Type: application/json

{
  "price": 9500.00,
  "quantity": 150,
  "is_featured": true
}
```

### Response

```json
{
  "success": true,
  "message": "Product updated successfully",
  "data": {
    "id": 21,
    "vendor_id": 1,
    "category_id": 3,
    "name": "Professional Yoga Mat - Premium Edition",
    "slug": "professional-yoga-mat-premium-edition-1732790450",
    "price": 9500.00,
    "quantity": 150,
    "is_featured": true,
    "status": "active",
    "vendor": {...},
    "category": {...},
    "images": [...]
  }
}
```

---

## Update Product Status

Quickly update a product's status without sending all fields.

**Endpoint:** `PATCH /api/v1/admin/products/{id}/status`

### Status Options
- `active` - Product is visible and available for purchase
- `inactive` - Product is hidden from customers but not deleted
- `draft` - Product is saved but not yet published

### Request Body

```json
{
  "status": "inactive"
}
```

### Example Request

```bash
PATCH /api/v1/admin/products/21/status
Authorization: Bearer {token}
Content-Type: application/json

{
  "status": "inactive"
}
```

### Response

```json
{
  "success": true,
  "message": "Product status updated successfully",
  "data": {
    "id": 21,
    "name": "Professional Yoga Mat",
    "status": "inactive"
  }
}
```

---

## Update Product Stock

Update only the stock quantity of a product.

**Endpoint:** `PATCH /api/v1/admin/products/{id}/stock`

### Request Body

```json
{
  "quantity": 200
}
```

### Example Request

```bash
PATCH /api/v1/admin/products/21/stock
Authorization: Bearer {token}
Content-Type: application/json

{
  "quantity": 200
}
```

### Response

```json
{
  "success": true,
  "message": "Product stock updated successfully",
  "data": {
    "id": 21,
    "quantity": 200,
    "stock_status": "In Stock"
  }
}
```

### Stock Status Values
- `Out of Stock` - quantity = 0
- `Low Stock` - quantity between 1-10
- `In Stock` - quantity > 10

---

## Toggle Featured Status

Toggle a product's featured status (true ↔ false).

**Endpoint:** `PATCH /api/v1/admin/products/{id}/featured`

### Example Request

```bash
PATCH /api/v1/admin/products/21/featured
Authorization: Bearer {token}
```

### Response

```json
{
  "success": true,
  "message": "Product featured status updated successfully",
  "data": {
    "id": 21,
    "is_featured": true
  }
}
```

---

## Delete Product

Permanently delete a product from the system.

**Endpoint:** `DELETE /api/v1/admin/products/{id}`

### Important Notes
- Products with existing orders **cannot** be deleted
- Consider using `status: inactive` instead of deleting
- This action is permanent and cannot be undone

### Example Request

```bash
DELETE /api/v1/admin/products/21
Authorization: Bearer {token}
```

### Success Response

```json
{
  "success": true,
  "message": "Product deleted successfully"
}
```

### Error Response (Product has orders)

```json
{
  "success": false,
  "message": "Cannot delete product with existing orders. Consider deactivating instead."
}
```

---

## Error Responses

### Unauthorized (401)
```json
{
  "success": false,
  "message": "Unauthorized"
}
```

### Forbidden (403)
```json
{
  "success": false,
  "message": "Forbidden: Insufficient permissions"
}
```

### Validation Error (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "price": ["The price must be at least 0."],
    "vendor_id": ["The selected vendor id is invalid."]
  }
}
```

### Not Found (404)
```json
{
  "success": false,
  "message": "Product not found"
}
```

---

## Quick Reference

### All Product Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/products` | List all products with filters |
| GET | `/api/v1/admin/products/{id}` | Get product details |
| POST | `/api/v1/admin/products` | Create new product |
| PUT | `/api/v1/admin/products/{id}` | Update product |
| PATCH | `/api/v1/admin/products/{id}/status` | Update status only |
| PATCH | `/api/v1/admin/products/{id}/stock` | Update stock only |
| PATCH | `/api/v1/admin/products/{id}/featured` | Toggle featured status |
| DELETE | `/api/v1/admin/products/{id}` | Delete product |

---

## Common Use Cases

### 1. List Active Products
```bash
GET /api/v1/admin/products?status=active&per_page=50
```

### 2. Find Out of Stock Products
```bash
GET /api/v1/admin/products?search=&per_page=100
# Then filter client-side for quantity=0, or use custom filter
```

### 3. Search Products by Name
```bash
GET /api/v1/admin/products?search=yoga
```

### 4. Get All Products from Specific Vendor
```bash
GET /api/v1/admin/products?vendor_id=1
```

### 5. Get All Products in Category
```bash
GET /api/v1/admin/products?category_id=2
```

### 6. Create Product with Minimal Data
```bash
POST /api/v1/admin/products
{
  "vendor_id": 1,
  "category_id": 2,
  "name": "New Product",
  "description": "Product description",
  "price": 5000,
  "quantity": 10
}
```

### 7. Update Only Price
```bash
PUT /api/v1/admin/products/21
{
  "price": 12000.00
}
```

### 8. Make Product Inactive
```bash
PATCH /api/v1/admin/products/21/status
{
  "status": "inactive"
}
```

### 9. Restock Product
```bash
PATCH /api/v1/admin/products/21/stock
{
  "quantity": 500
}
```

### 10. Feature a Product
```bash
PATCH /api/v1/admin/products/21/featured
```

---

## Testing with Postman

### Setup
1. Login as admin to get token
2. Save token to environment variable
3. Create collection with all endpoints
4. Add token to Authorization header

### Sample Postman Pre-request Script
```javascript
// Automatically add token from environment
pm.request.headers.add({
    key: 'Authorization',
    value: 'Bearer ' + pm.environment.get('admin_token')
});
```

### Sample Test Script (Save Token)
```javascript
// After login, save token
if (pm.response.code === 200) {
    var response = pm.response.json();
    pm.environment.set('admin_token', response.data.token);
}
```

---

## Best Practices

1. **Always validate vendor_id and category_id** before creating products
2. **Use status updates** instead of deleting products with orders
3. **Set appropriate stock levels** to avoid overselling
4. **Use featured flag** for homepage/promotional products
5. **Include descriptive names and descriptions** for SEO
6. **Test with different roles** to ensure proper access control
7. **Monitor stock levels** and set up alerts for low stock
8. **Use pagination** when listing products to improve performance
9. **Search and filter** instead of loading all products at once
10. **Update prices carefully** - consider impact on existing carts

---

## Admin Account for Testing

**Email:** `admin@easygear.ng`  
**Password:** `admin123`

To create additional admin users, update the user's role in the database:
```sql
UPDATE users SET role = 'admin' WHERE email = 'newadmin@example.com';
```
