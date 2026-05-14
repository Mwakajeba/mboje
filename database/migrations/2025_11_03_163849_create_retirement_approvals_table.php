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
        Schema::create('retirement_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('retirement_id');
            $table->integer('approval_level'); // 1, 2, 3, 4, 5
            $table->unsignedBigInteger('approver_id');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('comments')->nullable();
            $table->timestamp('action_date')->nullable();
            $table->decimal('amount_threshold', 15, 2)->nullable(); // Amount threshold at time of approval
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('retirement_id')->references('id')->on('retirements')->onDelete('cascade');
            $table->foreign('approver_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index(['retirement_id', 'approval_level']);
            $table->index(['approver_id', 'status']);
            $table->index('status');
            
            // Ensure no duplicate approvals for the same retirement/level/approver
            $table->unique(['retirement_id', 'approval_level', 'approver_id'], 'retirement_approvals_unique_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retirement_approvals');
    }
};
