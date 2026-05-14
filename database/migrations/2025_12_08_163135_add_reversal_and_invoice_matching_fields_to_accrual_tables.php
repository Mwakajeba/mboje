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
        // Add reversal journal reference to accrual_journals table
        Schema::table('accrual_journals', function (Blueprint $table) {
            $table->foreignId('reversal_journal_id')->nullable()->after('journal_id')
                ->constrained('journals')->onDelete('set null')
                ->comment('Reference to the auto-reversal journal entry');
        });

        // Add invoice matching fields to accrual_schedules table
        Schema::table('accrual_schedules', function (Blueprint $table) {
            $table->string('matched_invoice_number')->nullable()->after('attachment_path')
                ->comment('Invoice number that settled this accrual');
            $table->date('matched_invoice_date')->nullable()->after('matched_invoice_number')
                ->comment('Date of the matched invoice');
            $table->decimal('matched_invoice_amount', 20, 2)->nullable()->after('matched_invoice_date')
                ->comment('Amount of the matched invoice');
            $table->enum('settlement_status', ['open', 'partially_settled', 'fully_settled'])->default('open')
                ->after('matched_invoice_amount')
                ->comment('Status of accrual settlement with actual invoice');
            $table->timestamp('settled_at')->nullable()->after('settlement_status')
                ->comment('When the accrual was settled by invoice');
            $table->foreignId('settled_by')->nullable()->after('settled_at')
                ->constrained('users')->onDelete('set null')
                ->comment('User who matched the invoice');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accrual_journals', function (Blueprint $table) {
            $table->dropForeign(['reversal_journal_id']);
            $table->dropColumn('reversal_journal_id');
        });

        Schema::table('accrual_schedules', function (Blueprint $table) {
            $table->dropForeign(['settled_by']);
            $table->dropColumn([
                'matched_invoice_number',
                'matched_invoice_date',
                'matched_invoice_amount',
                'settlement_status',
                'settled_at',
                'settled_by',
            ]);
        });
    }
};
