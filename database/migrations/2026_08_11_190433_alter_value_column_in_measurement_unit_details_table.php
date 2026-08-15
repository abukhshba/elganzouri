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
        Schema::table('measurement_unit_details', function (Blueprint $table) {
            $table->decimal('value', 16, 6)->default(1)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('measurement_unit_details', function (Blueprint $table) {
            $table->decimal('value', 15, 6)->default(1)->change();
        });
    }
};
