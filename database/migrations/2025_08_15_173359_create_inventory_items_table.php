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
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('inventory_categories')->onDelete('cascade');
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->enum('item_type', ['product', 'service'])->default('product');
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('cost_price', 15, 2)->default(0.00);
            $table->string('unit_of_measure')->default('piece');
            $table->integer('minimum_stock')->nullable();
            $table->integer('reorder_level')->nullable();
            $table->decimal('average_cost', 15, 2)->default(0.00);
            $table->boolean('has_opening_balance')->default(false);
            $table->decimal('opening_balance_quantity', 15, 2)->default(0);
            $table->decimal('opening_balance_value', 15, 2)->default(0);
            $table->boolean('track_stock')->default(true);
            $table->integer('maximum_stock')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_service')->default(false);
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            
            // $table->softDeletes();
            $table->timestamps();
            
            // Indexes
            $table->index('is_active');
            $table->index('code');
            $table->index('sku');
            $table->index('barcode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
