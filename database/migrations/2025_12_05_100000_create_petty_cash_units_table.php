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
        if (Schema::hasTable('petty_cash_units')) {
            return; // Table already exists, skip migration
        }
        
        Schema::create('petty_cash_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->string('name'); // e.g., "HQ Petty Cash", "Branch A Petty Cash"
            $table->string('code')->unique(); // e.g., "PC-HQ", "PC-BR-A"
            $table->foreignId('custodian_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->decimal('float_amount', 15, 2)->default(0); // Initial float
            $table->decimal('current_balance', 15, 2)->default(0); // Running balance
            $table->decimal('maximum_limit', 15, 2)->nullable(); // Maximum allowed balance
            $table->decimal('approval_threshold', 15, 2)->nullable(); // Amount requiring approval
            $table->foreignId('petty_cash_account_id')->constrained('chart_accounts')->onDelete('restrict');
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->onDelete('set null');
            $table->foreignId('suspense_account_id')->nullable()->constrained('chart_accounts')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['company_id', 'branch_id']);
            $table->index('custodian_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petty_cash_units');
    }
};

