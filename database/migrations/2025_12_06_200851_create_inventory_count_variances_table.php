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
        Schema::create('inventory_count_variances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('count_entry_id')->constrained('inventory_count_entries')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->decimal('system_quantity', 15, 2)->default(0);
            $table->decimal('physical_quantity', 15, 2)->default(0);
            $table->decimal('variance_quantity', 15, 2)->default(0); // Physical - System
            $table->decimal('variance_percentage', 5, 2)->default(0); // (Variance / System) * 100
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('variance_value', 15, 2)->default(0); // Variance Qty * Unit Cost
            $table->enum('variance_type', ['zero', 'positive', 'negative'])->default('zero');
            $table->boolean('is_high_value')->default(false); // Flag if variance value exceeds threshold
            $table->boolean('requires_recount')->default(false);
            $table->decimal('recount_tolerance_percentage', 5, 2)->nullable(); // e.g., 5%
            $table->decimal('recount_tolerance_value', 15, 2)->nullable(); // e.g., TZS 50,000
            $table->text('investigation_notes')->nullable();
            $table->enum('status', ['pending', 'investigating', 'resolved', 'approved'])->default('pending');
            $table->timestamps();
            
            $table->index(['item_id', 'variance_type']);
            $table->index('is_high_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_count_variances');
    }
};
