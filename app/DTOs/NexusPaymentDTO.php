<?php

namespace App\DTOs;

use Illuminate\Support\Facades\Log;

class NexusPaymentDTO
{
    public function __construct(
        public readonly ?string $gateway,
        public readonly ?string $gatewayEvent,
        public readonly ?\DateTime $webhookTimestamp,
        public readonly bool $signatureValid,
        public readonly string $transactionId,
        public readonly ?string $transactionReference,
        public readonly ?string $statusRaw,
        public readonly ?string $status,
        public readonly ?float $amount,
        public readonly ?string $currency,
        public readonly ?string $paymentType,
        public readonly ?string $channel,
        public readonly ?\DateTime $createdAtPsp,
        public readonly ?\DateTime $paidAtPsp,
        public readonly ?string $courseId,
        public readonly ?string $cardType,
    ) {}

    /**
     * Create NexusPaymentDTO from array data
     */
    public static function fromArray(array $data): self
    {
        Log::info('🔄 [NEXUS-DTO] Creating NexusPaymentDTO from array', [
            'data_keys' => array_keys($data),
            'transaction_id' => $data['transaction_id'] ?? 'missing'
        ]);
        
        $webhookTimestamp = null;
        if (isset($data['webhook_timestamp'])) {
            try {
                // Handle different formats: DateTime object, array with 'date' key, or ISO string
                if ($data['webhook_timestamp'] instanceof \DateTime) {
                    $webhookTimestamp = $data['webhook_timestamp'];
                } elseif (is_array($data['webhook_timestamp']) && isset($data['webhook_timestamp']['date'])) {
                    $webhookTimestamp = new \DateTime($data['webhook_timestamp']['date']);
                } else {
                    $webhookTimestamp = new \DateTime($data['webhook_timestamp']);
                }
            } catch (\Exception $e) {
                $webhookTimestamp = null;
            }
        }

        $createdAtPsp = null;
        if (isset($data['created_at_psp'])) {
            try {
                // Handle different formats: DateTime object, array with 'date' key, or ISO string
                if ($data['created_at_psp'] instanceof \DateTime) {
                    $createdAtPsp = $data['created_at_psp'];
                } elseif (is_array($data['created_at_psp']) && isset($data['created_at_psp']['date'])) {
                    $createdAtPsp = new \DateTime($data['created_at_psp']['date']);
                } else {
                    $createdAtPsp = new \DateTime($data['created_at_psp']);
                }
            } catch (\Exception $e) {
                $createdAtPsp = null;
            }
        }

        $paidAtPsp = null;
        if (isset($data['paid_at_psp'])) {
            try {
                // Handle different formats: DateTime object, array with 'date' key, or ISO string
                if ($data['paid_at_psp'] instanceof \DateTime) {
                    $paidAtPsp = $data['paid_at_psp'];
                } elseif (is_array($data['paid_at_psp']) && isset($data['paid_at_psp']['date'])) {
                    $paidAtPsp = new \DateTime($data['paid_at_psp']['date']);
                } else {
                    $paidAtPsp = new \DateTime($data['paid_at_psp']);
                }
            } catch (\Exception $e) {
                $paidAtPsp = null;
            }
        }

        $dto = new self(
            gateway: $data['gateway'] ?? null,
            gatewayEvent: $data['gateway_event'] ?? null,
            webhookTimestamp: $webhookTimestamp,
            signatureValid: $data['signature_valid'] ?? false,
            transactionId: $data['transaction_id'] ?? '',
            transactionReference: $data['transaction_reference'] ?? null,
            statusRaw: $data['status_raw'] ?? null,
            status: $data['status'] ?? null,
            amount: isset($data['amount']) ? (float) $data['amount'] : null,
            currency: $data['currency'] ?? null,
            paymentType: $data['payment_type'] ?? null,
            channel: $data['channel'] ?? null,
            createdAtPsp: $createdAtPsp,
            paidAtPsp: $paidAtPsp,
            courseId: $data['course_id'] ?? null,
            cardType: $data['card_type'] ?? null,
        );
        
        Log::info('✅ [NEXUS-DTO] NexusPaymentDTO created from array', [
            'transaction_id' => $dto->transactionId,
            'gateway' => $dto->gateway,
            'gateway_event' => $dto->gatewayEvent
        ]);
        
        return $dto;
    }

    /**
     * Convert to array for database insertion or API communication
     */
    public function toArray(): array
    {
        Log::info('💾 [NEXUS-DTO] Converting NexusPaymentDTO to array', [
            'transaction_id' => $this->transactionId
        ]);
        
        // Convert DateTime objects to ISO 8601 strings for JSON serialization
        $webhookTimestamp = $this->webhookTimestamp instanceof \DateTime 
            ? $this->webhookTimestamp->format('c') 
            : $this->webhookTimestamp;
        
        $createdAtPsp = $this->createdAtPsp instanceof \DateTime 
            ? $this->createdAtPsp->format('c') 
            : $this->createdAtPsp;
        
        $paidAtPsp = $this->paidAtPsp instanceof \DateTime 
            ? $this->paidAtPsp->format('c') 
            : $this->paidAtPsp;
        
        $array = [
            'gateway' => $this->gateway,
            'gateway_event' => $this->gatewayEvent,
            'webhook_timestamp' => $webhookTimestamp,
            'signature_valid' => $this->signatureValid,
            'transaction_id' => $this->transactionId,
            'transaction_reference' => $this->transactionReference,
            'status_raw' => $this->statusRaw,
            'status' => $this->status,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'payment_type' => $this->paymentType,
            'channel' => $this->channel,
            'created_at_psp' => $createdAtPsp,
            'paid_at_psp' => $paidAtPsp,
            'course_id' => $this->courseId,
            'card_type' => $this->cardType,
        ];
        
        Log::info('✅ [NEXUS-DTO] NexusPaymentDTO converted to array', [
            'transaction_id' => $this->transactionId,
            'array_keys' => array_keys($array),
            'array_count' => count($array)
        ]);
        
        return $array;
    }

    /**
     * Convert to JSON for API communication
     */
    public function toJson(): string
    {
        $array = $this->toArray();
        
        // Convert DateTime objects to ISO 8601 strings
        if ($array['webhook_timestamp'] instanceof \DateTime) {
            $array['webhook_timestamp'] = $array['webhook_timestamp']->format('c');
        }
        if ($array['created_at_psp'] instanceof \DateTime) {
            $array['created_at_psp'] = $array['created_at_psp']->format('c');
        }
        if ($array['paid_at_psp'] instanceof \DateTime) {
            $array['paid_at_psp'] = $array['paid_at_psp']->format('c');
        }

        return json_encode($array);
    }
}



