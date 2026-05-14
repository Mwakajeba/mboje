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
        Schema::create('loan_accruals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');
            $table->date('accrual_date'); // Date for which interest is accrued
            $table->decimal('interest_accrued', 15, 2); // Interest amount accrued
            $table->decimal('opening_balance', 15, 2); // Loan balance at start of period
            $table->decimal('interest_rate', 5, 2); // Interest rate used for calculation
            $table->integer('days_in_period')->default(0); // Number of days in accrual period
            $table->string('calculation_basis')->nullable(); // ACT/365, ACT/360, 30/360
            $table->boolean('posted_flag')->default(false); // Whether GL entry has been posted
            $table->unsignedBigInteger('journal_id')->nullable(); // GL journal entry reference
            $table->string('journal_ref')->nullable(); // Journal reference number
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('loan_id')->references('id')->on('loans')->onDelete('cascade');
            $table->foreign('journal_id')->references('id')->on('journals')->onDelete('set null');
            
            // Indexes
            $table->index(['loan_id', 'accrual_date']);
            $table->index('posted_flag');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_accruals');
    }
};
