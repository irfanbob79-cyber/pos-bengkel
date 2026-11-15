<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->string('sku', 60)->unique();
            $table->string('barcode', 60)->nullable()->unique();
            $table->string('name', 200);
            $table->string('unit', 30)->default('pcs');
            $table->string('quality_type', 30)->default('aftermarket'); // oem, aftermarket, local
            $table->decimal('moving_avg_cost', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('name');
        });

        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('price_tier_id')->constrained('price_tiers')->cascadeOnDelete();
            $table->decimal('price', 15, 2)->default(0);
            $table->integer('min_qty')->default(1);
            $table->timestamps();

            $table->unique(['product_id', 'price_tier_id']);
        });

        Schema::create('product_motor_compatibility', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('motor_model_id')->constrained('motor_models')->cascadeOnDelete();
            $table->string('notes', 100)->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'motor_model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_motor_compatibility');
        Schema::dropIfExists('product_prices');
        Schema::dropIfExists('products');
    }
};
