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
        Schema::table('loan_payments', function (Blueprint $table) {
            // Add approved_by field
            if (!Schema::hasColumn('loan_payments', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('journal_id');
                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            }
            
            // Add posted_flag
            if (!Schema::hasColumn('loan_payments', 'posted_flag')) {
                $table->boolean('posted_flag')->default(false)->after('approved_by');
            }
            
            // Add payment_ref as alias for reference (keeping both for compatibility)
            if (!Schema::hasColumn('loan_payments', 'payment_ref')) {
                $table->string('payment_ref')->nullable()->after('reference');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_payments', function (Blueprint $table) {
            if (Schema::hasColumn('loan_payments', 'approved_by')) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn('approved_by');
            }
            if (Schema::hasColumn('loan_payments', 'posted_flag')) {
                $table->dropColumn('posted_flag');
            }
            if (Schema::hasColumn('loan_payments', 'payment_ref')) {
                $table->dropColumn('payment_ref');
            }
        });
    }
};
