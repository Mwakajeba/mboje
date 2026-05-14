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
        Schema::create('imprest_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('imprest_request_id')->nullable();
            $table->unsignedBigInteger('imprest_liquidation_id')->nullable();
            $table->string('document_type'); // receipt, invoice, voucher, etc.
            $table->string('document_name');
            $table->string('file_path');
            $table->string('file_size');
            $table->string('mime_type');
            $table->unsignedBigInteger('uploaded_by');
            $table->timestamps();

            $table->foreign('imprest_request_id')->references('id')->on('imprest_requests')->onDelete('cascade');
            $table->foreign('imprest_liquidation_id')->references('id')->on('imprest_liquidations')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users');

            $table->index(['imprest_request_id']);
            $table->index(['imprest_liquidation_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imprest_documents');
    }
};