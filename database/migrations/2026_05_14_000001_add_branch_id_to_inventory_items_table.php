<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optional nullable home/default branch on inventory_items.
     * Multi-branch visibility uses inventory_items_branches; this column is for legacy/compat only.
     */
    public function up(): void
    {
        if (Schema::hasTable('inventory_items') && ! Schema::hasColumn('inventory_items', 'branch_id')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->foreignId('branch_id')
                    ->nullable()
                    ->after('company_id')
                    ->constrained('branches')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('inventory_items') || ! Schema::hasColumn('inventory_items', 'branch_id')) {
            return;
        }

        Schema::table('inventory_items', function (Blueprint $table) {
            try {
                $table->dropForeign(['branch_id']);
            } catch (\Throwable $e) {
                try {
                    $table->dropConstrainedForeignId('branch_id');
                } catch (\Throwable $e2) {
                    // ignore
                }
            }
            $table->dropColumn('branch_id');
        });
    }
};
