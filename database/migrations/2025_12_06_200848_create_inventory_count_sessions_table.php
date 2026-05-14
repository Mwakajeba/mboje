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
        Schema::create('inventory_count_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('count_period_id')->constrained('inventory_count_periods')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('inventory_location_id')->constrained('inventory_locations')->onDelete('cascade');
            $table->string('session_number')->unique();
            $table->dateTime('snapshot_date'); // When system quantity was captured
            $table->dateTime('count_start_time')->nullable();
            $table->dateTime('count_end_time')->nullable();
            $table->enum('status', ['draft', 'frozen', 'counting', 'completed', 'cancelled'])->default('draft');
            $table->boolean('is_blind_count')->default(false); // Hide system quantity from counters
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['count_period_id', 'status']);
            $table->index('inventory_location_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_count_sessions');
    }
};
