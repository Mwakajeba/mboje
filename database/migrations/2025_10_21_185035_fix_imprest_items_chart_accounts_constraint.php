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
        Schema::table('imprest_items', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['chart_account_id']);
            
            // Add the foreign key constraint without onDelete restrict since chart_accounts doesn't have company_id
            $table->foreign('chart_account_id')->references('id')->on('chart_accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('imprest_items', function (Blueprint $table) {
            // Drop the modified foreign key
            $table->dropForeign(['chart_account_id']);
            
            // Restore the original constraint
            $table->foreign('chart_account_id')->references('id')->on('chart_accounts')->onDelete('restrict');
        });
    }
};
