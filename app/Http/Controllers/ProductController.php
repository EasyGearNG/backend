<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;

class ProductController extends Controller
{
    /**
     * Get all products with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Product::with(['category', 'vendor', 'images'])
                ->where('is_active', true);

            // Only filter by quantity if specifically requested to show in-stock items only
            if ($request->get('in_stock_only', false)) {
                $query->where('quantity', '>', 0);
            }

            // Search by name or description
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('short_description', 'like', "%{$search}%");
                });
            }

            // Filter by category
            if ($request->filled('category')) {
                $query->where('category_id', $request->get('category'));
            }

            // Filter by vendor
            if ($request->filled('vendor')) {
                $query->where('vendor_id', $request->get('vendor'));
            }

            // Filter by price range
            if ($request->filled('min_price')) {
                $query->where('price', '>=', $request->get('min_price'));
            }
            if ($request->filled('max_price')) {
                $query->where('price', '<=', $request->get('max_price'));
            }

            // Filter by rating
            if ($request->filled('min_rating')) {
                $query->where('average_rating', '>=', $request->get('min_rating'));
            }

            // Sort options
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            $allowedSorts = ['name', 'price', 'created_at', 'average_rating', 'total_sales'];
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = min($request->get('per_page', 10), 50);
            $products = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Products retrieved successfully',
                'data' => $products,
                'pagination_info' => [
                    'current_page' => $products->currentPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'last_page' => $products->lastPage(),
                ],
                'filters_applied' => [
                    'search' => $request->get('search'),
                    'category' => $request->get('category'),
                    'vendor' => $request->get('vendor'),
                    'min_price' => $request->get('min_price'),
                    'max_price' => $request->get('max_price'),
                    'min_rating' => $request->get('min_rating'),
                    'in_stock_only' => $request->get('in_stock_only', false),
                    'sort_by' => $request->get('sort_by', 'created_at'),
                    'sort_order' => $request->get('sort_order', 'desc'),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get featured products
     */
    public function featured(): JsonResponse
    {
        try {
            $products = Product::with(['category', 'vendor', 'images'])
                ->where('is_active', true)
                ->where('is_featured', true)
                ->orderBy('created_at', 'desc')
                ->limit(12)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Featured products retrieved successfully',
                'data' => $products
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving featured products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a single product by slug
     */
    public function show($slug): JsonResponse
    {
        try {
            $product = Product::with(['category', 'vendor', 'images', 'reviews.user'])
                ->where('is_active', true)
                ->where('slug', $slug)
                ->firstOrFail();

            // Increment view count
            $product->increment('view_count');

            // Get related products from the same category
            $relatedProducts = Product::with(['category', 'vendor', 'images'])
                ->where('is_active', true)
                ->where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)
                ->limit(6)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Product retrieved successfully',
                'data' => [
                    'product' => $product,
                    'related_products' => $relatedProducts
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get products by category
     */
    public function byCategory($categoryId): JsonResponse
    {
        try {
            $category = Category::findOrFail($categoryId);
            
            $products = Product::with(['category', 'vendor', 'images'])
                ->where('is_active', true)
                ->where('category_id', $categoryId)
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'message' => 'Category products retrieved successfully',
                'data' => [
                    'category' => $category,
                    'products' => $products
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving category products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get products by vendor
     */
    public function byVendor($vendorId): JsonResponse
    {
        try {
            $vendor = Vendor::findOrFail($vendorId);
            
            $products = Product::with(['category', 'vendor', 'images'])
                ->where('is_active', true)
                ->where('vendor_id', $vendorId)
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'message' => 'Vendor products retrieved successfully',
                'data' => [
                    'vendor' => $vendor,
                    'products' => $products
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving vendor products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search products
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'query' => 'required|string|min:2|max:100'
            ]);

            $searchQuery = $request->get('query');
            
            $products = Product::with(['category', 'vendor', 'images'])
                ->where('is_active', true)
                ->where(function ($q) use ($searchQuery) {
                    $q->where('name', 'like', "%{$searchQuery}%")
                      ->orWhere('description', 'like', "%{$searchQuery}%")
                      ->orWhere('short_description', 'like', "%{$searchQuery}%")
                      ->orWhereHas('category', function ($categoryQ) use ($searchQuery) {
                          $categoryQ->where('name', 'like', "%{$searchQuery}%");
                      });
                })
                ->orderByRaw("
                    CASE 
                        WHEN name LIKE ? THEN 1
                        WHEN short_description LIKE ? THEN 2
                        ELSE 3
                    END
                ", ["%{$searchQuery}%", "%{$searchQuery}%"])
                ->paginate(20);

            return response()->json([
                'success' => true,
                'message' => 'Search results retrieved successfully',
                'data' => $products,
                'search_query' => $searchQuery
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error searching products',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
