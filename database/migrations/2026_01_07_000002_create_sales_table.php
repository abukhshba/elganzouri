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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('cashbox_id')->nullable()->constrained('cashboxes');
            $table->string('status', 30)->default('DRAFT'); // DRAFT, CONFIRMED, CANCELLED
            $table->string('payment_status', 30)->default('PAID'); // UNPAID, PARTIAL, PAID
            $table->decimal('total_amount', 15, 4)->default(0.0000);
            $table->decimal('tax_amount', 15, 4)->default(0.0000);
            $table->decimal('discount_amount', 15, 4)->default(0.0000);
            $table->decimal('paid_amount', 15, 4)->default(0.0000);
            $table->decimal('due_amount', 15, 4)->default(0.0000);
            $table->decimal('total_cogs', 15, 4)->default(0.0000);
            $table->decimal('total_profit', 15, 4)->default(0.0000);
            $table->date('issue_date')->index();
            $table->date('due_date')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
