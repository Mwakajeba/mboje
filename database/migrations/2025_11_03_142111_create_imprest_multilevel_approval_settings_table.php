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
        Schema::create('imprest_multilevel_approval_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id');
            $table->boolean('approval_required')->default(false);
            $table->integer('approval_levels')->default(1); // Number of approval levels (1-5)
            
            // Level 1 approval settings
            $table->decimal('level1_amount_threshold', 15, 2)->nullable();
            $table->json('level1_approvers')->nullable(); // Array of user IDs
            
            // Level 2 approval settings
            $table->decimal('level2_amount_threshold', 15, 2)->nullable();
            $table->json('level2_approvers')->nullable();
            
            // Level 3 approval settings
            $table->decimal('level3_amount_threshold', 15, 2)->nullable();
            $table->json('level3_approvers')->nullable();
            
            // Level 4 approval settings
            $table->decimal('level4_amount_threshold', 15, 2)->nullable();
            $table->json('level4_approvers')->nullable();
            
            // Level 5 approval settings
            $table->decimal('level5_amount_threshold', 15, 2)->nullable();
            $table->json('level5_approvers')->nullable();
            
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->unique(['company_id', 'branch_id']);
            $table->index('approval_required');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imprest_multilevel_approval_settings');
    }
};
