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
        Schema::create('leases', function (Blueprint $table) {
            $table->id();
            $table->string('lease_number')->unique();
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('room_id')->nullable(); // For apartment/room rentals
            $table->unsignedBigInteger('tenant_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('monthly_rent', 15, 2)->default(0);
            $table->decimal('security_deposit', 15, 2)->default(0);
            $table->decimal('paid_deposit', 15, 2)->default(0);
            $table->decimal('deposit_balance', 15, 2)->default(0);
            $table->decimal('late_fee_amount', 10, 2)->default(0);
            $table->integer('late_fee_grace_days')->default(5);
            $table->enum('rent_due_day', [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31])->default(1);
            $table->enum('status', ['draft', 'active', 'expired', 'terminated', 'renewed'])->default('draft');
            $table->enum('payment_status', ['current', 'overdue', 'partial'])->default('current');
            $table->text('terms_conditions')->nullable();
            $table->text('special_conditions')->nullable();
            $table->date('termination_date')->nullable();
            $table->text('termination_reason')->nullable();
            $table->decimal('termination_fee', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');
            $table->foreign('room_id')->references('id')->on('rooms')->onDelete('set null');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            
            $table->index(['property_id', 'branch_id', 'company_id']);
            $table->index('status');
            $table->index('payment_status');
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leases');
    }
};