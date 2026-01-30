<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Vendor;
use App\Models\LogisticsCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class VendorFulfillmentController extends Controller
{
    /**
     * Get all orders tied to the vendor (all statuses)
     */
    public function getAllOrders(Request $request): JsonResponse
    {
        try {
            $vendor = auth()->user()->vendor;

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor profile not found',
                ], 404);
            }

            $query = OrderItem::with([
                'order.user',
                'order.payment',
                'order.shippingAddress',
                'product.images',
                'logisticsCompany'
            ])
            ->where('vendor_id', $vendor->id)
            ->orderBy('created_at', 'desc');

            // Filter by fulfillment status if provided
            if ($request->has('fulfillment_status')) {
                $query->where('fulfillment_status', $request->fulfillment_status);
            }

            // Filter by order status if provided
            if ($request->has('order_status')) {
                $query->whereHas('order', function ($q) use ($request) {
                    $q->where('status', $request->order_status);
                });
            }

            // Filter by date range if provided
            if ($request->has('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            // Search by order ID or product name
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('order_id', 'like', "%{$search}%")
                      ->orWhereHas('product', function ($pq) use ($search) {
                          $pq->where('name', 'like', "%{$search}%");
                      });
                });
            }

            $orders = $query->paginate($request->get('per_page', 15));

            // Add summary statistics
            $stats = [
                'total_orders' => OrderItem::where('vendor_id', $vendor->id)->count(),
                'pending' => OrderItem::where('vendor_id', $vendor->id)->where('fulfillment_status', 'pending')->count(),
                'dispatched' => OrderItem::where('vendor_id', $vendor->id)->where('fulfillment_status', 'dispatched')->count(),
                'confirmed' => OrderItem::where('vendor_id', $vendor->id)->where('fulfillment_status', 'confirmed')->count(),
                'total_revenue' => OrderItem::where('vendor_id', $vendor->id)
                    ->where('fulfillment_status', 'confirmed')
                    ->sum('subtotal'),
            ];

            return response()->json([
                'success' => true,
                'data' => $orders,
                'stats' => $stats,
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
     * Get vendor's pending orders (items to dispatch)
     */
    public function pendingOrders(Request $request): JsonResponse
    {
        try {
            $vendor = auth()->user()->vendor;

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor profile not found',
                ], 404);
            }

            $orders = OrderItem::with(['order.user', 'product'])
                ->where('vendor_id', $vendor->id)
                ->where('fulfillment_status', 'pending')
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $orders,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pending orders',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Dispatch order item (mark as dispatched)
     */
    public function dispatchItem(Request $request, $itemId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'logistics_company_id' => 'required|exists:logistics_companies,id',
            'dispatch_notes' => 'sometimes|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $vendor = auth()->user()->vendor;

            $orderItem = OrderItem::with(['order.payment', 'product'])
                ->where('vendor_id', $vendor->id)
                ->findOrFail($itemId);

            if ($orderItem->fulfillment_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Order item already dispatched or completed',
                ], 400);
            }

            // Ensure payment is successful
            if ($orderItem->order->payment->status !== 'success') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot dispatch - payment not confirmed',
                ], 400);
            }

            // Get logistics company and calculate fee
            $logisticsCompany = LogisticsCompany::findOrFail($request->logistics_company_id);
            
            if (!$logisticsCompany->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected logistics company is not active',
                ], 400);
            }

            // Calculate logistics fee (base fee + percentage of order value)
            $logisticsFee = $logisticsCompany->delivery_fee;
            if ($logisticsCompany->commission_percentage > 0) {
                $logisticsFee += ($orderItem->subtotal * $logisticsCompany->commission_percentage) / 100;
            }

            DB::transaction(function () use ($orderItem, $request, $logisticsCompany, $logisticsFee) {
                // Update order item status
                $orderItem->update([
                    'fulfillment_status' => 'dispatched',
                    'dispatched_at' => now(),
                    'dispatch_notes' => $request->dispatch_notes,
                    'logistics_company_id' => $logisticsCompany->id,
                    'logistics_fee' => $logisticsFee,
                ]);

                // Add funds to pending wallet (not yet available)
                $this->addToPendingWallet($orderItem);
            });

            return response()->json([
                'success' => true,
                'message' => 'Order item dispatched successfully. Funds added to pending balance.',
                'data' => $orderItem->fresh(['order', 'product', 'logisticsCompany']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to dispatch order item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirm delivery and release payment
     */
    public function confirmDelivery($itemId): JsonResponse
    {
        try {
            $orderItem = OrderItem::with(['order.payment', 'product', 'vendor'])
                ->findOrFail($itemId);

            // Only customer or admin can confirm
            $user = auth()->user();
            if ($user->role !== 'admin' && $orderItem->order->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to confirm this delivery',
                ], 403);
            }

            if ($orderItem->fulfillment_status !== 'dispatched') {
                return response()->json([
                    'success' => false,
                    'message' => 'Item must be dispatched before confirmation',
                ], 400);
            }

            DB::transaction(function () use ($orderItem) {
                // Update status
                $orderItem->update([
                    'fulfillment_status' => 'confirmed',
                    'confirmed_at' => now(),
                ]);

                // Process payment splits and credit wallets
                $this->processPaymentSplit($orderItem);
            });

            return response()->json([
                'success' => true,
                'message' => 'Delivery confirmed. Payment split processed.',
                'data' => $orderItem->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm delivery',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get vendor wallet details
     */
    public function getWallet(): JsonResponse
    {
        try {
            $vendor = auth()->user()->vendor;

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor profile not found',
                ], 404);
            }

            $wallet = Wallet::getOrCreate('vendor', $vendor->id);
            $recentTransactions = $wallet->transactions()
                ->with(['order', 'orderItem.product'])
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'wallet' => $wallet,
                    'recent_transactions' => $recentTransactions,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch wallet',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add funds to pending wallet (awaiting confirmation)
     */
    private function addToPendingWallet(OrderItem $orderItem): void
    {
        $vendor = $orderItem->vendor;
        $wallet = Wallet::getOrCreate('vendor', $vendor->id);

        // Calculate vendor's share (after commission)
        $commissionRate = $vendor->commission_rate ?? 15.00;
        $platformFee = ($orderItem->subtotal * $commissionRate) / 100;
        $vendorAmount = $orderItem->subtotal - $platformFee;

        $reference = 'PEND-' . $orderItem->id . '-' . Str::random(8);

        $wallet->addPending(
            $vendorAmount,
            $reference,
            "Pending payment for {$orderItem->product->name} (Order #{$orderItem->order_id})",
            [
                'order_id' => $orderItem->order_id,
                'order_item_id' => $orderItem->id,
                'product_id' => $orderItem->product_id,
                'subtotal' => $orderItem->subtotal,
                'vendor_amount' => $vendorAmount,
                'platform_fee' => $platformFee,
            ]
        );
    }

    /**
     * Process payment split when delivery is confirmed
     */
    private function processPaymentSplit(OrderItem $orderItem): void
    {
        $vendor = $orderItem->vendor;
        $vendorWallet = Wallet::getOrCreate('vendor', $vendor->id);
        
        // Calculate splits
        $commissionRate = $vendor->commission_rate ?? 15.00;
        $platformFee = ($orderItem->subtotal * $commissionRate) / 100;
        $logisticsFee = $orderItem->logistics_fee ?? 0;
        
        // Vendor gets: subtotal - platform fee - logistics fee
        $vendorAmount = $orderItem->subtotal - $platformFee - $logisticsFee;

        // Split platform fee between Easygear and Resilience (70/30 split)
        $easygearShare = $platformFee * 0.70;
        $resilienceShare = $platformFee * 0.30;

        $reference = 'SPLIT-' . $orderItem->id . '-' . Str::random(8);

        // 1. Convert pending to available for vendor (recalculate correct amount)
        $pendingAmount = $orderItem->subtotal - $platformFee; // Original pending amount
        $vendorWallet->confirmPending($pendingAmount);
        
        // Deduct logistics fee from vendor's balance
        if ($logisticsFee > 0) {
            $vendorWallet->debit(
                $logisticsFee,
                $reference . '-LOG',
                "Logistics fee for Order #{$orderItem->order_id}",
                [
                    'order_id' => $orderItem->order_id,
                    'order_item_id' => $orderItem->id,
                    'logistics_company_id' => $orderItem->logistics_company_id,
                ]
            );
        }
        
        $vendorWallet->transactions()->create([
            'order_id' => $orderItem->order_id,
            'order_item_id' => $orderItem->id,
            'type' => 'credit',
            'amount' => $vendorAmount,
            'balance_before' => $vendorWallet->balance - $vendorAmount,
            'balance_after' => $vendorWallet->balance,
            'reference' => $reference,
            'description' => "Payment for {$orderItem->product->name} (Order #{$orderItem->order_id})",
            'metadata' => [
                'order_id' => $orderItem->order_id,
                'subtotal' => $orderItem->subtotal,
                'platform_fee' => $platformFee,
                'logistics_fee' => $logisticsFee,
            ],
        ]);

        // 2. Credit Easygear wallet
        $easygearWallet = Wallet::getOrCreate('easygear', null);
        $easygearWallet->credit(
            $easygearShare,
            $reference,
            "Platform fee from Order #{$orderItem->order_id}",
            [
                'order_id' => $orderItem->order_id,
                'order_item_id' => $orderItem->id,
                'vendor_id' => $vendor->id,
            ]
        );

        // 3. Credit Resilience wallet
        $resilienceWallet = Wallet::getOrCreate('resilience', null);
        $resilienceWallet->credit(
            $resilienceShare,
            $reference,
            "Partner fee from Order #{$orderItem->order_id}",
            [
                'order_id' => $orderItem->order_id,
                'order_item_id' => $orderItem->id,
                'vendor_id' => $vendor->id,
            ]
        );

        // 4. Credit Logistics Company wallet (if assigned)
        if ($orderItem->logistics_company_id && $logisticsFee > 0) {
            $logisticsCompany = $orderItem->logisticsCompany;
            $logisticsWallet = $logisticsCompany->getOrCreateWallet();
            
            $logisticsWallet->credit(
                $logisticsFee,
                $reference,
                "Delivery fee for Order #{$orderItem->order_id}",
                [
                    'order_id' => $orderItem->order_id,
                    'order_item_id' => $orderItem->id,
                    'vendor_id' => $vendor->id,
                ]
            );
        }
    }

    /**
     * Get available logistics companies
     */
    public function getLogisticsCompanies(): JsonResponse
    {
        try {
            $companies = LogisticsCompany::where('is_active', true)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $companies,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch logistics companies',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
