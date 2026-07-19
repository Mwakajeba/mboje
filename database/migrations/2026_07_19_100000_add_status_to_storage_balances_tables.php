<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['permanent_storage_balances', 'customer_storage_balances'] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'status')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->string('status', 20)->default('active')->after('quantity_on_hand')->index();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['permanent_storage_balances', 'customer_storage_balances'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'status')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('status');
                });
            }
        }
    }
};
