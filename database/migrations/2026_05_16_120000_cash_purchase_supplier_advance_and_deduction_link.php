<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_purchases', function (Blueprint $table) {
            $table->decimal('supplier_advance_applied_amount', 15, 2)->default(0)->after('discount_amount');
        });

        Schema::table('supplier_advance_deductions', function (Blueprint $table) {
            $table->foreignId('supplier_advance_id')->nullable()->after('supplier_id')->constrained('supplier_advances')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('supplier_advance_deductions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_advance_id');
        });

        Schema::table('cash_purchases', function (Blueprint $table) {
            $table->dropColumn('supplier_advance_applied_amount');
        });
    }
};
