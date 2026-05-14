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
        Schema::create('credit_note_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_note_id')->constrained('credit_notes')->onDelete('cascade');
            $table->foreignId('sales_invoice_id')->nullable()->constrained('sales_invoices')->onDelete('cascade');
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->onDelete('set null');
            
            // Application details
            $table->decimal('amount_applied', 15, 2);
            $table->enum('application_type', ['invoice', 'refund', 'credit_balance'])->default('invoice');
            $table->date('application_date');
            $table->text('description')->nullable();
            
            // FX and currency
            $table->string('currency', 3)->default('TZS');
            $table->decimal('exchange_rate', 15, 6)->default(1.000000);
            $table->decimal('fx_gain_loss', 15, 2)->default(0);
            
            // Reference and tracking
            $table->string('reference_number')->nullable();
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            
            // User tracking
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['credit_note_id', 'application_type']);
            $table->index(['sales_invoice_id', 'application_type']);
            $table->index('application_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_note_applications');
    }
}; 