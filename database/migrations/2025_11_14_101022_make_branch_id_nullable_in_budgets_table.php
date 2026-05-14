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
        Schema::table('budgets', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['branch_id']);
            
            // Make branch_id nullable to allow "All Branches" option
            $table->unsignedBigInteger('branch_id')->nullable()->change();
            
            // Re-add the foreign key constraint with nullable support
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['branch_id']);
            
            // Make branch_id not nullable again
            $table->unsignedBigInteger('branch_id')->nullable(false)->change();
            
            // Re-add the foreign key constraint
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
        });
    }
};
