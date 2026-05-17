<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_advance_stock_lines', function (Blueprint $table) {
            $table->string('idadi', 255)->default('')->change();
        });
    }

    public function down(): void
    {
        Schema::table('supplier_advance_stock_lines', function (Blueprint $table) {
            $table->decimal('idadi', 15, 4)->default(0)->change();
        });
    }
};
