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
        Schema::table('bank_reconciliations', function (Blueprint $table) {
            $table->timestamp('adjustments_posted_at')->nullable()->after('approved_at')
                ->comment('When reconciliation adjustments were posted to GL');
            $table->foreignId('adjustments_posted_by')->nullable()->after('adjustments_posted_at')
                ->constrained('users')->onDelete('set null')
                ->comment('User who posted the adjustments (system on auto-post)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_reconciliations', function (Blueprint $table) {
            $table->dropForeign(['adjustments_posted_by']);
            $table->dropColumn(['adjustments_posted_at', 'adjustments_posted_by']);
        });
    }
};
