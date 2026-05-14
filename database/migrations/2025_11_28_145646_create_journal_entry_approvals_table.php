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
        Schema::create('journal_entry_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('journal_id');
            $table->integer('approval_level');
            $table->string('approver_type')->nullable(); // 'role' or 'user'
            $table->unsignedBigInteger('approver_id')->nullable(); // user ID if approver_type is 'user'
            $table->string('approver_name'); // role name or user name
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
            
            $table->foreign('journal_id')->references('id')->on('journals')->onDelete('cascade');
            $table->index(['journal_id', 'approval_level', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entry_approvals');
    }
};
