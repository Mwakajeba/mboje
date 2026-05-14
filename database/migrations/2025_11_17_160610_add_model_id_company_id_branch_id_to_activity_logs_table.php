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
        Schema::table('activity_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('activity_logs', 'model_id')) {
                $table->unsignedBigInteger('model_id')->nullable()->after('model');
                $table->index('model_id');
            }
            if (!Schema::hasColumn('activity_logs', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('user_id');
                $table->index('company_id');
            }
            if (!Schema::hasColumn('activity_logs', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('company_id');
                $table->index('branch_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            if (Schema::hasColumn('activity_logs', 'model_id')) {
                $table->dropIndex(['model_id']);
                $table->dropColumn('model_id');
            }
            if (Schema::hasColumn('activity_logs', 'company_id')) {
                $table->dropIndex(['company_id']);
                $table->dropColumn('company_id');
            }
            if (Schema::hasColumn('activity_logs', 'branch_id')) {
                $table->dropIndex(['branch_id']);
                $table->dropColumn('branch_id');
            }
        });
    }
};
