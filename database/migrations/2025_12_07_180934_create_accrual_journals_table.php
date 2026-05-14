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
        Schema::create('accrual_journals', function (Blueprint $table) {
            $table->id();
            
            // Link to schedule
            $table->foreignId('accrual_schedule_id')->constrained('accrual_schedules')->onDelete('cascade');
            
            // Period Information
            $table->string('period', 7); // Format: YYYY-MM
            $table->date('period_start_date');
            $table->date('period_end_date');
            $table->integer('days_in_period'); // For prorating
            
            // Amounts
            $table->decimal('amortisation_amount', 20, 2); // Amount for this period
            $table->decimal('fx_rate', 15, 6)->default(1); // FX rate at month-end
            $table->decimal('home_currency_amount', 20, 2); // Amount in home currency
            
            // FX Differences (IAS 21)
            $table->decimal('fx_difference', 20, 2)->default(0); // FX gain/loss
            $table->foreignId('fx_gain_loss_account_id')->nullable()->constrained('chart_accounts')->onDelete('set null');
            
            // Journal Reference
            $table->foreignId('journal_id')->nullable()->constrained('journals')->onDelete('set null'); // System-generated journal
            
            // Status
            $table->enum('status', ['pending', 'posted', 'reversed', 'cancelled'])->default('pending');
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Narration
            $table->text('narration'); // e.g., "Amortisation for Prepaid Insurance – Jan 2025"
            
            // Notes
            $table->text('notes')->nullable();
            
            // Company and Branch
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['accrual_schedule_id', 'period']);
            $table->index(['company_id', 'status']);
            $table->index('period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accrual_journals');
    }
};
