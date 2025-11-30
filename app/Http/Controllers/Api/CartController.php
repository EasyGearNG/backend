<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    /**
     * Get the authenticated user's cart.
     */
    public function index(): JsonResponse
    {
        try {
            $cart = $this->getOrCreateCart();
            $cart->load(['items.product.images', 'items.product.vendor']);

            return response()->json([
                'success' => true,
                'message' => 'Cart retrieved successfully',
                'data' => [
                    'cart_id' => $cart->id,
                    'items' => $cart->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product_id' => $item->product_id,
                            'product_name' => $item->product->name,
                            'product_slug' => $item->product->slug,
                            'product_image' => $item->product->primary_image,
                            'vendor_name' => $item->product->vendor->business_name ?? 'N/A',
                            'price' => $item->product->price,
                            'quantity' => $item->quantity,
                            'subtotal' => $item->subtotal,
                            'in_stock' => $item->product->is_in_stock,
                            'stock_quantity' => $item->product->quantity,
                        ];
                    }),
                    'total_items' => $cart->item_count,
                    'total_amount' => $cart->total,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve cart',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add item to cart.
     */
    public function addItem(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $product = Product::findOrFail($request->product_id);

            // Check if product is in stock
            if (!$product->is_in_stock) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product is out of stock',
                ], 400);
            }

            // Check if requested quantity is available
            if ($request->quantity > $product->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => "Only {$product->quantity} units available in stock",
                ], 400);
            }

            $cart = $this->getOrCreateCart();

            // Check if item already exists in cart
            $cartItem = $cart->items()->where('product_id', $request->product_id)->first();

            if ($cartItem) {
                // Update quantity
                $newQuantity = $cartItem->quantity + $request->quantity;

                if ($newQuantity > $product->quantity) {
                    return response()->json([
                        'success' => false,
                        'message' => "Cannot add more items. Only {$product->quantity} units available in stock",
                    ], 400);
                }

                $cartItem->update(['quantity' => $newQuantity]);
            } else {
                // Create new cart item
                $cartItem = $cart->items()->create([
                    'product_id' => $request->product_id,
                    'quantity' => $request->quantity,
                ]);
            }

            $cart->load(['items.product.images']);

            return response()->json([
                'success' => true,
                'message' => 'Item added to cart successfully',
                'data' => [
                    'cart_id' => $cart->id,
                    'total_items' => $cart->item_count,
                    'total_amount' => $cart->total,
                    'item' => [
                        'id' => $cartItem->id,
                        'product_name' => $cartItem->product->name,
                        'quantity' => $cartItem->quantity,
                        'subtotal' => $cartItem->subtotal,
                    ],
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add item to cart',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update cart item quantity.
     */
    public function updateItem(Request $request, $itemId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $cart = $this->getOrCreateCart();
            $cartItem = $cart->items()->findOrFail($itemId);

            // Check stock availability
            if ($request->quantity > $cartItem->product->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => "Only {$cartItem->product->quantity} units available in stock",
                ], 400);
            }

            $cartItem->update(['quantity' => $request->quantity]);

            return response()->json([
                'success' => true,
                'message' => 'Cart item updated successfully',
                'data' => [
                    'item' => [
                        'id' => $cartItem->id,
                        'quantity' => $cartItem->quantity,
                        'subtotal' => $cartItem->subtotal,
                    ],
                    'total_items' => $cart->item_count,
                    'total_amount' => $cart->total,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update cart item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove item from cart.
     */
    public function removeItem($itemId): JsonResponse
    {
        try {
            $cart = $this->getOrCreateCart();
            $cartItem = $cart->items()->findOrFail($itemId);
            $cartItem->delete();

            return response()->json([
                'success' => true,
                'message' => 'Item removed from cart successfully',
                'data' => [
                    'total_items' => $cart->item_count,
                    'total_amount' => $cart->total,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove item from cart',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear all items from cart.
     */
    public function clear(): JsonResponse
    {
        try {
            $cart = $this->getOrCreateCart();
            $cart->clearItems();

            return response()->json([
                'success' => true,
                'message' => 'Cart cleared successfully',
                'data' => [
                    'total_items' => 0,
                    'total_amount' => 0,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cart',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get or create cart for authenticated user.
     */
    private function getOrCreateCart(): Cart
    {
        return Cart::firstOrCreate(
            ['user_id' => Auth::id()],
        );
    }
}
