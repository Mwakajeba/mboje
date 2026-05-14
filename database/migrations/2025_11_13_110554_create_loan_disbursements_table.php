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
        Schema::create('loan_disbursements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');
            $table->date('disb_date'); // Disbursement date
            $table->decimal('amount_received', 15, 2); // Gross amount received
            $table->decimal('net_proceeds', 15, 2); // Net amount after deductions
            $table->unsignedBigInteger('bank_account_id')->nullable(); // Bank account where funds were received
            $table->string('ref_number')->nullable(); // Reference number from bank
            $table->decimal('bank_charges', 15, 2)->default(0); // Bank charges deducted
            $table->text('narration')->nullable(); // Notes/description
            $table->unsignedBigInteger('journal_id')->nullable(); // GL journal entry reference
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('loan_id')->references('id')->on('loans')->onDelete('cascade');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('set null');
            $table->foreign('journal_id')->references('id')->on('journals')->onDelete('set null');
            
            // Indexes
            $table->index(['loan_id', 'disb_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_disbursements');
    }
};
