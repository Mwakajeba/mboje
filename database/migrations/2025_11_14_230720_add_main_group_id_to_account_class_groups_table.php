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
        Schema::table('account_class_groups', function (Blueprint $table) {
            // Add main_group_id column
            $table->foreignId('main_group_id')->nullable()->after('class_id')->constrained('main_groups')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_class_groups', function (Blueprint $table) {
            $table->dropForeign(['main_group_id']);
            $table->dropColumn('main_group_id');
        });
    }
};
