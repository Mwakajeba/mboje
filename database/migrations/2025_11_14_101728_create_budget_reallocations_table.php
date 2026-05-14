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
        Schema::create('budget_reallocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('budget_id');
            $table->unsignedBigInteger('from_account_id'); // Source account
            $table->unsignedBigInteger('to_account_id');   // Destination account
            $table->decimal('amount', 20, 2);              // Amount being reallocated
            $table->text('reason')->nullable();            // Reason for reallocation
            $table->unsignedBigInteger('user_id');         // User who performed the reallocation
            $table->timestamps();

            $table->foreign('budget_id')->references('id')->on('budgets')->onDelete('cascade');
            $table->foreign('from_account_id')->references('id')->on('chart_accounts')->onDelete('restrict');
            $table->foreign('to_account_id')->references('id')->on('chart_accounts')->onDelete('restrict');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');

            $table->index(['budget_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_reallocations');
    }
};
