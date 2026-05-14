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
        Schema::table('petty_cash_transactions', function (Blueprint $table) {
            // Drop the existing foreign key constraint first
            $table->dropForeign(['expense_category_id']);
            
            // Make expense_category_id nullable to support line items
            $table->foreignId('expense_category_id')->nullable()->change();
            
            // Re-add the foreign key constraint
            $table->foreign('expense_category_id')->references('id')->on('petty_cash_expense_categories')->onDelete('restrict');
            
            // Add payee type fields
            $table->enum('payee_type', ['customer', 'supplier', 'other', 'employee'])->nullable()->after('payee');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null')->after('payee_type');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null')->after('customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('petty_cash_transactions', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['payee_type', 'customer_id', 'supplier_id']);
            // Note: We don't revert expense_category_id to required as it may break existing data
        });
    }
};

