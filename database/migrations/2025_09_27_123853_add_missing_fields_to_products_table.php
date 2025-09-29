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
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'slug')) {
                $table->string('slug')->after('name');
            }
            if (!Schema::hasColumn('products', 'short_description')) {
                $table->text('short_description')->nullable()->after('description');
            }
            if (!Schema::hasColumn('products', 'quantity')) {
                $table->integer('quantity')->default(0)->after('price');
            }
            if (!Schema::hasColumn('products', 'weight')) {
                $table->decimal('weight', 8, 2)->nullable()->after('quantity');
            }
            if (!Schema::hasColumn('products', 'dimensions')) {
                $table->string('dimensions')->nullable()->after('weight');
            }
            if (!Schema::hasColumn('products', 'status')) {
                $table->enum('status', ['active', 'inactive', 'draft'])->default('active')->after('is_active');
            }
            if (!Schema::hasColumn('products', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('status');
            }
            if (!Schema::hasColumn('products', 'average_rating')) {
                $table->decimal('average_rating', 3, 1)->default(0)->after('is_featured');
            }
            if (!Schema::hasColumn('products', 'total_reviews')) {
                $table->integer('total_reviews')->default(0)->after('average_rating');
            }
            if (!Schema::hasColumn('products', 'total_sales')) {
                $table->integer('total_sales')->default(0)->after('total_reviews');
            }
            if (!Schema::hasColumn('products', 'view_count')) {
                $table->integer('view_count')->default(0)->after('total_sales');
            }
        });

        // Add unique constraint to slug after ensuring all slugs are populated
        if (Schema::hasColumn('products', 'slug') && !DB::select("SHOW INDEX FROM products WHERE Key_name = 'products_slug_unique'")) {
            Schema::table('products', function (Blueprint $table) {
                $table->unique('slug');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'slug',
                'short_description', 
                'quantity',
                'weight',
                'dimensions',
                'status',
                'is_featured',
                'average_rating',
                'total_reviews',
                'total_sales',
                'view_count'
            ]);
        });
    }
};
