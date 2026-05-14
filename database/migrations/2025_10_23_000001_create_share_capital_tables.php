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
        /**
         * Master: Share Classes
         */
        if (! Schema::hasTable('share_classes')) {
            Schema::create('share_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');

            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();

            $table->boolean('has_par_value')->default(true);
            $table->decimal('par_value', 18, 6)->default(0);
            $table->string('currency_code', 3)->nullable(); // ISO 4217, linkable to currencies table if needed

            $table->enum('share_type', ['ordinary', 'preference', 'other'])->default('ordinary');
            $table->enum('voting_rights', ['full', 'limited', 'none'])->default('full');
            $table->enum('dividend_policy', ['discretionary', 'fixed', 'participating', 'none'])->default('discretionary');

            $table->boolean('redeemable')->default(false);
            $table->boolean('convertible')->default(false);
            $table->boolean('cumulative')->default(false);
            $table->boolean('participating')->default(false);

            // IFRS / IPSAS classification
            $table->enum('classification', ['equity', 'liability', 'compound'])->default('equity');

            // Authorized capital
            $table->unsignedBigInteger('authorized_shares')->nullable();
            $table->decimal('authorized_value', 20, 2)->nullable();

            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'share_type', 'classification']);
            });
        }

        /**
         * Master: Shareholders
         */
        if (! Schema::hasTable('shareholders')) {
            Schema::create('shareholders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');

            $table->string('code')->nullable(); // internal ID / reference
            $table->string('name');
            $table->enum('type', ['individual', 'corporate', 'government', 'employee', 'related_party', 'other'])->default('individual');

            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('country')->nullable();
            $table->string('tax_id')->nullable();

            $table->text('address')->nullable();

            $table->boolean('is_related_party')->default(false);
            $table->text('related_party_notes')->nullable();

            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'type', 'is_related_party']);
            $table->unique(['company_id', 'code']);
            });
        }

        /**
         * Share Issues (primary issues, rights, bonus, conversions, etc.)
         */
        if (! Schema::hasTable('share_issues')) {
            Schema::create('share_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('share_class_id')->constrained('share_classes')->onDelete('cascade');

            $table->enum('issue_type', [
                'initial',
                'rights',
                'bonus',
                'private_placement',
                'public_offering',
                'conversion',
                'other',
            ])->default('initial');

            $table->string('reference_number')->nullable();

            $table->date('issue_date');
            $table->date('record_date')->nullable();
            $table->date('settlement_date')->nullable();

            $table->decimal('price_per_share', 18, 6)->nullable();
            $table->decimal('par_value', 18, 6)->nullable();

            $table->unsignedBigInteger('total_shares')->default(0);
            $table->decimal('total_amount', 20, 2)->default(0);

            $table->enum('status', ['draft', 'approved', 'posted', 'cancelled'])->default('draft');
            $table->text('description')->nullable();

            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('posted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'share_class_id', 'issue_date']);
            $table->index(['company_id', 'status']);
            });
        }

        /**
         * Share Holdings (lot-level subledger)
         *
         * NOTE:
         * This migration is designed for a fresh schema where the `shareholders`
         * table has an `id` primary key. In existing databases where a legacy
         * `shareholders` table already exists with a different structure, we
         * skip creating `share_holdings` to avoid foreign key errors.
         */
        if (
            ! Schema::hasTable('share_holdings')
            && Schema::hasTable('shareholders')
            && (Schema::hasColumn('shareholders', 'id') || Schema::hasColumn('shareholders', 'shareholder_id'))
        ) {
            // Determine the correct primary key for shareholders table
            $shareholderPk = Schema::hasColumn('shareholders', 'id') ? 'id' : 'shareholder_id';
            
            Schema::create('share_holdings', function (Blueprint $table) use ($shareholderPk) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
                $table->unsignedBigInteger('shareholder_id');
                $table->foreign('shareholder_id')->references($shareholderPk)->on('shareholders')->onDelete('cascade');
                $table->foreignId('share_class_id')->constrained('share_classes')->onDelete('cascade');
                $table->foreignId('share_issue_id')->nullable()->constrained('share_issues')->onDelete('set null');

                $table->string('lot_number')->nullable(); // per-issue or per-transaction lot reference

                $table->date('acquisition_date');

                $table->unsignedBigInteger('shares_issued')->default(0);
                $table->unsignedBigInteger('shares_outstanding')->default(0);
                $table->unsignedBigInteger('shares_forfeited')->default(0);
                $table->unsignedBigInteger('shares_converted')->default(0);
                $table->unsignedBigInteger('shares_redeemed')->default(0);

                $table->decimal('paid_up_amount', 20, 2)->default(0);
                $table->decimal('unpaid_amount', 20, 2)->default(0);

                $table->enum('status', ['active', 'forfeited', 'converted', 'redeemed', 'cancelled'])->default('active');

                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

                $table->timestamps();
                $table->softDeletes();

                $table->unique(['company_id', 'shareholder_id', 'share_class_id', 'lot_number'], 'share_holdings_unique_lot');
                $table->index(['company_id', 'shareholder_id', 'share_class_id']);
            });
        }

        /**
         * Corporate Actions (generic header)
         */
        if (! Schema::hasTable('share_corporate_actions')) {
            Schema::create('share_corporate_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('share_class_id')->nullable()->constrained('share_classes')->onDelete('set null');

            $table->enum('action_type', [
                'issue',
                'rights',
                'bonus',
                'split',
                'reverse_split',
                'buyback',
                'conversion',
                'forfeiture',
                'call',
                'dividend',
                'other',
            ])->default('issue');

            $table->string('reference_number')->nullable();

            $table->date('record_date')->nullable();
            $table->date('ex_date')->nullable();
            $table->date('effective_date')->nullable();

            // Generic ratio fields (e.g. 1:5 bonus, 1:10 split)
            $table->decimal('ratio_numerator', 18, 6)->nullable();
            $table->decimal('ratio_denominator', 18, 6)->nullable();

            $table->decimal('price_per_share', 18, 6)->nullable(); // for rights, buybacks, etc.

            $table->text('notes')->nullable();

            $table->enum('status', ['draft', 'pending_approval', 'approved', 'executed', 'cancelled'])->default('draft');

            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('executed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'action_type', 'status']);
            $table->index(['company_id', 'effective_date']);
            });
        }

        /**
         * Dividends (header)
         */
        if (! Schema::hasTable('share_dividends')) {
            Schema::create('share_dividends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('share_class_id')->nullable()->constrained('share_classes')->onDelete('set null');
            $table->foreignId('corporate_action_id')->nullable()->constrained('share_corporate_actions')->onDelete('set null');

            $table->enum('dividend_type', ['cash', 'bonus', 'scrip'])->default('cash');

            $table->date('declaration_date');
            $table->date('record_date');
            $table->date('ex_date')->nullable();
            $table->date('payment_date')->nullable();

            $table->decimal('per_share_amount', 18, 6)->nullable(); // cash or scrip equivalent
            $table->decimal('total_amount', 20, 2)->nullable();
            $table->string('currency_code', 3)->nullable();

            $table->enum('status', ['draft', 'approved', 'declared', 'paying', 'paid', 'cancelled'])->default('draft');
            $table->text('description')->nullable();

            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'share_class_id', 'record_date']);
            $table->index(['company_id', 'status']);
            });
        }

        /**
         * Dividend Payments (per shareholder)
         *
         * As with share_holdings, this assumes a standard `shareholders` table
         * with an `id` primary key. On legacy databases where `shareholders`
         * has a different structure, we skip creating this table to avoid
         * foreign key errors.
         */
        if (
            ! Schema::hasTable('share_dividend_payments')
            && Schema::hasTable('shareholders')
            && (Schema::hasColumn('shareholders', 'id') || Schema::hasColumn('shareholders', 'shareholder_id'))
        ) {
            // Determine the correct primary key for shareholders table
            $shareholderPk = Schema::hasColumn('shareholders', 'id') ? 'id' : 'shareholder_id';
            
            Schema::create('share_dividend_payments', function (Blueprint $table) use ($shareholderPk) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
                $table->foreignId('dividend_id')->constrained('share_dividends')->onDelete('cascade');
                $table->unsignedBigInteger('shareholder_id');
                $table->foreign('shareholder_id')->references($shareholderPk)->on('shareholders')->onDelete('cascade');

                $table->decimal('gross_amount', 20, 2);
                $table->decimal('withholding_tax_amount', 20, 2)->default(0);
                $table->decimal('net_amount', 20, 2);

                $table->date('payment_date')->nullable();
                $table->string('payment_reference')->nullable();

                $table->enum('status', ['pending', 'paid', 'failed', 'cancelled'])->default('pending');

                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

                $table->timestamps();
                $table->softDeletes();

                $table->index(['company_id', 'dividend_id', 'status']);
                $table->index(['company_id', 'shareholder_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('share_dividend_payments');
        Schema::dropIfExists('share_dividends');
        Schema::dropIfExists('share_corporate_actions');
        Schema::dropIfExists('share_holdings');
        Schema::dropIfExists('share_issues');
        Schema::dropIfExists('shareholders');
        Schema::dropIfExists('share_classes');
    }
};


