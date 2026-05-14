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
        Schema::create('loan_restructure_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');
            $table->date('restructure_date'); // Date when restructuring occurred
            $table->text('reason')->nullable(); // Reason for restructuring
            $table->text('new_terms_summary')->nullable(); // Summary of new terms
            $table->json('old_terms')->nullable(); // Store old terms as JSON
            $table->json('new_terms')->nullable(); // Store new terms as JSON
            $table->unsignedBigInteger('approved_by')->nullable(); // User who approved the restructuring
            $table->text('approval_notes')->nullable();
            $table->json('attachments')->nullable(); // Supporting documents
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('loan_id')->references('id')->on('loans')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index(['loan_id', 'restructure_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_restructure_history');
    }
};
