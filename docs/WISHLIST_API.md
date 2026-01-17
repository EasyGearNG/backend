# Wishlist API Documentation

## Overview
The Wishlist API allows authenticated users to like/save products for later viewing. Users can add products to their wishlist, remove them, and view all their saved items.

## Base URL
All endpoints are prefixed with `/api/v1` and require authentication (Bearer token).

---

## Endpoints

### 1. Get User's Wishlist
Retrieve all products in the authenticated user's wishlist.

**Endpoint:** `GET /api/v1/wishlist`

**Authentication:** Required

**Response:**
```json
{
  "success": true,
  "message": "Wishlist retrieved successfully",
  "data": {
    "items": [
      {
        "id": 1,
        "product_id": 25,
        "product_name": "Wireless Headphones",
        "product_slug": "wireless-headphones",
        "product_image": "https://example.com/images/headphones.jpg",
        "vendor_name": "Tech Store",
        "price": 49.99,
        "in_stock": true,
        "stock_quantity": 15,
        "added_at": "2026-01-17 10:30:00"
      }
    ],
    "total_items": 1
  }
}
```

---

### 2. Add Product to Wishlist
Add a product to the user's wishlist.

**Endpoint:** `POST /api/v1/wishlist/add`

**Authentication:** Required

**Request Body:**
```json
{
  "product_id": 25
}
```

**Validation Rules:**
- `product_id`: required, must exist in products table

**Success Response (201):**
```json
{
  "success": true,
  "message": "Product added to wishlist successfully",
  "data": {
    "id": 1,
    "product_id": 25,
    "product_name": "Wireless Headphones",
    "product_slug": "wireless-headphones",
    "product_image": "https://example.com/images/headphones.jpg",
    "vendor_name": "Tech Store",
    "price": 49.99,
    "in_stock": true,
    "added_at": "2026-01-17 10:30:00"
  }
}
```

**Error Response (409 - Already exists):**
```json
{
  "success": false,
  "message": "Product already exists in wishlist"
}
```

---

### 3. Remove Product from Wishlist
Remove a product from the user's wishlist.

**Endpoint:** `DELETE /api/v1/wishlist/{productId}`

**Authentication:** Required

**URL Parameters:**
- `productId`: The ID of the product to remove

**Success Response:**
```json
{
  "success": true,
  "message": "Product removed from wishlist successfully"
}
```

**Error Response (404):**
```json
{
  "success": false,
  "message": "Product not found in wishlist"
}
```

---

### 4. Toggle Product in Wishlist
Add a product if not in wishlist, or remove it if already present. This is useful for "like" buttons.

**Endpoint:** `POST /api/v1/wishlist/toggle`

**Authentication:** Required

**Request Body:**
```json
{
  "product_id": 25
}
```

**Success Response (Added):**
```json
{
  "success": true,
  "message": "Product added to wishlist",
  "data": {
    "in_wishlist": true,
    "wishlist_id": 1
  }
}
```

**Success Response (Removed):**
```json
{
  "success": true,
  "message": "Product removed from wishlist",
  "data": {
    "in_wishlist": false
  }
}
```

---

### 5. Check if Product is in Wishlist
Check whether a specific product is in the user's wishlist.

**Endpoint:** `GET /api/v1/wishlist/check/{productId}`

**Authentication:** Required

**URL Parameters:**
- `productId`: The ID of the product to check

**Response:**
```json
{
  "success": true,
  "data": {
    "in_wishlist": true
  }
}
```

---

### 6. Clear Wishlist
Remove all products from the user's wishlist.

**Endpoint:** `DELETE /api/v1/wishlist`

**Authentication:** Required

**Response:**
```json
{
  "success": true,
  "message": "Wishlist cleared successfully",
  "data": {
    "deleted_count": 5
  }
}
```

---

## Frontend Integration Examples

### JavaScript/Fetch Example

```javascript
// Add product to wishlist
async function addToWishlist(productId, token) {
  const response = await fetch('http://localhost:8000/api/v1/wishlist/add', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    },
    body: JSON.stringify({ product_id: productId })
  });
  
  return await response.json();
}

// Toggle wishlist (for like button)
async function toggleWishlist(productId, token) {
  const response = await fetch('http://localhost:8000/api/v1/wishlist/toggle', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    },
    body: JSON.stringify({ product_id: productId })
  });
  
  return await response.json();
}

// Get wishlist
async function getWishlist(token) {
  const response = await fetch('http://localhost:8000/api/v1/wishlist', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  return await response.json();
}

// Remove from wishlist
async function removeFromWishlist(productId, token) {
  const response = await fetch(`http://localhost:8000/api/v1/wishlist/${productId}`, {
    method: 'DELETE',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  return await response.json();
}
```

### React Example with Heart Icon

```jsx
import { useState, useEffect } from 'react';

function WishlistButton({ productId, token }) {
  const [isInWishlist, setIsInWishlist] = useState(false);
  const [loading, setLoading] = useState(false);

  // Check if product is in wishlist on mount
  useEffect(() => {
    checkWishlistStatus();
  }, [productId]);

  const checkWishlistStatus = async () => {
    try {
      const response = await fetch(
        `http://localhost:8000/api/v1/wishlist/check/${productId}`,
        {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
          }
        }
      );
      const data = await response.json();
      setIsInWishlist(data.data.in_wishlist);
    } catch (error) {
      console.error('Error checking wishlist:', error);
    }
  };

  const handleToggleWishlist = async () => {
    setLoading(true);
    try {
      const response = await fetch('http://localhost:8000/api/v1/wishlist/toggle', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        },
        body: JSON.stringify({ product_id: productId })
      });
      
      const data = await response.json();
      
      if (data.success) {
        setIsInWishlist(data.data.in_wishlist);
      }
    } catch (error) {
      console.error('Error toggling wishlist:', error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <button 
      onClick={handleToggleWishlist}
      disabled={loading}
      className={`wishlist-btn ${isInWishlist ? 'active' : ''}`}
    >
      {isInWishlist ? '❤️' : '🤍'}
    </button>
  );
}
```

---

## Database Schema

The wishlist feature uses a `wishlists` table with the following structure:

```sql
CREATE TABLE wishlists (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY unique_user_product (user_id, product_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
```

**Key Features:**
- Unique constraint ensures a user can't add the same product twice
- Cascade delete: if user or product is deleted, wishlist entries are automatically removed
- Timestamps track when items were added

---

## Migration Instructions

To set up the wishlist feature, run the migration:

```bash
php artisan migrate
```

This will create the `wishlists` table in your database.

---

## Error Handling

All endpoints return consistent error responses:

```json
{
  "success": false,
  "message": "Error message here",
  "error": "Detailed error information (in development)"
}
```

Common HTTP status codes:
- `200`: Success
- `201`: Created (for add operations)
- `401`: Unauthorized (missing or invalid token)
- `404`: Not found
- `409`: Conflict (product already in wishlist)
- `422`: Validation error
- `500`: Server error
