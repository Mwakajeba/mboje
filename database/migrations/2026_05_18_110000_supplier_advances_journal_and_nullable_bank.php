<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('supplier_advances')) {
            return;
        }

        Schema::table('supplier_advances', function (Blueprint $table) {
            if (! Schema::hasColumn('supplier_advances', 'journal_id')) {
                $table->foreignId('journal_id')
                    ->nullable()
                    ->after('bank_account_id')
                    ->constrained('journals')
                    ->nullOnDelete();
            }
        });

        Schema::table('supplier_advances', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_advances', 'bank_account_id')) {
                $table->foreignId('bank_account_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('supplier_advances')) {
            return;
        }

        Schema::table('supplier_advances', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_advances', 'journal_id')) {
                $table->dropForeign(['journal_id']);
                $table->dropColumn('journal_id');
            }
        });
    }
};
