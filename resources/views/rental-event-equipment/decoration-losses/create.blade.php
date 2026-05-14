@extends('layouts.main')

@section('title', 'Record Decoration Equipment Loss')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
                ['label' => 'Decoration Loss Handling', 'url' => route('rental-event-equipment.decoration-losses.index'), 'icon' => 'bx bx-error'],
                ['label' => 'Record Loss', 'url' => '#', 'icon' => 'bx bx-plus'],
            ]" />

            <h6 class="mb-0 text-uppercase">RECORD DECORATION EQUIPMENT LOSS</h6>
            <hr />

            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bx bx-error me-2"></i>New Loss Record</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('rental-event-equipment.decoration-losses.store') }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="decoration_job_id" class="form-label">Decoration Job</label>
                                    <select id="decoration_job_id" name="decoration_job_id"
                                            class="form-select select2-single @error('decoration_job_id') is-invalid @enderror">
                                        <option value="">Not linked to a specific job</option>
                                        @foreach($jobs as $job)
                                            <option value="{{ $job->id }}" {{ old('decoration_job_id') == $job->id ? 'selected' : '' }}>
                                                {{ $job->job_number }} - {{ $job->customer->name ?? 'N/A' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('decoration_job_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="loss_date" class="form-label">Loss Date</label>
                                    <input type="date" id="loss_date" name="loss_date"
                                           class="form-control @error('loss_date') is-invalid @enderror"
                                           value="{{ old('loss_date', now()->toDateString()) }}">
                                    @error('loss_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="loss_type" class="form-label">Loss Type <span class="text-danger">*</span></label>
                                    <select id="loss_type" name="loss_type"
                                            class="form-select @error('loss_type') is-invalid @enderror" required>
                                        @php
                                            $selectedType = old('loss_type', 'business');
                                        @endphp
                                        <option value="business" {{ $selectedType === 'business' ? 'selected' : '' }}>Business Expense</option>
                                        <option value="employee" {{ $selectedType === 'employee' ? 'selected' : '' }}>Employee Liability</option>
                                    </select>
                                    @error('loss_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="responsible_employee_id" class="form-label">Responsible Employee</label>
                                    <select id="responsible_employee_id" name="responsible_employee_id"
                                            class="form-select select2-single @error('responsible_employee_id') is-invalid @enderror">
                                        <option value="">Not applicable / Business Loss</option>
                                        @foreach($employees as $employee)
                                            <option value="{{ $employee->id }}" {{ old('responsible_employee_id') == $employee->id ? 'selected' : '' }}>
                                                {{ $employee->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('responsible_employee_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Only required when Loss Type is Employee Liability</div>
                                </div>
                            </div>
                        </div>

                        <hr />

                        <h6 class="mb-3">Loss Items (Multiple Equipment)</h6>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle" id="lossItemsTable">
                                <thead class="table-light">
                                <tr>
                                    <th style="width: 45%;">Equipment</th>
                                    <th style="width: 15%;">Code</th>
                                    <th style="width: 15%;" class="text-end">Quantity Lost</th>
                                    <th style="width: 20%;">Notes</th>
                                    <th style="width: 5%;"></th>
                                </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>

                        <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="addLossItemRowBtn">
                            <i class="bx bx-plus me-1"></i>Add Equipment Loss
                        </button>

                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason / Explanation</label>
                            <textarea id="reason" name="reason" rows="3"
                                      class="form-control @error('reason') is-invalid @enderror"
                                      placeholder="Describe how the equipment was lost, and any follow-up actions...">{{ old('reason') }}</textarea>
                            @error('reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-warning small">
                            <i class="bx bx-info-circle me-1"></i>
                            This loss record is created as <strong>Draft</strong>. Confirming it later will classify it
                            as either a <strong>Business Expense</strong> or an <strong>Employee Liability</strong> in
                            your internal reports. Stock quantities for lost items are already handled in the returns
                            module.
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('rental-event-equipment.decoration-losses.index') }}" class="btn btn-secondary">
                                <i class="bx bx-x me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-danger">
                                <i class="bx bx-save me-1"></i>Save Loss (Draft)
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function () {
            if ($.fn.select2) {
                $('.select2-single').select2({
                    placeholder: 'Select',
                    allowClear: true,
                    width: '100%',
                    theme: 'bootstrap-5'
                });
            }

            function toggleEmployeeField() {
                const type = $('#loss_type').val();
                const $employeeSelect = $('#responsible_employee_id');
                if (type === 'employee') {
                    $employeeSelect.closest('.mb-3').find('.form-text').addClass('text-danger fw-semibold');
                } else {
                    $employeeSelect.val('').trigger('change');
                    $employeeSelect.closest('.mb-3').find('.form-text').removeClass('text-danger fw-semibold');
                }
            }

            $('#loss_type').on('change', toggleEmployeeField);
            toggleEmployeeField();

            // Prepare equipment options for dynamic rows (defaults: all equipment)
            @php
                $equipmentOptions = $equipment->map(function ($eq) {
                    return [
                        'id' => $eq->id,
                        'name' => $eq->name,
                        'code' => $eq->equipment_code,
                    ];
                })->values();
            @endphp
            const allEquipmentOptions = @json($equipmentOptions);
            let equipmentOptions = allEquipmentOptions.slice();

            function buildLossItemRow(index) {
                let optionsHtml = '<option value=\"\">Select Equipment</option>';
                equipmentOptions.forEach(function (eq) {
                    optionsHtml += '<option value=\"' + eq.id + '\" data-code=\"' + (eq.code || '') + '\">' +
                        (eq.name || 'N/A') + '</option>';
                });

                return '' +
                    '<tr>' +
                    '<td>' +
                    '<select name=\"items[' + index + '][equipment_id]\" ' +
                    'class=\"form-select form-select-sm loss-equipment-select select2-single\" required>' +
                    optionsHtml +
                    '</select>' +
                    '</td>' +
                    '<td class=\"text-muted small equipment-code\">-</td>' +
                    '<td>' +
                    '<input type=\"number\" min=\"1\" name=\"items[' + index + '][quantity_lost]\" ' +
                    'class=\"form-control form-control-sm\" required />' +
                    '</td>' +
                    '<td>' +
                    '<input type=\"text\" name=\"items[' + index + '][notes]\" ' +
                    'class=\"form-control form-control-sm\" />' +
                    '</td>' +
                    '<td class=\"text-center\">' +
                    '<button type=\"button\" class=\"btn btn-sm btn-outline-danger remove-row-btn\">' +
                    '<i class=\"bx bx-trash\"></i></button>' +
                    '</td>' +
                    '</tr>';
            }

            let rowIndex = 0;
            const $tableBody = $('#lossItemsTable tbody');

            function addRow() {
                $tableBody.append(buildLossItemRow(rowIndex));
                const $newRow = $tableBody.find('tr').last();
                const $select = $newRow.find('.select2-single');

                if ($.fn.select2) {
                    $select.select2({
                        placeholder: 'Select',
                        allowClear: true,
                        width: '100%',
                        theme: 'bootstrap-5'
                    });
                }

                rowIndex++;
            }

            $('#addLossItemRowBtn').on('click', function () {
                addRow();
            });

            $tableBody.on('click', '.remove-row-btn', function () {
                $(this).closest('tr').remove();
            });

            $tableBody.on('change', '.loss-equipment-select', function () {
                const selected = $(this).find('option:selected');
                const code = selected.data('code') || '-';
                const $row = $(this).closest('tr');
                $row.find('.equipment-code').text(code);
            });

            // React to job selection to prefill/filter equipment
            const equipmentForJobUrlTemplate = '{{ route('rental-event-equipment.decoration-losses.equipment-for-job', ['job' => '__JOB__']) }}';

            $('#decoration_job_id').on('change', function () {
                const jobId = $(this).val();

                if (!jobId) {
                    // Reset to all equipment
                    equipmentOptions = allEquipmentOptions.slice();
                    $tableBody.empty();
                    rowIndex = 0;
                    addRow();
                    return;
                }

                // Load equipment used on this job (if any), otherwise fall back to all
                const url = equipmentForJobUrlTemplate.replace('__JOB__', jobId);

                $.getJSON(url, function (response) {
                    if (response.success && response.equipment && response.equipment.length > 0) {
                        equipmentOptions = response.equipment;
                    } else {
                        equipmentOptions = allEquipmentOptions.slice();
                    }

                    $tableBody.empty();
                    rowIndex = 0;
                    addRow();
                }).fail(function () {
                    equipmentOptions = allEquipmentOptions.slice();
                    $tableBody.empty();
                    rowIndex = 0;
                    addRow();
                });
            });

            // Add one row by default
            addRow();
        });
    </script>
@endpush

