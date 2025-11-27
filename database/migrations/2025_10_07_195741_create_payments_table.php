<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            
            // Core transaction identifiers
            $table->string('transaction_id')->index();
            $table->string('tx_ref')->nullable()->index();
            $table->string('reference')->nullable()->index();
            $table->string('flw_ref')->nullable();
            $table->string('device_fingerprint')->nullable();
            
            // Amounts and fees
            $table->decimal('amount', 15, 2)->nullable();
            $table->decimal('charged_amount', 15, 2)->nullable();
            $table->decimal('requested_amount', 15, 2)->nullable(); // Paystack specific
            $table->decimal('app_fee', 15, 2)->nullable();
            $table->decimal('merchant_fee', 15, 2)->nullable();
            $table->string('currency', 3)->default('NGN');
            
            // Payment status and type
            $table->string('status');
            $table->string('payment_type')->nullable();
            $table->string('payment_page')->nullable();
            $table->decimal('price', 15, 2)->nullable();
            
            // Payment processing details
            $table->text('processor_response')->nullable();
            $table->string('gateway_response')->nullable(); // Paystack specific
            $table->string('auth_model')->nullable();
            $table->text('narration')->nullable();
            $table->string('domain')->nullable(); // Paystack specific (test/live)
            
            // Customer information
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable()->index();
            $table->string('customer_phone')->nullable();
            $table->string('customer_id')->nullable();
            $table->string('customer_code')->nullable(); // Paystack specific
            
            // Card/Authorization details
            $table->string('card_first_6digits')->nullable();
            $table->string('card_last_4digits')->nullable();
            $table->string('card_issuer')->nullable();
            $table->string('card_country')->nullable();
            $table->string('card_type')->nullable();
            $table->string('card_expiry')->nullable();
            $table->string('card_brand')->nullable(); // Paystack specific (visa, mastercard, etc.)
            $table->string('authorization_code')->nullable(); // Paystack specific
            $table->string('authorization_signature')->nullable(); // Paystack specific
            $table->boolean('card_reusable')->nullable(); // Paystack specific
            
            // Network and location
            $table->string('ip_address')->nullable();
            $table->string('account_id')->nullable();
            
            // Source information (Paystack specific)
            $table->string('source_type')->nullable(); // web, api, etc.
            $table->string('source_source')->nullable(); // checkout, etc.
            $table->string('source_entry_point')->nullable(); // request_inline, etc.
            $table->string('source_identifier')->nullable();
            
            // Additional Paystack fields
            $table->string('order_id')->nullable();
            $table->json('fees_breakdown')->nullable();
            $table->json('fees_split')->nullable();
            $table->json('plan')->nullable();
            $table->json('subaccount')->nullable();
            $table->json('split')->nullable();
            $table->json('pos_transaction_data')->nullable();
            $table->json('log')->nullable();
            
            // Webhook metadata
            $table->json('meta_data')->nullable();
            $table->json('raw_webhook_data')->nullable();
            $table->string('webhook_id')->nullable();
            $table->string('webhook_event')->nullable(); // charge.success, charge.failed, etc.
            $table->boolean('signature_valid')->default(false);
            
            // Timestamps
            $table->timestamp('paid_at')->nullable(); // Paystack specific
            $table->timestamp('flw_created_at')->nullable();
            $table->timestamps();
            
            // Indexes for common queries
            $table->index('status');
            $table->index('created_at');
            $table->index(['customer_email', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
