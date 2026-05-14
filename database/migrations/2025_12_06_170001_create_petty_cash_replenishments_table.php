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
        if (!Schema::hasTable('petty_cash_replenishments')) {
            Schema::create('petty_cash_replenishments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petty_cash_unit_id')->constrained('petty_cash_units')->onDelete('restrict');
            $table->string('replenishment_number')->unique();
            $table->date('request_date');
            $table->decimal('requested_amount', 15, 2);
            $table->decimal('approved_amount', 15, 2)->nullable();
            $table->text('reason');
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'paid', 'posted'])->default('draft');
            $table->foreignId('requested_by')->constrained('users')->onDelete('restrict'); // Custodian
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null'); // Supervisor
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('payment_voucher_id')->nullable()->constrained('payments')->onDelete('set null');
            $table->foreignId('source_account_id')->nullable()->constrained('bank_accounts')->onDelete('set null');
            $table->foreignId('journal_id')->nullable()->constrained('journals')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['petty_cash_unit_id', 'status'], 'pcr_unit_status_idx');
            $table->index('requested_by', 'pcr_requested_by_idx');
            $table->index('approved_by', 'pcr_approved_by_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petty_cash_replenishments');
    }
};

