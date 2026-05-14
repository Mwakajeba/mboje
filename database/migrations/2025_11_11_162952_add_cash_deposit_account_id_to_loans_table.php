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
        Schema::table('loans', function (Blueprint $table) {
            if (!Schema::hasColumn('loans', 'cash_deposit_account_id')) {
                $table->unsignedBigInteger('cash_deposit_account_id')->nullable()->after('bank_charges_account_id');
                $table->foreign('cash_deposit_account_id')->references('id')->on('cash_deposit_accounts')->onDelete('set null');
                $table->index('cash_deposit_account_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            if (Schema::hasColumn('loans', 'cash_deposit_account_id')) {
                $table->dropForeign(['cash_deposit_account_id']);
                $table->dropIndex(['cash_deposit_account_id']);
                $table->dropColumn('cash_deposit_account_id');
            }
        });
    }
};
