<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('supplier_opening_balances')) {
            return;
        }

        Schema::table('supplier_opening_balances', function (Blueprint $table) {
            if (! Schema::hasColumn('supplier_opening_balances', 'journal_id')) {
                $table->foreignId('journal_id')
                    ->nullable()
                    ->after('purchase_invoice_id')
                    ->constrained('journals')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('supplier_opening_balances', 'payable_chart_account_id')) {
                $table->unsignedBigInteger('payable_chart_account_id')->nullable()->after('journal_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('supplier_opening_balances')) {
            return;
        }

        Schema::table('supplier_opening_balances', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_opening_balances', 'journal_id')) {
                $table->dropForeign(['journal_id']);
                $table->dropColumn('journal_id');
            }
            if (Schema::hasColumn('supplier_opening_balances', 'payable_chart_account_id')) {
                $table->dropColumn('payable_chart_account_id');
            }
        });
    }
};
