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
        Schema::table('payments', function (Blueprint $table) {
            // Add currency and FX fields
            if (!Schema::hasColumn('payments', 'currency')) {
                $table->string('currency', 3)->default('TZS')->after('amount')->comment('Transaction currency');
            }
            if (!Schema::hasColumn('payments', 'exchange_rate')) {
                $table->decimal('exchange_rate', 15, 6)->default(1.000000)->after('currency');
            }
            if (!Schema::hasColumn('payments', 'amount_fcy')) {
                $table->decimal('amount_fcy', 20, 2)->nullable()->after('exchange_rate')->comment('Foreign currency amount');
            }
            if (!Schema::hasColumn('payments', 'amount_lcy')) {
                $table->decimal('amount_lcy', 20, 2)->nullable()->after('amount_fcy')->comment('Local currency (functional currency) amount');
            }
            if (!Schema::hasColumn('payments', 'fx_gain_loss')) {
                $table->decimal('fx_gain_loss', 20, 2)->default(0)->after('amount_lcy')->comment('Realized FX gain/loss');
            }
            if (!Schema::hasColumn('payments', 'fx_rate_used')) {
                $table->decimal('fx_rate_used', 15, 6)->nullable()->after('fx_gain_loss')->comment('FX rate used at transaction date');
            }
            if (!Schema::hasColumn('payments', 'payment_currency')) {
                $table->string('payment_currency', 3)->nullable()->after('fx_rate_used')->comment('Currency in which payment was actually made');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['currency', 'exchange_rate', 'amount_fcy', 'amount_lcy', 'fx_gain_loss', 'fx_rate_used', 'payment_currency']);
        });
    }
};

