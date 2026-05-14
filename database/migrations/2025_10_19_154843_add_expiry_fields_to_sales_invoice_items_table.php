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
        Schema::table('sales_invoice_items', function (Blueprint $table) {
            // Add expiry tracking fields
            $table->string('batch_number')->nullable()->after('stock_available');
            $table->date('expiry_date')->nullable()->after('batch_number');
            $table->json('expiry_consumption_details')->nullable()->after('expiry_date'); // Store FEFO consumption details
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_invoice_items', function (Blueprint $table) {
            $table->dropColumn(['batch_number', 'expiry_date', 'expiry_consumption_details']);
        });
    }
};