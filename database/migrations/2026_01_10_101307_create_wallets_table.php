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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->string('owner_type'); // vendor, easygear, resilience, logistics
            $table->unsignedBigInteger('owner_id')->nullable(); // vendor_id if applicable
            $table->decimal('balance', 12, 2)->default(0.00);
            $table->decimal('pending_balance', 12, 2)->default(0.00); // Funds not yet available
            $table->timestamps();
            
            $table->unique(['owner_type', 'owner_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
