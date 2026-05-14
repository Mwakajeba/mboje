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
        Schema::table('petty_cash_replenishments', function (Blueprint $table) {
            $table->foreignId('imprest_request_id')->nullable()->constrained('imprest_requests')->onDelete('set null')->after('journal_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('petty_cash_replenishments', function (Blueprint $table) {
            $table->dropForeign(['imprest_request_id']);
            $table->dropColumn('imprest_request_id');
        });
    }
};
