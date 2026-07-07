<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if user_id column already exists
        if (!Schema::hasColumn('vendors', 'user_id')) {
            // First, add the user_id column as nullable
            Schema::table('vendors', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('id');
            });

            // Create users for existing vendors (if any) — use DB::table to avoid SoftDeletes scope
            $vendors = DB::table('vendors')->get();
            foreach ($vendors as $vendor) {
                $userId = DB::table('users')->insertGetId([
                    'name'       => $vendor->name,
                    'email'      => $vendor->contact_email,
                    'password'   => bcrypt('temporary123'),
                    'role'       => 'vendor',
                    'is_active'  => $vendor->is_active,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('vendors')->where('id', $vendor->id)->update(['user_id' => $userId]);
            }

            // Now make the user_id column non-nullable and add the foreign key constraint
            Schema::table('vendors', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable(false)->change();
                $table->unique('user_id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
