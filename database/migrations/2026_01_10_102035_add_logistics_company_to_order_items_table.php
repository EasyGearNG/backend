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
            // Check if columns don't already exist before adding
            if (!Schema::hasColumn('order_items', 'logistics_company_id')) {
                $table->foreignId('logistics_company_id')->nullable()->constrained()->onDelete('set null');
            }
            if (!Schema::hasColumn('order_items', 'logistics_fee')) {
                $table->decimal('logistics_fee', 10, 2)->nullable();
            }
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
