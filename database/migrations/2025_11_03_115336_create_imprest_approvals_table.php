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
        Schema::create('imprest_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('imprest_request_id');
            $table->integer('approval_level'); // 1, 2, 3, 4, 5
            $table->unsignedBigInteger('approver_id');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('comments')->nullable();
            $table->timestamp('action_date')->nullable();
            $table->decimal('amount_threshold', 15, 2)->nullable(); // Amount threshold at time of approval
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('imprest_request_id')->references('id')->on('imprest_requests')->onDelete('cascade');
            $table->foreign('approver_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index(['imprest_request_id', 'approval_level']);
            $table->index(['approver_id', 'status']);
            $table->index('status');
            
                        // Ensure no duplicate approvals for the same request/level/approver
            $table->unique(['imprest_request_id', 'approval_level', 'approver_id'], 'imprest_approvals_unique_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imprest_approvals');
    }
};
