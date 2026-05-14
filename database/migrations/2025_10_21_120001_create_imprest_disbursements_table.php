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
        Schema::create('imprest_disbursements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('imprest_request_id');
            $table->string('disbursement_number')->unique();
            $table->decimal('amount_issued', 15, 2);
            $table->enum('payment_mode', ['cash', 'bank_transfer', 'cheque', 'mobile_money']);
            $table->string('reference_number')->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->unsignedBigInteger('issued_by');
            $table->timestamp('issued_at');
            $table->text('disbursement_notes')->nullable();
            $table->string('cheque_number')->nullable();
            $table->timestamps();

            $table->foreign('imprest_request_id')->references('id')->on('imprest_requests')->onDelete('cascade');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts');
            $table->foreign('issued_by')->references('id')->on('users');

            $table->index(['imprest_request_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imprest_disbursements');
    }
};