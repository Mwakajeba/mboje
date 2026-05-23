<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('daily_stoo_lines')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE daily_stoo_lines MODIFY thamani VARCHAR(255) NOT NULL DEFAULT ""');

            return;
        }

        Schema::table('daily_stoo_lines', function (Blueprint $table) {
            $table->string('thamani', 255)->default('')->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('daily_stoo_lines')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE daily_stoo_lines MODIFY thamani DECIMAL(15, 2) NOT NULL DEFAULT 0');

            return;
        }

        Schema::table('daily_stoo_lines', function (Blueprint $table) {
            $table->decimal('thamani', 15, 2)->default(0)->change();
        });
    }
};
