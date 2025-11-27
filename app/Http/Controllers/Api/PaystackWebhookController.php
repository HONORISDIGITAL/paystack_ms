<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\DTOs\PaystackPaymentDTO;
use App\DTOs\NexusPaymentDTO;
use App\Services\NexusApiService;
use App\Services\PaystackApiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;
use Sentry\Laravel\Facade as Sentry;

class PaystackWebhookController extends Controller
{
    /**
     * Handle Paystack payment webhook
     */
    public function handlePaymentWebhook(Request $request): JsonResponse
    {
        $webhookId = uniqid('webhook_');
        $timestamp = now()->toISOString();
        
        // Immediate logging when webhook is hit
        Log::info('🚀 PAYSTACK PAYMENT WEBHOOK HIT', [
            'webhook_id' => $webhookId,
            'timestamp' => $timestamp,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'url' => $request->fullUrl()
        ]);
        
        try {
            // Get all request data
            $headers = $request->headers->all();
            $payload = $request->all();
            $rawBody = $request->getContent();
            
            // Prepare webhook context for Sentry
            $webhookContext = [
                'webhook_id' => $webhookId,
                'timestamp' => $timestamp,
                'source' => 'paystack_payment_webhook',
                'headers' => $this->sanitizeHeaders($headers),
                'payload' => $this->sanitizePayload($payload),
                'raw_body' => $rawBody,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ];

            // Log webhook receipt to Sentry
            Sentry::addBreadcrumb(
                new \Sentry\Breadcrumb(
                    \Sentry\Breadcrumb::LEVEL_INFO,
                    \Sentry\Breadcrumb::TYPE_DEFAULT,
                    'paystack.webhook',
                    'Paystack payment webhook received',
                    $webhookContext
                )
            );

            // Set Sentry context
            Sentry::configureScope(function (\Sentry\State\Scope $scope) use ($webhookContext) {
                $scope->setContext('paystack_webhook', $webhookContext);
                $scope->setTag('webhook_type', 'payment');
                $scope->setTag('webhook_id', $webhookContext['webhook_id']);
            });

            // Validate webhook signature (optional but recommended)
            if ($this->validateWebhookSignature($request)) {
                $webhookContext['signature_valid'] = true;
                Log::info('Paystack webhook signature validated', $webhookContext);
            } else {
                $webhookContext['signature_valid'] = false;
                Log::warning('Paystack webhook signature validation failed', $webhookContext);
                
                // Capture invalid signature as Sentry event
                Sentry::captureMessage('Invalid Paystack webhook signature', \Sentry\Severity::warning());
            }

            // Extract key payment information
            $paymentInfo = $this->extractPaymentInfo($payload);
            
            // Save payment to database
            $this->savePaymentToDatabase($payload, $webhookId, $webhookContext['signature_valid'] ?? false);
            
            // Log payment details to Sentry
            Sentry::addBreadcrumb(
                new \Sentry\Breadcrumb(
                    \Sentry\Breadcrumb::LEVEL_INFO,
                    \Sentry\Breadcrumb::TYPE_DEFAULT,
                    'paystack.payment',
                    'Payment webhook processed',
                    array_merge($webhookContext, [
                        'payment_info' => $paymentInfo
                    ])
                )
            );

            // Log to Laravel logs as well
            Log::info('Paystack payment webhook processed', [
                'webhook_id' => $webhookId,
                'payment_info' => $paymentInfo,
                'signature_valid' => $webhookContext['signature_valid'] ?? false
            ]);

            // Also log to a dedicated webhook log file
            Log::channel('single')->info('=== PAYSTACK PAYMENT WEBHOOK ===', [
                'timestamp' => $timestamp,
                'webhook_id' => $webhookId,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'headers' => $this->sanitizeHeaders($request->headers->all()),
                'payload' => $this->sanitizePayload($request->all()),
                'payment_info' => $paymentInfo,
                'signature_valid' => $webhookContext['signature_valid'] ?? false,
                'raw_body' => $request->getContent()
            ]);

            // Return success response to Paystack
            return response()->json([
                'status' => 'success',
                'message' => 'Webhook received and processed',
                'webhook_id' => $webhookId,
                'timestamp' => $timestamp
            ], 200);

        } catch (Exception $e) {
            // Capture exception in Sentry
            Sentry::captureException($e, [
                'tags' => [
                    'webhook_type' => 'payment',
                    'webhook_id' => $webhookId,
                ],
                'extra' => [
                    'error_message' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                ]
            ]);

            Log::error('Paystack webhook processing failed', [
                'webhook_id' => $webhookId,
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);

            // Still return 200 to prevent Paystack from retrying
            return response()->json([
                'status' => 'error',
                'message' => 'Webhook processing failed',
                'webhook_id' => $webhookId,
                'timestamp' => $timestamp
            ], 200);
        }
    }

    /**
     * Handle Paystack transfer webhook
     */
    public function handleTransferWebhook(Request $request): JsonResponse
    {
        $webhookId = uniqid('webhook_');
        $timestamp = now()->toISOString();
        
        // Immediate logging when webhook is hit
        Log::info('🚀 PAYSTACK TRANSFER WEBHOOK HIT', [
            'webhook_id' => $webhookId,
            'timestamp' => $timestamp,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'url' => $request->fullUrl()
        ]);
        
        try {
            $headers = $request->headers->all();
            $payload = $request->all();
            $rawBody = $request->getContent();
            
            $webhookContext = [
                'webhook_id' => $webhookId,
                'timestamp' => $timestamp,
                'source' => 'paystack_transfer_webhook',
                'headers' => $this->sanitizeHeaders($headers),
                'payload' => $this->sanitizePayload($payload),
                'raw_body' => $rawBody,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ];

            Sentry::addBreadcrumb(
                new \Sentry\Breadcrumb(
                    \Sentry\Breadcrumb::LEVEL_INFO,
                    \Sentry\Breadcrumb::TYPE_DEFAULT,
                    'paystack.webhook',
                    'Paystack transfer webhook received',
                    $webhookContext
                )
            );

            Sentry::configureScope(function (\Sentry\State\Scope $scope) use ($webhookContext) {
                $scope->setContext('paystack_webhook', $webhookContext);
                $scope->setTag('webhook_type', 'transfer');
                $scope->setTag('webhook_id', $webhookContext['webhook_id']);
            });

            // Validate signature
            if ($this->validateWebhookSignature($request)) {
                $webhookContext['signature_valid'] = true;
            } else {
                $webhookContext['signature_valid'] = false;
                Sentry::captureMessage('Invalid Paystack transfer webhook signature', \Sentry\Severity::warning());
            }

            $transferInfo = $this->extractTransferInfo($payload);
            
            Sentry::addBreadcrumb(
                new \Sentry\Breadcrumb(
                    \Sentry\Breadcrumb::LEVEL_INFO,
                    \Sentry\Breadcrumb::TYPE_DEFAULT,
                    'paystack.transfer',
                    'Transfer webhook processed',
                    array_merge($webhookContext, [
                        'transfer_info' => $transferInfo
                    ])
                )
            );

            Log::info('Paystack transfer webhook processed', [
                'webhook_id' => $webhookId,
                'transfer_info' => $transferInfo
            ]);

            // Also log to a dedicated webhook log file
            Log::channel('single')->info('=== PAYSTACK TRANSFER WEBHOOK ===', [
                'timestamp' => $timestamp,
                'webhook_id' => $webhookId,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'headers' => $this->sanitizeHeaders($request->headers->all()),
                'payload' => $this->sanitizePayload($request->all()),
                'transfer_info' => $transferInfo,
                'signature_valid' => $webhookContext['signature_valid'] ?? false,
                'raw_body' => $request->getContent()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Transfer webhook received and processed',
                'webhook_id' => $webhookId,
                'timestamp' => $timestamp
            ], 200);

        } catch (Exception $e) {
            Sentry::captureException($e, [
                'tags' => [
                    'webhook_type' => 'transfer',
                    'webhook_id' => $webhookId,
                ]
            ]);

            Log::error('Paystack transfer webhook processing failed', [
                'webhook_id' => $webhookId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Transfer webhook processing failed',
                'webhook_id' => $webhookId,
                'timestamp' => $timestamp
            ], 200);
        }
    }

    /**
     * Handle generic Paystack webhook
     */
    public function handleGenericWebhook(Request $request): JsonResponse
    {
        $webhookId = uniqid('webhook_');
        $timestamp = now()->toISOString();
        
        // Immediate logging when webhook is hit
        Log::info('🚀 PAYSTACK GENERIC WEBHOOK HIT', [
            'webhook_id' => $webhookId,
            'timestamp' => $timestamp,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'url' => $request->fullUrl()
        ]);
        
        try {
            $headers = $request->headers->all();
            $payload = $request->all();
            $rawBody = $request->getContent();
            
            $webhookContext = [
                'webhook_id' => $webhookId,
                'timestamp' => $timestamp,
                'source' => 'paystack_generic_webhook',
                'headers' => $this->sanitizeHeaders($headers),
                'payload' => $this->sanitizePayload($payload),
                'raw_body' => $rawBody,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ];

            Sentry::addBreadcrumb(
                new \Sentry\Breadcrumb(
                    \Sentry\Breadcrumb::LEVEL_INFO,
                    \Sentry\Breadcrumb::TYPE_DEFAULT,
                    'paystack.webhook',
                    'Paystack generic webhook received',
                    $webhookContext
                )
            );

            Sentry::configureScope(function (\Sentry\State\Scope $scope) use ($webhookContext) {
                $scope->setContext('paystack_webhook', $webhookContext);
                $scope->setTag('webhook_type', 'generic');
                $scope->setTag('webhook_id', $webhookContext['webhook_id']);
            });

            Log::info('Paystack generic webhook received', [
                'webhook_id' => $webhookId,
                'payload' => $payload
            ]);

            // Also log to a dedicated webhook log file
            Log::channel('single')->info('=== PAYSTACK GENERIC WEBHOOK ===', [
                'timestamp' => $timestamp,
                'webhook_id' => $webhookId,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'headers' => $this->sanitizeHeaders($request->headers->all()),
                'payload' => $this->sanitizePayload($request->all()),
                'raw_body' => $request->getContent()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Generic webhook received and processed',
                'webhook_id' => $webhookId,
                'timestamp' => $timestamp
            ], 200);

        } catch (Exception $e) {
            Sentry::captureException($e, [
                'tags' => [
                    'webhook_type' => 'generic',
                    'webhook_id' => $webhookId,
                ]
            ]);

            Log::error('Paystack generic webhook processing failed', [
                'webhook_id' => $webhookId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Generic webhook processing failed',
                'webhook_id' => $webhookId,
                'timestamp' => $timestamp
            ], 200);
        }
    }

    /**
     * Validate Paystack webhook signature
     * 
     * According to Paystack documentation:
     * - Signature is in the 'x-paystack-signature' header
     * - Signature is computed as HMAC SHA512 of the request body using the secret key
     * - Must use the secret key (not a secret hash) for verification
     */
    private function validateWebhookSignature(Request $request): bool
    {
        $signature = $request->header('x-paystack-signature');
        // Paystack uses the secret key for signature verification, not a separate secret hash
        $secretKey = config('services.paystack.secret_key', env('PAYSTACK_SECRET_KEY'));
        
        // Debug logging
        Log::info('🔍 PAYSTACK SIGNATURE VALIDATION DEBUG', [
            'signature_header' => $signature,
            'secret_key_set' => !empty($secretKey),
            'secret_key_length' => strlen($secretKey ?? ''),
            'all_headers' => $request->headers->all()
        ]);
        
        if (!$signature || !$secretKey) {
            Log::warning('❌ Paystack signature validation failed - missing signature or secret key', [
                'has_signature' => !empty($signature),
                'has_secret_key' => !empty($secretKey)
            ]);
            return false;
        }

        // Get raw request body (must be raw, not parsed JSON)
        $payload = $request->getContent();
        
        // Compute expected signature using HMAC SHA512 with secret key
        // This matches Paystack's signature generation: hash_hmac('sha512', request_body, secret_key)
        $expectedSignature = hash_hmac('sha512', $payload, $secretKey);
        
        // Use hash_equals for timing-safe comparison
        $isValid = hash_equals($expectedSignature, $signature);
        
        Log::info('🔍 PAYSTACK SIGNATURE COMPARISON', [
            'expected_signature' => $expectedSignature,
            'received_signature' => $signature,
            'is_valid' => $isValid,
            'payload_length' => strlen($payload)
        ]);
        
        return $isValid;
    }

    /**
     * Extract key payment information from webhook payload
     */
    private function extractPaymentInfo(array $payload): array
    {
        $data = $payload['data'] ?? [];
        $customer = $data['customer'] ?? [];
        
        return [
            'transaction_id' => $data['id'] ?? null,
            'reference' => $data['reference'] ?? null,
            'amount' => isset($data['amount']) ? $data['amount'] / 100 : null, // Convert from kobo
            'currency' => $data['currency'] ?? null,
            'status' => $data['status'] ?? null,
            'email' => $customer['email'] ?? null,
            'customer_name_payload' => ($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''),
            'payment_type' => $data['channel'] ?? null,
            'created_at' => $data['created_at'] ?? null,
            'event_type' => $payload['event'] ?? null,
        ];
    }

    /**
     * Extract key transfer information from webhook payload
     */
    private function extractTransferInfo(array $payload): array
    {
        $data = $payload['data'] ?? [];
        
        return [
            'transfer_id' => $data['id'] ?? null,
            'reference' => $data['reference'] ?? null,
            'amount' => isset($data['amount']) ? $data['amount'] / 100 : null, // Convert from kobo
            'currency' => $data['currency'] ?? null,
            'status' => $data['status'] ?? null,
            'recipient_name' => $data['recipient']['name'] ?? null,
            'recipient_account' => $data['recipient']['account_number'] ?? null,
            'recipient_bank' => $data['recipient']['bank']['name'] ?? null,
            'created_at' => $data['created_at'] ?? null,
            'event_type' => $payload['event'] ?? null,
        ];
    }

    /**
     * Sanitize headers to remove sensitive data
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sensitiveKeys = ['authorization', 'x-paystack-signature', 'x-api-key', 'x-auth-token'];
        
        return array_map(function ($key, $value) use ($sensitiveKeys) {
            $lowerKey = strtolower($key);
            foreach ($sensitiveKeys as $sensitive) {
                if (str_contains($lowerKey, $sensitive)) {
                    return '[REDACTED]';
                }
            }
            return $value;
        }, array_keys($headers), $headers);
    }

    /**
     * Sanitize payload to remove sensitive information
     */
    private function sanitizePayload(array $payload): array
    {
        $sensitiveKeys = ['password', 'secret', 'key', 'token', 'pin', 'cvv', 'card_number', 'account_number'];
        
        return $this->recursiveSanitize($payload, $sensitiveKeys);
    }

    /**
     * Recursively sanitize nested arrays
     */
    private function recursiveSanitize(array $data, array $sensitiveKeys): array
    {
        foreach ($data as $key => $value) {
            $lowerKey = strtolower($key);
            foreach ($sensitiveKeys as $sensitive) {
                if (str_contains($lowerKey, $sensitive)) {
                    $data[$key] = '[REDACTED]';
                    break;
                }
            }
            
            if (is_array($value)) {
                $data[$key] = $this->recursiveSanitize($value, $sensitiveKeys);
            }
        }
        
        return $data;
    }

    /**
     * Save payment data to database
     */
    private function savePaymentToDatabase(array $payload, string $webhookId, bool $signatureValid): void
    {
        try {
            // Only process payment webhooks (charge.success, charge.failed, etc.)
            $event = $payload['event'] ?? '';
            if (!str_starts_with($event, 'charge.')) {
                Log::info('Skipping non-payment webhook event', [
                    'webhook_id' => $webhookId,
                    'event' => $event
                ]);
                return;
            }

            // Add webhook metadata to payload
            $payload['webhook_id'] = $webhookId;
            $payload['signature_valid'] = $signatureValid;

            Log::info('🔄 [PS-CONTROLLER] Creating PaystackPaymentDTO from webhook', [
                'webhook_id' => $webhookId,
                'event' => $payload['event'] ?? 'unknown'
            ]);

            // Create PaystackPaymentDTO from webhook data
            $paystackPaymentDTO = PaystackPaymentDTO::fromWebhook($payload);

            Log::info('✅ [PS-CONTROLLER] PaystackPaymentDTO created', [
                'webhook_id' => $webhookId,
                'transaction_id' => $paystackPaymentDTO->transactionId,
                'reference' => $paystackPaymentDTO->reference
            ]);

            // Prepare initial data to persist using DTO
            $saveData = $paystackPaymentDTO->toArray();

            Log::info('💾 [PS-CONTROLLER] Saving payment to database using DTO data', [
                'webhook_id' => $webhookId,
                'transaction_id' => $paystackPaymentDTO->transactionId,
                'data_keys' => array_keys($saveData)
            ]);

            // Check if payment already exists (by reference or transaction_id)
            $existingPayment = Payment::where('reference', $paystackPaymentDTO->reference)
                ->orWhere('transaction_id', $paystackPaymentDTO->transactionId)
                ->first();

            if ($existingPayment) {
                // Update existing payment with webhook data from DTO
                $existingPayment->update($saveData);
                Log::info('✅ [PS-CONTROLLER] Payment updated from webhook using DTO', [
                    'webhook_id' => $webhookId,
                    'payment_id' => $existingPayment->id,
                    'reference' => $paystackPaymentDTO->reference,
                    'transaction_id' => $paystackPaymentDTO->transactionId
                ]);
                $payment = $existingPayment;
            } else {
                // Create new payment record using DTO data
                $payment = Payment::create($saveData);
                Log::info('✅ [PS-CONTROLLER] Payment created from webhook using DTO', [
                    'webhook_id' => $webhookId,
                    'payment_id' => $payment->id,
                    'reference' => $paystackPaymentDTO->reference,
                    'transaction_id' => $paystackPaymentDTO->transactionId
                ]);
            }

            // Optionally verify with Paystack using tx_ref (disabled by default via config)
            // if (config('services.paystack.verify_after_webhook', false)) {
            //     try {
            //         $fw = new PaystackApiService();
            //         $verify = null;
            //         if (!empty($paymentDTO->txRef)) {
            //             $verify = $fw->verifyTransactionByRef((string)$paymentDTO->txRef);
            //         }
            //         if (is_array($verify) && ($verify['status'] ?? '') === 'success') {
            //             $data = $verify['data'] ?? [];
            //             $verifiedData = [];
            //             if (isset($data['status'])) $verifiedData['status'] = $data['status'];
            //             if (isset($data['amount'])) $verifiedData['amount'] = $data['amount'];
            //             if (isset($data['currency'])) $verifiedData['currency'] = $data['currency'];
            //             if (isset($data['flw_ref'])) $verifiedData['flw_ref'] = $data['flw_ref'];
            //             if (isset($data['tx_ref'])) $verifiedData['tx_ref'] = $data['tx_ref'];
            //             if (isset($data['payment_type'])) $verifiedData['payment_type'] = $data['payment_type'];
            //             if (isset($data['charged_amount'])) $verifiedData['charged_amount'] = $data['charged_amount'];
            //             if (isset($data['app_fee'])) $verifiedData['app_fee'] = $data['app_fee'];
            //             if (isset($data['merchant_fee'])) $verifiedData['merchant_fee'] = $data['merchant_fee'];
            //             if (isset($data['processor_response'])) $verifiedData['processor_response'] = $data['processor_response'];
            //             if (isset($data['auth_model'])) $verifiedData['auth_model'] = $data['auth_model'];
            //             if (isset($data['narration'])) $verifiedData['narration'] = $data['narration'];
            //             if (isset($data['customer']['name'])) $verifiedData['customer_name'] = $data['customer']['name'];
            //             if (isset($data['customer']['email'])) $verifiedData['customer_email'] = $data['customer']['email'];
            //             if (isset($data['customer']['phone_number'])) $verifiedData['customer_phone'] = $data['customer']['phone_number'];
            //             if (isset($data['card']['first_6digits'])) $verifiedData['card_first_6digits'] = $data['card']['first_6digits'];
            //             if (isset($data['card']['last_4digits'])) $verifiedData['card_last_4digits'] = $data['card']['last_4digits'];
            //             if (isset($data['card']['issuer'])) $verifiedData['card_issuer'] = $data['card']['issuer'];
            //             if (isset($data['card']['country'])) $verifiedData['card_country'] = $data['card']['country'];
            //             if (isset($data['card']['type'])) $verifiedData['card_type'] = $data['card']['type'];
            //             if (isset($data['card']['expiry'])) $verifiedData['card_expiry'] = $data['card']['expiry'];
            //             if (isset($data['ip'])) $verifiedData['ip_address'] = $data['ip'];
            //             if (isset($data['account_id'])) $verifiedData['account_id'] = $data['account_id'];
            //             if (isset($data['created_at'])) $verifiedData['flw_created_at'] = $data['created_at'];
            //             $payment->update($verifiedData);
            //             Log::info('Payment updated with verified data from Paystack', [
            //                 'webhook_id' => $webhookId,
            //                 'payment_id' => $payment->id,
            //                 'tx_ref' => $paymentDTO->txRef,
            //                 'verified_fields' => array_keys($verifiedData),
            //             ]);
            //         } else {
            //             Log::info('No verification data available from Paystack', [
            //                 'webhook_id' => $webhookId,
            //                 'payment_id' => $payment->id,
            //                 'tx_ref' => $paymentDTO->txRef,
            //                 'verify_status' => $verify['status'] ?? 'no_response',
            //             ]);
            //         }
            //     } catch (Exception $e) {
            //         Log::warning('Paystack verification failed', [
            //             'webhook_id' => $webhookId,
            //             'payment_id' => $payment->id,
            //             'tx_ref' => $paymentDTO->txRef,
            //             'error' => $e->getMessage(),
            //         ]);
            //     }
            // } else {
            //     Log::info('Skipping Paystack verification (disabled by config)', [
            //         'webhook_id' => $webhookId,
            //         'payment_id' => $payment->id,
            //         'tx_ref' => $paymentDTO->txRef,
            //     ]);
            // }

            Log::info('Payment saved to database successfully', [
                'webhook_id' => $webhookId,
                'payment_id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'amount' => $payment->formatted_amount,
                'status' => $payment->status,
                'email' => $payment->email
            ]);

            // Send payment data to Nexus using NexusPaymentDTO
            $this->sendPaymentToNexus($paystackPaymentDTO, $webhookId);

            // Log to Sentry for successful payment processing
            Sentry::addBreadcrumb(
                new \Sentry\Breadcrumb(
                    \Sentry\Breadcrumb::LEVEL_INFO,
                    \Sentry\Breadcrumb::TYPE_DEFAULT,
                    'payment.saved',
                    'Payment saved to database',
                    [
                        'payment_id' => $payment->id,
                        'transaction_id' => $payment->transaction_id,
                        'amount' => $payment->amount,
                        'currency' => $payment->currency,
                        'status' => $payment->status
                    ]
                )
            );

        } catch (Exception $e) {
            Log::error('Failed to save payment to database', [
                'webhook_id' => $webhookId,
                'error' => $e->getMessage(),
                'transaction_id' => $payload['data']['id'] ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);

            // Capture database save error in Sentry
            Sentry::withScope(function (\Sentry\State\Scope $scope) use ($e, $webhookId, $payload) {
                $scope->setTag('webhook_type', 'payment');
                $scope->setTag('webhook_id', $webhookId);
                $scope->setTag('operation', 'database_save');
                $scope->setExtra('transaction_id', $payload['data']['id'] ?? 'unknown');
                $scope->setExtra('error_message', $e->getMessage());
                Sentry::captureException($e);
            });
        }
    }

    /**
     * Send payment data to Nexus microservice
     */
    private function sendPaymentToNexus(PaystackPaymentDTO $paystackPaymentDTO, string $webhookId): void
    {
        try {
            Log::info('🔄 [PS-CONTROLLER] Converting PaystackPaymentDTO to NexusPaymentDTO', [
                'webhook_id' => $webhookId,
                'transaction_id' => $paystackPaymentDTO->transactionId
            ]);

            // Convert PaystackPaymentDTO to NexusPaymentDTO
            $nexusPaymentDTO = $paystackPaymentDTO->toNexusDTO();

            Log::info('✅ [PS-CONTROLLER] NexusPaymentDTO created', [
                'webhook_id' => $webhookId,
                'transaction_id' => $nexusPaymentDTO->transactionId,
                'gateway' => $nexusPaymentDTO->gateway
            ]);

            $nexusApiService = new NexusApiService();
            
            // Prepare payment data for Nexus using NexusPaymentDTO
            $paymentData = [
                'payment' => $nexusPaymentDTO->toArray(),
                'webhook_id' => $webhookId,
                'source' => 'paystack_webhook',
                'timestamp' => now()->toISOString()
            ];

            Log::info('📤 [PS-CONTROLLER] Sending payment data to Nexus', [
                'webhook_id' => $webhookId,
                'transaction_id' => $nexusPaymentDTO->transactionId,
                'gateway' => $nexusPaymentDTO->gateway
            ]);

            // Send payment notification to Nexus
            $response = $nexusApiService->notifyPayment($paymentData);

            Log::info('✅ [PS-CONTROLLER] Payment data sent to Nexus successfully', [
                'webhook_id' => $webhookId,
                'transaction_id' => $nexusPaymentDTO->transactionId,
                'nexus_response' => $response
            ]);

            // Log to Sentry for successful Nexus communication
            Sentry::addBreadcrumb(
                new \Sentry\Breadcrumb(
                    \Sentry\Breadcrumb::LEVEL_INFO,
                    \Sentry\Breadcrumb::TYPE_DEFAULT,
                    'nexus.notification.sent',
                    'Payment data sent to Nexus',
                    [
                        'transaction_id' => $nexusPaymentDTO->transactionId,
                        'webhook_id' => $webhookId,
                        'amount' => $nexusPaymentDTO->amount,
                        'currency' => $nexusPaymentDTO->currency
                    ]
                )
            );

        } catch (Exception $e) {
            Log::error('❌ [PS-CONTROLLER] Failed to send payment data to Nexus', [
                'webhook_id' => $webhookId,
                'transaction_id' => $paystackPaymentDTO->transactionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Capture Nexus communication error in Sentry
            Sentry::withScope(function (\Sentry\State\Scope $scope) use ($e, $webhookId, $paystackPaymentDTO) {
                $scope->setTag('webhook_type', 'payment');
                $scope->setTag('webhook_id', $webhookId);
                $scope->setTag('operation', 'nexus_communication');
                $scope->setExtra('transaction_id', $paystackPaymentDTO->transactionId ?? 'unknown');
                $scope->setExtra('error_message', $e->getMessage());
                Sentry::captureException($e);
            });
        }
    }
}
