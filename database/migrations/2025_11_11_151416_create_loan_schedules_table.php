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
        Schema::create('loan_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');
            $table->integer('installment_no');
            $table->date('due_date');
            
            // Principal amounts
            $table->decimal('opening_principal', 15, 2);
            $table->decimal('principal_due', 15, 2)->default(0);
            $table->decimal('principal_paid', 15, 2)->default(0);
            $table->decimal('closing_principal', 15, 2);
            
            // Interest amounts
            $table->decimal('interest_due', 15, 2);
            $table->decimal('interest_paid', 15, 2)->default(0);
            $table->decimal('interest_rate', 5, 2)->nullable(); // Rate applicable for this installment (for variable rates)
            
            // Total installment
            $table->decimal('installment_amount', 15, 2);
            $table->decimal('amount_paid', 15, 2)->default(0);
            
            // Status
            $table->enum('status', ['due', 'paid', 'partial', 'overdue', 'skipped'])->default('due');
            $table->date('paid_date')->nullable();
            
            // Tracking
            $table->integer('days_overdue')->default(0);
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('loan_id')->references('id')->on('loans')->onDelete('cascade');
            
            // Indexes
            $table->index(['loan_id', 'installment_no']);
            $table->index('due_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_schedules');
    }
};
