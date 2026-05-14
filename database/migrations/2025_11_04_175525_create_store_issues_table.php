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
        Schema::create('store_issues', function (Blueprint $table) {
            $table->id();
            $table->string('issue_voucher_number')->unique();
            $table->unsignedBigInteger('store_requisition_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('issued_by'); // Store clerk
            $table->unsignedBigInteger('received_by'); // Requisitioner
            
            $table->date('issue_date');
            $table->time('issue_time');
            $table->decimal('total_issue_value', 15, 2)->default(0);
            $table->enum('issue_type', ['full', 'partial'])->default('full');
            $table->text('issue_notes')->nullable();
            $table->enum('status', ['issued', 'returned', 'partially_returned'])->default('issued');
            
            // Delivery details
            $table->string('delivery_location')->nullable();
            $table->text('delivery_instructions')->nullable();
            $table->boolean('requires_signature')->default(true);
            $table->string('signature_path')->nullable(); // For digital signatures
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('store_requisition_id')->references('id')->on('store_requisitions')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('issued_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('received_by')->references('id')->on('users')->onDelete('cascade');
            
            // Indexes
            $table->index(['store_requisition_id']);
            $table->index(['company_id', 'issue_date']);
            $table->index(['branch_id', 'issue_date']);
            $table->index(['issued_by']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_issues');
    }
};
