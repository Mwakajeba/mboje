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
        Schema::table('gl_transactions', function (Blueprint $table) {
            $table->index(['branch_id', 'date', 'transaction_type', 'nature'], 'gt_branch_date_type_nature_idx');
            $table->index(['chart_account_id', 'date'], 'gt_account_date_idx');
            $table->index(['transaction_type', 'transaction_id'], 'gt_type_txid_idx');
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->index(['company_id', 'branch_id', 'invoice_date', 'status'], 'si_company_branch_date_status_idx');
            $table->index(['company_id', 'branch_id', 'balance_due'], 'si_company_branch_balance_due_idx');
        });

        Schema::table('cash_sales', function (Blueprint $table) {
            $table->index(['company_id', 'branch_id', 'sale_date'], 'cs_company_branch_sale_date_idx');
        });

        Schema::table('pos_sales', function (Blueprint $table) {
            $table->index(['company_id', 'branch_id', 'sale_date', 'deleted_at'], 'ps_company_branch_sale_deleted_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index(['branch_id', 'date'], 'payments_branch_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_branch_date_idx');
        });

        Schema::table('pos_sales', function (Blueprint $table) {
            $table->dropIndex('ps_company_branch_sale_deleted_idx');
        });

        Schema::table('cash_sales', function (Blueprint $table) {
            $table->dropIndex('cs_company_branch_sale_date_idx');
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropIndex('si_company_branch_date_status_idx');
            $table->dropIndex('si_company_branch_balance_due_idx');
        });

        Schema::table('gl_transactions', function (Blueprint $table) {
            $table->dropIndex('gt_branch_date_type_nature_idx');
            $table->dropIndex('gt_account_date_idx');
            $table->dropIndex('gt_type_txid_idx');
        });
    }
};
