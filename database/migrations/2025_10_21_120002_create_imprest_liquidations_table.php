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
        Schema::create('imprest_liquidations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('imprest_request_id');
            $table->string('liquidation_number')->unique();
            $table->decimal('total_spent', 15, 2);
            $table->decimal('balance_returned', 15, 2)->default(0);
            $table->date('liquidation_date');
            $table->text('liquidation_notes')->nullable();
            $table->enum('status', ['submitted', 'verified', 'approved', 'rejected'])->default('submitted');
            $table->unsignedBigInteger('submitted_by');
            $table->timestamp('submitted_at');
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->timestamps();

            $table->foreign('imprest_request_id')->references('id')->on('imprest_requests')->onDelete('cascade');
            $table->foreign('submitted_by')->references('id')->on('users');
            $table->foreign('verified_by')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users');

            $table->index(['imprest_request_id']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imprest_liquidations');
    }
};