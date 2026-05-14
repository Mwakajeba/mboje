<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Extend enum to include 'rejected'
            $table->enum('status', [
                'draft', 'pending_approval', 'approved', 'rejected',
                'in_production', 'ready_for_delivery', 'delivered', 'cancelled', 'on_hold'
            ])->default('draft')->change();

            // Add rejection tracking fields if missing
            if (!Schema::hasColumn('purchase_orders', 'rejected_by')) {
                $table->unsignedBigInteger('rejected_by')->nullable()->after('approved_at');
            }
            if (!Schema::hasColumn('purchase_orders', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            }
            if (!Schema::hasColumn('purchase_orders', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('rejected_at');
            }
        });

        // Add FK for rejected_by if users table exists
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_orders', 'rejected_by')) {
                $table->foreign('rejected_by')->references('id')->on('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Revert enum to original list without 'rejected'
            $table->enum('status', [
                'draft', 'pending_approval', 'approved',
                'in_production', 'ready_for_delivery', 'delivered', 'cancelled', 'on_hold'
            ])->default('draft')->change();

            // Drop rejection fields if they exist
            if (Schema::hasColumn('purchase_orders', 'rejected_by')) {
                $table->dropForeign(['rejected_by']);
                $table->dropColumn('rejected_by');
            }
            if (Schema::hasColumn('purchase_orders', 'rejected_at')) {
                $table->dropColumn('rejected_at');
            }
            if (Schema::hasColumn('purchase_orders', 'rejection_reason')) {
                $table->dropColumn('rejection_reason');
            }
        });
    }
};


