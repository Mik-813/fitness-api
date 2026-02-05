<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weighted_product_id')->constrained('weighted_products')->cascadeOnDelete();
            $table->date('record_date');
            $table->foreign('record_date')->references('record_date')->on('dates')->cascadeOnDelete();
            $table->integer('consumption_g');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumables');
    }
};
