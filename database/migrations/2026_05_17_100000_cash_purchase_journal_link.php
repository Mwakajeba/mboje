<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('journals') && ! Schema::hasColumn('journals', 'supplier_id')) {
            Schema::table('journals', function (Blueprint $table) {
                $table->foreignId('supplier_id')
                    ->nullable()
                    ->after('customer_id')
                    ->constrained('suppliers')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('cash_purchases') && ! Schema::hasColumn('cash_purchases', 'journal_id')) {
            Schema::table('cash_purchases', function (Blueprint $table) {
                $table->foreignId('journal_id')
                    ->nullable()
                    ->after('supplier_id')
                    ->constrained('journals')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cash_purchases') && Schema::hasColumn('cash_purchases', 'journal_id')) {
            Schema::table('cash_purchases', function (Blueprint $table) {
                $table->dropForeign(['journal_id']);
                $table->dropColumn('journal_id');
            });
        }

        if (Schema::hasTable('journals') && Schema::hasColumn('journals', 'supplier_id')) {
            Schema::table('journals', function (Blueprint $table) {
                $table->dropForeign(['supplier_id']);
                $table->dropColumn('supplier_id');
            });
        }
    }
};
