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
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropColumn(['vat_rate', 'vat_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->decimal('vat_rate', 8, 2)->nullable()->after('payment_days');
            $table->enum('vat_type', ['inclusive', 'exclusive'])->nullable()->after('vat_rate');
        });
    }
};
