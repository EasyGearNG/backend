<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentCallbackController extends Controller
{
    /**
     * Handle Paystack payment callback (simulates frontend).
     * This route receives the redirect from Paystack after payment.
     */
    public function handleCallback(Request $request)
    {
        // Get reference from query string
        $reference = $request->query('reference');
        $status = $request->query('status');

        if (!$reference) {
            return view('payment.error', [
                'message' => 'No payment reference found',
                'status' => 'error'
            ]);
        }

        // Verify payment via API
        $verificationUrl = url('/api/v1/checkout/verify');
        
        try {
            // Get the payment record to get the user's token
            $payment = Payment::where('transaction_id', $reference)->first();
            
            if (!$payment) {
                return view('payment.error', [
                    'message' => 'Payment record not found',
                    'status' => 'error',
                    'reference' => $reference
                ]);
            }

            // Get user's token (in a real app, this would come from the frontend session)
            $user = $payment->order->user;
            $token = $user->createToken('payment-verification')->plainTextToken;

            // Call the verification API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($verificationUrl, [
                'reference' => $reference
            ]);

            $data = $response->json();

            if ($data['success'] && $data['data']['payment_status'] === 'success') {
                // Payment successful
                return view('payment.success', [
                    'order_id' => $data['data']['order_id'],
                    'amount' => $data['data']['amount'],
                    'reference' => $reference,
                    'message' => 'Payment successful! Your order is being processed.'
                ]);
            } else {
                // Payment failed
                return view('payment.error', [
                    'message' => 'Payment verification failed. Please contact support.',
                    'status' => 'failed',
                    'reference' => $reference
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Payment callback error: ' . $e->getMessage());
            
            return view('payment.error', [
                'message' => 'An error occurred while verifying payment',
                'status' => 'error',
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Test endpoint to manually verify a payment.
     * Useful for testing without going through the full flow.
     */
    public function testVerification(Request $request)
    {
        $reference = $request->query('reference');
        
        if (!$reference) {
            return response()->json([
                'error' => 'Please provide a reference parameter'
            ], 400);
        }

        return redirect()->route('payment.callback', ['reference' => $reference]);
    }
}
