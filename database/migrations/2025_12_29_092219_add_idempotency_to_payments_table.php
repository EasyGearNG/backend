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
            // Add idempotency key to prevent duplicate payment processing
            $table->string('idempotency_key')->nullable()->unique()->after('transaction_id');
            
            // Track when payment was actually processed
            $table->timestamp('processed_at')->nullable()->after('payment_date');
            
            // Add unique constraint on transaction_id to prevent duplicates
            $table->unique('transaction_id');
            
            // Add index for faster lookups
            $table->index(['status', 'processed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['status', 'processed_at']);
            $table->dropUnique(['transaction_id']);
            $table->dropUnique(['idempotency_key']);
            
            // Drop columns
            $table->dropColumn(['idempotency_key', 'processed_at']);
        });
    }
};
