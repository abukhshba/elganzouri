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
        Schema::create('measurement_units', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('measurement_unit_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('measurement_unit_id')->constrained('measurement_units')->cascadeOnDelete();
            $table->json('name');
            $table->string('unit_type')->nullable(); // weight, length, numbers, etc.
            $table->boolean('is_default')->default(false);
            $table->decimal('value', 15, 6)->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('measurement_unit_details');
        Schema::dropIfExists('measurement_units');
    }
};
