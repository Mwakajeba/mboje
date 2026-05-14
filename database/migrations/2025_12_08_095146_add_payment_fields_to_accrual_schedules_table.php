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
        Schema::table('accrual_schedules', function (Blueprint $table) {
            // Payment method fields (only for prepayments)
            $table->enum('payment_method', ['bank', 'cash'])->nullable()->after('currency_code');
            $table->foreignId('bank_account_id')->nullable()->after('payment_method')->constrained('bank_accounts')->onDelete('set null');
            $table->date('payment_date')->nullable()->after('bank_account_id'); // Date when payment/receipt was made
            $table->foreignId('initial_journal_id')->nullable()->after('payment_date')->constrained('journals')->onDelete('set null'); // Reference to initial payment/receipt journal
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accrual_schedules', function (Blueprint $table) {
            $table->dropForeign(['bank_account_id']);
            $table->dropForeign(['initial_journal_id']);
            $table->dropColumn(['payment_method', 'bank_account_id', 'payment_date', 'initial_journal_id']);
        });
    }
};
