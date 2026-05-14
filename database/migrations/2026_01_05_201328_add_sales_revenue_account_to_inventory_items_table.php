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
        Schema::table('inventory_items', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_items', 'has_different_sales_revenue_account')) {
                $table->boolean('has_different_sales_revenue_account')->default(false)->after('track_expiry');
            }
            if (!Schema::hasColumn('inventory_items', 'sales_revenue_account_id')) {
                $table->unsignedBigInteger('sales_revenue_account_id')->nullable()->after('has_different_sales_revenue_account');
                $table->foreign('sales_revenue_account_id')->references('id')->on('chart_accounts')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_items', 'sales_revenue_account_id')) {
                $table->dropForeign(['sales_revenue_account_id']);
                $table->dropColumn('sales_revenue_account_id');
            }
            if (Schema::hasColumn('inventory_items', 'has_different_sales_revenue_account')) {
                $table->dropColumn('has_different_sales_revenue_account');
            }
        });
    }
};
