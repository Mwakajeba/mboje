<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_advance_manunuzi_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->date('entry_date');
            $table->string('maelezo', 2000);
            $table->decimal('kiasi', 15, 2);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'supplier_id']);
            $table->index(['supplier_id', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_advance_manunuzi_entries');
    }
};
