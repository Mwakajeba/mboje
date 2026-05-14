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
        Schema::create('approval_histories', function (Blueprint $table) {
            $table->id();
            $table->string('approvable_type')->comment('App\Models\Budget or App\Models\BankReconciliation');
            $table->unsignedBigInteger('approvable_id');
            $table->foreignId('approval_level_id')->constrained('approval_levels')->onDelete('cascade');
            $table->enum('action', ['submitted', 'approved', 'rejected', 'reassigned'])->comment('Action taken');
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null')->comment('User who took action');
            $table->text('comments')->nullable()->comment('Approval/rejection comments');
            $table->foreignId('reassigned_to_user_id')->nullable()->constrained('users')->onDelete('set null')->comment('If reassigned');
            $table->timestamps();
            
            // Indexes for faster lookups
            $table->index(['approvable_type', 'approvable_id'], 'approval_histories_approvable_index');
            $table->index('approval_level_id');
            $table->index('approver_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_histories');
    }
};
