<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_purchase_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cash_purchase_id');
            $table->unsignedBigInteger('inventory_item_id')->nullable();
            $table->string('description')->nullable();
            $table->string('unit_of_measure')->nullable();
            $table->decimal('quantity', 15, 2)->default(0);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->enum('vat_type', ['no_vat','inclusive','exclusive'])->default('no_vat');
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->timestamps();

            $table->index('cash_purchase_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_purchase_items');
    }
};
