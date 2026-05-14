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
        Schema::table('loans', function (Blueprint $table) {
            // Check if columns don't exist before adding
            if (!Schema::hasColumn('loans', 'lender_id')) {
                $table->unsignedBigInteger('lender_id')->nullable()->after('bank_account_id');
            }
            if (!Schema::hasColumn('loans', 'lender_name')) {
                $table->string('lender_name')->nullable()->after('bank_account_id');
            }
            
            // Facility Information
            if (!Schema::hasColumn('loans', 'facility_name')) {
                $table->string('facility_name')->nullable()->after('reference_no');
            }
            if (!Schema::hasColumn('loans', 'facility_type')) {
                $table->enum('facility_type', ['term_loan', 'revolving', 'overdraft', 'line_of_credit', 'other'])->nullable()->after('facility_name');
            }
            
            // Disbursement Details
            if (!Schema::hasColumn('loans', 'disbursed_amount')) {
                $table->decimal('disbursed_amount', 15, 2)->nullable()->after('principal_amount');
            }
            if (!Schema::hasColumn('loans', 'start_date')) {
                $table->date('start_date')->nullable()->after('disbursement_date');
            }
            if (!Schema::hasColumn('loans', 'maturity_date')) {
                $table->date('maturity_date')->nullable()->after('start_date');
            }
            
            // Repayment Method
            if (!Schema::hasColumn('loans', 'repayment_method')) {
                $table->enum('repayment_method', ['annuity', 'equal_principal', 'interest_only', 'bullet'])->nullable()->after('amortization_method');
            }
            
            // Interest Rate Details (for floating rates)
            if (!Schema::hasColumn('loans', 'base_rate_source')) {
                $table->string('base_rate_source')->nullable()->after('rate_type');
            }
            if (!Schema::hasColumn('loans', 'spread')) {
                $table->decimal('spread', 5, 2)->nullable()->after('base_rate_source');
            }
            
            // Fees
            if (!Schema::hasColumn('loans', 'fees_amount')) {
                $table->decimal('fees_amount', 15, 2)->default(0)->after('effective_interest_rate');
            }
            if (!Schema::hasColumn('loans', 'capitalise_fees')) {
                $table->boolean('capitalise_fees')->default(false)->after('fees_amount');
            }
            
            // Prepayment
            if (!Schema::hasColumn('loans', 'prepayment_allowed')) {
                $table->boolean('prepayment_allowed')->default(true)->after('capitalise_fees');
            }
            if (!Schema::hasColumn('loans', 'prepayment_penalty_rate')) {
                $table->decimal('prepayment_penalty_rate', 5, 2)->nullable()->after('prepayment_allowed');
            }
            
            // Attachments
            if (!Schema::hasColumn('loans', 'attachments')) {
                $table->json('attachments')->nullable()->after('notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $columns = [
                'lender_id',
                'lender_name',
                'facility_name',
                'facility_type',
                'disbursed_amount',
                'start_date',
                'maturity_date',
                'repayment_method',
                'base_rate_source',
                'spread',
                'fees_amount',
                'capitalise_fees',
                'prepayment_allowed',
                'prepayment_penalty_rate',
                'attachments',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('loans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
