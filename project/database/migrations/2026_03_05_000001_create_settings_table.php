<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('price')->default('');
            $table->boolean('kcal_100g')->default(true);
            $table->boolean('carbs_100g')->default(false);
            $table->boolean('protein_100g')->default(false);
            $table->boolean('fat_100g')->default(false);
            $table->boolean('sugar_100g')->default(false);
            $table->boolean('fiber_100g')->default(false);
            $table->string('currency_sign')->default('$');
            $table->enum('language', ['en', 'pl', 'ua'])->default('en');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};