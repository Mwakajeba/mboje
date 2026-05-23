<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_stoo_records', function (Blueprint $table) {
            $table->string('bidhaa', 255)->after('entry_date');
        });
    }

    public function down(): void
    {
        Schema::table('daily_stoo_records', function (Blueprint $table) {
            $table->dropColumn('bidhaa');
        });
    }
};
