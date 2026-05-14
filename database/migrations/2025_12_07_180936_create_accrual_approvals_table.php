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
        Schema::create('accrual_approvals', function (Blueprint $table) {
            $table->id();
            
            // Link to schedule
            $table->foreignId('accrual_schedule_id')->constrained('accrual_schedules')->onDelete('cascade');
            
            // Approval Level
            $table->integer('approval_level')->default(1); // For multi-level approvals
            
            // Approver
            $table->foreignId('approver_id')->constrained('users')->onDelete('restrict');
            
            // Status
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            
            // Comments
            $table->text('comments')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Timestamps
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['accrual_schedule_id', 'approval_level']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accrual_approvals');
    }
};
