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
        Schema::create('period_snapshots', function (Blueprint $table) {
            $table->id('snapshot_id');
            $table->unsignedBigInteger('close_id');
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('period_id');
            $table->decimal('opening_balance', 18, 2)->default(0);
            $table->decimal('period_debits', 18, 2)->default(0);
            $table->decimal('period_credits', 18, 2)->default(0);
            $table->decimal('closing_balance', 18, 2)->default(0);
            $table->timestamps();

            $table->foreign('close_id')->references('close_id')->on('close_batches')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('chart_accounts')->onDelete('cascade');
            $table->foreign('period_id')->references('period_id')->on('accounting_periods')->onDelete('cascade');
            $table->unique(['close_id', 'account_id']);
            $table->index(['period_id', 'account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('period_snapshots');
    }
};
