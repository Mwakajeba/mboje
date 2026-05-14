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
        Schema::table('loans', function (Blueprint $table) {
            if (! Schema::hasColumn('loans', 'loan_processing_fee_account_id')) {
                $table->unsignedBigInteger('loan_processing_fee_account_id')
                    ->nullable()
                    ->after('bank_charges_account_id');

                $table->foreign('loan_processing_fee_account_id')
                    ->references('id')
                    ->on('chart_accounts')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            if (Schema::hasColumn('loans', 'loan_processing_fee_account_id')) {
                $table->dropForeign(['loan_processing_fee_account_id']);
                $table->dropColumn('loan_processing_fee_account_id');
            }
        });
    }
};


