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
        Schema::create('debit_note_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debit_note_id')->constrained('debit_notes')->onDelete('cascade');
            $table->foreignId('purchase_invoice_id')->nullable()->constrained('purchase_invoices')->onDelete('cascade');
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->onDelete('set null');
            
            // Application details
            $table->decimal('amount_applied', 15, 2);
            $table->enum('application_type', ['invoice', 'refund', 'debit_balance'])->default('invoice');
            $table->date('application_date');
            $table->text('description')->nullable();
            
            // Currency and FX
            $table->string('currency', 3)->default('TZS');
            $table->decimal('exchange_rate', 10, 6)->default(1.000000);
            $table->decimal('fx_gain_loss', 15, 2)->default(0);
            
            // Reference and payment
            $table->string('reference_number')->nullable();
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            
            // Branch and user tracking
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debit_note_applications');
    }
};