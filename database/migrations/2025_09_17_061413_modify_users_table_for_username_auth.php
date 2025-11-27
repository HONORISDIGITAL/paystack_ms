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
        Schema::table('users', function (Blueprint $table) {
            // Add username field
            $table->string('username')->unique()->after('name');
            
            // Remove unique constraint from email
            $table->dropUnique(['email']);
            
            // Make email nullable since we're switching to username
            $table->string('email')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Remove username field
            $table->dropColumn('username');
            
            // Make email not nullable again
            $table->string('email')->nullable(false)->change();
            
            // Add back unique constraint to email
            $table->unique('email');
        });
    }
};
