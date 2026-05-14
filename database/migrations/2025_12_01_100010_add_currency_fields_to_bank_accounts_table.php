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
        Schema::table('bank_accounts', function (Blueprint $table) {
            // Add currency fields
            if (!Schema::hasColumn('bank_accounts', 'currency')) {
                $table->string('currency', 3)->default('TZS')->after('account_number')->comment('Currency of the bank account');
            }
            if (!Schema::hasColumn('bank_accounts', 'base_currency')) {
                $table->string('base_currency', 3)->nullable()->after('currency')->comment('Base currency for multi-currency accounts');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropColumn(['currency', 'base_currency']);
        });
    }
};

