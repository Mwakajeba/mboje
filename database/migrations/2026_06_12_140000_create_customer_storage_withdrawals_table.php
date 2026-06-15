<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_storage_withdrawals', function (Blueprint $table) {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_storage_withdrawals');
    }
};
