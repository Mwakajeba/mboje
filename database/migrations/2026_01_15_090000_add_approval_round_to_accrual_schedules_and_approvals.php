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
            if (!Schema::hasColumn('accrual_schedules', 'approval_round')) {
                $table->unsignedInteger('approval_round')->default(1)->after('status');
                $table->index(['company_id', 'approval_round', 'status'], 'accrual_schedules_company_round_status_idx');
            }
        });

        Schema::table('accrual_approvals', function (Blueprint $table) {
            if (!Schema::hasColumn('accrual_approvals', 'approval_round')) {
                $table->unsignedInteger('approval_round')->default(1)->after('approver_id');
                $table->index(['accrual_schedule_id', 'approval_round', 'approval_level'], 'accrual_approvals_schedule_round_level_idx');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accrual_approvals', function (Blueprint $table) {
            if (Schema::hasColumn('accrual_approvals', 'approval_round')) {
                $table->dropIndex('accrual_approvals_schedule_round_level_idx');
                $table->dropColumn('approval_round');
            }
        });

        Schema::table('accrual_schedules', function (Blueprint $table) {
            if (Schema::hasColumn('accrual_schedules', 'approval_round')) {
                $table->dropIndex('accrual_schedules_company_round_status_idx');
                $table->dropColumn('approval_round');
            }
        });
    }
};

