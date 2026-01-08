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
            if (!Schema::hasColumn('order_items', 'tracking_id')) {
                $table->string('tracking_id')->unique()->after('id');
            }
            if (!Schema::hasColumn('order_items', 'shipment_id')) {
                $table->unsignedBigInteger('shipment_id')->nullable()->after('tracking_id');
                if (Schema::hasTable('shipments')) {
                    $table->foreign('shipment_id')->references('id')->on('shipments')->onDelete('set null');
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['shipment_id']);
            $table->dropColumn(['tracking_id', 'shipment_id']);
        });
    }
};
