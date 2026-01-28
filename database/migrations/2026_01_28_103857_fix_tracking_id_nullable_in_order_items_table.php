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
        // Use raw SQL to modify the column to be nullable
        DB::statement('ALTER TABLE order_items MODIFY COLUMN tracking_id VARCHAR(255) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to NOT NULL (but this might fail if there are null values)
        DB::statement('ALTER TABLE order_items MODIFY COLUMN tracking_id VARCHAR(255) NOT NULL');
    }
};
