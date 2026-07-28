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
        Schema::create('document_number_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 50)->unique();
            $table->string('prefix', 10)->default('');
            $table->string('suffix', 10)->default('');
            $table->unsignedTinyInteger('padding')->default(5);
            $table->unsignedBigInteger('current_number')->default(0);
            $table->string('reset_period', 20)->default('NEVER'); // NEVER, YEARLY, MONTHLY
            $table->date('last_reset_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_number_sequences');
    }
};
