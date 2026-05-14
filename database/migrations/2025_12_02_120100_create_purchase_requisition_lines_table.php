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
        if (!Schema::hasTable('purchase_requisition_lines')) {
            Schema::create('purchase_requisition_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_requisition_id')->constrained('purchase_requisitions')->onDelete('cascade');
                $table->enum('item_type', ['inventory', 'service'])->default('inventory');
                $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
                $table->string('description', 500)->nullable();
                $table->decimal('quantity', 18, 4);
                $table->string('uom', 50)->nullable();
                $table->decimal('unit_price_estimate', 18, 4)->default(0);
                $table->decimal('line_total_estimate', 18, 2)->default(0);
                // Keep tax_group_id optional and not FK-bound for now to avoid coupling to tax tables
                $table->unsignedBigInteger('tax_group_id')->nullable();
                // Budget dimensions
                $table->foreignId('gl_account_id')->nullable()->constrained('chart_accounts')->nullOnDelete();
                $table->foreignId('cost_center_id')->nullable()->constrained('departments')->nullOnDelete();
                // Project is optional and projects table may not exist in all deployments, so avoid FK
                $table->unsignedBigInteger('project_id')->nullable();
                $table->foreignId('budget_line_id')->nullable()->constrained('budget_lines')->nullOnDelete();
                // Tracking conversion to PO
                $table->decimal('ordered_quantity', 18, 4)->default(0);
                $table->enum('line_status', ['open', 'partially_ordered', 'fully_ordered'])->default('open');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_requisition_lines');
    }
};


