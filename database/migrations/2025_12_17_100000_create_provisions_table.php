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
        Schema::create('provisions', function (Blueprint $table) {
            $table->id();

            // Document number
            $table->string('provision_number')->unique();

            // Classification & type (IAS 37)
            $table->enum('provision_type', [
                'legal_claim',
                'warranty',
                'onerous_contract',
                'environmental',
                'restructuring',
                'employee_benefit',
                'other',
            ])->default('other');

            $table->string('title');
            $table->text('description');

            // Recognition gatekeeper logic
            $table->enum('present_obligation_type', ['legal', 'constructive'])->nullable();
            $table->boolean('has_present_obligation')->default(false);

            // Probability & estimation
            $table->enum('probability', ['remote', 'possible', 'probable', 'virtually_certain'])->default('possible');
            $table->decimal('probability_percent', 5, 2)->nullable(); // e.g. 75.00
            $table->boolean('is_recognised')->default(false); // System flag: recognised vs only disclosed

            $table->enum('estimate_method', ['best_estimate', 'expected_value', 'most_likely_outcome'])
                ->default('best_estimate');

            // Measurement amounts (home currency)
            $table->string('currency_code', 3)->default('TZS');
            $table->decimal('fx_rate_at_creation', 15, 6)->default(1);
            $table->decimal('original_estimate', 20, 2); // Initial best estimate at recognition
            $table->decimal('current_balance', 20, 2); // Closing provision balance (liability)
            $table->decimal('utilised_amount', 20, 2)->default(0); // Amount used to settle obligation
            $table->decimal('reversed_amount', 20, 2)->default(0); // Over-provision reversals

            // Time value of money
            $table->boolean('is_discounted')->default(false);
            $table->decimal('discount_rate', 8, 4)->nullable(); // Pre-tax discount rate
            $table->date('expected_settlement_date')->nullable();

            // Account mapping (GL)
            $table->foreignId('expense_account_id')
                ->constrained('chart_accounts')
                ->onDelete('restrict'); // P&L expense

            $table->foreignId('provision_account_id')
                ->constrained('chart_accounts')
                ->onDelete('restrict'); // Liability on balance sheet

            $table->foreignId('unwinding_account_id')
                ->nullable()
                ->constrained('chart_accounts')
                ->onDelete('restrict'); // Finance cost (unwinding)

            // Status & lifecycle
            $table->enum('status', [
                'draft',
                'pending_approval',
                'approved',
                'active',
                'settled',
                'cancelled',
            ])->default('draft');

            // Approval workflow fields (for ApprovalService)
            $table->unsignedInteger('current_approval_level')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // Company / branch context
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');

            // Audit
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['provision_type', 'present_obligation_type']);
            $table->index('expected_settlement_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provisions');
    }
};


