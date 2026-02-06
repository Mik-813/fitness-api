<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->date('record_date');
            $table->string('title');
            $table->string('muscle');
            $table->string('secondary_muscle')->nullable();
            $table->string('bodypart');
            $table->string('equipment');
            $table->timestamps();

            // Assuming 'dates' table exists and 'record_date' is indexed or unique there
            $table->foreign('record_date')->references('record_date')->on('dates')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};