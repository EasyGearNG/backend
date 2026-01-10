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
            $table->foreignId('logistics_company_id')->nullable()->after('vendor_id')->constrained()->onDelete('set null');
            $table->decimal('logistics_fee', 10, 2)->nullable()->after('logistics_company_id'); // Fee for this specific delivery
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['logistics_company_id']);
            $table->dropColumn(['logistics_company_id', 'logistics_fee']);
        });
    }
};
