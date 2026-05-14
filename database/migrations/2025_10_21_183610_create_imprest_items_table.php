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
        Schema::create('imprest_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('imprest_request_id')->constrained('imprest_requests')->onDelete('cascade');
            $table->foreignId('chart_account_id')->constrained('chart_accounts')->onDelete('restrict');
            
            // Item details
            $table->text('notes')->nullable();
            $table->decimal('amount', 15, 2);
            
            // Audit fields
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            
            $table->timestamps();
            
            // Index for better performance
            $table->index(['imprest_request_id', 'chart_account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imprest_items');
    }
};
