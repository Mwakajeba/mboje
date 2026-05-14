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
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_orders', 'purchase_requisition_id')) {
                // Link purchase orders back to requisitions (nullable for old data)
                $table->foreignId('purchase_requisition_id')
                    ->nullable()
                    ->after('order_number')
                    ->constrained('purchase_requisitions')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_orders', 'purchase_requisition_id')) {
                try {
                    $table->dropForeign('purchase_orders_purchase_requisition_id_foreign');
                } catch (\Throwable $e) {
                    // ignore if FK name is different or already dropped
                }
                $table->dropColumn('purchase_requisition_id');
            }
        });
    }
};


