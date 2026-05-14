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
        Schema::create('retirement_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('retirement_id');
            $table->unsignedBigInteger('chart_account_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id');
            
            // Amount comparison (requested vs actual used)
            $table->decimal('requested_amount', 15, 2); // Amount originally requested for this expense
            $table->decimal('actual_amount', 15, 2);    // Amount actually spent
            $table->text('description')->nullable();     // Description of the expense
            $table->text('notes')->nullable();           // Additional notes/explanation
            
            // Audit fields
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('retirement_id')->references('id')->on('retirements')->onDelete('cascade');
            $table->foreign('chart_account_id')->references('id')->on('chart_accounts')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index(['retirement_id']);
            $table->index(['chart_account_id']);
            $table->index(['company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retirement_items');
    }
};
