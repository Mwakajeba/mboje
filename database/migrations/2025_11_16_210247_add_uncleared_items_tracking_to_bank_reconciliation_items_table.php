<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bank_reconciliation_items', function (Blueprint $table) {
            // Item type: DNC (Deposits Not Credited) or UPC (Unpresented Cheques)
            if (!Schema::hasColumn('bank_reconciliation_items', 'item_type')) {
                $table->enum('item_type', ['DNC', 'UPC', 'BANK_ERROR'])->nullable()->after('transaction_type');
            }
            
            // Origin tracking
            if (!Schema::hasColumn('bank_reconciliation_items', 'origin_date')) {
                $table->date('origin_date')->nullable()->after('transaction_date');
            }
            if (!Schema::hasColumn('bank_reconciliation_items', 'origin_month')) {
                $table->date('origin_month')->nullable()->after('origin_date');
            }
            if (!Schema::hasColumn('bank_reconciliation_items', 'origin_reconciliation_id')) {
                $table->foreignId('origin_reconciliation_id')->nullable()->constrained('bank_reconciliations')->onDelete('set null')->after('origin_month');
            }
            
            // Aging fields
            if (!Schema::hasColumn('bank_reconciliation_items', 'age_days')) {
                $table->integer('age_days')->default(0)->after('origin_reconciliation_id');
            }
            if (!Schema::hasColumn('bank_reconciliation_items', 'age_months')) {
                $table->decimal('age_months', 5, 2)->default(0)->after('age_days');
            }
            
            // Status tracking
            if (!Schema::hasColumn('bank_reconciliation_items', 'uncleared_status')) {
                $table->enum('uncleared_status', ['UNCLEARED', 'CLEARED', 'MANUALLY_CLOSED'])->default('UNCLEARED')->after('age_months');
            }
            
            // Clearing details
            if (!Schema::hasColumn('bank_reconciliation_items', 'clearing_date')) {
                $table->date('clearing_date')->nullable()->after('uncleared_status');
            }
            if (!Schema::hasColumn('bank_reconciliation_items', 'clearing_month')) {
                $table->date('clearing_month')->nullable()->after('clearing_date');
            }
            if (!Schema::hasColumn('bank_reconciliation_items', 'cleared_by')) {
                $table->foreignId('cleared_by')->nullable()->constrained('users')->onDelete('set null')->after('clearing_month');
            }
            if (!Schema::hasColumn('bank_reconciliation_items', 'clearing_reference')) {
                $table->string('clearing_reference')->nullable()->after('cleared_by');
            }
            
            // Manual close details
            if (!Schema::hasColumn('bank_reconciliation_items', 'manual_close_reason')) {
                $table->text('manual_close_reason')->nullable()->after('clearing_reference');
            }
            if (!Schema::hasColumn('bank_reconciliation_items', 'manual_closed_by')) {
                $table->foreignId('manual_closed_by')->nullable()->constrained('users')->onDelete('set null')->after('manual_close_reason');
            }
            if (!Schema::hasColumn('bank_reconciliation_items', 'manual_closed_at')) {
                $table->timestamp('manual_closed_at')->nullable()->after('manual_closed_by');
            }
            
            // Brought forward flag
            if (!Schema::hasColumn('bank_reconciliation_items', 'is_brought_forward')) {
                $table->boolean('is_brought_forward')->default(false)->after('manual_closed_at');
            }
            if (!Schema::hasColumn('bank_reconciliation_items', 'brought_forward_from_item_id')) {
                $table->foreignId('brought_forward_from_item_id')->nullable()->constrained('bank_reconciliation_items')->onDelete('set null')->after('is_brought_forward');
            }
        });

        // Add indexes separately with try-catch
        try {
            DB::statement('CREATE INDEX bank_recon_items_uncleared_type_idx ON bank_reconciliation_items(uncleared_status, item_type)');
        } catch (\Exception $e) {
            // Index may already exist
        }
        
        try {
            DB::statement('CREATE INDEX bank_recon_items_origin_status_idx ON bank_reconciliation_items(origin_month, uncleared_status)');
        } catch (\Exception $e) {
            // Index may already exist
        }
        
        try {
            DB::statement('CREATE INDEX bank_recon_items_age_status_idx ON bank_reconciliation_items(age_days, uncleared_status)');
        } catch (\Exception $e) {
            // Index may already exist
        }
        
        try {
            DB::statement('CREATE INDEX bank_recon_items_bf_recon_idx ON bank_reconciliation_items(is_brought_forward, bank_reconciliation_id)');
        } catch (\Exception $e) {
            // Index may already exist
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_reconciliation_items', function (Blueprint $table) {
            if (Schema::hasColumn('bank_reconciliation_items', 'brought_forward_from_item_id')) {
                $table->dropForeign(['brought_forward_from_item_id']);
            }
            if (Schema::hasColumn('bank_reconciliation_items', 'manual_closed_by')) {
                $table->dropForeign(['manual_closed_by']);
            }
            if (Schema::hasColumn('bank_reconciliation_items', 'cleared_by')) {
                $table->dropForeign(['cleared_by']);
            }
            if (Schema::hasColumn('bank_reconciliation_items', 'origin_reconciliation_id')) {
                $table->dropForeign(['origin_reconciliation_id']);
            }
            
            $columnsToDrop = [
                'item_type',
                'origin_date',
                'origin_month',
                'origin_reconciliation_id',
                'age_days',
                'age_months',
                'uncleared_status',
                'clearing_date',
                'clearing_month',
                'cleared_by',
                'clearing_reference',
                'manual_close_reason',
                'manual_closed_by',
                'manual_closed_at',
                'is_brought_forward',
                'brought_forward_from_item_id',
            ];
            
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('bank_reconciliation_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Drop indexes
        try {
            DB::statement('DROP INDEX bank_recon_items_uncleared_type_idx ON bank_reconciliation_items');
        } catch (\Exception $e) {}
        
        try {
            DB::statement('DROP INDEX bank_recon_items_origin_status_idx ON bank_reconciliation_items');
        } catch (\Exception $e) {}
        
        try {
            DB::statement('DROP INDEX bank_recon_items_age_status_idx ON bank_reconciliation_items');
        } catch (\Exception $e) {}
        
        try {
            DB::statement('DROP INDEX bank_recon_items_bf_recon_idx ON bank_reconciliation_items');
        } catch (\Exception $e) {}
    }
};
