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
        Schema::create('count_session_approval_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->integer('approval_levels')->default(1);
            $table->boolean('require_approval_for_all')->default(true);
            $table->integer('escalation_time')->default(24); // hours
            
            // Level 1 approval settings
            $table->string('level1_approval_type')->default('role'); // role or user
            $table->json('level1_approvers')->nullable(); // array of role names or user IDs
            $table->string('level1_name')->default('Supervisor'); // Display name
            
            // Level 2 approval settings
            $table->string('level2_approval_type')->nullable();
            $table->json('level2_approvers')->nullable();
            $table->string('level2_name')->nullable();
            
            // Level 3 approval settings
            $table->string('level3_approval_type')->nullable();
            $table->json('level3_approvers')->nullable();
            $table->string('level3_name')->nullable();
            
            // Level 4 approval settings
            $table->string('level4_approval_type')->nullable();
            $table->json('level4_approvers')->nullable();
            $table->string('level4_name')->nullable();
            
            // Level 5 approval settings
            $table->string('level5_approval_type')->nullable();
            $table->json('level5_approvers')->nullable();
            $table->string('level5_name')->nullable();
            
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->unique('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('count_session_approval_settings');
    }
};
