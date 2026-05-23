<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_stoo_records', function (Blueprint $table) {
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

        Schema::create('daily_stoo_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_stoo_record_id')->constrained('daily_stoo_records')->cascadeOnDelete();
            $table->text('maelezo');
            $table->decimal('thamani', 15, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_stoo_lines');
        Schema::dropIfExists('daily_stoo_records');
    }
};
