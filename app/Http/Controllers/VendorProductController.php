<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class VendorProductController extends Controller
{
    /**
     * Get vendor's products with pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $vendorId = Auth::user()->vendor->id;
            
            $query = Product::with(['category', 'images'])
                ->where('vendor_id', $vendorId);

            // Filter by status
            if ($request->filled('status')) {
                if ($request->get('status') === 'active') {
                    $query->where('is_active', true);
                } else {
                    $query->where('is_active', false);
                }
            }

            // Search by name
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where('name', 'like', "%{$search}%");
            }

            // Sort options
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            $allowedSorts = ['name', 'price', 'created_at', 'quantity', 'status'];
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder);
            }

            $products = $query->paginate($request->get('per_page', 20));

            return response()->json([
                'success' => true,
                'message' => 'Products retrieved successfully',
                'data' => $products
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
     * Create a new product
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'short_description' => 'required|string|max:500',
                'description' => 'required|string',
                'price' => 'required|numeric|min:0',
                'quantity' => 'required|integer|min:0',
                'category_id' => 'required|exists:categories,id',
                'sku' => 'nullable|string|unique:products,sku|max:100',
                'weight' => 'nullable|numeric|min:0',
                'dimensions' => 'nullable|string|max:100',
                'is_featured' => 'boolean',
                'status' => 'required|in:active,inactive,draft',
                'images' => 'nullable|array|max:5',
                'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048'
            ]);

            $vendorId = Auth::user()->vendor->id;

            $product = Product::create([
                'vendor_id' => $vendorId,
                'name' => $request->name,
                'slug' => Str::slug($request->name) . '-' . time(),
                'short_description' => $request->short_description,
                'description' => $request->description,
                'price' => $request->price,
                'quantity' => $request->quantity,
                'category_id' => $request->category_id,
                'sku' => $request->sku ?: 'SKU-' . strtoupper(Str::random(8)),
                'weight' => $request->weight,
                'dimensions' => $request->dimensions,
                'is_featured' => $request->boolean('is_featured'),
                'status' => $request->status,
            ]);

            // Handle image uploads
            if ($request->hasFile('images')) {
                $this->uploadProductImages($product, $request->file('images'));
            }

            $product->load(['category', 'images']);

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $product
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific product by ID (vendor's product only)
     */
    public function show($id): JsonResponse
    {
        try {
            $vendorId = Auth::user()->vendor->id;
            
            $product = Product::with(['category', 'images', 'reviews.user'])
                ->where('vendor_id', $vendorId)
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Product retrieved successfully',
                'data' => $product
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found or you do not have permission to view it'
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
     * Update a product
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $vendorId = Auth::user()->vendor->id;
            
            $product = Product::where('vendor_id', $vendorId)->findOrFail($id);

            $request->validate([
                'name' => 'required|string|max:255',
                'short_description' => 'required|string|max:500',
                'description' => 'required|string',
                'price' => 'required|numeric|min:0',
                'quantity' => 'required|integer|min:0',
                'category_id' => 'required|exists:categories,id',
                'sku' => 'nullable|string|max:100|unique:products,sku,' . $product->id,
                'weight' => 'nullable|numeric|min:0',
                'dimensions' => 'nullable|string|max:100',
                'is_featured' => 'boolean',
                'status' => 'required|in:active,inactive,draft',
                'images' => 'nullable|array|max:5',
                'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
                'remove_images' => 'nullable|array',
                'remove_images.*' => 'exists:product_images,id'
            ]);

            $product->update([
                'name' => $request->name,
                'slug' => $product->name !== $request->name ? 
                    Str::slug($request->name) . '-' . time() : $product->slug,
                'short_description' => $request->short_description,
                'description' => $request->description,
                'price' => $request->price,
                'quantity' => $request->quantity,
                'category_id' => $request->category_id,
                'sku' => $request->sku ?: $product->sku,
                'weight' => $request->weight,
                'dimensions' => $request->dimensions,
                'is_featured' => $request->boolean('is_featured'),
                'status' => $request->status,
            ]);

            // Handle image removal
            if ($request->filled('remove_images')) {
                $this->removeProductImages($product, $request->remove_images);
            }

            // Handle new image uploads
            if ($request->hasFile('images')) {
                $this->uploadProductImages($product, $request->file('images'));
            }

            $product->load(['category', 'images']);

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $product
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found or you do not have permission to update it'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a product
     */
    public function destroy($id): JsonResponse
    {
        try {
            $vendorId = Auth::user()->vendor->id;
            
            $product = Product::where('vendor_id', $vendorId)->findOrFail($id);

            // Remove all product images
            foreach ($product->images as $image) {
                if (Storage::exists($image->image_path)) {
                    Storage::delete($image->image_path);
                }
            }

            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found or you do not have permission to delete it'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update product stock quantity
     */
    public function updateStock(Request $request, $id): JsonResponse
    {
        try {
            $vendorId = Auth::user()->vendor->id;
            
            $product = Product::where('vendor_id', $vendorId)->findOrFail($id);

            $request->validate([
                'quantity' => 'required|integer|min:0'
            ]);

            $product->update([
                'quantity' => $request->quantity
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Product stock updated successfully',
                'data' => [
                    'product_id' => $product->id,
                    'new_quantity' => $product->quantity
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found or you do not have permission to update it'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating product stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get vendor's product statistics
     */
    public function stats(): JsonResponse
    {
        try {
            $vendorId = Auth::user()->vendor->id;

            $stats = [
                'total_products' => Product::where('vendor_id', $vendorId)->count(),
                'active_products' => Product::where('vendor_id', $vendorId)->where('is_active', true)->count(),
                'inactive_products' => Product::where('vendor_id', $vendorId)->where('is_active', false)->count(),
                'draft_products' => Product::where('vendor_id', $vendorId)->where('status', 'draft')->count(),
                'out_of_stock' => Product::where('vendor_id', $vendorId)->where('quantity', 0)->count(),
                'low_stock' => Product::where('vendor_id', $vendorId)
                    ->where('quantity', '>', 0)
                    ->where('quantity', '<=', 10)
                    ->count(),
                'featured_products' => Product::where('vendor_id', $vendorId)
                    ->where('is_featured', true)
                    ->count()
            ];

            return response()->json([
                'success' => true,
                'message' => 'Product statistics retrieved successfully',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving product statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload product images
     */
    private function uploadProductImages(Product $product, array $images): void
    {
        foreach ($images as $image) {
            $path = $image->store('products', 'public');
            
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $path,
                'alt_text' => $product->name
            ]);
        }
    }

    /**
     * Remove product images
     */
    private function removeProductImages(Product $product, array $imageIds): void
    {
        $images = ProductImage::where('product_id', $product->id)
            ->whereIn('id', $imageIds)
            ->get();

        foreach ($images as $image) {
            if (Storage::disk('public')->exists($image->image_path)) {
                Storage::disk('public')->delete($image->image_path);
            }
            $image->delete();
        }
    }
}
