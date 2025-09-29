<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Vendor;
use App\Models\User;

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

            // Create users for existing vendors (if any)
            $vendors = Vendor::all();
            foreach ($vendors as $vendor) {
                // Create a user account for existing vendor
                $user = User::create([
                    'name' => $vendor->name,
                    'email' => $vendor->contact_email,
                    'password' => bcrypt('temporary123'), // They'll need to reset
                    'role' => 'vendor',
                    'is_active' => $vendor->is_active,
                ]);

                // Link the vendor to the user
                $vendor->update(['user_id' => $user->id]);
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
