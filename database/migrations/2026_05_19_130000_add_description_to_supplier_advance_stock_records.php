<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_advance_stock_records', function (Blueprint $table) {
            $table->text('description')->nullable()->after('entry_date');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_advance_stock_records', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
