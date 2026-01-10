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
        Schema::table('wallet_withdrawals', function (Blueprint $table) {
            $table->string('paystack_transfer_code')->nullable()->after('reference');
            $table->bigInteger('paystack_transfer_id')->nullable()->after('paystack_transfer_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_withdrawals', function (Blueprint $table) {
            $table->dropColumn(['paystack_transfer_code', 'paystack_transfer_id']);
        });
    }
};
