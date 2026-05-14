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
        Schema::table('purchase_invoices', function (Blueprint $table) {
            // Add FX fields if they don't exist
            // Note: currency and exchange_rate already exist
            if (!Schema::hasColumn('purchase_invoices', 'amount_fcy')) {
                $table->decimal('amount_fcy', 20, 2)->nullable()->after('exchange_rate')->comment('Foreign currency amount');
            }
            if (!Schema::hasColumn('purchase_invoices', 'amount_lcy')) {
                $table->decimal('amount_lcy', 20, 2)->nullable()->after('amount_fcy')->comment('Local currency (functional currency) amount');
            }
            if (!Schema::hasColumn('purchase_invoices', 'fx_gain_loss')) {
                $table->decimal('fx_gain_loss', 20, 2)->default(0)->after('amount_lcy')->comment('Realized FX gain/loss');
            }
            if (!Schema::hasColumn('purchase_invoices', 'fx_rate_used')) {
                $table->decimal('fx_rate_used', 15, 6)->nullable()->after('fx_gain_loss')->comment('FX rate used at transaction date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropColumn(['amount_fcy', 'amount_lcy', 'fx_gain_loss', 'fx_rate_used']);
        });
    }
};

