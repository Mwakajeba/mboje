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
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Add approval workflow fields
            $table->unsignedInteger('current_approval_level')->nullable()->after('status');
            $table->unsignedBigInteger('submitted_by')->nullable()->after('current_approval_level');
            $table->timestamp('submitted_at')->nullable()->after('submitted_by');
            
            // Add foreign key for submitted_by
            $table->foreign('submitted_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['submitted_by']);
            $table->dropColumn(['current_approval_level', 'submitted_by', 'submitted_at']);
        });
    }
};

