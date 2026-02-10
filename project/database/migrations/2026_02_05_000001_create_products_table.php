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
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->decimal('kcal_100g', 8, 2)->nullable();
            $table->decimal('carbs_100g', 8, 2)->nullable();
            $table->decimal('protein_100g', 8, 2)->nullable();
            $table->decimal('fat_100g', 8, 2)->nullable();
            $table->decimal('sugar_100g', 8, 2)->nullable();
            $table->decimal('fiber_100g', 8, 2)->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'title']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
