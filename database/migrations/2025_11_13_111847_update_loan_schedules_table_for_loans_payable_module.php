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
        Schema::table('loan_schedules', function (Blueprint $table) {
            // Add period_no (alias for installment_no, but keeping both for compatibility)
            if (!Schema::hasColumn('loan_schedules', 'period_no')) {
                $table->integer('period_no')->nullable()->after('loan_id');
            }
            
            // Add period_start and period_end
            if (!Schema::hasColumn('loan_schedules', 'period_start')) {
                $table->date('period_start')->nullable()->after('due_date');
            }
            if (!Schema::hasColumn('loan_schedules', 'period_end')) {
                $table->date('period_end')->nullable()->after('period_start');
            }
            
            // Add total_due (sum of principal_due + interest_due)
            if (!Schema::hasColumn('loan_schedules', 'total_due')) {
                $table->decimal('total_due', 15, 2)->nullable()->after('interest_due');
            }
            
            // Add opening_balance and closing_balance (aliases for opening_principal and closing_principal)
            if (!Schema::hasColumn('loan_schedules', 'opening_balance')) {
                $table->decimal('opening_balance', 15, 2)->nullable()->after('opening_principal');
            }
            if (!Schema::hasColumn('loan_schedules', 'closing_balance')) {
                $table->decimal('closing_balance', 15, 2)->nullable()->after('closing_principal');
            }
        });
        
        // Update status enum to include 'Scheduled' and 'Adjusted'
        // Note: We can't modify enum directly, so we'll need to handle this in application logic
        // The existing enum values are: 'due', 'paid', 'partial', 'overdue', 'skipped'
        // We'll map: 'due' = 'Scheduled', 'paid' = 'Paid', 'partial' = 'Partial', 'overdue' = 'Overdue', 'skipped' = 'Adjusted'
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_schedules', function (Blueprint $table) {
            $columns = [
                'period_no',
                'period_start',
                'period_end',
                'total_due',
                'opening_balance',
                'closing_balance',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('loan_schedules', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
