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
        if (!Schema::hasTable('petty_cash_transaction_items')) {
            Schema::create('petty_cash_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petty_cash_transaction_id')->constrained('petty_cash_transactions')->onDelete('cascade');
            $table->foreignId('chart_account_id')->constrained('chart_accounts')->onDelete('restrict');
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index('petty_cash_transaction_id');
            $table->index('chart_account_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petty_cash_transaction_items');
    }
};

