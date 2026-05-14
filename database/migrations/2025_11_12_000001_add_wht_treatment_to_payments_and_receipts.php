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
        // Add WHT fields to payments table
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'wht_treatment')) {
                $table->enum('wht_treatment', ['EXCLUSIVE', 'INCLUSIVE', 'GROSS_UP', 'NONE'])->default('EXCLUSIVE')->after('amount');
            }
            if (!Schema::hasColumn('payments', 'wht_rate')) {
                $table->decimal('wht_rate', 5, 2)->default(0)->after('wht_treatment');
            }
            if (!Schema::hasColumn('payments', 'wht_amount')) {
                $table->decimal('wht_amount', 20, 2)->default(0)->after('wht_rate');
            }
            if (!Schema::hasColumn('payments', 'net_payable')) {
                $table->decimal('net_payable', 20, 2)->nullable()->after('wht_amount'); // Net amount after WHT
            }
            if (!Schema::hasColumn('payments', 'total_cost')) {
                $table->decimal('total_cost', 20, 2)->nullable()->after('net_payable'); // Total cost (for Gross-Up)
            }
        });

        // Add WHT fields to receipts table
        Schema::table('receipts', function (Blueprint $table) {
            if (!Schema::hasColumn('receipts', 'wht_treatment')) {
                $table->enum('wht_treatment', ['EXCLUSIVE', 'INCLUSIVE', 'NONE'])->default('EXCLUSIVE')->after('amount');
            }
            if (!Schema::hasColumn('receipts', 'wht_rate')) {
                $table->decimal('wht_rate', 5, 2)->default(0)->after('wht_treatment');
            }
            if (!Schema::hasColumn('receipts', 'wht_amount')) {
                $table->decimal('wht_amount', 20, 2)->default(0)->after('wht_rate');
            }
            if (!Schema::hasColumn('receipts', 'net_receivable')) {
                $table->decimal('net_receivable', 20, 2)->nullable()->after('wht_amount'); // Net amount after WHT
            }
        });

        // Add WHT fields to payment_items table
        Schema::table('payment_items', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_items', 'wht_treatment')) {
                $table->enum('wht_treatment', ['EXCLUSIVE', 'INCLUSIVE', 'GROSS_UP', 'NONE'])->default('EXCLUSIVE')->after('amount');
            }
            if (!Schema::hasColumn('payment_items', 'wht_rate')) {
                $table->decimal('wht_rate', 5, 2)->default(0)->after('wht_treatment');
            }
            if (!Schema::hasColumn('payment_items', 'wht_amount')) {
                $table->decimal('wht_amount', 20, 2)->default(0)->after('wht_rate');
            }
            if (!Schema::hasColumn('payment_items', 'base_amount')) {
                $table->decimal('base_amount', 20, 2)->nullable()->after('wht_amount'); // Base amount before WHT
            }
            if (!Schema::hasColumn('payment_items', 'net_payable')) {
                $table->decimal('net_payable', 20, 2)->nullable()->after('base_amount'); // Net amount after WHT
            }
            if (!Schema::hasColumn('payment_items', 'total_cost')) {
                $table->decimal('total_cost', 20, 2)->nullable()->after('net_payable'); // Total cost (for Gross-Up)
            }
        });

        // Add WHT fields to receipt_items table
        Schema::table('receipt_items', function (Blueprint $table) {
            if (!Schema::hasColumn('receipt_items', 'wht_treatment')) {
                $table->enum('wht_treatment', ['EXCLUSIVE', 'INCLUSIVE', 'NONE'])->default('EXCLUSIVE')->after('amount');
            }
            if (!Schema::hasColumn('receipt_items', 'wht_rate')) {
                $table->decimal('wht_rate', 5, 2)->default(0)->after('wht_treatment');
            }
            if (!Schema::hasColumn('receipt_items', 'wht_amount')) {
                $table->decimal('wht_amount', 20, 2)->default(0)->after('wht_rate');
            }
            if (!Schema::hasColumn('receipt_items', 'base_amount')) {
                $table->decimal('base_amount', 20, 2)->nullable()->after('wht_amount'); // Base amount before WHT
            }
            if (!Schema::hasColumn('receipt_items', 'net_receivable')) {
                $table->decimal('net_receivable', 20, 2)->nullable()->after('base_amount'); // Net amount after WHT
            }
        });

        // Add allow_gross_up flag to suppliers table
        Schema::table('suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('suppliers', 'allow_gross_up')) {
                $table->boolean('allow_gross_up')->default(false)->after('vat_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['wht_treatment', 'wht_rate', 'wht_amount', 'net_payable', 'total_cost']);
        });

        Schema::table('receipts', function (Blueprint $table) {
            $table->dropColumn(['wht_treatment', 'wht_rate', 'wht_amount', 'net_receivable']);
        });

        Schema::table('payment_items', function (Blueprint $table) {
            $table->dropColumn(['wht_treatment', 'wht_rate', 'wht_amount', 'base_amount', 'net_payable', 'total_cost']);
        });

        Schema::table('receipt_items', function (Blueprint $table) {
            $table->dropColumn(['wht_treatment', 'wht_rate', 'wht_amount', 'base_amount', 'net_receivable']);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('allow_gross_up');
        });
    }
};

