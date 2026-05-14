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
        Schema::create('wht_transactions', function (Blueprint $table) {
            $table->id('wht_id');
            $table->unsignedBigInteger('transaction_id')->nullable(); // Linked to payment or receipt
            $table->string('transaction_type', 50)->nullable(); // 'payment' or 'receipt'
            $table->enum('wht_treatment', ['EXCLUSIVE', 'INCLUSIVE', 'GROSS_UP', 'NONE'])->default('EXCLUSIVE');
            $table->enum('vat_mode', ['EXCLUSIVE', 'INCLUSIVE', 'NONE'])->default('EXCLUSIVE');
            $table->decimal('wht_rate', 5, 2)->default(0);
            $table->decimal('base_amount', 18, 2)->default(0);
            $table->decimal('wht_amount', 18, 2)->default(0);
            $table->decimal('total_cost', 18, 2)->default(0);
            $table->enum('status', ['PENDING', 'REMITTED', 'CERT_RECEIVED'])->default('PENDING');
            $table->string('related_doc', 255)->nullable(); // Reference to certificate or TRA return
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['transaction_id', 'transaction_type']);
            $table->index('status');
            $table->index(['company_id', 'branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wht_transactions');
    }
};
