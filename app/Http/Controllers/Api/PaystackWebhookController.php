<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WalletWithdrawal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    /**
     * Handle Paystack transfer webhooks
     */
    public function handleTransferWebhook(Request $request): JsonResponse
    {
        // Verify webhook signature
        $paystackSignature = $request->header('x-paystack-signature');
        $computedSignature = hash_hmac('sha512', $request->getContent(), env('PAYSTACK_SECRET_KEY'));

        if ($paystackSignature !== $computedSignature) {
            Log::warning('Invalid Paystack webhook signature');
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $event = $request->input('event');
        $data = $request->input('data');

        Log::info('Paystack webhook received', [
            'event' => $event,
            'reference' => $data['reference'] ?? null,
        ]);

        try {
            switch ($event) {
                case 'transfer.success':
                    $this->handleTransferSuccess($data);
                    break;

                case 'transfer.failed':
                    $this->handleTransferFailed($data);
                    break;

                case 'transfer.reversed':
                    $this->handleTransferReversed($data);
                    break;

                default:
                    Log::info('Unhandled Paystack webhook event: ' . $event);
            }

            return response()->json(['message' => 'Webhook processed successfully']);
        } catch (\Exception $e) {
            Log::error('Paystack webhook processing error: ' . $e->getMessage(), [
                'event' => $event,
                'data' => $data,
            ]);

            return response()->json(['message' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Handle successful transfer
     */
    private function handleTransferSuccess(array $data): void
    {
        $reference = $data['reference'];
        $transferCode = $data['transfer_code'] ?? null;

        $withdrawal = WalletWithdrawal::where('reference', $reference)->first();

        if (!$withdrawal) {
            Log::warning('Withdrawal not found for transfer success', ['reference' => $reference]);
            return;
        }

        if ($withdrawal->status === 'completed') {
            Log::info('Withdrawal already marked as completed', ['reference' => $reference]);
            return;
        }

        $withdrawal->update([
            'status' => 'completed',
            'processed_at' => now(),
            'metadata' => array_merge($withdrawal->metadata ?? [], [
                'paystack_success_data' => $data,
                'completed_via' => 'webhook',
            ]),
        ]);

        Log::info('Transfer marked as successful', [
            'withdrawal_id' => $withdrawal->id,
            'reference' => $reference,
            'amount' => $withdrawal->amount,
        ]);
    }

    /**
     * Handle failed transfer
     */
    private function handleTransferFailed(array $data): void
    {
        $reference = $data['reference'];
        $transferCode = $data['transfer_code'] ?? null;

        $withdrawal = WalletWithdrawal::where('reference', $reference)->first();

        if (!$withdrawal) {
            Log::warning('Withdrawal not found for transfer failure', ['reference' => $reference]);
            return;
        }

        if (in_array($withdrawal->status, ['completed', 'failed'])) {
            Log::info('Withdrawal already processed', ['reference' => $reference]);
            return;
        }

        // Refund to wallet
        $wallet = $withdrawal->wallet;
        $wallet->credit(
            $withdrawal->amount,
            $reference . '-REFUND',
            'Refund for failed Paystack transfer',
            [
                'original_withdrawal_id' => $withdrawal->id,
                'refund_reason' => 'paystack_transfer_failed',
                'paystack_data' => $data,
            ]
        );

        $withdrawal->update([
            'status' => 'failed',
            'processed_at' => now(),
            'notes' => 'Paystack transfer failed: ' . ($data['reason'] ?? 'Unknown reason'),
            'metadata' => array_merge($withdrawal->metadata ?? [], [
                'paystack_failure_data' => $data,
                'failed_via' => 'webhook',
                'refunded' => true,
            ]),
        ]);

        Log::info('Transfer marked as failed and refunded', [
            'withdrawal_id' => $withdrawal->id,
            'reference' => $reference,
            'amount' => $withdrawal->amount,
            'reason' => $data['reason'] ?? 'Unknown',
        ]);
    }

    /**
     * Handle reversed transfer
     */
    private function handleTransferReversed(array $data): void
    {
        $reference = $data['reference'];

        $withdrawal = WalletWithdrawal::where('reference', $reference)->first();

        if (!$withdrawal) {
            Log::warning('Withdrawal not found for transfer reversal', ['reference' => $reference]);
            return;
        }

        // Refund to wallet if not already refunded
        if ($withdrawal->status !== 'failed') {
            $wallet = $withdrawal->wallet;
            $wallet->credit(
                $withdrawal->amount,
                $reference . '-REVERSAL',
                'Refund for reversed Paystack transfer',
                [
                    'original_withdrawal_id' => $withdrawal->id,
                    'refund_reason' => 'paystack_transfer_reversed',
                    'paystack_data' => $data,
                ]
            );

            $withdrawal->update([
                'status' => 'failed',
                'processed_at' => now(),
                'notes' => 'Paystack transfer reversed: ' . ($data['reason'] ?? 'Unknown reason'),
                'metadata' => array_merge($withdrawal->metadata ?? [], [
                    'paystack_reversal_data' => $data,
                    'reversed_via' => 'webhook',
                    'refunded' => true,
                ]),
            ]);

            Log::info('Transfer reversed and refunded', [
                'withdrawal_id' => $withdrawal->id,
                'reference' => $reference,
                'amount' => $withdrawal->amount,
            ]);
        }
    }
}
