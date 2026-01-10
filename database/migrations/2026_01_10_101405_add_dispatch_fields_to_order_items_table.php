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
        Schema::table('order_items', function (Blueprint $table) {
            $table->enum('fulfillment_status', ['pending', 'dispatched', 'delivered', 'confirmed'])->default('pending');
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->text('dispatch_notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['fulfillment_status', 'dispatched_at', 'delivered_at', 'confirmed_at', 'dispatch_notes']);
        });
    }
};
