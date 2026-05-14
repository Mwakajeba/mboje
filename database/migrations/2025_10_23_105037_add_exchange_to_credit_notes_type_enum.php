<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE credit_notes MODIFY COLUMN type ENUM('return','exchange','discount','correction','overbilling','service_adjustment','post_invoice_discount','refund','restocking_fee','scrap_writeoff','advance_refund','fx_adjustment','other') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE credit_notes MODIFY COLUMN type ENUM('return','discount','correction','overbilling','service_adjustment','post_invoice_discount','refund','restocking_fee','scrap_writeoff','advance_refund','fx_adjustment','other') NOT NULL");
        }
    }
};
