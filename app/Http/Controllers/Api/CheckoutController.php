<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CheckoutController extends Controller
{
    private $paystackSecretKey;
    private $paystackPublicKey;
    private $paystackBaseUrl = 'https://api.paystack.co';

    public function __construct()
    {
        $this->paystackSecretKey = env('PAYSTACK_SECRET_KEY');
        $this->paystackPublicKey = env('PAYSTACK_PUBLIC_KEY');
    }

    /**
     * Initialize checkout process.
     */
    public function initializeCheckout(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'shipping_address_id' => 'required|exists:user_addresses,id',
            'billing_address_id' => 'nullable|exists:user_addresses,id',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();
            $cart = Cart::where('user_id', $user->id)->first();

            if (!$cart || $cart->items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart is empty',
                ], 400);
            }

            // Validate stock availability
            foreach ($cart->items as $item) {
                if (!$item->product->is_in_stock) {
                    return response()->json([
                        'success' => false,
                        'message' => "Product '{$item->product->name}' is out of stock",
                    ], 400);
                }

                if ($item->quantity > $item->product->quantity) {
                    return response()->json([
                        'success' => false,
                        'message' => "Only {$item->product->quantity} units of '{$item->product->name}' available",
                    ], 400);
                }
            }

            // Calculate totals
            $subtotal = $cart->total;
            $shippingCost = $this->calculateShipping($cart);
            $taxAmount = $this->calculateTax($subtotal);
            $totalAmount = $subtotal + $shippingCost + $taxAmount;

            // Create order
            DB::beginTransaction();

            $order = Order::create([
                'user_id' => $user->id,
                'order_date' => now(),
                'status' => 'pending',
                'total_amount' => $totalAmount,
                'shipping_address_id' => $request->shipping_address_id,
                'billing_address_id' => $request->billing_address_id ?? $request->shipping_address_id,
                'shipping_cost' => $shippingCost,
                'tax_amount' => $taxAmount,
                'discount_amount' => 0,
                'notes' => $request->notes,
            ]);

            // Create order items
            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'vendor_id' => $item->product->vendor_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->price,
                    'total' => $item->subtotal,
                ]);
            }

            // Initialize Paystack payment
            $paymentData = $this->initializePaystackPayment($order, $user);

            if (!$paymentData['success']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to initialize payment',
                    'error' => $paymentData['message'],
                ], 500);
            }

            // Create payment record with idempotency key
            Payment::create([
                'order_id' => $order->id,
                'amount' => $totalAmount,
                'payment_method' => 'card',
                'status' => 'pending',
                'transaction_id' => $paymentData['reference'],
                'idempotency_key' => $this->generateIdempotencyKey($order->id, $user->id),
                'gateway_response' => $paymentData['data'],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Checkout initialized successfully',
                'data' => [
                    'order_id' => $order->id,
                    'payment_url' => $paymentData['authorization_url'],
                    'reference' => $paymentData['reference'],
                    'amount' => $totalAmount,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Checkout initialization failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Checkout failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify payment and complete order (idempotent).
     */
    public function verifyPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Find payment record with lock to prevent race conditions
            $payment = Payment::where('transaction_id', $request->reference)
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment record not found',
                ], 404);
            }

            // IDEMPOTENCY CHECK: If already processed, return the existing result
            if ($payment->isProcessed()) {
                $order = $payment->order;
                
                return response()->json([
                    'success' => true,
                    'message' => $payment->status === 'success' 
                        ? 'Payment already verified successfully' 
                        : 'Payment already processed as failed',
                    'data' => [
                        'order_id' => $order->id,
                        'payment_status' => $payment->status,
                        'order_status' => $order->status,
                        'amount' => $payment->amount,
                        'already_processed' => true,
                        'processed_at' => $payment->processed_at,
                    ],
                ]);
            }

            // Verify payment with Paystack
            $verification = $this->verifyPaystackPayment($request->reference);

            if (!$verification['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment verification failed',
                    'error' => $verification['message'],
                ], 400);
            }

            $paymentData = $verification['data'];

            DB::beginTransaction();

            // Update payment status and mark as processed
            $payment->update([
                'status' => $paymentData['status'] === 'success' ? 'success' : 'failed',
                'payment_date' => now(),
                'processed_at' => now(),
                'gateway_response' => $paymentData,
            ]);

            $order = $payment->order;

            if ($paymentData['status'] === 'success') {
                // Update order status
                $order->update(['status' => 'processing']);

                // Update product inventory
                foreach ($order->items as $item) {
                    $product = $item->product;
                    $product->decrement('quantity', $item->quantity);
                }

                // Clear user's cart
                $cart = Cart::where('user_id', $order->user_id)->first();
                if ($cart) {
                    $cart->clearItems();
                }
            } else {
                // Mark order as failed
                $order->update(['status' => 'failed']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $paymentData['status'] === 'success' 
                    ? 'Payment verified successfully' 
                    : 'Payment failed',
                'data' => [
                    'order_id' => $order->id,
                    'payment_status' => $payment->status,
                    'order_status' => $order->status,
                    'amount' => $payment->amount,
                    'already_processed' => false,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment verification failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get checkout summary.
     */
    public function getCheckoutSummary(): JsonResponse
    {
        try {
            $user = Auth::user();
            $cart = Cart::where('user_id', $user->id)->first();

            if (!$cart || $cart->items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart is empty',
                ], 400);
            }

            $subtotal = $cart->total;
            $shippingCost = $this->calculateShipping($cart);
            $taxAmount = $this->calculateTax($subtotal);
            $totalAmount = $subtotal + $shippingCost + $taxAmount;

            return response()->json([
                'success' => true,
                'message' => 'Checkout summary retrieved successfully',
                'data' => [
                    'subtotal' => $subtotal,
                    'shipping_cost' => $shippingCost,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $totalAmount,
                    'items_count' => $cart->item_count,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve checkout summary',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Initialize payment with Paystack.
     */
    private function initializePaystackPayment(Order $order, $user): array
    {
        try {
            $reference = 'ORD-' . $order->id . '-' . time();
            $amountInKobo = $order->total_amount * 100; // Convert to kobo

            // Determine callback URL: use frontend if configured, otherwise use backend route
            $frontendUrl = config('frontend.url');
            $callbackPath = config('frontend.payment_callback_path');
            $callbackUrl = $frontendUrl 
                ? rtrim($frontendUrl, '/') . $callbackPath
                : route('payment.callback');

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->paystackSecretKey,
                'Content-Type' => 'application/json',
            ])->post($this->paystackBaseUrl . '/transaction/initialize', [
                'email' => $user->email,
                'amount' => $amountInKobo,
                'reference' => $reference,
                'callback_url' => $callbackUrl,
                'metadata' => [
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'custom_fields' => [
                        [
                            'display_name' => 'Order ID',
                            'variable_name' => 'order_id',
                            'value' => $order->id,
                        ],
                    ],
                ],
            ]);

            $data = $response->json();

            if ($response->successful() && $data['status']) {
                return [
                    'success' => true,
                    'authorization_url' => $data['data']['authorization_url'],
                    'access_code' => $data['data']['access_code'],
                    'reference' => $data['data']['reference'],
                    'data' => $data['data'],
                ];
            }

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Failed to initialize payment',
            ];
        } catch (\Exception $e) {
            Log::error('Paystack initialization error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify payment with Paystack.
     */
    private function verifyPaystackPayment(string $reference): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->paystackSecretKey,
            ])->get($this->paystackBaseUrl . '/transaction/verify/' . $reference);

            $data = $response->json();

            if ($response->successful() && $data['status']) {
                return [
                    'success' => true,
                    'data' => $data['data'],
                ];
            }

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Payment verification failed',
            ];
        } catch (\Exception $e) {
            Log::error('Paystack verification error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate idempotency key for payment.
     */
    private function generateIdempotencyKey(int $orderId, int $userId): string
    {
        return hash('sha256', "payment-{$orderId}-{$userId}-" . now()->timestamp);
    }

    /**
     * Calculate shipping cost (you can customize this logic).
     */
    private function calculateShipping(Cart $cart): float
    {
        // Simple flat rate shipping
        // You can make this more complex based on weight, location, etc.
        return 2000.00; // ₦2,000 flat rate
    }

    /**
     * Calculate tax amount (you can customize this logic).
     */
    private function calculateTax(float $subtotal): float
    {
        // 7.5% VAT
        return $subtotal * 0.075;
    }
}
