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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->string('room_number');
            $table->string('room_name')->nullable();
            $table->enum('room_type', ['single', 'double', 'twin', 'suite', 'apartment', 'studio', 'penthouse', 'other']);
            $table->integer('capacity')->default(1);
            $table->decimal('rate_per_night', 10, 2)->default(0);
            $table->decimal('rate_per_month', 10, 2)->default(0);
            $table->enum('status', ['available', 'occupied', 'maintenance', 'out_of_order'])->default('available');
            $table->text('description')->nullable();
            $table->json('images')->nullable();
            $table->json('amenities')->nullable();
            $table->decimal('size_sqm', 8, 2)->nullable();
            $table->integer('floor_number')->nullable();
            $table->string('view_type')->nullable();
            $table->boolean('has_balcony')->default(false);
            $table->boolean('has_kitchen')->default(false);
            $table->boolean('has_wifi')->default(true);
            $table->boolean('has_ac')->default(false);
            $table->boolean('has_tv')->default(false);
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            $table->index(['property_id', 'branch_id', 'company_id']);
            $table->index('room_type');
            $table->index('status');
            $table->unique(['property_id', 'room_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
