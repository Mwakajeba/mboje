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
        Schema::create('inventory_count_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('count_session_id')->constrained('inventory_count_sessions')->onDelete('cascade');
            $table->foreignId('variance_id')->nullable()->constrained('inventory_count_variances')->onDelete('set null');
            $table->foreignId('item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->foreignId('inventory_location_id')->constrained('inventory_locations')->onDelete('cascade');
            $table->string('adjustment_number')->unique();
            $table->decimal('adjustment_quantity', 15, 2); // Positive for surplus, negative for shortage
            $table->decimal('adjustment_value', 15, 2);
            $table->enum('adjustment_type', ['shortage', 'surplus']); // Shortage = negative, Surplus = positive
            $table->string('reason_code')->nullable(); // wrong_posting, theft, damage, expired, unrecorded_issue, unrecorded_receipt
            $table->text('reason_description')->nullable();
            $table->json('supporting_documents')->nullable(); // Array of file paths/photos
            $table->text('supervisor_comments')->nullable();
            $table->text('finance_comments')->nullable();
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'rejected', 'posted'])->default('draft');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('posted_at')->nullable();
            $table->foreignId('journal_id')->nullable()->constrained('journals')->onDelete('set null');
            $table->foreignId('movement_id')->nullable()->constrained('inventory_movements')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['count_session_id', 'status']);
            $table->index('adjustment_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_count_adjustments');
    }
};
