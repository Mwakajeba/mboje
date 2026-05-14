<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_movements', 'writeoff_type')) {
                $table->string('writeoff_type', 20)->nullable()->after('movement_type')
                    ->comment('Applicable only when movement_type = write_off: write_off or stock_out');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_movements', 'writeoff_type')) {
                $table->dropColumn('writeoff_type');
            }
        });
    }
};
