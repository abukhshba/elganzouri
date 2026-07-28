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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories');
            $table->foreignId('brand_id')->nullable()->constrained('brands')->onDelete('set null');
            $table->foreignId('base_unit_id')->constrained('units');
            $table->string('sku', 60)->unique();
            $table->string('barcode', 60)->nullable()->index();
            $table->text('name');
            $table->text('description')->nullable();
            $table->decimal('min_stock_alert', 15, 4)->default(0.0000);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
