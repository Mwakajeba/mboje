<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_items', 'branch_id')) {
                // Drop FK constraint first if exists
                try {
                    $table->dropForeign(['branch_id']);
                } catch (\Throwable $e) {
                    // Ignore if FK name differs; attempt generic drop
                    try { $table->dropConstrainedForeignId('branch_id'); } catch (\Throwable $e2) {}
                }
                $table->dropColumn('branch_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('company_id');
        });
    }
};


