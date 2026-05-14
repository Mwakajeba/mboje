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
        Schema::create('store_req_approval_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            
            // Level 1 settings
            $table->boolean('level_1_enabled')->default(false);
            $table->unsignedBigInteger('level_1_user_id')->nullable();
            $table->unsignedBigInteger('level_1_role_id')->nullable();
            
            // Level 2 settings
            $table->boolean('level_2_enabled')->default(false);
            $table->unsignedBigInteger('level_2_user_id')->nullable();
            $table->unsignedBigInteger('level_2_role_id')->nullable();
            
            // Level 3 settings
            $table->boolean('level_3_enabled')->default(false);
            $table->unsignedBigInteger('level_3_user_id')->nullable();
            $table->unsignedBigInteger('level_3_role_id')->nullable();
            
            // Level 4 settings
            $table->boolean('level_4_enabled')->default(false);
            $table->unsignedBigInteger('level_4_user_id')->nullable();
            $table->unsignedBigInteger('level_4_role_id')->nullable();
            
            // Level 5 settings
            $table->boolean('level_5_enabled')->default(false);
            $table->unsignedBigInteger('level_5_user_id')->nullable();
            $table->unsignedBigInteger('level_5_role_id')->nullable();
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            
            // Foreign keys with shorter names
            $table->foreign('company_id', 'sras_company_fk')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('level_1_user_id', 'sras_l1_user_fk')->references('id')->on('users')->onDelete('set null');
            $table->foreign('level_1_role_id', 'sras_l1_role_fk')->references('id')->on('roles')->onDelete('set null');
            $table->foreign('level_2_user_id', 'sras_l2_user_fk')->references('id')->on('users')->onDelete('set null');
            $table->foreign('level_2_role_id', 'sras_l2_role_fk')->references('id')->on('roles')->onDelete('set null');
            $table->foreign('level_3_user_id', 'sras_l3_user_fk')->references('id')->on('users')->onDelete('set null');
            $table->foreign('level_3_role_id', 'sras_l3_role_fk')->references('id')->on('roles')->onDelete('set null');
            $table->foreign('level_4_user_id', 'sras_l4_user_fk')->references('id')->on('users')->onDelete('set null');
            $table->foreign('level_4_role_id', 'sras_l4_role_fk')->references('id')->on('roles')->onDelete('set null');
            $table->foreign('level_5_user_id', 'sras_l5_user_fk')->references('id')->on('users')->onDelete('set null');
            $table->foreign('level_5_role_id', 'sras_l5_role_fk')->references('id')->on('roles')->onDelete('set null');
            $table->foreign('created_by', 'sras_created_fk')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by', 'sras_updated_fk')->references('id')->on('users')->onDelete('set null');
            
            // Unique constraint
            $table->unique('company_id');
            
            // Indexes
            $table->index('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_req_approval_settings');
    }
};
