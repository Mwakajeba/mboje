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
        Schema::create('gl_revaluation_history', function (Blueprint $table) {
            $table->id();
            $table->date('revaluation_date');
            $table->enum('item_type', ['AR', 'AP', 'BANK', 'LOAN', 'INTERCOMPANY']);
            $table->string('item_ref', 255)->nullable(); // Invoice number, payment reference, etc.
            $table->unsignedBigInteger('item_id')->nullable(); // ID of the related record
            $table->decimal('original_rate', 15, 6);
            $table->decimal('closing_rate', 15, 6);
            $table->decimal('base_amount', 20, 2); // LCY amount
            $table->decimal('fcy_amount', 20, 2); // FCY amount
            $table->decimal('gain_loss', 20, 2); // Positive = gain, Negative = loss
            $table->foreignId('posted_journal_id')->nullable()->constrained('journals')->onDelete('set null');
            $table->foreignId('reversal_journal_id')->nullable()->constrained('journals')->onDelete('set null');
            $table->boolean('is_reversed')->default(false);
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Indexes (using custom names to avoid MySQL 64-char limit)
            $table->index(['revaluation_date', 'item_type', 'company_id'], 'gl_reval_date_type_company_idx');
            $table->index('item_id', 'gl_reval_item_id_idx');
            $table->index('posted_journal_id', 'gl_reval_journal_id_idx');
            $table->index('is_reversed', 'gl_reval_reversed_idx');
            $table->index(['company_id', 'branch_id'], 'gl_reval_company_branch_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gl_revaluation_history');
    }
};

