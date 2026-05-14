<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_count_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->string('period_name');
            $table->enum('count_type', ['cycle', 'year_end', 'ad_hoc'])->default('cycle');
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'ad_hoc'])->nullable();
            $table->date('count_start_date');
            $table->date('count_end_date');
            $table->foreignId('inventory_location_id')->nullable()->constrained('inventory_locations')->onDelete('set null');
            $table->foreignId('responsible_staff_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['draft', 'scheduled', 'in_progress', 'completed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'status']);
            $table->index('count_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_count_periods');
    }
};
