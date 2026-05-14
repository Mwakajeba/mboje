<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_locations', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_locations', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('branch_id');
                $table->index('company_id');
            }
        });

        // Backfill company_id from branches.company_id when possible
        if (Schema::hasColumn('inventory_locations', 'company_id') && Schema::hasTable('branches')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('UPDATE inventory_locations il JOIN branches b ON il.branch_id = b.id SET il.company_id = b.company_id WHERE il.company_id IS NULL');
            } else {
                // SQLite doesn't support JOIN in UPDATE, use a subquery instead
                DB::statement('UPDATE inventory_locations SET company_id = (SELECT company_id FROM branches WHERE id = inventory_locations.branch_id) WHERE company_id IS NULL AND branch_id IS NOT NULL');
            }
        }
    }

    public function down(): void
    {
        Schema::table('inventory_locations', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_locations', 'company_id')) {
                $table->dropIndex(['company_id']);
                $table->dropColumn('company_id');
            }
        });
    }
};


