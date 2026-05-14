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
        Schema::create('approval_level_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_level_id')->constrained('approval_levels')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null')->comment('Specific user approver');
            $table->unsignedBigInteger('role_id')->nullable()->comment('OR specific role approver');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null')->comment('Optional: branch-specific approver');
            $table->timestamps();
            
            // Indexes for faster lookups
            $table->index('approval_level_id');
            $table->index('user_id');
            $table->index('role_id');
            $table->index('branch_id');
            
            // Ensure either user_id or role_id is set (but not both required)
            // This will be enforced at application level
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_level_assignments');
    }
};
