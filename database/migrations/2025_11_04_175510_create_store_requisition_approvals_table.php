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
        Schema::create('store_requisition_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_requisition_id');
            $table->integer('approval_level'); // 1, 2, 3, 4, 5
            $table->unsignedBigInteger('approver_id');
            $table->enum('action', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('action_date')->nullable();
            $table->text('comments')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            
            $table->timestamps();
            
            // Foreign keys with shorter names
            $table->foreign('store_requisition_id', 'sra_req_fk')->references('id')->on('store_requisitions')->onDelete('cascade');
            $table->foreign('approver_id', 'sra_approver_fk')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by', 'sra_created_fk')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by', 'sra_updated_fk')->references('id')->on('users')->onDelete('set null');
            
            // Indexes with shorter names
            $table->index(['store_requisition_id', 'approval_level'], 'sra_req_level_idx');
            $table->index(['approver_id', 'action'], 'sra_approver_action_idx');
            $table->index('action', 'sra_action_idx');
            $table->index('approval_level', 'sra_level_idx');
            
            // Unique constraint to prevent duplicate approvals
            $table->unique(['store_requisition_id', 'approval_level', 'approver_id'], 'sra_unique_approval');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_requisition_approvals');
    }
};
