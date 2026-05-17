<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('supplier_advance_stock_records')) {
            Schema::create('supplier_advance_stock_records', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
                $table->string('bidhaa', 255);
                $table->date('entry_date');
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['company_id', 'supplier_id']);
                $table->index(['supplier_id', 'entry_date']);
            });
        }

        if (! Schema::hasTable('supplier_advance_stock_lines')) {
            Schema::create('supplier_advance_stock_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('stock_record_id')->constrained('supplier_advance_stock_records')->cascadeOnDelete();
                $table->string('transaction_type', 32);
                $table->decimal('idadi', 15, 4)->default(0);
                $table->decimal('thamani', 15, 2)->default(0);
                $table->timestamps();

                $table->unique(['stock_record_id', 'transaction_type'], 'sa_stock_lines_record_type_unique');
            });
        } elseif (! $this->indexExists('supplier_advance_stock_lines', 'sa_stock_lines_record_type_unique')) {
            Schema::table('supplier_advance_stock_lines', function (Blueprint $table) {
                $table->unique(['stock_record_id', 'transaction_type'], 'sa_stock_lines_record_type_unique');
            });
        }

        if (Schema::hasTable('supplier_advance_stock_entries')) {
            $legacy = DB::table('supplier_advance_stock_entries')->orderBy('id')->get();
            foreach ($legacy as $row) {
                $recordId = DB::table('supplier_advance_stock_records')->insertGetId([
                    'company_id' => $row->company_id,
                    'branch_id' => $row->branch_id,
                    'supplier_id' => $row->supplier_id,
                    'bidhaa' => $row->bidhaa,
                    'entry_date' => $row->created_at ? date('Y-m-d', strtotime($row->created_at)) : now()->toDateString(),
                    'user_id' => $row->user_id,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
                DB::table('supplier_advance_stock_lines')->insert([
                    'stock_record_id' => $recordId,
                    'transaction_type' => $row->transaction_type,
                    'idadi' => $row->idadi,
                    'thamani' => $row->thamani,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
            Schema::dropIfExists('supplier_advance_stock_entries');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_advance_stock_lines');
        Schema::dropIfExists('supplier_advance_stock_records');

        if (! Schema::hasTable('supplier_advance_stock_entries')) {
            Schema::create('supplier_advance_stock_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
                $table->string('bidhaa', 255);
                $table->string('transaction_type', 32);
                $table->decimal('idadi', 15, 4)->default(0);
                $table->decimal('thamani', 15, 2)->default(0);
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);

        return count($indexes) > 0;
    }
};
