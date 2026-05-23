<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_mauzo_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->date('entry_date');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'supplier_id']);
            $table->index(['supplier_id', 'entry_date']);
        });

        Schema::create('daily_mauzo_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_mauzo_record_id')->constrained('daily_mauzo_records')->cascadeOnDelete();
            $table->text('maelezo');
            $table->decimal('kiasi', 15, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_mauzo_lines');
        Schema::dropIfExists('daily_mauzo_records');
    }
};
