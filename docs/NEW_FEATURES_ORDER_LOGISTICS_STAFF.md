# New Features Implementation Guide

## Overview
This document describes three new features that have been implemented:
1. Admin endpoint to assign orders to logistics companies
2. Vendor endpoint to add and manage staff
3. Fixed vendor approval status update

---

## 1. Admin: Assign Orders to Logistics Companies

### Endpoint
**POST** `/api/v1/admin/orders/assign-logistics`

### Description
Allows admins to bulk assign multiple order items to a specific logistics company.

### Request Headers
```
Authorization: Bearer {admin_token}
Content-Type: application/json
```

### Request Body
```json
{
  "order_item_ids": [1, 2, 3, 4],
  "logistics_company_id": 5,
  "tracking_number": "TRACK123456" // optional
}
```

### Response - Success (200)
```json
{
  "success": true,
  "message": "Order items assigned to logistics company successfully",
  "data": {
    "updated_items": [
      {
        "id": 1,
        "order_id": 10,
        "product_id": 5,
        "vendor_id": 2,
        "logistics_company_id": 5,
        "logistics_fee": 2500.00,
        "tracking_number": "TRACK123456",
        "fulfillment_status": "pending",
        "order": {...},
        "product": {...},
        "logisticsCompany": {...}
      }
    ],
    "total_assigned": 4,
    "errors": []
  }
}
```

### Response - Validation Error (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "order_item_ids": ["The order item ids field is required."],
    "logistics_company_id": ["The logistics company id field is required."]
  }
}
```

### Business Logic
- Only order items with successful payments can be assigned
- Calculates logistics fee based on delivery fee + commission percentage
- Updates logistics company's pending wallet balance
- Prevents reassignment of already assigned order items
- Returns errors for items that cannot be assigned

### Usage Example
```bash
curl -X POST https://api.easygear.ng/api/v1/admin/orders/assign-logistics \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "order_item_ids": [1, 2, 3],
    "logistics_company_id": 2,
    "tracking_number": "DHL123456"
  }'
```

---

## 2. Vendor: Staff Management

### Database Schema
A new `vendor_staff` table has been created with the following structure:
- `id` - Primary key
- `vendor_id` - Foreign key to vendors table
- `user_id` - Foreign key to users table
- `role` - Staff role (e.g., 'staff', 'manager', 'assistant')
- `position` - Job position (e.g., 'Sales Manager', 'Inventory Clerk')
- `permissions` - Text field for permissions (JSON or comma-separated)
- `is_active` - Boolean flag for active/inactive status
- `timestamps` - Created at and updated at

### Migration
To create the table, run:
```bash
php artisan migrate
```

### Endpoints

#### 2.1 Get All Staff Members
**GET** `/api/v1/vendor/staff`

**Response**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "vendor_id": 2,
      "user_id": 15,
      "role": "manager",
      "position": "Sales Manager",
      "permissions": "view_orders,manage_products",
      "is_active": true,
      "created_at": "2026-02-21T12:00:00.000000Z",
      "updated_at": "2026-02-21T12:00:00.000000Z",
      "user": {
        "id": 15,
        "name": "John Doe",
        "email": "john@example.com",
        "username": "johndoe",
        "phone_number": "+2348012345678",
        "role": "vendor",
        "is_active": true
      }
    }
  ]
}
```

#### 2.2 Add New Staff Member
**POST** `/api/v1/vendor/staff`

**Request Body**
```json
{
  "name": "Jane Smith",
  "email": "jane@example.com",
  "username": "janesmith",
  "password": "securepassword123",
  "phone_number": "+2348087654321",
  "role": "staff",
  "position": "Inventory Clerk",
  "permissions": "view_orders,manage_inventory"
}
```

**Response (201)**
```json
{
  "success": true,
  "message": "Staff member added successfully",
  "data": {
    "id": 2,
    "vendor_id": 2,
    "user_id": 16,
    "role": "staff",
    "position": "Inventory Clerk",
    "permissions": "view_orders,manage_inventory",
    "is_active": true,
    "created_at": "2026-02-21T13:00:00.000000Z",
    "updated_at": "2026-02-21T13:00:00.000000Z",
    "user": {
      "id": 16,
      "name": "Jane Smith",
      "email": "jane@example.com",
      "username": "janesmith",
      "role": "vendor",
      "is_active": true
    }
  }
}
```

#### 2.3 Update Staff Member
**PUT** `/api/v1/vendor/staff/{staffId}`

**Request Body**
```json
{
  "role": "manager",
  "position": "Senior Sales Manager",
  "permissions": "view_orders,manage_products,view_reports",
  "is_active": true
}
```

**Response (200)**
```json
{
  "success": true,
  "message": "Staff member updated successfully",
  "data": {
    "id": 1,
    "vendor_id": 2,
    "user_id": 15,
    "role": "manager",
    "position": "Senior Sales Manager",
    "permissions": "view_orders,manage_products,view_reports",
    "is_active": true,
    "user": {...}
  }
}
```

#### 2.4 Remove Staff Member
**DELETE** `/api/v1/vendor/staff/{staffId}`

**Response (200)**
```json
{
  "success": true,
  "message": "Staff member removed successfully"
}
```

### Business Logic
- Staff members are created with a user account (role = 'vendor')
- Each staff member is linked to a vendor
- Staff members can have different roles and permissions
- A user can only be staff for a vendor once (unique constraint)
- Removing staff deletes the staff record but not the user account

### Usage Example
```bash
# Add staff member
curl -X POST https://api.easygear.ng/api/v1/vendor/staff \
  -H "Authorization: Bearer YOUR_VENDOR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "username": "johndoe",
    "password": "password123",
    "phone_number": "+2348012345678",
    "role": "manager",
    "position": "Sales Manager"
  }'

# Get all staff
curl -X GET https://api.easygear.ng/api/v1/vendor/staff \
  -H "Authorization: Bearer YOUR_VENDOR_TOKEN"

# Update staff
curl -X PUT https://api.easygear.ng/api/v1/vendor/staff/1 \
  -H "Authorization: Bearer YOUR_VENDOR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "role": "manager",
    "is_active": false
  }'

# Remove staff
curl -X DELETE https://api.easygear.ng/api/v1/vendor/staff/1 \
  -H "Authorization: Bearer YOUR_VENDOR_TOKEN"
```

---

## 3. Fixed: Vendor Approval Status Update

### Problem
When a vendor was approved by an admin, the approval status was not being properly reflected in the vendors table.

### Solution
The `updateVendorStatus` method in `AdminController.php` has been enhanced with:
- **Database Transaction**: Ensures both vendor and user status updates are atomic
- **Proper Error Handling**: Rollback on failure
- **Status Verification**: Returns both vendor and user status in response
- **Data Refresh**: Ensures the latest data is returned after update

### Endpoint
**PATCH** `/api/v1/admin/vendors/{id}/status`

**Request Body**
```json
{
  "is_active": true
}
```

**Response**
```json
{
  "success": true,
  "message": "Vendor approved successfully",
  "data": {
    "vendor": {
      "id": 2,
      "user_id": 5,
      "name": "FitGear Store",
      "contact_email": "info@fitgear.com",
      "is_active": true,
      "created_at": "2026-01-15T10:00:00.000000Z",
      "updated_at": "2026-02-21T13:30:00.000000Z"
    },
    "user_status": true
  }
}
```

### What Was Fixed
1. **Wrapped in Transaction**: Both vendor and user updates are now in a DB transaction
2. **Added Validation**: Checks if vendor exists before update
3. **Refresh Data**: Calls `$vendor->refresh()` to ensure latest data is returned
4. **Return User Status**: Response now includes `user_status` to verify user account was also updated
5. **Proper Rollback**: On any error, changes are rolled back

### Testing the Fix
```bash
# Approve vendor
curl -X PATCH https://api.easygear.ng/api/v1/admin/vendors/2/status \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "is_active": true
  }'

# Verify in database
mysql> SELECT v.id, v.name, v.is_active as vendor_active, u.is_active as user_active 
       FROM vendors v 
       JOIN users u ON v.user_id = u.id 
       WHERE v.id = 2;
```

---

## Database Changes

### New Table: vendor_staff
Run the migration to create this table:
```bash
php artisan migrate
```

### Modified Models
1. **Vendor.php** - Added `staff()` relationship
2. **VendorStaff.php** - New model created
3. **User.php** - No changes needed (existing relationships work)

---

## Security Considerations

### Admin Endpoints
- All admin endpoints require `admin` role
- Protected by `auth:sanctum` and `role:admin` middleware

### Vendor Endpoints
- All vendor endpoints require `vendor` role
- Protected by `auth:sanctum` and `vendor` middleware
- Staff members are created with `vendor` role for proper access control

### Password Security
- Staff passwords are hashed using Laravel's `Hash::make()`
- Minimum password length: 6 characters (consider increasing to 8)

---

## Future Enhancements

### Suggested Improvements
1. **Staff Permissions System**: Implement granular permission checking
2. **Staff Activity Logs**: Track what staff members do
3. **Email Notifications**: Send invitation emails to new staff
4. **Bulk Operations**: Allow bulk assignment of orders to logistics
5. **Real-time Notifications**: Notify vendors when staff is added/removed

---

## Support

For any issues or questions:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Verify database migrations ran successfully
3. Ensure proper middleware is applied to routes
4. Test with Postman or similar API testing tool

---

**Implementation Date**: February 21, 2026
**Laravel Version**: 11.x
**API Version**: v1
