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
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_number')->unique();
            $table->unsignedBigInteger('sales_order_id')->nullable(); // Link to sales order
            $table->unsignedBigInteger('customer_id');
            $table->date('delivery_date');
            $table->time('delivery_time')->nullable();
            $table->enum('status', ['draft', 'picking', 'packed', 'in_transit', 'delivered', 'cancelled', 'returned'])->default('draft');
            $table->enum('delivery_type', ['pickup', 'delivery', 'shipping'])->default('delivery');
            $table->string('delivery_address')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('vehicle_number')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_phone')->nullable();
            $table->text('delivery_instructions')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('total_quantity', 10, 2)->default(0);
            $table->decimal('total_weight', 10, 2)->default(0);
            $table->string('weight_unit')->default('kg');
            $table->boolean('stock_updated')->default(false);
            $table->timestamp('stock_updated_at')->nullable();
            $table->unsignedBigInteger('picked_by')->nullable();
            $table->timestamp('picked_at')->nullable();
            $table->unsignedBigInteger('packed_by')->nullable();
            $table->timestamp('packed_at')->nullable();
            $table->unsignedBigInteger('delivered_by')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->string('received_by_name')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->text('delivery_notes')->nullable();
            $table->text('return_reason')->nullable();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('picked_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('packed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('delivered_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['branch_id', 'company_id']);
            $table->index(['status', 'delivery_date']);
            $table->index(['customer_id', 'status']);
            $table->index(['sales_order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
