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
            // Drop the existing unique constraint on reference
            $table->dropUnique('receipts_reference_unique');
            
            // Add a new unique constraint on reference and reference_type
            // This allows multiple receipts per cash deposit but prevents duplicates
            $table->unique(['reference', 'reference_type'], 'receipts_reference_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            // Drop the new unique constraint
            $table->dropUnique('receipts_reference_type_unique');
            
            // Restore the original unique constraint on reference
            $table->unique('reference', 'receipts_reference_unique');
        });
    }
};
