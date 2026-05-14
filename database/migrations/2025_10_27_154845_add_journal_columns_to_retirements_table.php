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
        Schema::table('retirements', function (Blueprint $table) {
            $table->bigInteger('journal_id')->unsigned()->nullable()->after('approved_at');
            $table->bigInteger('closed_by')->unsigned()->nullable()->after('journal_id');
            $table->timestamp('closed_at')->nullable()->after('closed_by');
            
            $table->foreign('journal_id')->references('id')->on('journals')->onDelete('set null');
            $table->foreign('closed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('retirements', function (Blueprint $table) {
            $table->dropForeign(['journal_id']);
            $table->dropForeign(['closed_by']);
            $table->dropColumn(['journal_id', 'closed_by', 'closed_at']);
        });
    }
};
