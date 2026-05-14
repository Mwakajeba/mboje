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
        // Update cash_sales table
        Schema::table('cash_sales', function (Blueprint $table) {
            $table->dropColumn('vat_type');
        });

        // Update credit_notes table
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->dropColumn('vat_type');
        });
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->enum('vat_type', ['inclusive', 'exclusive', 'no_vat'])->default('inclusive')->after('vat_rate');
        });

        // Update pos_sales table
        Schema::table('pos_sales', function (Blueprint $table) {
            $table->dropColumn('vat_type');
        });
        Schema::table('pos_sales', function (Blueprint $table) {
            $table->enum('vat_type', ['inclusive', 'exclusive', 'no_vat'])->default('inclusive')->after('vat_rate');
        });

        // Update credit_note_items table
        Schema::table('credit_note_items', function (Blueprint $table) {
            $table->dropColumn('vat_type');
        });
        Schema::table('credit_note_items', function (Blueprint $table) {
            $table->enum('vat_type', ['inclusive', 'exclusive', 'no_vat'])->default('inclusive')->after('line_total');
        });

        // Update pos_sale_items table
        Schema::table('pos_sale_items', function (Blueprint $table) {
            $table->dropColumn('vat_type');
        });
        Schema::table('pos_sale_items', function (Blueprint $table) {
            $table->enum('vat_type', ['inclusive', 'exclusive', 'no_vat'])->default('inclusive')->after('line_total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert cash_sales table
        Schema::table('cash_sales', function (Blueprint $table) {
            $table->dropColumn('vat_type');
        });
        Schema::table('cash_sales', function (Blueprint $table) {
            $table->enum('vat_type', ['inclusive', 'exclusive'])->default('inclusive')->after('vat_rate');
        });

        // Revert credit_notes table
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->dropColumn('vat_type');
        });
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->enum('vat_type', ['inclusive', 'exclusive'])->default('inclusive')->after('vat_rate');
        });

        // Revert pos_sales table
        Schema::table('pos_sales', function (Blueprint $table) {
            $table->dropColumn('vat_type');
        });
        Schema::table('pos_sales', function (Blueprint $table) {
            $table->enum('vat_type', ['inclusive', 'exclusive'])->default('inclusive')->after('vat_rate');
        });

        // Revert credit_note_items table
        Schema::table('credit_note_items', function (Blueprint $table) {
            $table->dropColumn('vat_type');
        });
        Schema::table('credit_note_items', function (Blueprint $table) {
            $table->enum('vat_type', ['inclusive', 'exclusive'])->default('inclusive')->after('line_total');
        });

        // Revert pos_sale_items table
        Schema::table('pos_sale_items', function (Blueprint $table) {
            $table->dropColumn('vat_type');
        });
        Schema::table('pos_sale_items', function (Blueprint $table) {
            $table->enum('vat_type', ['inclusive', 'exclusive'])->default('inclusive')->after('line_total');
        });
    }
};
