<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('driver_trips')) {
            Schema::create('driver_trips', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->string('trip_name');
                $table->string('driver_name');
                $table->text('vehicle_info')->nullable();
                $table->decimal('trip_price', 15, 2)->default(0);
                $table->date('trip_date');
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['company_id', 'branch_id'], 'dt_company_branch_idx');
                $table->index(['company_id', 'trip_date'], 'dt_company_date_idx');
            });
        }

        $this->createRecordTable(
            'driver_trip_mapato_records',
            'driver_trip_mapato_lines',
            'driver_trip_mapato_record_id',
            'dt_mapato_rec_trip_fk',
            'dt_mapato_line_rec_fk'
        );

        $this->createRecordTable(
            'driver_trip_matumizi_records',
            'driver_trip_matumizi_lines',
            'driver_trip_matumizi_record_id',
            'dt_matumizi_rec_trip_fk',
            'dt_matumizi_line_rec_fk'
        );
    }

    private function createRecordTable(
        string $recordTable,
        string $lineTable,
        string $lineForeignKey,
        string $recordTripFkName,
        string $lineRecordFkName
    ): void {
        if (! Schema::hasTable($recordTable)) {
            Schema::create($recordTable, function (Blueprint $table) use ($recordTripFkName) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->foreignId('driver_trip_id')->constrained('driver_trips', indexName: $recordTripFkName)->cascadeOnDelete();
                $table->date('entry_date');
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['company_id', 'driver_trip_id'], $recordTripFkName . '_co_idx');
                $table->index(['driver_trip_id', 'entry_date'], $recordTripFkName . '_dt_idx');
            });
        }

        if (! Schema::hasTable($lineTable)) {
            Schema::create($lineTable, function (Blueprint $table) use ($recordTable, $lineForeignKey, $lineRecordFkName) {
                $table->id();
                $table->foreignId($lineForeignKey)
                    ->constrained($recordTable, indexName: $lineRecordFkName)
                    ->cascadeOnDelete();
                $table->text('maelezo');
                $table->decimal('kiasi', 15, 2)->default(0);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_trip_matumizi_lines');
        Schema::dropIfExists('driver_trip_matumizi_records');
        Schema::dropIfExists('driver_trip_mapato_lines');
        Schema::dropIfExists('driver_trip_mapato_records');
        Schema::dropIfExists('driver_trips');
    }
};
