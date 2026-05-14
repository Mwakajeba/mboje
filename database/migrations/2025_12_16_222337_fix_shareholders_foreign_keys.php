<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Note: SQLite doesn't support information_schema, so we skip the FK check
     * and just try to add the foreign keys (they'll fail silently if they exist)
     */
    public function up(): void
    {
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        
        // Fix foreign keys for share_holdings if they don't exist
        if (Schema::hasTable('share_holdings') && Schema::hasTable('shareholders')) {
            $shouldAddFk = true;
            
            // For MySQL, check if foreign key exists
            if (!$isSqlite) {
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'share_holdings' 
                    AND COLUMN_NAME = 'shareholder_id' 
                    AND REFERENCED_TABLE_NAME = 'shareholders'
                ");
                $shouldAddFk = empty($foreignKeys);
            }
            
            if ($shouldAddFk) {
                try {
                    // Determine the correct primary key
                    $shareholderPk = Schema::hasColumn('shareholders', 'id') ? 'id' : 'shareholder_id';
                    
                    Schema::table('share_holdings', function (Blueprint $table) use ($shareholderPk) {
                        $table->foreign('shareholder_id')->references($shareholderPk)->on('shareholders')->onDelete('cascade');
                    });
                } catch (\Exception $e) {
                    // FK may already exist
                }
            }
        }
        
        // Fix foreign keys for share_dividend_payments if they don't exist
        if (Schema::hasTable('share_dividend_payments') && Schema::hasTable('shareholders')) {
            $shouldAddFk = true;
            
            // For MySQL, check if foreign key exists
            if (!$isSqlite) {
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'share_dividend_payments' 
                    AND COLUMN_NAME = 'shareholder_id' 
                    AND REFERENCED_TABLE_NAME = 'shareholders'
                ");
                $shouldAddFk = empty($foreignKeys);
            }
            
            if ($shouldAddFk) {
                try {
                    // Determine the correct primary key
                    $shareholderPk = Schema::hasColumn('shareholders', 'id') ? 'id' : 'shareholder_id';
                    
                    Schema::table('share_dividend_payments', function (Blueprint $table) use ($shareholderPk) {
                        $table->foreign('shareholder_id')->references($shareholderPk)->on('shareholders')->onDelete('cascade');
                    });
                } catch (\Exception $e) {
                    // FK may already exist
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            if (Schema::hasTable('share_holdings')) {
                Schema::table('share_holdings', function (Blueprint $table) {
                    $table->dropForeign(['shareholder_id']);
                });
            }
        } catch (\Exception $e) {
            // FK may not exist
        }
        
        try {
            if (Schema::hasTable('share_dividend_payments')) {
                Schema::table('share_dividend_payments', function (Blueprint $table) {
                    $table->dropForeign(['shareholder_id']);
                });
            }
        } catch (\Exception $e) {
            // FK may not exist
        }
    }
};
