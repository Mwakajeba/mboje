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
        // Add VAT mode to payments table
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'vat_mode')) {
                $table->enum('vat_mode', ['EXCLUSIVE', 'INCLUSIVE', 'NONE'])->default('EXCLUSIVE')->after('wht_treatment');
            }
            if (!Schema::hasColumn('payments', 'vat_amount')) {
                $table->decimal('vat_amount', 20, 2)->default(0)->after('vat_mode');
            }
            if (!Schema::hasColumn('payments', 'base_amount')) {
                $table->decimal('base_amount', 20, 2)->nullable()->after('vat_amount'); // Base amount before VAT
            }
        });

        // Add VAT mode to receipts table
        Schema::table('receipts', function (Blueprint $table) {
            if (!Schema::hasColumn('receipts', 'vat_mode')) {
                $table->enum('vat_mode', ['EXCLUSIVE', 'INCLUSIVE', 'NONE'])->default('EXCLUSIVE')->after('wht_treatment');
            }
            if (!Schema::hasColumn('receipts', 'vat_amount')) {
                $table->decimal('vat_amount', 20, 2)->default(0)->after('vat_mode');
            }
            if (!Schema::hasColumn('receipts', 'base_amount')) {
                $table->decimal('base_amount', 20, 2)->nullable()->after('vat_amount'); // Base amount before VAT
            }
        });

        // Add VAT mode to payment_items table
        Schema::table('payment_items', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_items', 'vat_mode')) {
                $table->enum('vat_mode', ['EXCLUSIVE', 'INCLUSIVE', 'NONE'])->default('EXCLUSIVE')->after('wht_treatment');
            }
            if (!Schema::hasColumn('payment_items', 'vat_amount')) {
                $table->decimal('vat_amount', 20, 2)->default(0)->after('vat_mode');
            }
        });

        // Add VAT mode to receipt_items table
        Schema::table('receipt_items', function (Blueprint $table) {
            if (!Schema::hasColumn('receipt_items', 'vat_mode')) {
                $table->enum('vat_mode', ['EXCLUSIVE', 'INCLUSIVE', 'NONE'])->default('EXCLUSIVE')->after('wht_treatment');
            }
            if (!Schema::hasColumn('receipt_items', 'vat_amount')) {
                $table->decimal('vat_amount', 20, 2)->default(0)->after('vat_mode');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'vat_mode')) {
                $table->dropColumn('vat_mode');
            }
            if (Schema::hasColumn('payments', 'vat_amount')) {
                $table->dropColumn('vat_amount');
            }
            if (Schema::hasColumn('payments', 'base_amount')) {
                $table->dropColumn('base_amount');
            }
        });

        Schema::table('receipts', function (Blueprint $table) {
            if (Schema::hasColumn('receipts', 'vat_mode')) {
                $table->dropColumn('vat_mode');
            }
            if (Schema::hasColumn('receipts', 'vat_amount')) {
                $table->dropColumn('vat_amount');
            }
            if (Schema::hasColumn('receipts', 'base_amount')) {
                $table->dropColumn('base_amount');
            }
        });

        Schema::table('payment_items', function (Blueprint $table) {
            if (Schema::hasColumn('payment_items', 'vat_mode')) {
                $table->dropColumn('vat_mode');
            }
            if (Schema::hasColumn('payment_items', 'vat_amount')) {
                $table->dropColumn('vat_amount');
            }
        });

        Schema::table('receipt_items', function (Blueprint $table) {
            if (Schema::hasColumn('receipt_items', 'vat_mode')) {
                $table->dropColumn('vat_mode');
            }
            if (Schema::hasColumn('receipt_items', 'vat_amount')) {
                $table->dropColumn('vat_amount');
            }
        });
    }
};
