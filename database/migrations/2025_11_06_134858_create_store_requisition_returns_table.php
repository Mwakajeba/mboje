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
        Schema::create('store_requisition_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_requisition_id')->constrained()->onDelete('cascade');
            $table->date('return_date');
            $table->text('return_reason');
            $table->decimal('total_return_amount', 15, 2)->default(0);
            $table->foreignId('processed_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['store_requisition_id', 'return_date']);
            $table->index(['company_id', 'branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_requisition_returns');
    }
};
