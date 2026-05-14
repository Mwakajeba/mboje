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
        Schema::table('purchase_quotation_items', function (Blueprint $table) {
            // Add VAT type field after tax_type
            $table->enum('vat_type', ['no_vat', 'inclusive', 'exclusive'])->default('no_vat')->after('tax_type');

            // Add VAT rate field
            $table->decimal('vat_rate', 5, 2)->default(0)->after('vat_type');

            // Drop the old tax_type column and recreate as tax_calculation_type
            $table->dropColumn('tax_type');
            $table->enum('tax_calculation_type', ['percentage', 'fixed'])->default('percentage')->after('vat_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_quotation_items', function (Blueprint $table) {
            // Drop the new VAT fields and tax_calculation_type
            $table->dropColumn(['vat_type', 'vat_rate', 'tax_calculation_type']);

            // Recreate the original tax_type column
            $table->enum('tax_type', ['percentage', 'fixed'])->default('percentage');
        });
    }
};
