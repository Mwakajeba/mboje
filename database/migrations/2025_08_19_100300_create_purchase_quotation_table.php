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
        Schema::create('purchase_quotation', function (Blueprint $table) {
            $table->id();
            // Optional link to a purchase requisition this quote is responding to.
            // Use a nullable unsignedBigInteger without FK to avoid errors when the
            // purchase_requisitions table does not exist in some deployments.
            $table->unsignedBigInteger('purchase_requisition_id')->nullable();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->date('start_date');
            $table->date('due_date');
            $table->enum('status', ['draft', 'sent', 'approved', 'rejected', 'expired'])->default('draft');
            $table->string('reference')->nullable();
            $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('createdby')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_quotation');
    }
};
