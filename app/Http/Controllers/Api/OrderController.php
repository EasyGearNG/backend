<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Get all orders for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            $query = Order::with([
                'items.product.images',
                'items.vendor',
                'payment',
                'shippingAddress'
            ])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

            // Filter by status if provided
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by payment status if provided
            if ($request->has('payment_status')) {
                $query->whereHas('payment', function ($q) use ($request) {
                    $q->where('status', $request->payment_status);
                });
            }

            // Filter by date range if provided
            if ($request->has('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            $orders = $query->paginate($request->get('per_page', 15));

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
     * Get specific order details for the authenticated user
     */
    public function show($id): JsonResponse
    {
        try {
            $user = auth()->user();

            $order = Order::with([
                'items.product.images',
                'items.product.category',
                'items.vendor',
                'items.logisticsCompany',
                'payment',
                'shippingAddress',
                'billingAddress'
            ])
            ->where('user_id', $user->id)
            ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $order,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch order details',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
