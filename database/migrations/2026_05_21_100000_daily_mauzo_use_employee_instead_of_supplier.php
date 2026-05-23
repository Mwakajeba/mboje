<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('daily_mauzo_records', 'supplier_id')) {
            $foreignKeys = collect(DB::select(
                "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'daily_mauzo_records'
                   AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                   AND CONSTRAINT_NAME LIKE '%supplier_id%'"
            ))->pluck('CONSTRAINT_NAME');

            foreach ($foreignKeys as $name) {
                DB::statement('ALTER TABLE `daily_mauzo_records` DROP FOREIGN KEY `'.$name.'`');
            }

            Schema::table('daily_mauzo_records', function (Blueprint $table) {
                $table->dropColumn('supplier_id');
            });
        }

        if (! Schema::hasColumn('daily_mauzo_records', 'employee_id')) {
            Schema::table('daily_mauzo_records', function (Blueprint $table) {
                if (Schema::hasTable('hr_employees')) {
                    $table->foreignId('employee_id')->after('branch_id')->constrained('hr_employees')->cascadeOnDelete();
                } else {
                    $table->foreignId('employee_id')->after('branch_id')->constrained('users')->cascadeOnDelete();
                }

                $table->index(['company_id', 'employee_id']);
                $table->index(['employee_id', 'entry_date']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('daily_mauzo_records', 'employee_id')) {
            Schema::table('daily_mauzo_records', function (Blueprint $table) {
                $table->dropForeign(['employee_id']);
                $table->dropIndex(['company_id', 'employee_id']);
                $table->dropIndex(['employee_id', 'entry_date']);
                $table->dropColumn('employee_id');
            });
        }

        if (! Schema::hasColumn('daily_mauzo_records', 'supplier_id')) {
            Schema::table('daily_mauzo_records', function (Blueprint $table) {
                $table->foreignId('supplier_id')->after('branch_id')->constrained('suppliers')->cascadeOnDelete();
                $table->index(['company_id', 'supplier_id']);
                $table->index(['supplier_id', 'entry_date']);
            });
        }
    }
};
