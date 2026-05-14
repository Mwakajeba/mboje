<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_receipt_items', function (Blueprint $table) {
            if (!Schema::hasColumn('goods_receipt_items', 'accepted_quantity')) {
                $table->decimal('accepted_quantity', 15, 2)->default(0)->after('quantity_received');
            }
            if (!Schema::hasColumn('goods_receipt_items', 'item_qc_status')) {
                $table->enum('item_qc_status', ['pending','passed','failed'])->nullable()->after('accepted_quantity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('goods_receipt_items', function (Blueprint $table) {
            if (Schema::hasColumn('goods_receipt_items', 'item_qc_status')) {
                $table->dropColumn('item_qc_status');
            }
            if (Schema::hasColumn('goods_receipt_items', 'accepted_quantity')) {
                $table->dropColumn('accepted_quantity');
            }
        });
    }
};


