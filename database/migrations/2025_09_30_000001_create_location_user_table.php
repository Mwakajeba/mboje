<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('inventory_location_id')->constrained('inventory_locations')->onDelete('cascade');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'inventory_location_id']);
            // Optional: enforce one default per user at DB-level if supported by your DB
            // For MySQL, emulate in application logic; for PostgreSQL, use partial index.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_user');
    }
};


