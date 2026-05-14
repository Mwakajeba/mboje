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
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->unsignedBigInteger('proforma_id')->nullable(); // Link to original proforma
            $table->unsignedBigInteger('customer_id');
            $table->date('order_date');
            $table->date('expected_delivery_date');
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'in_production', 'ready_for_delivery', 'delivered', 'cancelled', 'on_hold'])->default('draft');
            $table->enum('payment_terms', ['immediate', 'net_15', 'net_30', 'net_45', 'net_60', 'custom'])->default('immediate');
            $table->integer('payment_days')->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->enum('vat_type', ['no_vat', 'inclusive', 'exclusive'])->default('no_vat');
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('discount_rate', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('customer_credit_limit', 15, 2)->nullable();
            $table->decimal('customer_current_balance', 15, 2)->default(0);
            $table->decimal('available_credit', 15, 2)->default(0);
            $table->boolean('credit_check_passed')->default(false);
            $table->boolean('inventory_check_passed')->default(false);
            $table->text('credit_check_notes')->nullable();
            $table->text('inventory_check_notes')->nullable();
            $table->text('notes')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('proforma_id')->references('id')->on('sales_proformas')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['branch_id', 'company_id']);
            $table->index(['status', 'order_date']);
            $table->index(['customer_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
