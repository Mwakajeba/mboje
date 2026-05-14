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
        // Check if columns already exist and skip if they do
        if (Schema::hasColumn('deliveries', 'has_transport_cost') && Schema::hasColumn('deliveries', 'transport_cost')) {
            // Columns already exist, nothing to do
            return;
        }

        Schema::table('deliveries', function (Blueprint $table) {
            // Drop existing columns if they exist
            if (Schema::hasColumn('deliveries', 'delivery_fee')) {
                $table->dropColumn('delivery_fee');
            }
            if (Schema::hasColumn('deliveries', 'delivery_margin')) {
                $table->dropColumn('delivery_margin');
            }
            
            // Add the correct fields only if they don't exist
            if (!Schema::hasColumn('deliveries', 'has_transport_cost')) {
                $table->boolean('has_transport_cost')->default(false)->after('weight_unit');
            }
            if (!Schema::hasColumn('deliveries', 'transport_cost')) {
                $table->decimal('transport_cost', 10, 2)->default(0)->after('has_transport_cost');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn('has_transport_cost');
        });
    }
};
