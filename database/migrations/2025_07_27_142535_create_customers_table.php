<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customerNo')->unique();
            $table->string('name');
            $table->text('description')->nullable(); 
            $table->string('phone');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->string('email')->unique();
            // Use a single, consistent numeric credit limit column
            $table->decimal('credit_limit', 15, 2)->nullable();
            // Company details (singular definitions)
            $table->string('tin_number')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('company_name')->nullable();
            $table->string('company_registration_number')->nullable();
            $table->boolean('has_cash_deposit')->default(false);
            $table->text('company_address')->nullable();
            $table->text('business_description')->nullable();

            // Foreign key to branches
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
