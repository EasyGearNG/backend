<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AuthStatusController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\WaitlistController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\VendorProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::prefix('v1')->group(function () {
    Route::get('/', function () {
        return response()->json([
            'success' => true,
            'message' => 'Easygear API is running'
        ]);
    });
    
    // Authentication routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    // Frontend-friendly auth check (public) — returns authenticated: true/false and user when available
    Route::get('/auth/check', [AuthStatusController::class, 'check']);
    
    // Waitlist routes
    Route::post('/waitlist/join', [WaitlistController::class, 'join']);
    Route::post('/waitlist/check-email', [WaitlistController::class, 'checkEmail']);
    Route::get('/waitlist/stats', [WaitlistController::class, 'stats']);
    
    // Public Product routes (no authentication required)
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']); // List all products with filtering
        Route::get('/featured', [ProductController::class, 'featured']); // Get featured products
        Route::get('/search', [ProductController::class, 'search']); // Search products
        Route::get('/category/{categoryId}', [ProductController::class, 'byCategory']); // Products by category
        Route::get('/vendor/{vendorId}', [ProductController::class, 'byVendor']); // Products by vendor
        Route::get('/{slug}', [ProductController::class, 'show']); // Get single product details by slug
    });
    
    // Public Category routes
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']); // List all categories
        Route::get('/{id}', [CategoryController::class, 'show']); // Get single category
    });
});

// Protected routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Authentication routes
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);

    // Waitlist routes (admin only)
    Route::middleware('role:admin')->group(function () {
        // Route::get('/waitlist/stats', [WaitlistController::class, 'stats']);
    });
    
    // Admin routes (admin only)
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        // Dashboard & Analytics
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/revenue-analytics', [AdminController::class, 'revenueAnalytics']);
        
        // User Management
        Route::get('/users', [AdminController::class, 'users']);
        Route::get('/users/{id}', [AdminController::class, 'showUser']);
        Route::put('/users/{id}', [AdminController::class, 'updateUser']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
        
        // Order Management
        Route::get('/orders', [AdminController::class, 'orders']);
        Route::get('/orders/{id}', [AdminController::class, 'showOrder']);
        Route::patch('/orders/{id}/status', [AdminController::class, 'updateOrderStatus']);
        
        // Product Management
        Route::get('/products', [AdminController::class, 'products']);
        Route::get('/products/{id}', [AdminController::class, 'showProduct']);
        Route::post('/products', [AdminController::class, 'createProduct']);
        Route::put('/products/{id}', [AdminController::class, 'updateProduct']);
        Route::patch('/products/{id}/status', [AdminController::class, 'updateProductStatus']);
        Route::patch('/products/{id}/stock', [AdminController::class, 'updateProductStock']);
        Route::patch('/products/{id}/featured', [AdminController::class, 'toggleProductFeatured']);
        Route::delete('/products/{id}', [AdminController::class, 'deleteProduct']);
        
        // Vendor Management
        Route::get('/vendors', [AdminController::class, 'vendors']);
        Route::get('/vendors/{id}', [AdminController::class, 'showVendor']);
        Route::patch('/vendors/{id}/status', [AdminController::class, 'updateVendorStatus']);
        
        // Payment Management
        Route::get('/payments', [AdminController::class, 'payments']);
        
        // Category Management
        Route::get('/categories', [AdminController::class, 'categories']);
        Route::post('/categories', [AdminController::class, 'createCategory']);
        Route::put('/categories/{id}', [AdminController::class, 'updateCategory']);
        Route::delete('/categories/{id}', [AdminController::class, 'deleteCategory']);
    });

    // User info route
    Route::get('/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $request->user()
            ]
        ]);
    });
    
    // Cart routes (authenticated users)
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']); // Get cart
        Route::post('/add', [CartController::class, 'addItem']); // Add item to cart
        Route::put('/items/{itemId}', [CartController::class, 'updateItem']); // Update cart item quantity
        Route::delete('/items/{itemId}', [CartController::class, 'removeItem']); // Remove item from cart
        Route::delete('/clear', [CartController::class, 'clear']); // Clear all cart items
    });
    
    // Address routes (authenticated users)
    Route::prefix('addresses')->group(function () {
        Route::get('/', [AddressController::class, 'index']); // Get all addresses
        Route::post('/', [AddressController::class, 'store']); // Create new address
        Route::get('/{id}', [AddressController::class, 'show']); // Get single address
        Route::put('/{id}', [AddressController::class, 'update']); // Update address
        Route::delete('/{id}', [AddressController::class, 'destroy']); // Delete address
        Route::patch('/{id}/default', [AddressController::class, 'setDefault']); // Set as default address
    });
    
    // Checkout routes (authenticated users)
    Route::prefix('checkout')->group(function () {
        Route::get('/summary', [CheckoutController::class, 'getCheckoutSummary']); // Get checkout summary
        Route::post('/initialize', [CheckoutController::class, 'initializeCheckout']); // Initialize checkout
        Route::post('/verify', [CheckoutController::class, 'verifyPayment']); // Verify payment
    });
    
    // Shipment management and tracking
    Route::prefix('shipments')->group(function () {
        Route::post('/create', [\App\Http\Controllers\Api\ShipmentController::class, 'createShipment']); // Admin/driver: create shipment and assign order items
        Route::post('/update', [\App\Http\Controllers\Api\ShipmentController::class, 'updateShipment']); // Admin/driver: update shipment location/status
        Route::get('/track/{tracking_id}', [\App\Http\Controllers\Api\ShipmentController::class, 'trackByTrackingId']); // Customer: track by tracking_id
    });

    // Vendor Product Management Routes (vendors only)
    Route::prefix('vendor/products')->middleware('vendor')->group(function () {
        Route::get('/', [VendorProductController::class, 'index']); // List vendor's products
        Route::post('/', [VendorProductController::class, 'store']); // Create new product
        Route::get('/stats', [VendorProductController::class, 'stats']); // Get product statistics
        Route::get('/{id}', [VendorProductController::class, 'show']); // Get specific product
        Route::put('/{id}', [VendorProductController::class, 'update']); // Update product
        Route::delete('/{id}', [VendorProductController::class, 'destroy']); // Delete product
        Route::patch('/{id}/stock', [VendorProductController::class, 'updateStock']); // Update stock only
    });
});
