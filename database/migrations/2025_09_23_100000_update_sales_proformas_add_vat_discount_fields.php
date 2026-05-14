<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_proformas', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_proformas', 'vat_type')) {
                $table->enum('vat_type', ['no_vat','exclusive','inclusive'])->default('no_vat')->after('status');
            }
            if (!Schema::hasColumn('sales_proformas', 'discount_type')) {
                $table->enum('discount_type', ['none','percentage','amount'])->default('none')->after('vat_type');
            }
            if (!Schema::hasColumn('sales_proformas', 'discount_rate')) {
                $table->decimal('discount_rate', 5, 2)->default(0)->after('vat_amount');
            }
            if (!Schema::hasColumn('sales_proformas', 'tax_amount')) {
                $table->decimal('tax_amount', 15, 2)->default(0)->after('vat_amount');
            }
            if (!Schema::hasColumn('sales_proformas', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('company_id');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_proformas', function (Blueprint $table) {
            if (Schema::hasColumn('sales_proformas', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('sales_proformas', 'tax_amount')) {
                $table->dropColumn('tax_amount');
            }
            if (Schema::hasColumn('sales_proformas', 'discount_rate')) {
                $table->dropColumn('discount_rate');
            }
            if (Schema::hasColumn('sales_proformas', 'discount_type')) {
                $table->dropColumn('discount_type');
            }
            if (Schema::hasColumn('sales_proformas', 'vat_type')) {
                $table->dropColumn('vat_type');
            }
        });
    }
};


