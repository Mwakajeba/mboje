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
            // Add currency and FX fields
            // Note: currency_id already exists, we're adding currency string and FX fields
            if (!Schema::hasColumn('loans', 'currency')) {
                $table->string('currency', 3)->default('TZS')->after('currency_id')->comment('Currency code of the loan');
            }
            if (!Schema::hasColumn('loans', 'exchange_rate')) {
                $table->decimal('exchange_rate', 15, 6)->default(1.000000)->after('currency')->comment('Exchange rate at disbursement');
            }
            if (!Schema::hasColumn('loans', 'amount_fcy')) {
                $table->decimal('amount_fcy', 20, 2)->nullable()->after('exchange_rate')->comment('Foreign currency principal amount');
            }
            if (!Schema::hasColumn('loans', 'amount_lcy')) {
                $table->decimal('amount_lcy', 20, 2)->nullable()->after('amount_fcy')->comment('Local currency (functional currency) principal amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['currency', 'exchange_rate', 'amount_fcy', 'amount_lcy']);
        });
    }
};

