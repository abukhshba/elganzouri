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
        Schema::create('cashbox_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cashbox_id')->constrained('cashboxes');
            $table->string('transaction_type', 20); // IN, OUT
            $table->string('reference_type', 60)->index();
            $table->unsignedBigInteger('reference_id')->index();
            $table->decimal('amount', 15, 4);
            $table->decimal('balance_before', 15, 4);
            $table->decimal('balance_after', 15, 4);
            $table->foreignId('user_id')->constrained('users');
            $table->string('description', 255)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['cashbox_id', 'created_at'], 'idx_cashbox_history');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashbox_transactions');
    }
};
