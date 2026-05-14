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
        Schema::create('exchange_rate_history', function (Blueprint $table) {
            $table->id();
            $table->string('from_currency', 3);
            $table->string('to_currency', 3);
            $table->decimal('rate', 15, 6);
            $table->date('rate_date');
            $table->string('source', 50)->default('api'); // api, manual, fallback
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['from_currency', 'to_currency', 'rate_date']);
            $table->index('rate_date');
            
            // Unique constraint to prevent duplicate entries for same date and currency pair
            $table->unique(['from_currency', 'to_currency', 'rate_date'], 'unique_currency_pair_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rate_history');
    }
};
