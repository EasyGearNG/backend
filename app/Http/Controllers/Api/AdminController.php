<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Payment;
use App\Models\Category;
use App\Models\Wallet;
use App\Models\LogisticsCompany;
use App\Models\WalletWithdrawal;
use App\Mail\AdminInvitation;
use App\Services\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Get admin dashboard statistics
     */
    public function dashboard(): JsonResponse
    {
        try {
            $stats = [
                'users' => [
                    'total' => User::count(),
                    'customers' => User::where('role', 'customer')->count(),
                    'vendors' => User::where('role', 'vendor')->count(),
                    'admins' => User::where('role', 'admin')->count(),
                    'active' => User::where('is_active', true)->count(),
                    'new_this_month' => User::whereMonth('created_at', now()->month)->count(),
                ],
                'orders' => [
                    'total' => Order::count(),
                    'pending' => Order::where('status', 'pending')->count(),
                    'processing' => Order::where('status', 'processing')->count(),
                    'completed' => Order::where('status', 'completed')->count(),
                    'cancelled' => Order::where('status', 'cancelled')->count(),
                    'revenue_total' => Order::where('status', 'completed')->sum('total_amount'),
                    'revenue_this_month' => Order::where('status', 'completed')
                        ->whereMonth('created_at', now()->month)
                        ->sum('total_amount'),
                ],
                'products' => [
                    'total' => Product::count(),
                    'active' => Product::where('status', 'active')->count(),
                    'out_of_stock' => Product::where('quantity', 0)->count(),
                    'low_stock' => Product::whereBetween('quantity', [1, 10])->count(),
                ],
                'vendors' => [
                    'total' => Vendor::count(),
                    'active' => Vendor::where('is_active', true)->count(),
                    'inactive' => Vendor::where('is_active', false)->count(),
                ],
                'payments' => [
                    'total' => Payment::sum('amount'),
                    'pending' => Payment::where('status', 'pending')->sum('amount'),
                    'completed' => Payment::where('status', 'completed')->sum('amount'),
                    'failed' => Payment::where('status', 'failed')->count(),
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all users with filtering and pagination
     */
    public function users(Request $request): JsonResponse
    {
        try {
            $query = User::query();

            // Filter by role
            if ($request->has('role')) {
                $query->where('role', $request->role);
            }

            // Filter by status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Search by name or email
            if ($request->has('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('email', 'like', '%' . $request->search . '%');
                });
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $users = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $users,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single user details
     */
    public function showUser($id): JsonResponse
    {
        try {
            $user = User::with(['vendor', 'orders', 'addresses'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }
    }

    /**
     * Update user
     */
    public function updateUser(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'phone_number' => 'sometimes|string|max:15',
            'role' => 'sometimes|in:customer,vendor,admin',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = User::findOrFail($id);
            $user->update($request->only(['name', 'email', 'phone_number', 'role', 'is_active']));

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create new user (including admin invites)
     */
    public function createUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone_number' => 'sometimes|string|max:15',
            'role' => 'required|in:customer,vendor,admin',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $plainPassword = $request->password;
            
            // Generate username from email
            $username = explode('@', $request->email)[0] . '_' . Str::random(4);
            
            $userData = [
                'name' => $request->name,
                'username' => $username,
                'email' => $request->email,
                'password' => Hash::make($plainPassword),
                'phone_number' => $request->phone_number,
                'role' => $request->role,
                'is_active' => $request->get('is_active', true),
            ];

            $user = User::create($userData);

            // If creating a vendor, also create vendor record
            if ($request->role === 'vendor' && $request->has('business_name')) {
                Vendor::create([
                    'user_id' => $user->id,
                    'business_name' => $request->business_name,
                    'business_address' => $request->business_address,
                    'is_active' => true,
                ]);
            }

            // Send invitation email if user is admin
            if ($request->role === 'admin') {
                try {
                    Mail::to($user->email)->send(new AdminInvitation($user, $plainPassword));
                } catch (\Exception $e) {
                    // Log the error but don't fail the user creation
                    \Log::warning('Failed to send admin invitation email: ' . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'User created successfully' . ($request->role === 'admin' ? '. Invitation email sent.' : ''),
                'data' => $user,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete user
     */
    public function deleteUser($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            
            // Prevent deleting own account
            if ($user->id === auth()->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot delete your own account',
                ], 400);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all orders with filtering
     */
    public function orders(Request $request): JsonResponse
    {
        try {
            $query = Order::with(['user', 'items.product', 'payment']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by date range
            if ($request->has('start_date')) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }
            if ($request->has('end_date')) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }

            // Search by order ID or user
            if ($request->has('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('id', 'like', '%' . $request->search . '%')
                      ->orWhereHas('user', function ($q) use ($request) {
                          $q->where('name', 'like', '%' . $request->search . '%')
                            ->orWhere('email', 'like', '%' . $request->search . '%');
                      });
                });
            }

            $orders = $query->orderBy('created_at', 'desc')
                           ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $orders,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single order details
     */
    public function showOrder($id): JsonResponse
    {
        try {
            $order = Order::with(['user', 'items.product', 'items.vendor', 'payment', 'tracking'])
                         ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }
    }

    /**
     * Update order status
     */
    public function updateOrderStatus(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,processing,shipped,delivered,completed,cancelled',
            'notes' => 'sometimes|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $order = Order::findOrFail($id);
            $order->status = $request->status;
            
            if ($request->has('notes')) {
                $order->notes = $request->notes;
            }
            
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully',
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all products with filtering
     */
    public function products(Request $request): JsonResponse
    {
        try {
            $query = Product::with(['vendor', 'category']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by category
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            // Filter by vendor
            if ($request->has('vendor_id')) {
                $query->where('vendor_id', $request->vendor_id);
            }

            // Search
            if ($request->has('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%');
                });
            }

            $products = $query->orderBy('created_at', 'desc')
                             ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $products,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single product details
     */
    public function showProduct($id): JsonResponse
    {
        try {
            $product = Product::with(['vendor', 'category', 'images', 'reviews'])
                             ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $product,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }
    }

    /**
     * Create new product (admin can create for any vendor)
     */
    public function createProduct(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'vendor_id' => 'required|exists:vendors,id',
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'short_description' => 'sometimes|string|max:500',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'weight' => 'sometimes|numeric|min:0',
            'dimensions' => 'sometimes|string|max:100',
            'brand' => 'sometimes|string|max:100',
            'image_url' => 'sometimes|url',
            'size_options' => 'sometimes|array',
            'color_options' => 'sometimes|array',
            'is_featured' => 'sometimes|boolean',
            'status' => 'sometimes|in:active,inactive,draft',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $product = Product::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $product,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update product (admin can update any product)
     */
    public function updateProduct(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'vendor_id' => 'sometimes|exists:vendors,id',
            'category_id' => 'sometimes|exists:categories,id',
            'name' => 'sometimes|string|max:255',
            'short_description' => 'sometimes|string|max:500',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'quantity' => 'sometimes|integer|min:0',
            'weight' => 'sometimes|numeric|min:0',
            'dimensions' => 'sometimes|string|max:100',
            'brand' => 'sometimes|string|max:100',
            'image_url' => 'sometimes|url',
            'size_options' => 'sometimes|array',
            'color_options' => 'sometimes|array',
            'is_featured' => 'sometimes|boolean',
            'status' => 'sometimes|in:active,inactive,draft',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $product = Product::findOrFail($id);
            
            // If name is updated, regenerate slug
            if ($request->has('name') && $request->name !== $product->name) {
                $request->merge(['slug' => Str::slug($request->name) . '-' . time()]);
            }
            
            $product->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $product->fresh(['vendor', 'category', 'images']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update product status
     */
    public function updateProductStatus(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive,draft',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $product = Product::findOrFail($id);
            $product->status = $request->status;
            $product->save();

            return response()->json([
                'success' => true,
                'message' => 'Product status updated successfully',
                'data' => $product,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update product stock
     */
    public function updateProductStock(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $product = Product::findOrFail($id);
            $product->quantity = $request->quantity;
            $product->save();

            return response()->json([
                'success' => true,
                'message' => 'Product stock updated successfully',
                'data' => [
                    'id' => $product->id,
                    'quantity' => $product->quantity,
                    'stock_status' => $product->stock_status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product stock',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle product featured status
     */
    public function toggleProductFeatured(Request $request, $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            $product->is_featured = !$product->is_featured;
            $product->save();

            return response()->json([
                'success' => true,
                'message' => 'Product featured status updated successfully',
                'data' => [
                    'id' => $product->id,
                    'is_featured' => $product->is_featured,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update featured status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete product
     */
    public function deleteProduct($id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            
            // Check if product has orders
            if ($product->orderItems()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete product with existing orders. Consider deactivating instead.',
                ], 400);
            }
            
            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all vendors
     */
    public function vendors(Request $request): JsonResponse
    {
        try {
            $query = Vendor::with('user');

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Search
            if ($request->has('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('business_name', 'like', '%' . $request->search . '%')
                      ->orWhere('business_email', 'like', '%' . $request->search . '%');
                });
            }

            $vendors = $query->orderBy('created_at', 'desc')
                            ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $vendors,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch vendors',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single vendor details
     */
    public function showVendor($id): JsonResponse
    {
        try {
            $vendor = Vendor::with(['user', 'products'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $vendor,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor not found',
            ], 404);
        }
    }

    /**
     * Update vendor status (Approve/Disapprove)
     */
    public function updateVendorStatus(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $vendor = Vendor::with('user')->findOrFail($id);
            $vendor->is_active = $request->boolean('is_active');
            $vendor->save();

            // Also update the user's is_active status
            if ($vendor->user) {
                $vendor->user->is_active = $request->boolean('is_active');
                $vendor->user->save();
            }

            $statusText = $request->boolean('is_active') ? 'approved' : 'disapproved';

            return response()->json([
                'success' => true,
                'message' => "Vendor {$statusText} successfully",
                'data' => $vendor,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update vendor status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get revenue analytics
     */
    public function revenueAnalytics(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', 'month'); // day, week, month, year

            $startDate = match ($period) {
                'day' => now()->startOfDay(),
                'week' => now()->startOfWeek(),
                'month' => now()->startOfMonth(),
                'year' => now()->startOfYear(),
                default => now()->startOfMonth(),
            };

            $revenue = Order::where('status', 'completed')
                ->where('created_at', '>=', $startDate)
                ->selectRaw('DATE(created_at) as date, SUM(total_amount) as total')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $summary = [
                'total_revenue' => Order::where('status', 'completed')
                    ->where('created_at', '>=', $startDate)
                    ->sum('total_amount'),
                'total_orders' => Order::where('created_at', '>=', $startDate)->count(),
                'average_order_value' => Order::where('status', 'completed')
                    ->where('created_at', '>=', $startDate)
                    ->avg('total_amount'),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => $period,
                    'summary' => $summary,
                    'daily_revenue' => $revenue,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch revenue analytics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get payment transactions
     */
    public function payments(Request $request): JsonResponse
    {
        try {
            $query = Payment::with(['order.user']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by payment method
            if ($request->has('payment_method')) {
                $query->where('payment_method', $request->payment_method);
            }

            // Date range
            if ($request->has('start_date')) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }
            if ($request->has('end_date')) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }

            $payments = $query->orderBy('created_at', 'desc')
                             ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $payments,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get categories
     */
    public function categories(Request $request): JsonResponse
    {
        try {
            $query = Category::withCount('products');

            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            $categories = $query->orderBy('name')
                               ->paginate($request->get('per_page', 50));

            return response()->json([
                'success' => true,
                'data' => $categories,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch categories',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create category
     */
    public function createCategory(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories',
            'description' => 'sometimes|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $request->only(['name', 'description']);
            $data['slug'] = Str::slug($request->name) . '-' . Str::random(8);
            
            $category = Category::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully',
                'data' => $category,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create category',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update category
     */
    public function updateCategory(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:categories,name,' . $id,
            'description' => 'sometimes|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $category = Category::findOrFail($id);
            
            $data = $request->only(['name', 'description']);
            if ($request->has('name')) {
                $data['slug'] = Str::slug($request->name) . '-' . Str::random(8);
            }
            
            $category->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully',
                'data' => $category,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update category',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete category
     */
    public function deleteCategory($id): JsonResponse
    {
        try {
            $category = Category::findOrFail($id);
            
            // Check if category has products
            if ($category->products()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete category with existing products',
                ], 400);
            }

            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all partner wallets (Easygear, Resilience, Logistics)
     */
    public function getPartnerWallets(Request $request): JsonResponse
    {
        try {
            $partners = ['easygear', 'resilience', 'logistics'];
            $walletsData = [];

            foreach ($partners as $partner) {
                $wallet = Wallet::getOrCreate($partner, null);
                
                // Get recent transactions if requested
                $transactions = null;
                if ($request->boolean('include_transactions')) {
                    $transactions = $wallet->transactions()
                        ->with(['order', 'orderItem.product'])
                        ->orderBy('created_at', 'desc')
                        ->limit($request->get('transaction_limit', 50))
                        ->get();
                }

                $walletsData[] = [
                    'partner' => ucfirst($partner),
                    'wallet' => $wallet,
                    'transactions' => $transactions,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $walletsData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch partner wallets',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get specific partner wallet details with transactions
     */
    public function getPartnerWallet(string $partner, Request $request): JsonResponse
    {
        try {
            $allowedPartners = ['easygear', 'resilience', 'logistics'];
            
            if (!in_array(strtolower($partner), $allowedPartners)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid partner name. Allowed: ' . implode(', ', $allowedPartners),
                ], 400);
            }

            $wallet = Wallet::getOrCreate(strtolower($partner), null);
            
            $query = $wallet->transactions()
                ->with(['order.user', 'orderItem.product', 'orderItem.vendor']);

            // Filter by date range
            if ($request->has('start_date')) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }
            if ($request->has('end_date')) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }

            // Filter by type
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            $transactions = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));

            return response()->json([
                'success' => true,
                'data' => [
                    'partner' => ucfirst($partner),
                    'wallet' => $wallet,
                    'transactions' => $transactions,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch partner wallet',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all vendor wallets summary
     */
    public function getVendorWallets(Request $request): JsonResponse
    {
        try {
            $query = Wallet::where('owner_type', 'vendor')
                ->with('owner'); // This will load the vendor relationship

            // Search by vendor name
            if ($request->has('search')) {
                $query->whereHas('owner', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%');
                });
            }

            $wallets = $query->orderBy('balance', 'desc')
                ->paginate($request->get('per_page', 20));

            return response()->json([
                'success' => true,
                'data' => $wallets,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch vendor wallets',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all logistics companies with wallet info
     */
    public function getLogisticsCompanies(Request $request): JsonResponse
    {
        try {
            $companies = LogisticsCompany::orderBy('name')->get();

            $companiesWithWallets = $companies->map(function ($company) {
                $wallet = $company->getOrCreateWallet();
                return [
                    'company' => $company,
                    'wallet' => $wallet,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $companiesWithWallets,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch logistics companies',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create logistics company payout/withdrawal
     */
    public function createLogisticsPayout(Request $request, $companyId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'sometimes|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $company = LogisticsCompany::findOrFail($companyId);

            // Validate bank details exist
            if (!$company->account_number || !$company->bank_name) {
                return response()->json([
                    'success' => false,
                    'message' => 'Logistics company bank details not configured',
                ], 400);
            }

            $wallet = $company->getOrCreateWallet();

            // Check sufficient balance
            if ($wallet->balance < $request->amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient wallet balance',
                    'data' => [
                        'available_balance' => $wallet->balance,
                        'requested_amount' => $request->amount,
                    ],
                ], 400);
            }

            $paystackService = new PaystackService();

            DB::transaction(function () use ($wallet, $company, $request, $paystackService, &$withdrawal) {
                $reference = 'WD-LOG-' . $company->id . '-' . Str::random(12);

                // Create or get Paystack recipient
                if (!$company->paystack_recipient_code) {
                    // Need to get bank code from bank name
                    // For now, we'll need to add bank_code to logistics_companies or resolve it
                    // Let's skip automatic recipient creation for now and handle it manually
                    $recipientCode = null;
                } else {
                    $recipientCode = $company->paystack_recipient_code;
                }

                // Debit the wallet first
                $wallet->debit(
                    $request->amount,
                    $reference,
                    "Withdrawal to {$company->bank_name} - {$company->account_number}",
                    [
                        'logistics_company_id' => $company->id,
                        'bank_name' => $company->bank_name,
                        'account_number' => $company->account_number,
                        'account_name' => $company->account_name,
                    ]
                );

                // Create withdrawal record
                $withdrawal = WalletWithdrawal::create([
                    'wallet_id' => $wallet->id,
                    'recipient_type' => 'logistics_company',
                    'recipient_id' => $company->id,
                    'amount' => $request->amount,
                    'bank_name' => $company->bank_name,
                    'account_number' => $company->account_number,
                    'account_name' => $company->account_name,
                    'reference' => $reference,
                    'status' => 'pending',
                    'notes' => $request->notes,
                    'metadata' => [
                        'initiated_by' => auth()->user()->id,
                        'initiated_by_name' => auth()->user()->name,
                    ],
                ]);

                // If recipient code exists, initiate Paystack transfer
                if ($recipientCode) {
                    $transferResult = $paystackService->initiateTransfer(
                        'balance',
                        $request->amount,
                        $recipientCode,
                        $request->notes ?? "Payout to {$company->name}",
                        $reference
                    );

                    if ($transferResult['success']) {
                        $withdrawal->update([
                            'paystack_transfer_code' => $transferResult['transfer_code'],
                            'paystack_transfer_id' => $transferResult['id'],
                            'status' => 'processing',
                            'metadata' => array_merge($withdrawal->metadata ?? [], [
                                'paystack_response' => $transferResult['data'],
                                'auto_transfer' => true,
                            ]),
                        ]);
                    } else {
                        // Transfer failed, add error to metadata but keep as pending for manual processing
                        $withdrawal->update([
                            'metadata' => array_merge($withdrawal->metadata ?? [], [
                                'paystack_error' => $transferResult['message'],
                                'auto_transfer_failed' => true,
                            ]),
                        ]);
                    }
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Payout initiated successfully',
                'data' => [
                    'withdrawal' => $withdrawal->fresh(),
                    'wallet_balance' => $wallet->fresh()->balance,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payout',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all withdrawals/payouts
     */
    public function getWithdrawals(Request $request): JsonResponse
    {
        try {
            $query = WalletWithdrawal::with('wallet');

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by recipient type
            if ($request->has('recipient_type')) {
                $query->where('recipient_type', $request->recipient_type);
            }

            // Date range
            if ($request->has('start_date')) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }
            if ($request->has('end_date')) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }

            $withdrawals = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));

            // Load recipients
            $withdrawals->getCollection()->transform(function ($withdrawal) {
                $withdrawal->recipient_details = $withdrawal->recipient();
                return $withdrawal;
            });

            return response()->json([
                'success' => true,
                'data' => $withdrawals,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch withdrawals',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update withdrawal status (mark as completed/failed)
     */
    public function updateWithdrawalStatus(Request $request, $withdrawalId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:processing,completed,failed',
            'notes' => 'sometimes|string|max:500',
            'metadata' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $withdrawal = WalletWithdrawal::findOrFail($withdrawalId);

            if (in_array($withdrawal->status, ['completed', 'failed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Withdrawal already processed',
                ], 400);
            }

            $updateData = [
                'status' => $request->status,
            ];

            if ($request->has('notes')) {
                $updateData['notes'] = $request->notes;
            }

            if ($request->has('metadata')) {
                $updateData['metadata'] = array_merge(
                    $withdrawal->metadata ?? [],
                    $request->metadata
                );
            }

            if (in_array($request->status, ['completed', 'failed'])) {
                $updateData['processed_at'] = now();
            }

            // If failed, refund to wallet
            if ($request->status === 'failed') {
                DB::transaction(function () use ($withdrawal, $updateData) {
                    $wallet = $withdrawal->wallet;
                    $wallet->credit(
                        $withdrawal->amount,
                        $withdrawal->reference . '-REFUND',
                        'Refund for failed withdrawal',
                        [
                            'original_withdrawal_id' => $withdrawal->id,
                            'refund_reason' => 'withdrawal_failed',
                        ]
                    );

                    $withdrawal->update($updateData);
                });
            } else {
                $withdrawal->update($updateData);
            }

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal status updated successfully',
                'data' => $withdrawal->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update withdrawal status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create or update Paystack transfer recipient for logistics company
     */
    public function createPaystackRecipient(Request $request, $companyId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'bank_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $company = LogisticsCompany::findOrFail($companyId);

            if (!$company->account_number || !$company->account_name) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bank account details not configured',
                ], 400);
            }

            $paystackService = new PaystackService();

            // Create transfer recipient
            $result = $paystackService->createTransferRecipient(
                'nuban',
                $company->account_name,
                $company->account_number,
                $request->bank_code,
                'NGN'
            );

            if ($result['success']) {
                $company->update([
                    'bank_code' => $request->bank_code,
                    'paystack_recipient_code' => $result['recipient_code'],
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Paystack recipient created successfully',
                    'data' => [
                        'company' => $company->fresh(),
                        'recipient_code' => $result['recipient_code'],
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Paystack recipient',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list of Nigerian banks from Paystack
     */
    public function getPaystackBanks(): JsonResponse
    {
        try {
            $paystackService = new PaystackService();
            $result = $paystackService->listBanks('NG');

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['banks'],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch banks',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
