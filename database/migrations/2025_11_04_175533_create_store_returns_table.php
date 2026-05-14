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
        Schema::create('store_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_note_number')->unique();
            $table->unsignedBigInteger('store_issue_id');
            $table->unsignedBigInteger('store_requisition_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('returned_by'); // User returning items
            $table->unsignedBigInteger('verified_by')->nullable(); // Store clerk
            $table->unsignedBigInteger('approved_by')->nullable(); // Supervisor
            
            $table->date('return_date');
            $table->time('return_time');
            $table->decimal('total_return_value', 15, 2)->default(0);
            $table->text('return_reason');
            $table->enum('return_condition', ['good', 'damaged', 'expired', 'defective'])->default('good');
            $table->text('condition_notes')->nullable();
            $table->enum('status', ['pending', 'verified', 'approved', 'rejected'])->default('pending');
            
            // Return processing
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->boolean('stock_updated')->default(false);
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('store_issue_id')->references('id')->on('store_issues')->onDelete('cascade');
            $table->foreign('store_requisition_id')->references('id')->on('store_requisitions')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('returned_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index(['store_issue_id']);
            $table->index(['store_requisition_id']);
            $table->index(['company_id', 'return_date']);
            $table->index(['branch_id', 'return_date']);
            $table->index(['status']);
            $table->index(['return_condition']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_returns');
    }
};
