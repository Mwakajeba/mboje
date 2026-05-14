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
        Schema::table('pos_sales', function (Blueprint $table) {
            // Add discount_type and discount_rate columns if they don't exist
            if (!Schema::hasColumn('pos_sales', 'discount_type')) {
                $table->enum('discount_type', ['none', 'percentage', 'fixed'])->default('none')->after('discount_amount');
            }
            if (!Schema::hasColumn('pos_sales', 'discount_rate')) {
                $table->decimal('discount_rate', 15, 2)->default(0)->after('discount_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_sales', function (Blueprint $table) {
            if (Schema::hasColumn('pos_sales', 'discount_rate')) {
                $table->dropColumn('discount_rate');
            }
            if (Schema::hasColumn('pos_sales', 'discount_type')) {
                $table->dropColumn('discount_type');
            }
        });
    }
};
