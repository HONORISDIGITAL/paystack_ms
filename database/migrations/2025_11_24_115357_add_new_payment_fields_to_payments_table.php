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
        Schema::table('payments', function (Blueprint $table) {
            // Add new fields as specified by user - all fields are nullable
            // to handle cases where data may not be available from webhooks or Nexus
            $table->string('gateway')->nullable()->after('id');
            $table->string('event')->nullable()->after('gateway');
            $table->timestamp('timestamp')->nullable()->after('event');
            $table->string('channel')->nullable()->after('payment_type');
            $table->string('email')->nullable()->after('customer_email');
            $table->string('referrer')->nullable()->after('email');
            $table->string('checkout_url')->nullable()->after('referrer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'gateway',
                'event',
                'timestamp',
                'channel',
                'email',
                'referrer',
                'checkout_url',
            ]);
        });
    }
};
