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
        Schema::create('imprest_liquidation_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('imprest_liquidation_id');
            $table->string('expense_category');
            $table->text('description');
            $table->decimal('amount', 15, 2);
            $table->date('expense_date');
            $table->string('receipt_number')->nullable();
            $table->string('supplier_name')->nullable();
            $table->unsignedBigInteger('chart_account_id')->nullable();
            $table->timestamps();

            $table->foreign('imprest_liquidation_id')->references('id')->on('imprest_liquidations')->onDelete('cascade');
            $table->foreign('chart_account_id')->references('id')->on('chart_accounts');

            $table->index(['imprest_liquidation_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imprest_liquidation_items');
    }
};