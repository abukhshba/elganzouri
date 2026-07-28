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
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_inventory_id')->constrained('item_inventories');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('item_id')->constrained('items');
            $table->foreignId('base_unit_id')->constrained('units');
            $table->string('transaction_type', 30);
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_cost', 15, 4);
            $table->decimal('total_cost', 15, 4);
            $table->decimal('balance_after', 15, 4);
            $table->decimal('average_cost_after', 15, 4);
            $table->string('reference_type', 60)->index();
            $table->unsignedBigInteger('reference_id')->index();
            $table->foreignId('performed_by')->constrained('users');
            $table->string('notes', 255)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['item_id', 'warehouse_id', 'created_at'], 'idx_ledger_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
