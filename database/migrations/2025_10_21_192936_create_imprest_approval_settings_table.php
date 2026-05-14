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
        Schema::create('imprest_approval_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('approval_role', ['checker', 'approver', 'provider']);
            $table->boolean('is_active')->default(true);
            $table->decimal('amount_limit', 15, 2)->nullable(); // Maximum amount this user can approve
            $table->json('department_ids')->nullable(); // JSON array of department IDs this user can handle
            
            // Audit fields
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('restrict');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['company_id', 'approval_role', 'is_active'], 'ias_company_role_active_idx');
            $table->index(['user_id', 'approval_role'], 'ias_user_role_idx');
            
            // Unique constraint to prevent duplicate role assignments for same user
            $table->unique(['user_id', 'approval_role', 'company_id'], 'ias_user_role_company_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imprest_approval_settings');
    }
};
