<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Note: SQLite uses string columns, so enum values are already allowed.
     * This migration is only needed for MySQL. Skip for SQLite.
     */
    public function up(): void
    {
        // Check if table exists before modifying
        if (!Schema::hasTable('cashflow_forecast_items')) {
            return;
        }
        
        // Skip for SQLite - string columns accept any value
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }
        
        // MySQL: Add 'recurring_expense' to the existing enum values
        DB::statement("ALTER TABLE cashflow_forecast_items MODIFY COLUMN source_type ENUM(
            'accounts_receivable',
            'accounts_payable',
            'sales_order',
            'loan_disbursement',
            'loan_payment',
            'payroll',
            'tax_vat',
            'tax_wht',
            'tax_paye',
            'tax_sdl',
            'tax_pension',
            'recurring_bill',
            'recurring_expense',
            'capex',
            'petty_cash',
            'manual_adjustment'
        )");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if table exists before modifying
        if (!Schema::hasTable('cashflow_forecast_items')) {
            return;
        }
        
        // Skip for SQLite
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }
        
        // Remove 'recurring_expense' from the enum
        DB::statement("ALTER TABLE cashflow_forecast_items MODIFY COLUMN source_type ENUM(
            'accounts_receivable',
            'accounts_payable',
            'sales_order',
            'loan_disbursement',
            'loan_payment',
            'payroll',
            'tax_vat',
            'tax_wht',
            'tax_paye',
            'tax_sdl',
            'tax_pension',
            'recurring_bill',
            'capex',
            'petty_cash',
            'manual_adjustment'
        )");
    }
};
