<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_proforma_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_proforma_items', 'vat_type')) {
                $table->enum('vat_type', ['no_vat','exclusive','inclusive'])->default('no_vat')->after('unit_price');
            }
            if (!Schema::hasColumn('sales_proforma_items', 'line_total')) {
                $table->decimal('line_total', 15, 2)->after('subtotal');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_proforma_items', function (Blueprint $table) {
            if (Schema::hasColumn('sales_proforma_items', 'line_total')) {
                $table->dropColumn('line_total');
            }
            if (Schema::hasColumn('sales_proforma_items', 'vat_type')) {
                $table->dropColumn('vat_type');
            }
        });
    }
};


