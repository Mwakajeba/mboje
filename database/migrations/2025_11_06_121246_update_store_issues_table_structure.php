<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('store_issues', function (Blueprint $table) {
                // Drop foreign key constraints first
                $table->dropForeign(['received_by']);
                
                // Drop old columns that are not used
                $table->dropColumn([
                    'issue_voucher_number',
                    'received_by',
                    'issue_time',
                    'total_issue_value',
                    'issue_type',
                    'issue_notes',
                    'delivery_location',
                    'delivery_instructions',
                    'requires_signature',
                    'signature_path'
                ]);
                
                // Add new columns that the controller expects
                $table->string('voucher_no')->unique()->after('id');
                $table->unsignedBigInteger('issued_to')->after('issued_by'); // Who receives the items
                $table->decimal('total_amount', 15, 2)->default(0)->after('issued_to');
                $table->text('description')->nullable()->after('total_amount');
                $table->text('remarks')->nullable()->after('description');
                $table->unsignedBigInteger('created_by')->after('remarks');
                $table->unsignedBigInteger('updated_by')->after('created_by');
                
                // Update status enum to match what controller expects
                $table->enum('status', ['pending', 'issued', 'cancelled'])->default('issued')->change();
                
                // Add foreign key for issued_to (linking to users table)
                $table->foreign('issued_to')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade');
            });
        }
        // SQLite doesn't support dropping columns with unique constraints easily, so we skip for SQLite
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_issues', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['issued_to']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            
            // Drop new columns
            $table->dropColumn([
                'voucher_no',
                'issued_to',
                'total_amount',
                'description',
                'remarks',
                'created_by',
                'updated_by'
            ]);
            
            // Restore old columns
            $table->string('issue_voucher_number')->unique()->after('id');
            $table->unsignedBigInteger('received_by')->after('issued_by');
            $table->time('issue_time')->after('issue_date');
            $table->decimal('total_issue_value', 15, 2)->default(0)->after('issue_time');
            $table->enum('issue_type', ['full', 'partial'])->default('full')->after('total_issue_value');
            $table->text('issue_notes')->nullable()->after('issue_type');
            $table->string('delivery_location')->nullable()->after('issue_notes');
            $table->text('delivery_instructions')->nullable()->after('delivery_location');
            $table->boolean('requires_signature')->default(true)->after('delivery_instructions');
            $table->string('signature_path')->nullable()->after('requires_signature');
            
            // Restore old status enum
            $table->enum('status', ['issued', 'returned', 'partially_returned'])->default('issued')->change();
            
            // Restore old foreign key
            $table->foreign('received_by')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
