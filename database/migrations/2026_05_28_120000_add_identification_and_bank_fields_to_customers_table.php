<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('id_type')->nullable()->after('phone');
            $table->string('id_number')->nullable()->after('id_type');
            $table->string('bank_name')->nullable()->after('credit_limit');
            $table->string('bank_account_number')->nullable()->after('bank_name');
            $table->string('account_name')->nullable()->after('bank_account_number');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'id_type',
                'id_number',
                'bank_name',
                'bank_account_number',
                'account_name',
            ]);
        });
    }
};
