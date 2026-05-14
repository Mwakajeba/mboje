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
        Schema::table('chart_accounts', function (Blueprint $table) {
            // Add account_type field (parent or child)
            $table->enum('account_type', ['parent', 'child'])->default('child')->after('account_name');
            
            // Add parent_id field for child accounts
            $table->foreignId('parent_id')->nullable()->after('account_type')->constrained('chart_accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chart_accounts', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['account_type', 'parent_id']);
        });
    }
};
