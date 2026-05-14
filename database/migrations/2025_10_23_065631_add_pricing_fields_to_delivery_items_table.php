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
        Schema::table('delivery_items', function (Blueprint $table) {
            $table->decimal('unit_price', 10, 2)->default(0)->after('unit_of_measure');
            $table->string('vat_type', 20)->default('inclusive')->after('unit_price');
            $table->decimal('vat_rate', 5, 2)->default(0)->after('vat_type');
            $table->decimal('vat_amount', 10, 2)->default(0)->after('vat_rate');
            $table->decimal('line_total', 10, 2)->default(0)->after('vat_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_items', function (Blueprint $table) {
            $table->dropColumn(['unit_price', 'vat_type', 'vat_rate', 'vat_amount', 'line_total']);
        });
    }
};
