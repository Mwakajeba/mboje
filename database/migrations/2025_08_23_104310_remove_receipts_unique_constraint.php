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
        Schema::table('receipts', function (Blueprint $table) {
            // Drop the unique constraint on reference and reference_type
            // This allows multiple receipts per cash deposit
            $table->dropUnique('receipts_reference_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            // Restore the unique constraint on reference and reference_type
            $table->unique(['reference', 'reference_type'], 'receipts_reference_type_unique');
        });
    }
};
