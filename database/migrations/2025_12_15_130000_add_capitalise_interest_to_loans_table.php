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
            if (! Schema::hasColumn('loans', 'capitalise_interest')) {
                $table->boolean('capitalise_interest')->default(false)->after('capitalise_fees');
            }

            if (! Schema::hasColumn('loans', 'capitalised_interest_account_id')) {
                $table->unsignedBigInteger('capitalised_interest_account_id')
                    ->nullable()
                    ->after('loan_processing_fee_account_id');

                $table->foreign('capitalised_interest_account_id')
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
            if (Schema::hasColumn('loans', 'capitalised_interest_account_id')) {
                $table->dropForeign(['capitalised_interest_account_id']);
                $table->dropColumn('capitalised_interest_account_id');
            }

            if (Schema::hasColumn('loans', 'capitalise_interest')) {
                $table->dropColumn('capitalise_interest');
            }
        });
    }
};


