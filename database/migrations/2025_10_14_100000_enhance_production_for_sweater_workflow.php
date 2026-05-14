<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create work orders table
        Schema::create('work_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id');
            $table->string('wo_number', 50)->unique();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('product_name');
            $table->string('style');
            $table->json('sizes_quantities'); // JSON: {"S": 10, "M": 15, "L": 20}
            $table->date('due_date');
            $table->date('start_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->enum('status', [
                'PLANNED', 'MATERIAL_ISSUED', 'KNITTING', 'CUTTING',
                'JOINING', 'EMBROIDERY', 'IRONING_FINISHING', 'QC',
                'PACKAGING', 'DISPATCHED', 'CANCELLED'
            ])->default('PLANNED');
            $table->boolean('requires_logo')->default(false);
            $table->unsignedBigInteger('inventory_location_id')->nullable();
            $table->enum('work_order_type', ['customer', 'inventory_location'])->default('customer');
            $table->boolean('require_knitting')->default(true);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('customer_id')->references('id')->on('customers');
            $table->foreign('created_by')->references('id')->on('users');
        });

        // Create BOM (Bill of Materials) table
        Schema::create('work_order_bom', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('work_order_id');
            $table->unsignedBigInteger('material_item_id'); // references inventory_items
            $table->string('material_type')->default('yarn'); // yarn, thread, labels, etc.
            $table->decimal('required_quantity', 10, 3);
            $table->string('unit_of_measure', 20);
            $table->decimal('variance_allowed', 5, 2)->default(5.00); // percentage
            $table->timestamps();

            $table->foreign('work_order_id')->references('id')->on('work_orders')->onDelete('cascade');
            $table->foreign('material_item_id')->references('id')->on('inventory_items');
        });

        // Create production processes table
        Schema::create('work_order_processes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('work_order_id');
            $table->enum('process_stage', [
                'PLANNED', 'MATERIAL_ISSUED', 'KNITTING', 'CUTTING',
                'JOINING', 'EMBROIDERY', 'IRONING_FINISHING', 'QC',
                'PACKAGING', 'DISPATCHED'
            ]);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'rework_required'])->default('pending');
            $table->unsignedBigInteger('operator_id')->nullable();
            $table->unsignedBigInteger('machine_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('work_order_id')->references('id')->on('work_orders')->onDelete('cascade');
            $table->foreign('operator_id')->references('id')->on('users');
            $table->foreign('machine_id')->references('id')->on('production_machines');
        });

        // Create material issues table
        Schema::create('material_issues', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('work_order_id');
            $table->string('issue_voucher_number', 50)->unique();
            $table->unsignedBigInteger('material_item_id');
            $table->string('lot_number')->nullable();
            $table->decimal('quantity_issued', 10, 3);
            $table->string('unit_of_measure', 20);
            $table->unsignedBigInteger('issued_by');
            $table->unsignedBigInteger('received_by');
            $table->string('bin_location')->nullable();
            $table->string('line_location')->nullable();
            $table->timestamp('issued_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('work_order_id')->references('id')->on('work_orders');
            $table->foreign('material_item_id')->references('id')->on('inventory_items');
            $table->foreign('issued_by')->references('id')->on('users');
            $table->foreign('received_by')->references('id')->on('users');
        });

        // Create production records table for each stage
        Schema::create('production_records', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('work_order_id');
            $table->enum('stage', [
                'KNITTING', 'CUTTING', 'JOINING', 'EMBROIDERY',
                'IRONING_FINISHING', 'QC', 'PACKAGING'
            ]);
            $table->json('input_materials')->nullable(); // JSON for materials used
            $table->json('output_data')->nullable(); // JSON for stage-specific outputs
            $table->json('wastage_data')->nullable(); // JSON for wastage/defects
            $table->decimal('yield_percentage', 5, 2)->nullable();
            $table->unsignedBigInteger('operator_id')->nullable();
            $table->unsignedBigInteger('machine_id')->nullable();
            $table->integer('operator_time_minutes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->foreign('work_order_id')->references('id')->on('work_orders');
            $table->foreign('operator_id')->references('id')->on('users');
            $table->foreign('machine_id')->references('id')->on('production_machines');
        });

        // Create quality check records
        Schema::create('quality_checks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('work_order_id');
            $table->enum('result', ['pass', 'fail', 'rework_required']);
            $table->json('defect_codes')->nullable(); // JSON array of defect codes
            $table->json('measurements')->nullable(); // JSON for measurement checks
            $table->boolean('seam_strength_ok')->default(true);
            $table->boolean('logo_position_ok')->default(true);
            $table->text('rework_notes')->nullable();
            $table->unsignedBigInteger('inspector_id');
            $table->timestamp('inspected_at');
            $table->timestamps();

            $table->foreign('work_order_id')->references('id')->on('work_orders');
            $table->foreign('inspector_id')->references('id')->on('users');
        });

        // Create packaging records
        Schema::create('packaging_records', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('work_order_id');
            $table->json('packed_quantities'); // JSON: {"S": 8, "M": 15, "L": 18}
            $table->json('carton_numbers'); // JSON array of carton numbers
            $table->json('barcode_data')->nullable(); // JSON for barcode information
            $table->unsignedBigInteger('packed_by');
            $table->timestamp('packed_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('work_order_id')->references('id')->on('work_orders');
            $table->foreign('packed_by')->references('id')->on('users');
        });

        // Add production stage to production_machines table if it doesn't exist
        if (!Schema::hasColumn('production_machines', 'production_stage')) {
            Schema::table('production_machines', function (Blueprint $table) {
                $table->enum('production_stage', [
                    'KNITTING', 'CUTTING', 'JOINING', 'EMBROIDERY',
                    'IRONING_FINISHING', 'PACKAGING'
                ])->nullable()->after('status');
                $table->string('gauge')->nullable()->after('production_stage');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('packaging_records');
        Schema::dropIfExists('quality_checks');
        Schema::dropIfExists('production_records');
        Schema::dropIfExists('material_issues');
        Schema::dropIfExists('work_order_processes');
        Schema::dropIfExists('work_order_bom');
        Schema::dropIfExists('work_orders');

        if (Schema::hasColumn('production_machines', 'production_stage')) {
            Schema::table('production_machines', function (Blueprint $table) {
                $table->dropColumn(['production_stage', 'gauge']);
            });
        }
    }
};
