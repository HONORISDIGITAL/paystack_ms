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
        $columns = [
            'tx_ref',
            'flw_ref',
            'device_fingerprint',
            'auth_model',
            'customer_name',
            'customer_email',
            'customer_phone',
            'customer_id',
            'customer_code',
            'card_first_6digits',
            'card_last_4digits',
            'authorization_code',
            'card_reusable',
            'source_type',
            'source_source',
            'source_entry_point',
            'source_identifier',
            'order_id',
            'fees_breakdown',
            'fees_split',
            'plan',
            'subaccount',
            'split',
            'pos_transaction_data',
            'raw_webhook_data',
            'card_expiry',
            'card_brand',
            'authorization_signature',
            'narration',
            'webhook_event',
        ];

        $columnsToDrop = array_filter($columns, fn ($column) => Schema::hasColumn('payments', $column));

        if (!empty($columnsToDrop)) {
            Schema::table('payments', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'tx_ref')) {
                $table->string('tx_ref')->nullable()->index();
            }
            if (!Schema::hasColumn('payments', 'flw_ref')) {
                $table->string('flw_ref')->nullable();
            }
            if (!Schema::hasColumn('payments', 'device_fingerprint')) {
                $table->string('device_fingerprint')->nullable();
            }
            if (!Schema::hasColumn('payments', 'auth_model')) {
                $table->string('auth_model')->nullable();
            }
            if (!Schema::hasColumn('payments', 'customer_name')) {
                $table->string('customer_name')->nullable();
            }
            if (!Schema::hasColumn('payments', 'customer_email')) {
                $table->string('customer_email')->nullable()->index();
            }
            if (!Schema::hasColumn('payments', 'customer_phone')) {
                $table->string('customer_phone')->nullable();
            }
            if (!Schema::hasColumn('payments', 'customer_id')) {
                $table->string('customer_id')->nullable();
            }
            if (!Schema::hasColumn('payments', 'customer_code')) {
                $table->string('customer_code')->nullable();
            }
            if (!Schema::hasColumn('payments', 'card_first_6digits')) {
                $table->string('card_first_6digits')->nullable();
            }
            if (!Schema::hasColumn('payments', 'card_last_4digits')) {
                $table->string('card_last_4digits')->nullable();
            }
            if (!Schema::hasColumn('payments', 'authorization_code')) {
                $table->string('authorization_code')->nullable();
            }
            if (!Schema::hasColumn('payments', 'card_reusable')) {
                $table->boolean('card_reusable')->nullable();
            }
            if (!Schema::hasColumn('payments', 'source_type')) {
                $table->string('source_type')->nullable();
            }
            if (!Schema::hasColumn('payments', 'source_source')) {
                $table->string('source_source')->nullable();
            }
            if (!Schema::hasColumn('payments', 'source_entry_point')) {
                $table->string('source_entry_point')->nullable();
            }
            if (!Schema::hasColumn('payments', 'source_identifier')) {
                $table->string('source_identifier')->nullable();
            }
            if (!Schema::hasColumn('payments', 'order_id')) {
                $table->string('order_id')->nullable();
            }
            if (!Schema::hasColumn('payments', 'fees_breakdown')) {
                $table->json('fees_breakdown')->nullable();
            }
            if (!Schema::hasColumn('payments', 'fees_split')) {
                $table->json('fees_split')->nullable();
            }
            if (!Schema::hasColumn('payments', 'plan')) {
                $table->json('plan')->nullable();
            }
            if (!Schema::hasColumn('payments', 'subaccount')) {
                $table->json('subaccount')->nullable();
            }
            if (!Schema::hasColumn('payments', 'split')) {
                $table->json('split')->nullable();
            }
            if (!Schema::hasColumn('payments', 'pos_transaction_data')) {
                $table->json('pos_transaction_data')->nullable();
            }
            if (!Schema::hasColumn('payments', 'raw_webhook_data')) {
                $table->json('raw_webhook_data')->nullable();
            }
            if (!Schema::hasColumn('payments', 'card_expiry')) {
                $table->string('card_expiry')->nullable();
            }
            if (!Schema::hasColumn('payments', 'card_brand')) {
                $table->string('card_brand')->nullable();
            }
            if (!Schema::hasColumn('payments', 'authorization_signature')) {
                $table->string('authorization_signature')->nullable();
            }
            if (!Schema::hasColumn('payments', 'narration')) {
                $table->text('narration')->nullable();
            }
            if (!Schema::hasColumn('payments', 'webhook_event')) {
                $table->string('webhook_event')->nullable();
            }
        });
    }
};
