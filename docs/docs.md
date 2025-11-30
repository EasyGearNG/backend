EasyGear eCommerce Software Documentation

Overview

EasyGear is Nigeria’s one-stop shop for sports gear, offering a robust eCommerce platform to facilitate online sales, inventory management, and vendor coordination. This document outlines the software modules and the corresponding database schema for EasyGear’s ecommerce platform. The platform is designed to support both single-vendor and marketplace models, with local payment gateways (e.g., Paystack, Flutterwave), and logistics integration.

 

Software Modules

The eCommerce platform for EasyGear includes the following modules, categorized into core and additional modules (recommended for enhanced functionality).

Core Modules

These modules were identified as essential for the platform's operation:

1. Authentication
Manages user registration, login, and role-based access (e.g., customers, admins, vendors). Supports secure password hashing and session management.
2. Product Category
Organizes sports gear into categories and subcategories (e.g., Football > Boots) for easy navigation and filtering.
3. Products
Handles product listings, including details like name, price, description, images, and attributes (e.g., size, color).
4. Stock
Tracks inventory levels, stock availability, and low-stock alerts to ensure efficient supply chain management.
5. Vendors
Manages vendor profiles, contact details, and bank information for marketplace operations.
6. Orders
Processes customer orders, including order creation, status updates, and total calculations.
7. Order Tracking
Provides real-time updates on order status, from placement to delivery, with tracking numbers for logistics.
8. Payments
Integrates with payment gateways (e.g., Paystack, Flutterwave) to handle transactions, supporting card, bank transfer, USSD, and cash-on-delivery.
9. Payment Split
Facilitates splitting payments between the platform and vendors in a marketplace model, accounting for commissions.
Additional Modules

To enhance user experience, operational efficiency, and scalability, the following modules are recommended:

1. Customer Management
Manages user profiles, addresses, wishlists, and purchase history to enable personalization and loyalty programs.
2. Shopping Cart
Allows customers to add, remove, or modify items before checkout, with session persistence for seamless shopping.
3. Shipping/Delivery Management
Integrates with logistics providers (e.g., GIG Logistics) to calculate shipping costs and manage delivery zones.
4. Reviews and Ratings
Enables customers to rate products and leave feedback, improving trust and product discoverability.
5. Discounts/Promotions/Coupons
Supports promo codes, flash sales, and bundle offers to drive sales, especially during sports seasons.
6. Returns/Refunds
Manages return requests, refunds, and exchanges, integrating with payments for reversals.
7. Reporting/Analytics
Provides insights into sales, inventory, customer behavior, and vendor performance via dashboards.
8. Search and Filtering
Offers advanced search with filters (e.g., by brand, size, sport type, price) to improve product discoverability.
9. Notifications/Alerts
Sends emails, SMS, or push notifications for order updates, stock alerts, or promotions, using SMS gateways (e.g., Twilio).
10. Admin Dashboard
Provides a backend interface for managing modules, user roles, and system configurations.
11. Taxes Management
Handles Nigerian VAT (7.5%) and other taxes, automatically applying them to orders.
12. Integration Module
Supports third-party integrations, such as payment gateways, analytics tools (e.g., Google Analytics), and email services.
 

Database Schema

The database schema is designed for a relational database (e.g., MySQL, PostgreSQL) to support the core and additional modules. The schema is normalized to avoid redundancy, uses primary keys (PK) and foreign keys (FK) for relationships, and includes timestamps for auditing. Fields are assigned appropriate data types (e.g., INT for IDs, DECIMAL for prices, VARCHAR for strings). Nigeria-specific considerations, such as Naira currency and local payment methods, are incorporated.

Authentication Module

1. Users
Stores user account details for authentication (use JWT) and role management.
• id (PK, INT, auto-increment)
• username (VARCHAR, unique)
• email (VARCHAR, unique)
• password_hash (VARCHAR)
• role (ENUM: 'customer', 'admin', 'vendor')
• phone_number (VARCHAR)
• is_active (BOOLEAN, default: true)
• created_at (TIMESTAMP, default: CURRENT_TIMESTAMP)
• updated_at (TIMESTAMP)
2. User_Addresses
Stores customer shipping and billing addresses.
• id (PK, INT, auto-increment)
• user_id (FK to Users.id)
• address_line1 (VARCHAR)
• address_line2 (VARCHAR)
• city (VARCHAR)
• state (VARCHAR)
• postal_code (VARCHAR)
• country (VARCHAR, default: 'Nigeria')
• address_type (ENUM: 'shipping', 'billing')
• is_default (BOOLEAN, default: false)
Product Category Module

3. Categories
Organizes products into categories and subcategories.
• id (PK, INT, auto-increment)
• name (VARCHAR, unique)
• description (TEXT)
• parent_id (FK to Categories.id, for subcategories)
• slug (VARCHAR, for URL-friendly names)
• created_at (TIMESTAMP)
• updated_at (TIMESTAMP)
Products Module

4. Products
Stores product details for sports gear.
• id (PK, INT, auto-increment)
• name (VARCHAR)
• description (TEXT)
• price (DECIMAL(10,2))
• category_id (FK to Categories.id)
• vendor_id (FK to Vendors.id)
• image_url (VARCHAR)
• sku (VARCHAR, unique)
• brand (VARCHAR)
• size_options (JSON or VARCHAR)
• color_options (JSON or VARCHAR)
• is_active (BOOLEAN, default: true)
• created_at (TIMESTAMP)
• updated_at (TIMESTAMP)
Stock Module

5. Inventory
Tracks stock levels for products.
• id (PK, INT, auto-increment)
• product_id (FK to Products.id)
• quantity_available (INT)
• quantity_sold (INT, default: 0)
• low_stock_threshold (INT, default: 10)
• location (VARCHAR, e.g., warehouse name)
• updated_at (TIMESTAMP)
Vendors Module

6. Vendors
Manages vendor information for marketplace operations.
• id (PK, INT, auto-increment)
• name (VARCHAR)
• contact_email (VARCHAR)
• contact_phone (VARCHAR)
• address (TEXT)
• bank_details (JSON, e.g., account_number, bank_name)
• commission_rate (DECIMAL(5,2))
• is_active (BOOLEAN, default: true)
• created_at (TIMESTAMP)
• updated_at (TIMESTAMP)
Orders Module

7. Orders
Stores customer order details.
• id (PK, INT, auto-increment)
• user_id (FK to Users.id)
• order_date (TIMESTAMP, default: CURRENT_TIMESTAMP)
• status (ENUM: 'pending', 'processing', 'shipped', 'delivered', 'cancelled')
• total_amount (DECIMAL(10,2))
• shipping_address_id (FK to User_Addresses.id)
• billing_address_id (FK to User_Addresses.id)
• shipping_cost (DECIMAL(10,2))
• tax_amount (DECIMAL(10,2))
• discount_amount (DECIMAL(10,2))
• notes (TEXT)
8. Order_Items
Stores individual items within an order.
• id (PK, INT, auto-increment)
• order_id (FK to Orders.id)
• product_id (FK to Products.id)
• quantity (INT)
• price_at_purchase (DECIMAL(10,2))
• subtotal (DECIMAL(10,2))
Order Tracking Module

9. Order_Tracking
Tracks order status updates.
• id (PK, INT, auto-increment)
• order_id (FK to Orders.id)
• status_update (ENUM: 'placed', 'packed', 'shipped', 'in_transit', 'delivered')
• timestamp (TIMESTAMP, default: CURRENT_TIMESTAMP)
• location (VARCHAR)
• notes (TEXT)
• tracking_number (VARCHAR)
Payments Module

10. Payments
Manages payment transactions.
• id (PK, INT, auto-increment)
• order_id (FK to Orders.id)
• amount (DECIMAL(10,2))
• payment_method (ENUM: 'card', 'bank_transfer', 'mobile_money', 'cash_on_delivery')
• status (ENUM: 'pending', 'success', 'failed', 'refunded')
• transaction_id (VARCHAR)
• payment_date (TIMESTAMP)
• gateway_response (JSON)
Payment Split Module

11. Payment_Splits
Handles payment distribution in marketplace model.
• id (PK, INT, auto-increment)
• payment_id (FK to Payments.id)
• vendor_id (FK to Vendors.id)
• amount_to_vendor (DECIMAL(10,2))
• platform_fee (DECIMAL(10,2))
• split_date (TIMESTAMP)
• status (ENUM: 'pending', 'processed')
Additional Modules (Optional Tables)

12. Carts
Manages customer shopping carts.
• id (PK, INT, auto-increment)
• user_id (FK to Users.id)
• created_at (TIMESTAMP)
• updated_at (TIMESTAMP)
13. Cart_Items
Stores items in a cart.
• id (PK, INT, auto-increment)
• cart_id (FK to Carts.id)
• product_id (FK to Products.id)
• quantity (INT)
14. Reviews
Stores product reviews and ratings.
• id (PK, INT, auto-increment)
• product_id (FK to Products.id)
• user_id (FK to Users.id)
• rating (INT, 1-5)
• comment (TEXT)
• created_at (TIMESTAMP)
15. Discounts
Manages promotional discounts.
• id (PK, INT, auto-increment)
• code (VARCHAR, unique)
• discount_type (ENUM: 'percentage', 'fixed')
• discount_value (DECIMAL(10,2))
• valid_from (DATE)
• valid_to (DATE)
• min_order_amount (DECIMAL(10,2))
• max_uses (INT)
16. Returns
Handles return and refund requests.
• id (PK, INT, auto-increment)
• order_id (FK to Orders.id)
• reason (TEXT)
• status (ENUM: 'requested', 'approved', 'processed')
• refund_amount (DECIMAL(10,2))
• created_at (TIMESTAMP)
 