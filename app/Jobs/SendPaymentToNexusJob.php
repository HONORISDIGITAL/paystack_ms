<?php

namespace App\Jobs;

use App\DTOs\NexusPaymentDTO;
use App\Services\NexusApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;
use Sentry\Laravel\Facade as Sentry;

class SendPaymentToNexusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $maxExceptions = 2;
    public int $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public NexusPaymentDTO $nexusPaymentDTO,
        public string $webhookId,
        public array $metadata = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('🔄 [PS-JOB] Processing SendPaymentToNexusJob', [
                'webhook_id' => $this->webhookId,
                'transaction_id' => $this->nexusPaymentDTO->transactionId,
                'attempt' => $this->attempts()
            ]);

            $nexusApiService = new NexusApiService();
            
            // Prepare payment data for Nexus using NexusPaymentDTO
            $paymentData = [
                'payment' => $this->nexusPaymentDTO->toArray(),
                'webhook_id' => $this->webhookId,
                'source' => 'paystack_webhook',
                'timestamp' => now()->toISOString(),
                'metadata' => $this->metadata
            ];

            // Send payment notification to Nexus
            $response = $nexusApiService->notifyPayment($paymentData);

            Log::info('✅ [PS-JOB] Payment data sent to Nexus successfully via queue', [
                'webhook_id' => $this->webhookId,
                'transaction_id' => $this->nexusPaymentDTO->transactionId,
                'nexus_response' => $response,
                'attempt' => $this->attempts()
            ]);

            // Log to Sentry for successful Nexus communication
            Sentry::addBreadcrumb(
                new \Sentry\Breadcrumb(
                    \Sentry\Breadcrumb::LEVEL_INFO,
                    \Sentry\Breadcrumb::TYPE_DEFAULT,
                    'nexus.notification.sent.queued',
                    'Payment data sent to Nexus via queue',
                    [
                        'transaction_id' => $this->nexusPaymentDTO->transactionId,
                        'webhook_id' => $this->webhookId,
                        'amount' => $this->nexusPaymentDTO->amount,
                        'currency' => $this->nexusPaymentDTO->currency,
                        'attempt' => $this->attempts()
                    ]
                )
            );

        } catch (Exception $e) {
            Log::error('❌ [PS-JOB] SendPaymentToNexusJob failed', [
                'webhook_id' => $this->webhookId,
                'transaction_id' => $this->nexusPaymentDTO->transactionId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'trace' => $e->getTraceAsString()
            ]);

            // Capture Nexus communication error in Sentry
            Sentry::withScope(function (\Sentry\State\Scope $scope) use ($e) {
                $scope->setTag('webhook_type', 'payment');
                $scope->setTag('webhook_id', $this->webhookId);
                $scope->setTag('operation', 'nexus_communication_queued');
                $scope->setTag('job_attempt', (string) $this->attempts());
                $scope->setExtra('transaction_id', $this->nexusPaymentDTO->transactionId);
                $scope->setExtra('error_message', $e->getMessage());
                Sentry::captureException($e);
            });

            // Re-throw the exception to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('❌ [PS-JOB] SendPaymentToNexusJob failed permanently', [
            'webhook_id' => $this->webhookId,
            'transaction_id' => $this->nexusPaymentDTO->transactionId,
            'error' => $exception->getMessage(),
            'total_attempts' => $this->attempts()
        ]);

        // Capture final failure in Sentry
        Sentry::withScope(function (\Sentry\State\Scope $scope) use ($exception) {
            $scope->setTag('webhook_type', 'payment');
            $scope->setTag('webhook_id', $this->webhookId);
            $scope->setTag('operation', 'nexus_communication_queued_failed');
            $scope->setTag('final_failure', 'true');
            $scope->setExtra('transaction_id', $this->nexusPaymentDTO->transactionId);
            $scope->setExtra('total_attempts', $this->attempts());
            Sentry::captureException($exception);
        });
    }
}




