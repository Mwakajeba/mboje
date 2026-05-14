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
        Schema::create('cash_flow_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_flow_category_id')->constrained('cash_flow_categories')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_subtotal')->default(false);
            $table->boolean('is_total')->default(false);
            $table->foreignId('parent_id')->nullable()->constrained('cash_flow_line_items')->onDelete('cascade');
            $table->string('account_code_prefix')->nullable()->comment('For mapping chart accounts');
            $table->json('transaction_types')->nullable()->comment('Array of transaction types');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['cash_flow_category_id', 'sort_order']);
        });
        
        // Add column to chart_accounts to link to cash flow line items
        Schema::table('chart_accounts', function (Blueprint $table) {
            $table->foreignId('cash_flow_line_item_id')->nullable()->after('cash_flow_category_id')
                ->constrained('cash_flow_line_items')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chart_accounts', function (Blueprint $table) {
            $table->dropForeign(['cash_flow_line_item_id']);
            $table->dropColumn('cash_flow_line_item_id');
        });
        
        Schema::dropIfExists('cash_flow_line_items');
    }
};
