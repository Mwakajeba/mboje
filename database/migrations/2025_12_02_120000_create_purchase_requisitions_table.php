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
        Schema::create('purchase_requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('pr_no')->unique();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            // Link to departments table (cost centers)
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('requestor_id')->constrained('users')->onDelete('cascade');
            // Optional preferred supplier chosen after comparison/quotes
            $table->foreignId('preferred_supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->date('required_date')->nullable();
            $table->string('justification', 1000)->nullable();
            $table->enum('status', [
                'draft',
                'submitted',
                'pending_approval',
                'in_review',
                'approved',
                'po_created',
                'cancelled',
                'rejected',
            ])->default('draft');
            $table->unsignedInteger('current_approval_level')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejection_reason', 1000)->nullable();
            // Link to created purchase order when generated
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            // Budget / amount fields
            $table->foreignId('budget_id')->nullable()->constrained('budgets')->nullOnDelete();
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->string('currency', 10)->nullable();
            $table->decimal('exchange_rate', 18, 6)->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_requisitions');
    }
};


