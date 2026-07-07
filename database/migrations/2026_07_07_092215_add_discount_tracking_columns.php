<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Track how many times each code has been used
        Schema::table('discounts', function (Blueprint $table) {
            $table->integer('times_used')->default(0)->after('max_uses');
            $table->boolean('is_active')->default(true)->after('times_used');
        });

        // Record which discount code was applied to an order
        Schema::table('orders', function (Blueprint $table) {
            $table->string('discount_code')->nullable()->after('discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->dropColumn(['times_used', 'is_active']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('discount_code');
        });
    }
};
