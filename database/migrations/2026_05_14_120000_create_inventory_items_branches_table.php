<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['inventory_item_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items_branches');
    }
};
