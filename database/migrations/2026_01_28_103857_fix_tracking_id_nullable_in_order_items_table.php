<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite creates the column nullable from the original migration; only MySQL needs this fix
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE order_items MODIFY COLUMN tracking_id VARCHAR(255) NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE order_items MODIFY COLUMN tracking_id VARCHAR(255) NOT NULL');
        }
    }
};
