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
        Schema::create('production_batches', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('batch_number', 50)->unique();
            $table->unsignedBigInteger('item_id');
            $table->integer('quantity_planned')->nullable();
            $table->integer('quantity_produced')->default(0);
            $table->integer('quantity_defective')->default(0);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->enum('status', ['planned','in_progress','completed','cancelled'])->default('planned');
            $table->timestamps();

            $table->foreign('item_id')->references('id')->on('inventory_items');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_batches');
    }
};
