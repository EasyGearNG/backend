# EasyGear eCommerce API

## Overview
This is the backend API for EasyGear, Nigeria's one-stop shop for sports gear. The API provides endpoints for authentication, product management, orders, payments, and more.

## Features Implemented

### ✅ Database Schema
- **Users** - Customer, vendor, and admin authentication
- **Categories** - Hierarchical product categories 
- **Products** - Sports gear with variants (size, color)
- **Vendors** - Marketplace vendor management
- **Orders** - Order processing and management
- **Payments** - Payment processing with splits
- **Inventory** - Stock tracking
- **Reviews** - Product ratings and reviews
- **Carts** - Shopping cart functionality
- **Additional Tables** - Discounts, returns, order tracking

### ✅ Authentication System
- **User Registration** - Customer and vendor registration
- **User Login** - Login with email or username
- **Cookie-based Authentication** - Secure HTTP-only cookies using Laravel Sanctum
- **Profile Management** - Update profile and change password
- **Token Refresh** - Automatic token refresh functionality
- **Role-based Access** - Admin, customer, and vendor roles
- **Enhanced Security** - XSS and CSRF protection

### ✅ API Endpoints

#### Authentication Endpoints
```
POST /api/v1/register         - Register new user (sets auth cookie)
POST /api/v1/login           - User login (sets auth cookie)
GET  /api/v1/profile         - Get user profile (protected)
PUT  /api/v1/profile         - Update profile (protected)
POST /api/v1/change-password - Change password (protected)
POST /api/v1/refresh-token   - Refresh auth token (protected)
POST /api/v1/logout          - Logout (clears auth cookie)
POST /api/v1/logout-all      - Logout from all devices (protected)
GET  /api/v1/user           - Get current user (protected)
```

**Note**: All authentication now uses HTTP-only cookies for enhanced security. No need to manually include Authorization headers!

## Installation & Setup

### Prerequisites
- PHP 8.2+
- Composer
- MySQL/MariaDB
- XAMPP (for local development)

### Database Configuration
Update your `.env` file with database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=easy_gear_api
DB_USERNAME=root
DB_PASSWORD=
```

### Run Migrations & Seeders
```bash
# Run migrations to create tables
php artisan migrate

# Seed initial data (categories and admin users)
php artisan db:seed
```

### Test Accounts
After running seeders, these test accounts will be available:

**Admin Account:**
- Username: `admin`
- Email: `admin@easygear.ng`
- Password: `admin123`
- Role: `admin`

**Customer Account:**
- Username: `customer`
- Email: `customer@test.com`
- Password: `password`
- Role: `customer`

**Vendor Account:**
- Username: `vendor`
- Email: `vendor@test.com`
- Password: `password`
- Role: `vendor`

## API Usage Examples

### Registration
```bash
curl -X POST http://localhost:8000/api/v1/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "username": "johndoe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "phone_number": "+2348012345678",
    "role": "customer"
  }'
```

### Login
```bash
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{
    "login": "johndoe",
    "password": "password123"
  }' \
  -c cookies.txt  # Save cookies for subsequent requests
```

### Access Protected Endpoint
```bash
curl -X GET http://localhost:8000/api/v1/profile \
  -b cookies.txt  # Use saved cookies for authentication
```

## Database Schema Overview

### Core Tables
- **users** - User accounts with roles
- **categories** - Product categories (hierarchical)
- **vendors** - Marketplace vendors
- **products** - Product catalog
- **inventory** - Stock management
- **orders** - Customer orders
- **order_items** - Items within orders
- **payments** - Payment transactions
- **payment_splits** - Revenue sharing

### Additional Tables
- **user_addresses** - Customer addresses
- **carts** & **cart_items** - Shopping cart
- **reviews** - Product reviews
- **discounts** - Promotional codes
- **returns** - Return requests
- **order_tracking** - Shipment tracking

## Next Steps for Development

### 1. Product Management APIs
- Create product CRUD endpoints
- Category management
- Vendor product management

### 2. Shopping Cart APIs
- Add/remove cart items
- Cart checkout process

### 3. Order Management APIs
- Order creation and processing
- Order status updates
- Order history

### 4. Payment Integration
- Paystack integration
- Flutterwave integration
- Payment webhook handling

### 5. Advanced Features
- Search and filtering
- Inventory management
- Review system
- Admin dashboard APIs

## Security Features
- Password hashing with bcrypt
- JWT token authentication
- Role-based access control
- Input validation
- SQL injection protection (Eloquent ORM)

## Nigerian-Specific Features
- Default currency: Nigerian Naira (NGN)
- Local payment methods support
- Nigerian phone number format
- States and cities support

## Development Commands

```bash
# Start development server
php artisan serve

# Clear application cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Generate application key
php artisan key:generate

# Run database migrations
php artisan migrate

# Seed database with initial data
php artisan db:seed

# Create new migration
php artisan make:migration create_table_name

# Create new model
php artisan make:model ModelName

# Create new controller
php artisan make:controller ControllerName
```
