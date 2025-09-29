<?php

use App\Http\Controllers\Api\AuthController;
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

    // User info route
    Route::get('/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $request->user()
            ]
        ]);
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
