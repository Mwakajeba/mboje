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
        Schema::create('loan_covenants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');
            $table->string('covenant_type'); // e.g., "Debt-to-Equity Ratio", "Current Ratio", "Minimum Cash Balance"
            $table->string('covenant_name'); // Human-readable name
            $table->text('description')->nullable(); // Description of the covenant
            $table->decimal('threshold_value', 15, 2)->nullable(); // Required threshold value
            $table->enum('comparison_operator', ['>=', '<=', '=', '>', '<'])->default('>='); // How to compare
            $table->decimal('actual_value', 15, 2)->nullable(); // Actual value from financials
            $table->date('period'); // Period for which covenant is measured
            $table->enum('status', ['compliant', 'non_compliant', 'pending', 'waived'])->default('pending');
            $table->text('notes')->nullable();
            $table->date('next_review_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('loan_id')->references('id')->on('loans')->onDelete('cascade');
            
            // Indexes
            $table->index(['loan_id', 'period']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_covenants');
    }
};
