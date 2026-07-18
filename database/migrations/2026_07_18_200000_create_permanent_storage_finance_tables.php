<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permanent_storage_mapato', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('inventory_item_id')->index();
            $table->string('sababu');
            $table->decimal('kiasi', 15, 2);
            $table->date('entry_date');
            $table->unsignedBigInteger('created_by')->index();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
        });

        Schema::create('permanent_storage_gharama', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('inventory_item_id')->index();
            $table->string('sababu');
            $table->decimal('kiasi', 15, 2);
            $table->date('entry_date');
            $table->unsignedBigInteger('created_by')->index();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
        });

        Schema::create('permanent_storage_malipo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('inventory_item_id')->index();
            $table->string('sababu');
            $table->decimal('kiasi', 15, 2);
            $table->date('entry_date');
            $table->unsignedBigInteger('created_by')->index();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permanent_storage_malipo');
        Schema::dropIfExists('permanent_storage_gharama');
        Schema::dropIfExists('permanent_storage_mapato');
    }
};
