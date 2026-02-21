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
        Schema::create('vendor_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('role')->default('staff'); // e.g., 'staff', 'manager', 'assistant'
            $table->string('position')->nullable(); // e.g., 'Sales Manager', 'Inventory Clerk'
            $table->text('permissions')->nullable(); // JSON or comma-separated list of permissions
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Ensure a user can only be staff for a vendor once
            $table->unique(['vendor_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_staff');
    }
};
