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
        Schema::table('petty_cash_settings', function (Blueprint $table) {
            $table->decimal('maximum_limit', 15, 2)->nullable()->after('max_transaction_amount')->comment('Maximum allowed balance for petty cash units');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('petty_cash_settings', function (Blueprint $table) {
            $table->dropColumn('maximum_limit');
        });
    }
};
