<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_proformas', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_proformas', 'currency')) {
                $table->string('currency', 3)->default('TZS')->after('subtotal');
            }

            if (!Schema::hasColumn('sales_proformas', 'exchange_rate')) {
                $table->decimal('exchange_rate', 15, 6)->default(1.000000)->after('currency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_proformas', function (Blueprint $table) {
            if (Schema::hasColumn('sales_proformas', 'exchange_rate')) {
                $table->dropColumn('exchange_rate');
            }

            if (Schema::hasColumn('sales_proformas', 'currency')) {
                $table->dropColumn('currency');
            }
        });
    }
};
