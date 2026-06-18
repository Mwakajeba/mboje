<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('driver_trips') || Schema::hasColumn('driver_trips', 'status')) {
            return;
        }

        Schema::table('driver_trips', function (Blueprint $table) {
            $table->string('status', 20)->default('hai')->after('trip_date');
            $table->index('status', 'dt_status_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('driver_trips') || ! Schema::hasColumn('driver_trips', 'status')) {
            return;
        }

        Schema::table('driver_trips', function (Blueprint $table) {
            $table->dropIndex('dt_status_idx');
            $table->dropColumn('status');
        });
    }
};
