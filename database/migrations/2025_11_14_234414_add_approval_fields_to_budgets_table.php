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
        Schema::table('budgets', function (Blueprint $table) {
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'rejected', 'active', 'archived'])
                ->default('draft')
                ->after('description')
                ->comment('Budget status');
            
            $table->unsignedTinyInteger('current_approval_level')->nullable()->after('status')
                ->comment('Current approval level being processed');
            
            $table->foreignId('submitted_by')->nullable()->after('current_approval_level')
                ->constrained('users')->onDelete('set null')
                ->comment('User who submitted for approval');
            
            $table->timestamp('submitted_at')->nullable()->after('submitted_by')
                ->comment('When budget was submitted for approval');
            
            $table->foreignId('approved_by')->nullable()->after('submitted_at')
                ->constrained('users')->onDelete('set null')
                ->comment('User who gave final approval');
            
            $table->timestamp('approved_at')->nullable()->after('approved_by')
                ->comment('When budget was finally approved');
            
            $table->foreignId('rejected_by')->nullable()->after('approved_at')
                ->constrained('users')->onDelete('set null')
                ->comment('User who rejected the budget');
            
            $table->timestamp('rejected_at')->nullable()->after('rejected_by')
                ->comment('When budget was rejected');
            
            $table->text('rejection_reason')->nullable()->after('rejected_at')
                ->comment('Reason for rejection');
            
            // Indexes for faster lookups
            $table->index('status');
            $table->index('submitted_by');
            $table->index('approved_by');
            $table->index('rejected_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropForeign(['submitted_by']);
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['rejected_by']);
            $table->dropIndex(['status']);
            $table->dropIndex(['submitted_by']);
            $table->dropIndex(['approved_by']);
            $table->dropIndex(['rejected_by']);
            
            $table->dropColumn([
                'status',
                'current_approval_level',
                'submitted_by',
                'submitted_at',
                'approved_by',
                'approved_at',
                'rejected_by',
                'rejected_at',
                'rejection_reason'
            ]);
        });
    }
};
