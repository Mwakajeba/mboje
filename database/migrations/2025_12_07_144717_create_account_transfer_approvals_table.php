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
        if (Schema::hasTable('account_transfer_approvals')) {
            return;
        }
        
        Schema::create('account_transfer_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_transfer_id')->constrained('account_transfers')->onDelete('cascade');
            $table->integer('approval_level')->default(1);
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->string('approver_type')->nullable(); // role or user
            $table->string('approver_name')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'escalated'])->default('pending');
            $table->text('comments')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->timestamps();
            
            $table->foreign('approver_id')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['account_transfer_id', 'approval_level'], 'at_approvals_transfer_level_idx');
            $table->index(['status', 'approval_level'], 'at_approvals_status_level_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_transfer_approvals');
    }
};
