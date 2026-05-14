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
        Schema::create('close_adjustments', function (Blueprint $table) {
            $table->id('adj_id');
            $table->unsignedBigInteger('close_id');
            $table->date('adj_date');
            $table->unsignedBigInteger('gl_debit_account');
            $table->unsignedBigInteger('gl_credit_account');
            $table->decimal('amount', 18, 2);
            $table->text('description');
            $table->string('source_document', 200)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('posted_journal_id')->nullable();
            $table->timestamps();

            $table->foreign('close_id')->references('close_id')->on('close_batches')->onDelete('cascade');
            $table->foreign('gl_debit_account')->references('id')->on('chart_accounts')->onDelete('restrict');
            $table->foreign('gl_credit_account')->references('id')->on('chart_accounts')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('posted_journal_id')->references('id')->on('journals')->onDelete('set null');
            $table->index(['close_id', 'adj_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('close_adjustments');
    }
};
