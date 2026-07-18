<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_storage_balances') && ! Schema::hasColumn('customer_storage_balances', 'mazunguko')) {
            Schema::table('customer_storage_balances', function (Blueprint $table) {
                $table->unsignedInteger('mazunguko')->default(1)->after('inventory_item_id');
            });

            Schema::table('customer_storage_balances', function (Blueprint $table) {
                $table->dropUnique('customer_storage_balances_unique');
            });

            Schema::table('customer_storage_balances', function (Blueprint $table) {
                $table->unique(
                    ['company_id', 'branch_id', 'customer_id', 'inventory_item_id', 'mazunguko'],
                    'customer_storage_balances_unique'
                );
            });
        }

        foreach (['customer_storage_receipts', 'customer_storage_withdrawals', 'customer_storage_sales'] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'mazunguko')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedInteger('mazunguko')->default(1)->after('inventory_item_id')->index();
                });
            }
        }

        foreach (['customer_storage_mapato', 'customer_storage_gharama', 'customer_storage_malipo'] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'mazunguko')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedInteger('mazunguko')->default(1)->after('inventory_item_id')->index();
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customer_storage_balances') && Schema::hasColumn('customer_storage_balances', 'mazunguko')) {
            Schema::table('customer_storage_balances', function (Blueprint $table) {
                $table->dropUnique('customer_storage_balances_unique');
            });

            Schema::table('customer_storage_balances', function (Blueprint $table) {
                $table->dropColumn('mazunguko');
            });

            Schema::table('customer_storage_balances', function (Blueprint $table) {
                $table->unique(
                    ['company_id', 'branch_id', 'customer_id', 'inventory_item_id'],
                    'customer_storage_balances_unique'
                );
            });
        }

        foreach ([
            'customer_storage_receipts',
            'customer_storage_withdrawals',
            'customer_storage_sales',
            'customer_storage_mapato',
            'customer_storage_gharama',
            'customer_storage_malipo',
        ] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'mazunguko')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->dropColumn('mazunguko');
                });
            }
        }
    }
};
