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
        Schema::create('batch_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('batch_id');
            $table->unsignedBigInteger('order_id');
            $table->integer('assigned_quantity')->nullable();
            $table->timestamps();

            $table->foreign('batch_id')->references('id')->on('production_batches')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('sales_orders')->onDelete('cascade');
            $table->unique(['batch_id', 'order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_orders');
    }
};
