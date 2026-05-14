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
        Schema::create('count_adjustment_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('count_adjustment_id')->constrained('inventory_count_adjustments')->onDelete('cascade');
            $table->unsignedTinyInteger('approval_level')->comment('1=Store Supervisor, 2=Inventory Manager, 3=Finance Manager, 4=CFO');
            $table->string('level_name', 100)->comment('Store Supervisor, Inventory Manager, etc.');
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('comments')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
            
            $table->index(['count_adjustment_id', 'approval_level'], 'idx_adj_approval_level');
            $table->index('approver_id', 'idx_adj_approver');
            $table->index('status', 'idx_adj_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('count_adjustment_approvals');
    }
};
