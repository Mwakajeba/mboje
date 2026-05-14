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
        Schema::table('receipts', function (Blueprint $table) {
            if (!Schema::hasColumn('receipts', 'payment_method')) {
                $table->enum('payment_method', ['bank_transfer', 'cash', 'cheque'])->nullable()->after('bank_account_id');
            }
            
            // Add cheque reference
            if (!Schema::hasColumn('receipts', 'cheque_id')) {
                $table->foreignId('cheque_id')->nullable()->after('payment_method')->constrained('cheques')->onDelete('set null');
            }
            
            // Add cheque deposit status
            if (!Schema::hasColumn('receipts', 'cheque_deposited')) {
                $table->boolean('cheque_deposited')->default(false)->after('cheque_id');
            }
            
            if (!Schema::hasColumn('receipts', 'cheque_deposited_at')) {
                $table->timestamp('cheque_deposited_at')->nullable()->after('cheque_deposited');
            }
            
            if (!Schema::hasColumn('receipts', 'cheque_deposited_by')) {
                $table->foreignId('cheque_deposited_by')->nullable()->after('cheque_deposited_at')->constrained('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            if (Schema::hasColumn('receipts', 'cheque_deposited_by')) {
                $table->dropForeign(['cheque_deposited_by']);
                $table->dropColumn('cheque_deposited_by');
            }
            if (Schema::hasColumn('receipts', 'cheque_deposited_at')) {
                $table->dropColumn('cheque_deposited_at');
            }
            if (Schema::hasColumn('receipts', 'cheque_deposited')) {
                $table->dropColumn('cheque_deposited');
            }
            if (Schema::hasColumn('receipts', 'cheque_id')) {
                $table->dropForeign(['cheque_id']);
                $table->dropColumn('cheque_id');
            }
            if (Schema::hasColumn('receipts', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
        });
    }
};

