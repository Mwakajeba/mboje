<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Use raw SQL to avoid requiring doctrine/dbal for change()
            DB::statement('ALTER TABLE goods_receipts MODIFY purchase_order_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Revert to NOT NULL (may fail if nulls exist; ensure cleanup before rollback)
            DB::statement('ALTER TABLE goods_receipts MODIFY purchase_order_id BIGINT UNSIGNED NOT NULL');
        }
    }
};


