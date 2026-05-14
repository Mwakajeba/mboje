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
        Schema::create('approval_levels', function (Blueprint $table) {
            $table->id();
            $table->string('module', 50)->comment('budget or bank_reconciliation');
            $table->unsignedTinyInteger('level')->comment('1, 2, 3, etc.');
            $table->string('level_name', 100)->comment('Department Head, Finance Manager, CFO');
            $table->boolean('is_required')->default(true);
            $table->unsignedTinyInteger('approval_order')->comment('Order of approval');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->timestamps();
            
            // Unique constraint: one level per module per company
            $table->unique(['module', 'level', 'company_id'], 'unique_module_level_company');
            
            // Index for faster lookups
            $table->index(['module', 'company_id', 'approval_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_levels');
    }
};
