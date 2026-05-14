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
        Schema::create('fx_rates', function (Blueprint $table) {
            $table->id();
            $table->date('rate_date');
            $table->string('from_currency', 3);
            $table->string('to_currency', 3);
            $table->decimal('spot_rate', 15, 6);
            $table->decimal('month_end_rate', 15, 6)->nullable();
            $table->decimal('average_rate', 15, 6)->nullable();
            $table->enum('source', ['manual', 'api', 'import'])->default('manual');
            $table->boolean('is_locked')->default(false);
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Indexes
            $table->index('rate_date');
            $table->index(['from_currency', 'to_currency']);
            $table->index('company_id');
            $table->index('is_locked');
            
            // Unique constraint: one rate per date, currency pair, and company
            $table->unique(['rate_date', 'from_currency', 'to_currency', 'company_id'], 'unique_fx_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fx_rates');
    }
};

