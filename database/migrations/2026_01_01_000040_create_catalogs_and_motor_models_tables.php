<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique()->nullable();
            $table->string('name', 100);
            $table->timestamps();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        Schema::create('motor_models', function (Blueprint $table) {
            $table->id();
            $table->string('brand', 50); // Honda, Yamaha, Suzuki, Kawasaki, etc.
            $table->string('name', 100);  // Beat FI, Vario 125, NMAX, etc.
            $table->integer('engine_capacity')->nullable(); // cc (110, 125, 150, etc.)
            $table->timestamps();

            $table->index(['brand', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('motor_models');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');
    }
};
