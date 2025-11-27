<?php

namespace App\DTOs;

use Illuminate\Support\Facades\Log;

class PaystackPaymentDTO
{
    public function __construct(
        public readonly ?string $gateway,
        public readonly ?string $event,
        public readonly ?\DateTime $timestamp,
        public readonly ?string $webhookId,
        public readonly bool $signatureValid,
        public readonly string $transactionId,
        public readonly ?string $reference,
        public readonly string $status, // Raw status from Paystack
        public readonly ?string $processedStatus, // Processed status (e.g., successful -> success) - not in DB
        public readonly ?float $amount,
        public readonly ?string $currency,
        public readonly ?string $paymentType,
        public readonly ?string $channel,
        public readonly ?\DateTime $createdAt, // PSP created_at
        public readonly ?\DateTime $paidAt, // PSP paid_at
        public readonly ?string $email,
        public readonly ?string $domain,
        public readonly ?string $gatewayResponse,
        public readonly ?float $merchantFee,
        public readonly ?float $requestedAmount,
        public readonly ?float $chargedAmount,
        public readonly ?string $cardIssuer,
        public readonly ?string $cardType,
        public readonly ?string $cardCountry,
    ) {}

    /**
     * Create PaystackPaymentDTO from Paystack webhook data
     */
    public static function fromWebhook(array $webhookData): self
    {
        Log::info('🔄 [PS-DTO] Creating PaystackPaymentDTO from webhook data', [
            'webhook_keys' => array_keys($webhookData),
            'has_data' => isset($webhookData['data']),
            'event' => $webhookData['event'] ?? null
        ]);

        $data = $webhookData['data'] ?? [];
        $customer = $data['customer'] ?? [];
        $authorization = $data['authorization'] ?? [];
        
        // Map payment_type/channel from webhook to channel field
        $channel = $data['channel'] ?? $authorization['channel'] ?? null;
        
        // Get webhook_id (added by controller)
        $webhookId = $webhookData['webhook_id'] ?? null;
        
        Log::info('🔄 [PS-DTO] Extracted webhook data', [
            'transaction_id' => $data['id'] ?? 'missing',
            'reference' => $data['reference'] ?? 'missing',
            'channel' => $channel,
            'event' => $webhookData['event'] ?? 'missing',
            'webhook_id' => $webhookId
        ]);

        // Parse timestamps
        $timestamp = null;
        if (isset($webhookData['timestamp'])) {
            try {
                $timestamp = new \DateTime($webhookData['timestamp']);
            } catch (\Exception $e) {
                // Try alternative formats
                $timestamp = \DateTime::createFromFormat('Y-m-d H:i:s', $webhookData['timestamp']) ?: null;
            }
        }

        $createdAt = null;
        if (isset($data['created_at'])) {
            try {
                $createdAt = new \DateTime($data['created_at']);
            } catch (\Exception $e) {
                $createdAt = \DateTime::createFromFormat('Y-m-d H:i:s', $data['created_at']) ?: null;
            }
        }

        $paidAt = null;
        if (isset($data['paid_at'])) {
            try {
                $paidAt = new \DateTime($data['paid_at']);
            } catch (\Exception $e) {
                $paidAt = \DateTime::createFromFormat('Y-m-d H:i:s', $data['paid_at']) ?: null;
            }
        }

        // Get gateway name (should be 'paystack')
        $gateway = 'paystack';

        // Get event type
        $event = $webhookData['event'] ?? null;

        // Get raw status
        $rawStatus = $data['status'] ?? '';

        // Process status (for now, just use raw status - logic can be added later)
        $processedStatus = self::processStatus($rawStatus);

        // Extract email (from customer or data)
        $email = $customer['email'] ?? $data['email'] ?? null;

        $requestedAmount = isset($data['requested_amount']) ? (float) $data['requested_amount'] / 100 : null;
        $chargedAmount = isset($data['charged_amount'])
            ? (float) $data['charged_amount'] / 100
            : (isset($data['amount']) ? (float) $data['amount'] / 100 : null);
        $amountValue = $chargedAmount ?? $requestedAmount;

        $merchantFee = isset($data['fees']) ? (float) $data['fees'] / 100 : null;
        $cardCountry = $authorization['country_code'] ?? $data['card_country'] ?? null;
        $cardIssuer = $authorization['bank'] ?? $data['card_issuer'] ?? null;
        $cardType = $authorization['card_type'] ?? $data['card_type'] ?? null;

        $dto = new self(
            gateway: $gateway,
            event: $event,
            timestamp: $timestamp,
            webhookId: $webhookId,
            signatureValid: $webhookData['signature_valid'] ?? false,
            transactionId: (string) ($data['id'] ?? ''),
            reference: $data['reference'] ?? null,
            status: $rawStatus,
            processedStatus: $processedStatus,
            amount: $amountValue,
            currency: $data['currency'] ?? null,
            paymentType: $channel, // Use channel as payment_type
            channel: $channel,
            createdAt: $createdAt,
            paidAt: $paidAt,
            email: $email,
            domain: $data['domain'] ?? null,
            gatewayResponse: $data['gateway_response'] ?? null,
            merchantFee: $merchantFee,
            requestedAmount: $requestedAmount,
            chargedAmount: $chargedAmount,
            cardIssuer: $cardIssuer,
            cardType: $cardType,
            cardCountry: $cardCountry,
        );
        
        Log::info('✅ [PS-DTO] PaystackPaymentDTO created successfully', [
            'transaction_id' => $dto->transactionId,
            'gateway' => $dto->gateway,
            'event' => $dto->event,
            'status' => $dto->status,
            'amount' => $dto->amount,
            'currency' => $dto->currency,
            'channel' => $dto->channel,
            'reference' => $dto->reference,
            'webhook_id' => $dto->webhookId
        ]);
        
        return $dto;
    }

    /**
     * Create PaystackPaymentDTO from array data (e.g., from Nexus)
     */
    public static function fromArray(array $data): self
    {
        Log::info('🔄 [PS-DTO] Creating PaystackPaymentDTO from array data', [
            'data_keys' => array_keys($data),
            'transaction_id' => $data['transaction_id'] ?? 'missing'
        ]);
        
        Log::info('🔍 [PS-DTO] Reference mapping input', [
            'raw_reference' => $data['reference'] ?? null,
            'transaction_reference' => $data['transaction_reference'] ?? null,
            'tx_ref' => $data['tx_ref'] ?? null,
        ]);

        // Parse timestamps
        $timestamp = null;
        if (isset($data['timestamp'])) {
            try {
                $timestamp = $data['timestamp'] instanceof \DateTime 
                    ? $data['timestamp'] 
                    : new \DateTime($data['timestamp']);
            } catch (\Exception $e) {
                $timestamp = null;
            }
        }

        $createdAt = null;
        if (isset($data['created_at']) || isset($data['psp_created_at'])) {
            $dateValue = $data['created_at'] ?? $data['psp_created_at'];
            try {
                $createdAt = $dateValue instanceof \DateTime 
                    ? $dateValue 
                    : new \DateTime($dateValue);
            } catch (\Exception $e) {
                $createdAt = null;
            }
        }

        $paidAt = null;
        if (isset($data['paid_at']) || isset($data['psp_paid_at'])) {
            $dateValue = $data['paid_at'] ?? $data['psp_paid_at'];
            try {
                $paidAt = $dateValue instanceof \DateTime 
                    ? $dateValue 
                    : new \DateTime($dateValue);
            } catch (\Exception $e) {
                $paidAt = null;
            }
        }

        // Get gateway (default to 'paystack' if not provided)
        $gateway = $data['gateway'] ?? 'paystack';

        // Get event type
        $event = $data['event'] ?? null;

        // Get raw status
        $rawStatus = $data['status'] ?? '';

        // Process status (for now, just use raw status - logic can be added later)
        $processedStatus = self::processStatus($rawStatus);

        $reference = $data['reference'] 
            ?? $data['transaction_reference'] 
            ?? $data['tx_ref'] 
            ?? null;

        Log::info('🧭 [PS-DTO] Reference selected for DTO', [
            'reference' => $reference,
            'transaction_id' => $data['transaction_id'] ?? null,
        ]);

        $requestedAmount = isset($data['requested_amount'])
            ? (float) $data['requested_amount']
            : (isset($data['amount_requested']) ? (float) $data['amount_requested'] : null);

        $chargedAmount = isset($data['charged_amount'])
            ? (float) $data['charged_amount']
            : (isset($data['amount_charged']) ? (float) $data['amount_charged'] : (isset($data['amount']) ? (float) $data['amount'] : null));
        $amountValue = $chargedAmount ?? $requestedAmount;

        $merchantFee = isset($data['merchant_fee']) ? (float) $data['merchant_fee'] : null;
        if ($merchantFee === null && isset($data['fees'])) {
            $merchantFee = (float) $data['fees'];
        }
        $cardCountry = $data['card_country'] ?? null;
        $cardIssuer = $data['card_issuer'] ?? null;
        $cardType = $data['card_type'] ?? null;

        $dto = new self(
            gateway: $gateway,
            event: $event,
            timestamp: $timestamp,
            webhookId: $data['webhook_id'] ?? null,
            signatureValid: $data['signature_valid'] ?? false,
            transactionId: (string) ($data['transaction_id'] ?? ''),
            reference: $reference,
            status: $rawStatus,
            processedStatus: $processedStatus,
            amount: $amountValue,
            currency: $data['currency'] ?? 'NGN', // Default to NGN if not provided
            paymentType: $data['payment_type'] ?? $data['channel'] ?? null,
            channel: $data['payment_type'] ?? $data['channel'] ?? null, // Map payment_type to channel
            createdAt: $createdAt,
            paidAt: $paidAt,
            email: $data['email'] ?? $data['customer_email'] ?? null,
            domain: $data['domain'] ?? null,
            gatewayResponse: $data['gateway_response'] ?? null,
            merchantFee: $merchantFee,
            requestedAmount: $requestedAmount,
            chargedAmount: $chargedAmount,
            cardIssuer: $cardIssuer,
            cardType: $cardType,
            cardCountry: $cardCountry,
        );

        Log::info('✅ [PS-DTO] PaystackPaymentDTO created from array', [
            'transaction_id' => $dto->transactionId,
            'gateway' => $dto->gateway,
            'status' => $dto->status
        ]);

        return $dto;
    }

    /**
     * Process status (e.g., successful -> success)
     * This logic can be expanded later
     */
    private static function processStatus(string $rawStatus): string
    {
        // For now, just return the raw status
        // This can be expanded with mapping logic later
        return $rawStatus;
    }

    /**
     * Convert DTO to array for database insertion
     */
    public function toArray(): array
    {
        Log::info('💾 [PS-DTO] Converting PaystackPaymentDTO to array for database', [
            'transaction_id' => $this->transactionId,
            'reference' => $this->reference,
        ]);
        
        $array = [
            'gateway' => $this->gateway,
            'event' => $this->event,
            'timestamp' => $this->timestamp,
            'webhook_id' => $this->webhookId,
            'signature_valid' => $this->signatureValid,
            'transaction_id' => $this->transactionId,
            'reference' => $this->reference,
            'status' => $this->status, // Raw status goes to DB
            'amount' => $this->amount,
            'requested_amount' => $this->requestedAmount,
            'charged_amount' => $this->chargedAmount,
            'currency' => $this->currency ?? 'NGN', // Default to NGN if null (matches database default)
            'payment_type' => $this->paymentType,
            'channel' => $this->channel,
            'flw_created_at' => $this->createdAt, // Use flw_created_at for PSP created_at (similar to Flutterwave)
            'paid_at' => $this->paidAt,
            'email' => $this->email,
            'domain' => $this->domain,
            'gateway_response' => $this->gatewayResponse,
            'merchant_fee' => $this->merchantFee,
            'card_issuer' => $this->cardIssuer,
            'card_type' => $this->cardType,
            'card_country' => $this->cardCountry,
        ];
        
        Log::info('✅ [PS-DTO] PaystackPaymentDTO converted to array', [
            'transaction_id' => $this->transactionId,
            'array_keys' => array_keys($array),
            'array_count' => count($array)
        ]);
        
        return $array;
    }

    /**
     * Convert to NexusPaymentDTO for communication with Nexus
     */
    public function toNexusDTO(): NexusPaymentDTO
    {
        Log::info('🔄 [PS-DTO] Converting PaystackPaymentDTO to NexusPaymentDTO', [
            'transaction_id' => $this->transactionId
        ]);
        
        $nexusDTO = new NexusPaymentDTO(
            gateway: $this->gateway,
            gatewayEvent: $this->event,
            webhookTimestamp: $this->timestamp,
            signatureValid: $this->signatureValid,
            transactionId: $this->transactionId,
            transactionReference: $this->reference,
            statusRaw: $this->status,
            status: $this->processedStatus ?? $this->status,
            amount: $this->requestedAmount,
            currency: $this->currency,
            paymentType: $this->paymentType,
            channel: $this->channel,
            createdAtPsp: $this->createdAt,
            paidAtPsp: $this->paidAt,
            courseId: null, // Paystack doesn't have course_id
            cardType: null, // Paystack doesn't provide card_type in the same way
        );
        
        Log::info('✅ [PS-DTO] NexusPaymentDTO created from PaystackPaymentDTO', [
            'transaction_id' => $nexusDTO->transactionId,
            'gateway' => $nexusDTO->gateway,
            'gateway_event' => $nexusDTO->gatewayEvent
        ]);
        
        return $nexusDTO;
    }
}

