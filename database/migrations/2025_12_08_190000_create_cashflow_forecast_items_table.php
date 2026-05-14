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
        if (Schema::hasTable('cashflow_forecast_items')) {
            return; // Table already exists, skip migration
        }
        
        try {
            Schema::create('cashflow_forecast_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cashflow_forecast_id')->constrained('cashflow_forecasts')->onDelete('cascade');
            $table->date('forecast_date');
            $table->enum('type', ['inflow', 'outflow']);
            $table->enum('source_type', [
                'accounts_receivable',
                'accounts_payable',
                'sales_order',
                'loan_disbursement',
                'loan_payment',
                'payroll',
                'tax_vat',
                'tax_wht',
                'tax_paye',
                'tax_sdl',
                'tax_pension',
                'recurring_bill',
                'recurring_expense',
                'capex',
                'petty_cash',
                'manual_adjustment'
            ]);
            $table->string('source_reference')->nullable(); // Invoice number, loan number, etc.
            $table->unsignedBigInteger('source_id')->nullable(); // ID of the source record
            $table->decimal('amount', 15, 2);
            $table->decimal('probability', 5, 2)->default(100.00); // Percentage probability (0-100)
            $table->text('description');
            $table->text('notes')->nullable();
            $table->boolean('is_manual_adjustment')->default(false);
            $table->foreignId('adjusted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['cashflow_forecast_id', 'forecast_date'], 'cfi_forecast_date_idx');
            $table->index(['type', 'source_type'], 'cfi_type_source_idx');
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Table might already exist, check again
            if (str_contains($e->getMessage(), 'already exists') && Schema::hasTable('cashflow_forecast_items')) {
                return; // Table exists, skip migration
            }
            throw $e; // Re-throw if it's a different error
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashflow_forecast_items');
    }
};

