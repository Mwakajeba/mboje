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
        Schema::create('tax_depreciation_classes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable(); // Company-specific overrides (optional)
            $table->string('class_code', 20)->unique(); // e.g., "Class 1", "Class 2"
            $table->string('description');
            $table->decimal('rate', 10, 6)->nullable(); // Depreciation rate (%)
            $table->enum('method', ['reducing_balance', 'straight_line', 'immediate_write_off', 'useful_life'])->default('reducing_balance');
            $table->text('special_condition')->nullable(); // Notes for first-year allowance, agriculture, etc.
            $table->string('legal_reference')->default('Income Tax (Depreciable Assets) Schedule – Tanzania');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
            $table->index('class_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_depreciation_classes');
    }
};
