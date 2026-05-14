<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('inventory_items', 'has_wholesale')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->boolean('has_wholesale')->default(false)->after('unit_price');
            });
        }
        if (! Schema::hasColumn('inventory_items', 'wholesale_unit_price')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $after = Schema::hasColumn('inventory_items', 'has_wholesale') ? 'has_wholesale' : 'unit_price';
                $table->decimal('wholesale_unit_price', 15, 2)->nullable()->after($after);
            });
        }

        if (Schema::hasTable('inventory_item_prices') && ! Schema::hasColumn('inventory_item_prices', 'wholesale_unit_price')) {
            Schema::table('inventory_item_prices', function (Blueprint $table) {
                $table->decimal('wholesale_unit_price', 15, 2)->nullable()->after('unit_price');
            });
        }

        if (Schema::hasTable('inventory_item_location_prices') && ! Schema::hasColumn('inventory_item_location_prices', 'wholesale_unit_price')) {
            Schema::table('inventory_item_location_prices', function (Blueprint $table) {
                $table->decimal('wholesale_unit_price', 15, 2)->nullable()->after('unit_price');
            });
        }

        if (! Schema::hasColumn('sales_invoice_items', 'price_tier')) {
            Schema::table('sales_invoice_items', function (Blueprint $table) {
                $table->string('price_tier', 20)->nullable()->after('unit_price');
            });
        }

        if (! Schema::hasColumn('pos_sale_items', 'price_tier')) {
            Schema::table('pos_sale_items', function (Blueprint $table) {
                $table->string('price_tier', 20)->nullable()->after('unit_price');
            });
        }

        if (! Schema::hasColumn('cash_sale_items', 'price_tier')) {
            Schema::table('cash_sale_items', function (Blueprint $table) {
                $table->string('price_tier', 20)->nullable()->after('unit_price');
            });
        }
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn(['has_wholesale', 'wholesale_unit_price']);
        });

        if (Schema::hasTable('inventory_item_prices')) {
            Schema::table('inventory_item_prices', function (Blueprint $table) {
                $table->dropColumn('wholesale_unit_price');
            });
        }

        if (Schema::hasTable('inventory_item_location_prices')) {
            Schema::table('inventory_item_location_prices', function (Blueprint $table) {
                $table->dropColumn('wholesale_unit_price');
            });
        }

        Schema::table('sales_invoice_items', function (Blueprint $table) {
            $table->dropColumn('price_tier');
        });

        Schema::table('pos_sale_items', function (Blueprint $table) {
            $table->dropColumn('price_tier');
        });

        Schema::table('cash_sale_items', function (Blueprint $table) {
            $table->dropColumn('price_tier');
        });
    }
};
