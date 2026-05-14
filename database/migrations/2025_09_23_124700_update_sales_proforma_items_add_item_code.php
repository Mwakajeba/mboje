<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_proforma_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_proforma_items', 'item_code')) {
                $table->string('item_code')->nullable()->after('item_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_proforma_items', function (Blueprint $table) {
            if (Schema::hasColumn('sales_proforma_items', 'item_code')) {
                $table->dropColumn('item_code');
            }
        });
    }
};


