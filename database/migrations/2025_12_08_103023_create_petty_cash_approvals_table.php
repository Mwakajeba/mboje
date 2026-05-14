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
        if (!Schema::hasTable('petty_cash_approvals')) {
            Schema::create('petty_cash_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petty_cash_unit_id')->constrained('petty_cash_units')->onDelete('cascade');
            $table->integer('approval_level');
            $table->string('approver_type')->nullable(); // 'role' or 'user'
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('approver_name');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
            
            $table->index(['petty_cash_unit_id', 'approval_level']);
            $table->index('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petty_cash_approvals');
    }
};
