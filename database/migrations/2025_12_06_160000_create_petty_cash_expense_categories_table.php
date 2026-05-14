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
        if (Schema::hasTable('petty_cash_expense_categories')) {
            return; // Table already exists, skip migration
        }
        
        Schema::create('petty_cash_expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('name'); // e.g., "Fuel", "Airtime", "Transport", "Lunch", "Stationery"
            $table->string('code')->nullable();
            $table->foreignId('expense_account_id')->constrained('chart_accounts')->onDelete('restrict');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['company_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petty_cash_expense_categories');
    }
};

