<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permanent_storage_receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('inventory_item_id')->index();
            $table->decimal('quantity', 15, 2);
            $table->date('received_date');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->index();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
        });

        Schema::create('permanent_storage_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('inventory_item_id')->index();
            $table->decimal('quantity_on_hand', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'branch_id', 'customer_id', 'inventory_item_id'], 'permanent_storage_balances_unique');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('restrict');
        });

        Schema::create('permanent_storage_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('inventory_item_id')->index();
            $table->decimal('quantity', 15, 2);
            $table->string('reason', 50);
            $table->text('notes')->nullable();
            $table->date('withdrawn_date');
            $table->unsignedBigInteger('created_by')->index();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
        });

        Schema::create('permanent_storage_sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('inventory_item_id')->index();
            $table->decimal('quantity', 15, 2);
            $table->decimal('price', 15, 2);
            $table->decimal('total', 15, 2);
            $table->unsignedBigInteger('withdrawal_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->index();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('restrict');
            $table->foreign('withdrawal_id')->references('id')->on('permanent_storage_withdrawals')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permanent_storage_sales');
        Schema::dropIfExists('permanent_storage_withdrawals');
        Schema::dropIfExists('permanent_storage_balances');
        Schema::dropIfExists('permanent_storage_receipts');
    }
};
