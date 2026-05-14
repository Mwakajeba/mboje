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
        Schema::table('companies', function (Blueprint $table) {
            // Add functional currency field
            if (!Schema::hasColumn('companies', 'functional_currency')) {
                $table->string('functional_currency', 3)->default('TZS')->after('status')->comment('Functional currency for this company (IAS 21)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('functional_currency');
        });
    }
};

