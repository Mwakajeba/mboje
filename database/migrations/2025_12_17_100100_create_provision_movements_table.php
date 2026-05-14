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
        Schema::create('provision_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provision_id')
                ->constrained('provisions')
                ->onDelete('cascade');

            $table->date('movement_date');

            $table->enum('movement_type', [
                'initial_recognition',
                'remeasure_increase',
                'remeasure_decrease',
                'utilisation',
                'reversal',
                'unwinding',
            ]);

            $table->text('description')->nullable();

            // Amounts
            $table->string('currency_code', 3)->default('TZS');
            $table->decimal('fx_rate', 15, 6)->default(1);
            $table->decimal('foreign_amount', 20, 2);
            $table->decimal('home_amount', 20, 2);

            // Resulting balance on provision after this movement
            $table->decimal('balance_after_movement', 20, 2);

            // Linked GL journal (if posted)
            $table->foreignId('journal_id')
                ->nullable()
                ->constrained('journals')
                ->onDelete('set null');

            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();

            $table->index(['provision_id', 'movement_date']);
            $table->index('movement_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provision_movements');
    }
};


