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
        Schema::create('lipisha_payment_logs', function (Blueprint $table) {
            $table->id();
            $table->string('bill_number')->nullable()->index();
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('receipt')->nullable();
            $table->string('transaction_ref')->nullable()->index();
            $table->datetime('transaction_date')->nullable();
            $table->string('bill_id')->nullable();
            $table->string('payment_id')->nullable();
            $table->json('metadata')->nullable();
            $table->text('raw_payload')->nullable();
            $table->string('status')->default('pending')->index(); // pending, processed, failed
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['bill_number', 'status']);
            $table->index('transaction_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lipisha_payment_logs');
    }
};












