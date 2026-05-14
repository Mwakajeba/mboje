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
        Schema::table('imprest_settings', function (Blueprint $table) {
            $table->integer('retirement_period_days')->nullable()->after('imprest_receivables_account');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('imprest_settings', function (Blueprint $table) {
            $table->dropColumn('retirement_period_days');
        });
    }
};
