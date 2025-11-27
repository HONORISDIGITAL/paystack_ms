<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class NexusApiService
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private ?string $token = null;

    public function __construct()
    {
        $this->baseUrl = config('services.nexus.api_url', env('NEXUS_API_URL'));
        $this->username = config('services.nexus.username', env('NEXUS_USERNAME'));
        $this->password = config('services.nexus.password', env('NEXUS_PASSWORD'));
    }

    /**
     * Authenticate with Nexus API and get token
     */
    public function authenticate(): string
    {
        if ($this->token) {
            return $this->token;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl . '/api/login', [
                    'username' => $this->username,
                    'password' => $this->password,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->token = $data['token'] ?? null;
                
                if (!$this->token) {
                    throw new Exception('No token received from Nexus API');
                }

                Log::info('Successfully authenticated with Nexus API');
                return $this->token;
            }

            throw new Exception('Authentication failed: ' . $response->body());
        } catch (Exception $e) {
            Log::error('Nexus authentication failed', [
                'error' => $e->getMessage(),
                'url' => $this->baseUrl . '/api/login'
            ]);
            throw $e;
        }
    }

    /**
     * Make an authenticated request to Nexus API
     */
    public function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $token = $this->authenticate();

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->$method($this->baseUrl . $endpoint, $data);

            if ($response->successful()) {
                return $response->json();
            }

            throw new Exception('API request failed: ' . $response->body());
        } catch (Exception $e) {
            Log::error('Nexus API request failed', [
                'error' => $e->getMessage(),
                'method' => $method,
                'endpoint' => $endpoint,
                'url' => $this->baseUrl . $endpoint
            ]);
            throw $e;
        }
    }

    /**
     * Get Nexus health status
     */
    public function getHealth(): array
    {
        return $this->makeRequest('GET', '/api/health');
    }

    /**
     * Get Nexus user profile
     */
    public function getUserProfile(): array
    {
        return $this->makeRequest('GET', '/api/profile');
    }

    /**
     * Send payment notification to Nexus
     */
    public function notifyPayment(array $paymentData): array
    {
        return $this->makeRequest('POST', '/api/payments/notify', $paymentData);
    }

    /**
     * Send webhook event to Nexus
     */
    public function sendWebhookEvent(string $eventType, array $eventData): array
    {
        return $this->makeRequest('POST', '/api/webhooks/paystack', [
            'event_type' => $eventType,
            'data' => $eventData,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Clear the stored token (force re-authentication)
     */
    public function clearToken(): void
    {
        $this->token = null;
    }
}


