<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WishlistController extends Controller
{
    /**
     * Get the authenticated user's wishlist.
     */
    public function index(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $wishlists = Wishlist::where('user_id', $user->id)
                ->with(['product.images', 'product.vendor'])
                ->latest()
                ->get();

            $items = $wishlists->map(function ($wishlist) {
                $product = $wishlist->product;
                return [
                    'id' => $wishlist->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_slug' => $product->slug,
                    'product_image' => $product->primary_image,
                    'vendor_name' => $product->vendor->business_name ?? 'N/A',
                    'price' => $product->price,
                    'in_stock' => $product->is_in_stock,
                    'stock_quantity' => $product->quantity,
                    'added_at' => $wishlist->created_at->toDateTimeString(),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Wishlist retrieved successfully',
                'data' => [
                    'items' => $items,
                    'total_items' => $items->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve wishlist',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add a product to the wishlist.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|exists:products,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = Auth::user();
            $productId = $request->product_id;

            // Check if product already exists in wishlist
            $existingWishlist = Wishlist::where('user_id', $user->id)
                ->where('product_id', $productId)
                ->first();

            if ($existingWishlist) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product already exists in wishlist',
                ], 409);
            }

            // Add product to wishlist
            $wishlist = Wishlist::create([
                'user_id' => $user->id,
                'product_id' => $productId,
            ]);

            $wishlist->load(['product.images', 'product.vendor']);

            return response()->json([
                'success' => true,
                'message' => 'Product added to wishlist successfully',
                'data' => [
                    'id' => $wishlist->id,
                    'product_id' => $wishlist->product->id,
                    'product_name' => $wishlist->product->name,
                    'product_slug' => $wishlist->product->slug,
                    'product_image' => $wishlist->product->primary_image,
                    'vendor_name' => $wishlist->product->vendor->business_name ?? 'N/A',
                    'price' => $wishlist->product->price,
                    'in_stock' => $wishlist->product->is_in_stock,
                    'added_at' => $wishlist->created_at->toDateTimeString(),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add product to wishlist',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove a product from the wishlist.
     */
    public function destroy($productId): JsonResponse
    {
        try {
            $user = Auth::user();

            $wishlist = Wishlist::where('user_id', $user->id)
                ->where('product_id', $productId)
                ->first();

            if (!$wishlist) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found in wishlist',
                ], 404);
            }

            $wishlist->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product removed from wishlist successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove product from wishlist',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if a product is in the user's wishlist.
     */
    public function check($productId): JsonResponse
    {
        try {
            $user = Auth::user();

            $exists = Wishlist::where('user_id', $user->id)
                ->where('product_id', $productId)
                ->exists();

            return response()->json([
                'success' => true,
                'data' => [
                    'in_wishlist' => $exists,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check wishlist status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle a product in the wishlist (add if not present, remove if present).
     */
    public function toggle(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|exists:products,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = Auth::user();
            $productId = $request->product_id;

            $wishlist = Wishlist::where('user_id', $user->id)
                ->where('product_id', $productId)
                ->first();

            if ($wishlist) {
                // Remove from wishlist
                $wishlist->delete();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Product removed from wishlist',
                    'data' => [
                        'in_wishlist' => false,
                    ],
                ]);
            } else {
                // Add to wishlist
                $wishlist = Wishlist::create([
                    'user_id' => $user->id,
                    'product_id' => $productId,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Product added to wishlist',
                    'data' => [
                        'in_wishlist' => true,
                        'wishlist_id' => $wishlist->id,
                    ],
                ], 201);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle wishlist',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear all items from the wishlist.
     */
    public function clear(): JsonResponse
    {
        try {
            $user = Auth::user();

            $deletedCount = Wishlist::where('user_id', $user->id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Wishlist cleared successfully',
                'data' => [
                    'deleted_count' => $deletedCount,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear wishlist',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
