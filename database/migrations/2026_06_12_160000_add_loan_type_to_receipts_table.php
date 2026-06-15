<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('receipts') && ! Schema::hasColumn('receipts', 'loan_type')) {
            Schema::table('receipts', function (Blueprint $table) {
                $table->string('loan_type', 100)->nullable()->after('description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('receipts') && Schema::hasColumn('receipts', 'loan_type')) {
            Schema::table('receipts', function (Blueprint $table) {
                $table->dropColumn('loan_type');
            });
        }
    }
};
