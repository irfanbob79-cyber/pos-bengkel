<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_tier_id')->nullable()->constrained('price_tiers')->nullOnDelete();
            $table->string('name', 150);
            $table->string('phone', 30)->nullable()->index();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('motor_model_id')->nullable()->constrained('motor_models')->nullOnDelete();
            $table->string('plate_number', 25)->unique(); // Plat Nomor Unique B-Tree Index
            $table->string('chassis_number', 50)->nullable();
            $table->string('engine_number', 50)->nullable();
            $table->unsignedInteger('last_km')->default(0);
            $table->date('next_service_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('customers');
    }
};
