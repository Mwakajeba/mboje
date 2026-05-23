<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createRecordTable('daily_matumizi_records', 'daily_matumizi_lines', 'daily_matumizi_record_id');
        $this->createRecordTable('daily_manunuzi_records', 'daily_manunuzi_lines', 'daily_manunuzi_record_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_matumizi_lines');
        Schema::dropIfExists('daily_matumizi_records');
        Schema::dropIfExists('daily_manunuzi_lines');
        Schema::dropIfExists('daily_manunuzi_records');
    }

    private function createRecordTable(string $recordsTable, string $linesTable, string $linesFk): void
    {
        Schema::create($recordsTable, function (Blueprint $table) use ($recordsTable) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            if (Schema::hasTable('hr_employees')) {
                $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            } else {
                $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            }
            $table->date('entry_date');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'employee_id']);
            $table->index(['employee_id', 'entry_date']);
        });

        Schema::create($linesTable, function (Blueprint $table) use ($recordsTable, $linesFk) {
            $table->id();
            $table->foreignId($linesFk)->constrained($recordsTable)->cascadeOnDelete();
            $table->text('maelezo');
            $table->decimal('kiasi', 15, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }
};
