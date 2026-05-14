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
        Schema::table('petty_cash_settings', function (Blueprint $table) {
            // Approval configuration
            $table->boolean('approval_required')->default(false)->after('auto_approve_below_threshold');
            $table->integer('approval_levels')->default(2)->after('approval_required');
            $table->decimal('auto_approval_limit', 15, 2)->nullable()->after('approval_levels');
            $table->decimal('approval_threshold_1', 15, 2)->nullable()->after('auto_approval_limit');
            $table->decimal('approval_threshold_2', 15, 2)->nullable()->after('approval_threshold_1');
            $table->decimal('approval_threshold_3', 15, 2)->nullable()->after('approval_threshold_2');
            $table->decimal('approval_threshold_4', 15, 2)->nullable()->after('approval_threshold_3');
            $table->decimal('approval_threshold_5', 15, 2)->nullable()->after('approval_threshold_4');
            $table->integer('escalation_time')->default(24)->after('approval_threshold_5'); // hours
            $table->boolean('require_approval_for_all')->default(false)->after('escalation_time');
            
            // Level 1 approval settings
            $table->string('level1_approval_type')->nullable()->default('role')->after('require_approval_for_all'); // role or user
            $table->json('level1_approvers')->nullable()->after('level1_approval_type'); // array of role names or user IDs
            
            // Level 2 approval settings
            $table->string('level2_approval_type')->nullable()->after('level1_approvers');
            $table->json('level2_approvers')->nullable()->after('level2_approval_type');
            
            // Level 3 approval settings
            $table->string('level3_approval_type')->nullable()->after('level2_approvers');
            $table->json('level3_approvers')->nullable()->after('level3_approval_type');
            
            // Level 4 approval settings
            $table->string('level4_approval_type')->nullable()->after('level3_approvers');
            $table->json('level4_approvers')->nullable()->after('level4_approval_type');
            
            // Level 5 approval settings
            $table->string('level5_approval_type')->nullable()->after('level4_approvers');
            $table->json('level5_approvers')->nullable()->after('level5_approval_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('petty_cash_settings', function (Blueprint $table) {
            $table->dropColumn([
                'approval_required',
                'approval_levels',
                'auto_approval_limit',
                'approval_threshold_1',
                'approval_threshold_2',
                'approval_threshold_3',
                'approval_threshold_4',
                'approval_threshold_5',
                'escalation_time',
                'require_approval_for_all',
                'level1_approval_type',
                'level1_approvers',
                'level2_approval_type',
                'level2_approvers',
                'level3_approval_type',
                'level3_approvers',
                'level4_approval_type',
                'level4_approvers',
                'level5_approval_type',
                'level5_approvers',
            ]);
        });
    }
};
