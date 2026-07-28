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
        Schema::create('item_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->decimal('current_quantity', 15, 4)->default(0.0000);
            $table->decimal('reserved_quantity', 15, 4)->default(0.0000);
            $table->decimal('average_cost', 15, 4)->default(0.0000);
            $table->decimal('stock_value', 15, 4)->default(0.0000);
            $table->decimal('last_purchase_price', 15, 4)->default(0.0000);
            $table->decimal('last_sale_price', 15, 4)->default(0.0000);
            $table->decimal('minimum_quantity', 15, 4)->default(0.0000);
            $table->decimal('maximum_quantity', 15, 4)->default(0.0000);
            $table->decimal('reorder_quantity', 15, 4)->default(0.0000);
            $table->unsignedBigInteger('last_transaction_id')->nullable();
            $table->timestamps();

            $table->unique(['item_id', 'warehouse_id'], 'uk_item_warehouse');
            $table->index(['warehouse_id', 'item_id', 'current_quantity'], 'idx_item_wh_qty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_inventories');
    }
};
