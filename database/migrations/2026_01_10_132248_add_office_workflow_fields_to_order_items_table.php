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
            // Office workflow fields
            if (!Schema::hasColumn('order_items', 'office_location')) {
                $table->string('office_location')->nullable()->comment('EasyGear office that received the item (Jos, Lagos, etc.)');
            }
            if (!Schema::hasColumn('order_items', 'inspection_notes')) {
                $table->text('inspection_notes')->nullable()->comment('Notes from office inspection');
            }
            if (!Schema::hasColumn('order_items', 'tag_number')) {
                $table->string('tag_number')->nullable()->comment('Tag/tracking number assigned by office');
            }
            if (!Schema::hasColumn('order_items', 'tracking_number')) {
                $table->string('tracking_number')->nullable()->comment('Logistics company tracking number');
            }
            if (!Schema::hasColumn('order_items', 'received_at_office_at')) {
                $table->timestamp('received_at_office_at')->nullable()->comment('When vendor delivered to office');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'office_location',
                'inspection_notes',
                'tag_number',
                'tracking_number',
                'received_at_office_at',
            ]);
        });
    }
};
