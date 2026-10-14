<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opening_balances', function (Blueprint $table) {
            $table->id();
            $table->string('opening_number', 50)->unique();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->date('issue_date')->index();
            $table->decimal('total_value', 15, 4)->default(0.0000);
            $table->foreignId('user_id')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('opening_balance_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opening_balance_id')->constrained('opening_balances')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('items');
            $table->foreignId('measurement_unit_detail_id')->constrained('measurement_unit_details');
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_cost', 15, 4);
            $table->decimal('selling_price', 15, 4)->nullable();
            $table->decimal('conversion_factor', 15, 4)->default(1.0000);
            $table->decimal('base_quantity', 15, 4);
            $table->decimal('base_unit_cost', 15, 4);
            $table->decimal('line_total', 15, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opening_balance_items');
        Schema::dropIfExists('opening_balances');
    }
};
