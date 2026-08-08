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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_group_id')->nullable()->constrained('unit_groups')->onDelete('cascade');
            $table->text('name');
            $table->string('short_name', 20)->unique();
            $table->boolean('is_base')->default(false);
            $table->boolean('is_custom_per_item')->default(false);
            $table->decimal('global_conversion_factor', 15, 4)->default(1.0000);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
