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
        if (Schema::hasTable('cashflow_forecasts')) {
            return; // Table already exists, skip migration
        }
        
        Schema::create('cashflow_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->string('forecast_name');
            $table->enum('scenario', ['best_case', 'base_case', 'worst_case'])->default('base_case');
            $table->enum('timeline', ['daily', 'weekly', 'monthly', 'quarterly'])->default('monthly');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('starting_cash_balance', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['company_id', 'branch_id', 'is_active'], 'cf_company_branch_active_idx');
            $table->index(['start_date', 'end_date'], 'cf_dates_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashflow_forecasts');
    }
};

