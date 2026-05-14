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
        // Some databases may already have this column from the original create migration.
        if (!Schema::hasColumn('purchase_quotation', 'purchase_requisition_id')) {
            Schema::table('purchase_quotation', function (Blueprint $table) {
                // Nullable link back to PR, no FK to avoid cross-module dependency issues
                $table->unsignedBigInteger('purchase_requisition_id')->nullable()->after('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('purchase_quotation', 'purchase_requisition_id')) {
            Schema::table('purchase_quotation', function (Blueprint $table) {
                $table->dropColumn('purchase_requisition_id');
            });
        }
    }
};


