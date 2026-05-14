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
        if (!Schema::hasTable('petty_cash_settings')) {
            Schema::create('petty_cash_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->enum('operation_mode', ['sub_imprest', 'standalone'])->default('standalone');
            $table->decimal('default_float_amount', 15, 2)->nullable();
            $table->decimal('max_transaction_amount', 15, 2)->nullable();
            $table->json('allowed_expense_categories')->nullable(); // Array of GL account IDs
            $table->boolean('require_receipt')->default(true);
            $table->decimal('minimum_balance_trigger', 15, 2)->nullable(); // Trigger replenishment when balance falls below this
            $table->boolean('auto_approve_below_threshold')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->index(['company_id', 'operation_mode'], 'pc_settings_company_mode_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petty_cash_settings');
    }
};


