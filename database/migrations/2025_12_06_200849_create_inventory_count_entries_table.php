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
        Schema::create('inventory_count_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('count_session_id')->constrained('inventory_count_sessions')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->foreignId('inventory_location_id')->constrained('inventory_locations')->onDelete('cascade');
            $table->string('bin_location')->nullable(); // Specific bin/rack location
            $table->decimal('system_quantity', 15, 2)->default(0); // Snapshot at count start
            $table->decimal('physical_quantity', 15, 2)->nullable(); // Counted quantity
            $table->decimal('recount_quantity', 15, 2)->nullable(); // Recount if needed
            $table->enum('condition', ['good', 'damaged', 'expired', 'obsolete', 'missing'])->default('good');
            $table->string('lot_number')->nullable();
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('counted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('counted_at')->nullable();
            $table->foreignId('recounted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('recounted_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('verified_at')->nullable();
            $table->enum('status', ['pending', 'counted', 'recounted', 'verified', 'disputed'])->default('pending');
            $table->timestamps();
            
            $table->index(['count_session_id', 'item_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_count_entries');
    }
};
