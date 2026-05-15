<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->date('advance_date');
            $table->string('reference', 64)->nullable();
            $table->foreignId('debit_chart_account_id')->constrained('chart_accounts')->restrictOnDelete();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->string('attachment_path')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'supplier_id']);
            $table->index(['company_id', 'advance_date']);
        });

        Schema::create('supplier_advance_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('deduction_date');
            $table->string('source_type', 64)->default('manual');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'supplier_id']);
            $table->index(['supplier_id', 'deduction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_advance_deductions');
        Schema::dropIfExists('supplier_advances');
    }
};
