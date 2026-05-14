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
        if (Schema::hasTable('shareholders')) {
            Schema::table('shareholders', function (Blueprint $table) {
                // Check if columns exist before adding
                if (!Schema::hasColumn('shareholders', 'code')) {
                    $table->string('code')->nullable()->after('company_id');
                }
                
                if (!Schema::hasColumn('shareholders', 'email')) {
                    $table->string('email')->nullable()->after('type');
                }
                
                if (!Schema::hasColumn('shareholders', 'phone')) {
                    $table->string('phone')->nullable()->after('email');
                }
                
                if (!Schema::hasColumn('shareholders', 'country')) {
                    $table->string('country')->nullable()->after('phone');
                }
                
                if (!Schema::hasColumn('shareholders', 'tax_id')) {
                    $table->string('tax_id')->nullable()->after('country');
                }
                
                if (!Schema::hasColumn('shareholders', 'address')) {
                    $table->text('address')->nullable()->after('tax_id');
                }
                
                if (!Schema::hasColumn('shareholders', 'is_related_party')) {
                    $table->boolean('is_related_party')->default(false)->after('address');
                }
                
                if (!Schema::hasColumn('shareholders', 'related_party_notes')) {
                    $table->text('related_party_notes')->nullable()->after('is_related_party');
                }
                
                if (!Schema::hasColumn('shareholders', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('related_party_notes');
                }
                
                if (!Schema::hasColumn('shareholders', 'updated_by')) {
                    $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null')->after('created_by');
                }
                
                // Add soft deletes if not exists
                if (!Schema::hasColumn('shareholders', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
            
            // Map existing status to is_active
            if (Schema::hasColumn('shareholders', 'status') && Schema::hasColumn('shareholders', 'is_active')) {
                DB::statement("UPDATE shareholders SET is_active = CASE WHEN status = 'Active' THEN 1 ELSE 0 END");
            }
            
            // Map contact_info to email/phone if contact_info exists
            if (Schema::hasColumn('shareholders', 'contact_info')) {
                // Try to extract email from contact_info (basic implementation)
                // This is a placeholder - you may need to adjust based on your data format
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('shareholders')) {
            Schema::table('shareholders', function (Blueprint $table) {
                // Remove columns if they exist
                $columnsToDrop = [
                    'code', 'email', 'phone', 'country', 'tax_id', 'address',
                    'is_related_party', 'related_party_notes', 'is_active', 'updated_by', 'deleted_at'
                ];
                
                foreach ($columnsToDrop as $column) {
                    if (Schema::hasColumn('shareholders', $column)) {
                        if ($column === 'updated_by') {
                            $table->dropForeign(['updated_by']);
                        }
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
