<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop view if exists first (SQLite compatible)
        DB::statement("DROP VIEW IF EXISTS petty_cash_reconciliation_view");
        
        DB::statement("
            CREATE VIEW petty_cash_reconciliation_view AS
            SELECT 
                pcu.id AS unit_id,
                pcu.company_id,
                pcu.branch_id,
                pcu.name AS unit_name,
                pcu.code AS unit_code,
                pcu.float_amount AS opening_balance,
                pcu.current_balance AS system_balance,
                pcu.maximum_limit,
                pcu.custodian_id,
                u_custodian.name AS custodian_name,
                pcu.supervisor_id,
                u_supervisor.name AS supervisor_name,
                COALESCE(SUM(CASE 
                    WHEN pcr.entry_type = 'disbursement' 
                    AND pcr.status = 'posted'
                    THEN pcr.amount 
                    ELSE 0 
                END), 0) AS total_disbursed,
                COALESCE(SUM(CASE 
                    WHEN pcr.entry_type = 'replenishment' 
                    AND pcr.status = 'posted'
                    THEN pcr.amount 
                    ELSE 0 
                END), 0) AS total_replenished,
                (pcu.float_amount - 
                 COALESCE(SUM(CASE 
                     WHEN pcr.entry_type = 'disbursement' 
                     AND pcr.status = 'posted'
                     THEN pcr.amount 
                     ELSE 0 
                 END), 0) + 
                 COALESCE(SUM(CASE 
                     WHEN pcr.entry_type = 'replenishment' 
                     AND pcr.status = 'posted'
                     THEN pcr.amount 
                     ELSE 0 
                 END), 0)) AS closing_cash_calculated,
                (pcu.float_amount - 
                 COALESCE(SUM(CASE 
                     WHEN pcr.entry_type = 'disbursement' 
                     AND pcr.status = 'posted'
                     THEN pcr.amount 
                     ELSE 0 
                 END), 0) + 
                 COALESCE(SUM(CASE 
                     WHEN pcr.entry_type = 'replenishment' 
                     AND pcr.status = 'posted'
                     THEN pcr.amount 
                     ELSE 0 
                 END), 0) - 
                 pcu.current_balance) AS variance,
                COUNT(DISTINCT CASE 
                    WHEN pcr.entry_type = 'disbursement' 
                    AND pcr.status != 'posted'
                    THEN pcr.id 
                END) AS outstanding_vouchers_count,
                COALESCE(SUM(CASE 
                    WHEN pcr.entry_type = 'disbursement' 
                    AND pcr.status != 'posted'
                    THEN pcr.amount 
                    ELSE 0 
                END), 0) AS outstanding_vouchers_amount,
                MAX(pcr.register_date) AS last_transaction_date,
                MIN(pcr.register_date) AS first_transaction_date,
                pcu.is_active,
                pcu.created_at AS unit_created_at,
                pcu.updated_at AS unit_updated_at
            FROM petty_cash_units pcu
            LEFT JOIN petty_cash_register pcr ON pcr.petty_cash_unit_id = pcu.id
            LEFT JOIN users u_custodian ON u_custodian.id = pcu.custodian_id
            LEFT JOIN users u_supervisor ON u_supervisor.id = pcu.supervisor_id
            WHERE pcu.deleted_at IS NULL
            GROUP BY 
                pcu.id, 
                pcu.company_id, 
                pcu.branch_id, 
                pcu.name, 
                pcu.code, 
                pcu.float_amount, 
                pcu.current_balance, 
                pcu.maximum_limit,
                pcu.custodian_id,
                u_custodian.name,
                pcu.supervisor_id,
                u_supervisor.name,
                pcu.is_active,
                pcu.created_at,
                pcu.updated_at
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS petty_cash_reconciliation_view");
    }
};
