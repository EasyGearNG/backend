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
        Schema::create('wallet_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->string('recipient_type'); // logistics_company, vendor
            $table->unsignedBigInteger('recipient_id');
            $table->decimal('amount', 12, 2);
            $table->string('bank_name');
            $table->string('account_number');
            $table->string('account_name');
            $table->string('reference')->unique();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Store transfer response, error details, etc.
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            $table->index(['recipient_type', 'recipient_id']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_withdrawals');
    }
};
