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
        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->id('period_id');
            $table->unsignedBigInteger('fy_id');
            $table->string('period_label', 20); // "2026-01" or "Q1-2026"
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('period_type', ['MONTH', 'QUARTER', 'YEAR'])->default('MONTH');
            $table->string('status', 20)->default('OPEN'); // OPEN, CLOSED, LOCKED
            $table->unsignedBigInteger('locked_by')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->foreign('fy_id')->references('fy_id')->on('fiscal_years')->onDelete('cascade');
            $table->foreign('locked_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['fy_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_periods');
    }
};
