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
        Schema::table('imprest_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_id')->nullable()->after('rejection_reason');
            $table->unsignedBigInteger('disbursed_by')->nullable()->after('payment_id');
            $table->timestamp('disbursed_at')->nullable()->after('disbursed_by');
            $table->decimal('disbursed_amount', 15, 2)->nullable()->after('disbursed_at');
            
            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('cascade');
            $table->foreign('disbursed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('imprest_requests', function (Blueprint $table) {
            $table->dropForeign(['payment_id']);
            $table->dropForeign(['disbursed_by']);
            $table->dropColumn(['payment_id', 'disbursed_by', 'disbursed_at', 'disbursed_amount']);
        });
    }
};
