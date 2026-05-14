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
        Schema::create('inventory_count_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('count_session_id')->constrained('inventory_count_sessions')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('role', ['counter', 'supervisor', 'verifier'])->default('counter');
            $table->string('assigned_area')->nullable(); // Specific area/location assigned
            $table->dateTime('assigned_at')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->unique(['count_session_id', 'user_id', 'role'], 'unique_session_user_role');
            $table->index('count_session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_count_teams');
    }
};
