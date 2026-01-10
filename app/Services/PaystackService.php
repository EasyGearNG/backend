<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    private $secretKey;
    private $baseUrl = 'https://api.paystack.co';

    public function __construct()
    {
        $this->secretKey = env('PAYSTACK_SECRET_KEY');
    }

    /**
     * Create a transfer recipient for bank account
     * 
     * @param string $type Recipient type: 'nuban' for Nigerian bank accounts
     * @param string $name Account holder name
     * @param string $accountNumber Bank account number
     * @param string $bankCode Bank code (e.g., '058' for GTBank)
     * @param string|null $currency Currency code (NGN, USD, etc.)
     * @return array
     */
    public function createTransferRecipient(
        string $type,
        string $name,
        string $accountNumber,
        string $bankCode,
        ?string $currency = 'NGN'
    ): array {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/transferrecipient', [
                'type' => $type,
                'name' => $name,
                'account_number' => $accountNumber,
                'bank_code' => $bankCode,
                'currency' => $currency,
            ]);

            $data = $response->json();

            if ($response->successful() && $data['status']) {
                return [
                    'success' => true,
                    'recipient_code' => $data['data']['recipient_code'],
                    'data' => $data['data'],
                ];
            }

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Failed to create transfer recipient',
            ];
        } catch (\Exception $e) {
            Log::error('Paystack create recipient error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Initiate a transfer to a recipient
     * 
     * @param string $source Source of funds: 'balance'
     * @param float $amount Amount in naira (will be converted to kobo)
     * @param string $recipientCode Recipient code from createTransferRecipient
     * @param string|null $reason Transfer reason/description
     * @param string|null $reference Custom reference (optional, Paystack will generate if not provided)
     * @return array
     */
    public function initiateTransfer(
        string $source,
        float $amount,
        string $recipientCode,
        ?string $reason = null,
        ?string $reference = null
    ): array {
        try {
            $amountInKobo = $amount * 100;

            $payload = [
                'source' => $source,
                'amount' => $amountInKobo,
                'recipient' => $recipientCode,
            ];

            if ($reason) {
                $payload['reason'] = $reason;
            }

            if ($reference) {
                $payload['reference'] = $reference;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/transfer', $payload);

            $data = $response->json();

            if ($response->successful() && $data['status']) {
                return [
                    'success' => true,
                    'transfer_code' => $data['data']['transfer_code'],
                    'id' => $data['data']['id'],
                    'reference' => $data['data']['reference'],
                    'status' => $data['data']['status'],
                    'data' => $data['data'],
                ];
            }

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Failed to initiate transfer',
            ];
        } catch (\Exception $e) {
            Log::error('Paystack initiate transfer error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify transfer status
     * 
     * @param string $reference Transfer reference
     * @return array
     */
    public function verifyTransfer(string $reference): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->get($this->baseUrl . '/transfer/verify/' . $reference);

            $data = $response->json();

            if ($response->successful() && $data['status']) {
                return [
                    'success' => true,
                    'status' => $data['data']['status'],
                    'data' => $data['data'],
                ];
            }

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Transfer verification failed',
            ];
        } catch (\Exception $e) {
            Log::error('Paystack verify transfer error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Finalize transfer (disable OTP requirement for future transfers)
     * 
     * @param string $transferCode Transfer code to finalize
     * @param string $otp OTP sent to phone
     * @return array
     */
    public function finalizeTransfer(string $transferCode, string $otp): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/transfer/finalize_transfer', [
                'transfer_code' => $transferCode,
                'otp' => $otp,
            ]);

            $data = $response->json();

            if ($response->successful() && $data['status']) {
                return [
                    'success' => true,
                    'message' => $data['message'],
                    'data' => $data['data'],
                ];
            }

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Failed to finalize transfer',
            ];
        } catch (\Exception $e) {
            Log::error('Paystack finalize transfer error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * List all banks supported by Paystack
     * 
     * @param string $country Country code (NG for Nigeria, GH for Ghana, etc.)
     * @return array
     */
    public function listBanks(string $country = 'NG'): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->get($this->baseUrl . '/bank', [
                'country' => $country,
            ]);

            $data = $response->json();

            if ($response->successful() && $data['status']) {
                return [
                    'success' => true,
                    'banks' => $data['data'],
                ];
            }

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Failed to fetch banks',
            ];
        } catch (\Exception $e) {
            Log::error('Paystack list banks error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Resolve bank account to get account name
     * 
     * @param string $accountNumber Bank account number
     * @param string $bankCode Bank code
     * @return array
     */
    public function resolveAccountNumber(string $accountNumber, string $bankCode): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->get($this->baseUrl . '/bank/resolve', [
                'account_number' => $accountNumber,
                'bank_code' => $bankCode,
            ]);

            $data = $response->json();

            if ($response->successful() && $data['status']) {
                return [
                    'success' => true,
                    'account_number' => $data['data']['account_number'],
                    'account_name' => $data['data']['account_name'],
                    'bank_id' => $data['data']['bank_id'] ?? null,
                ];
            }

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Failed to resolve account',
            ];
        } catch (\Exception $e) {
            Log::error('Paystack resolve account error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
