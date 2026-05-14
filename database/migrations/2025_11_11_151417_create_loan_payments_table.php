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
        Schema::create('loan_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');
            $table->unsignedBigInteger('loan_schedule_id')->nullable(); // If payment is for a specific installment
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            
            // Allocation
            $table->decimal('allocation_interest', 15, 2)->default(0);
            $table->decimal('allocation_principal', 15, 2)->default(0);
            $table->decimal('allocation_fees', 15, 2)->default(0);
            $table->decimal('allocation_penalty', 15, 2)->default(0);
            
            // Payment method
            $table->unsignedBigInteger('bank_account_id')->nullable(); // Bank account from which payment is made
            $table->string('payment_method')->nullable(); // cash, bank_transfer, cheque, etc.
            $table->string('reference')->nullable(); // Cheque number, transaction reference, etc.
            
            // GL Transaction tracking
            $table->unsignedBigInteger('journal_id')->nullable();
            
            // Metadata
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('loan_id')->references('id')->on('loans')->onDelete('cascade');
            $table->foreign('loan_schedule_id')->references('id')->on('loan_schedules')->onDelete('set null');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('set null');
            $table->foreign('journal_id')->references('id')->on('journals')->onDelete('set null');
            
            // Indexes
            $table->index(['loan_id', 'payment_date']);
            $table->index('loan_schedule_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_payments');
    }
};
