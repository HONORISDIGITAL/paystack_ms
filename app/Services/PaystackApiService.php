<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class PaystackApiService
{
    private string $baseUrl;
    private string $secretKey;

    public function __construct()
    {
        $this->baseUrl = config('services.paystack.base_url', 'https://api.paystack.com/v3');
        $this->secretKey = config('services.paystack.secret_key');
    }

    /**
     * Verify a transaction by id
     */
    public function verifyTransactionById(string $transactionId): array
    {
        return $this->getJson("/transactions/{$transactionId}/verify");
    }

    /**
     * Verify a transaction by reference (tx_ref)
     */
    public function verifyTransactionByRef(string $txRef): array
    {
        return $this->getJson("/transactions/verify_by_reference?tx_ref=" . urlencode($txRef));
    }

    private function getJson(string $endpoint): array
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->secretKey,
                ])
                ->get($this->baseUrl . $endpoint);

            if ($response->successful()) {
                return $response->json();
            }

            throw new Exception('Paystack API error: ' . $response->body());
        } catch (Exception $e) {
            Log::error('Paystack verify request failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}



