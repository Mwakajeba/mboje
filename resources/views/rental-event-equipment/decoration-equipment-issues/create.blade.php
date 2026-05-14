@extends('layouts.main')

@section('title', 'New Decoration Equipment Issue')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
                ['label' => 'Decoration Equipment Issues', 'url' => route('rental-event-equipment.decoration-equipment-issues.index'), 'icon' => 'bx bx-user-check'],
                ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus'],
            ]" />

            <h6 class="mb-0 text-uppercase">CREATE DECORATION EQUIPMENT ISSUE</h6>
            <hr />

            <div class="card shadow-sm">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0"><i class="bx bx-user-check me-2"></i>Issue Equipment to Decorators</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('rental-event-equipment.decoration-equipment-issues.store') }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-5">
                                <div class="mb-3">
                                    <label for="decoration_job_id" class="form-label">Decoration Job <span class="text-danger">*</span></label>
                                    <select id="decoration_job_id" name="decoration_job_id"
                                            class="form-select select2-single @error('decoration_job_id') is-invalid @enderror"
                                            required>
                                        <option value="">Select Job</option>
                                        @foreach($jobs as $job)
                                            <option value="{{ $job->id }}" {{ old('decoration_job_id', optional($selectedJob)->id) == $job->id ? 'selected' : '' }}>
                                                {{ $job->job_number }} - {{ $job->customer->name ?? 'N/A' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('decoration_job_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="decorator_id" class="form-label">Decorator / Team Lead</label>
                                    <select id="decorator_id" name="decorator_id"
                                            class="form-select select2-single @error('decorator_id') is-invalid @enderror">
                                        <option value="">Select Decorator (optional)</option>
                                        @foreach($decorators as $decorator)
                                            <option value="{{ $decorator->id }}" {{ old('decorator_id') == $decorator->id ? 'selected' : '' }}>
                                                {{ $decorator->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('decorator_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="issue_date" class="form-label">Issue Date <span class="text-danger">*</span></label>
                                    <input type="date" id="issue_date" name="issue_date"
                                           class="form-control @error('issue_date') is-invalid @enderror"
                                           value="{{ old('issue_date', now()->toDateString()) }}" required>
                                    @error('issue_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea id="notes" name="notes" rows="2"
                                      class="form-control @error('notes') is-invalid @enderror"
                                      placeholder="Any special instructions for the decorator or storekeeper...">{{ old('notes') }}</textarea>
                            @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr />

                        <h6 class="mb-3">Equipment to Issue</h6>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle" id="equipmentTable">
                                <thead class="table-light">
                                <tr>
                                    <th style="width: 35%;">Equipment</th>
                                    <th style="width: 15%;">Code</th>
                                    <th style="width: 15%;">Available</th>
                                    <th style="width: 15%;">Quantity</th>
                                    <th style="width: 20%;">Remarks</th>
                                    <th style="width: 5%;"></th>
                                </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>

                        <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="addEquipmentRowBtn">
                            <i class="bx bx-plus me-1"></i>Add Equipment
                        </button>

                        <div class="alert alert-info small">
                            <i class="bx bx-info-circle me-1"></i>
                            Quantities issued here will move equipment from <strong>Available</strong> into
                            <strong>In Event Use</strong> once the issue is confirmed.
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('rental-event-equipment.decoration-equipment-issues.index') }}" class="btn btn-secondary">
                                <i class="bx bx-x me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-warning">
                                <i class="bx bx-save me-1"></i>Save Issue (Draft)
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @php
        $equipmentOptions = $equipment->map(function ($eq) {
            return [
                'id' => $eq->id,
                'name' => $eq->name,
                'code' => $eq->equipment_code,
                'available' => $eq->quantity_available,
            ];
        })->values();
    @endphp
    <script>
        const equipmentOptions = @json($equipmentOptions);

        function buildEquipmentRow(index) {
            let optionsHtml = '<option value=\"\">Select Equipment</option>';
            equipmentOptions.forEach(function (eq) {
                optionsHtml += '<option value=\"' + eq.id + '\" data-code=\"' + (eq.code || '') + '\" data-available=\"' + (eq.available ?? 0) + '\">' +
                    (eq.name || 'N/A') + '</option>';
            });

            return '' +
                '<tr>' +
                '<td>' +
                '<select name=\"items[' + index + '][equipment_id]\" class=\"form-select form-select-sm equipment-select select2-single\" required>' +
                optionsHtml +
                '</select>' +
                '</td>' +
                '<td class=\"text-muted small equipment-code\">-</td>' +
                '<td class=\"text-end small equipment-available\">0</td>' +
                '<td>' +
                '<input type=\"number\" min=\"1\" name=\"items[' + index + '][quantity]\" class=\"form-control form-control-sm\" required />' +
                '</td>' +
                '<td>' +
                '<input type=\"text\" name=\"items[' + index + '][remarks]\" class=\"form-control form-control-sm\" />' +
                '</td>' +
                '<td class=\"text-center\">' +
                '<button type=\"button\" class=\"btn btn-sm btn-outline-danger remove-row-btn\"><i class=\"bx bx-trash\"></i></button>' +
                '</td>' +
                '</tr>';
        }

        $(function () {
            if ($.fn.select2) {
                $('.select2-single').select2({
                    placeholder: 'Select',
                    allowClear: true,
                    width: '100%',
                    theme: 'bootstrap-5'
                });
            }

            let rowIndex = 0;
            const $tableBody = $('#equipmentTable tbody');

            function addRow() {
                $tableBody.append(buildEquipmentRow(rowIndex));
                rowIndex++;

                if ($.fn.select2) {
                    $tableBody.find('.select2-single').last().select2({
                        placeholder: 'Select',
                        allowClear: true,
                        width: '100%',
                        theme: 'bootstrap-5'
                    });
                }
            }

            $('#addEquipmentRowBtn').on('click', function () {
                addRow();
            });

            $tableBody.on('click', '.remove-row-btn', function () {
                $(this).closest('tr').remove();
            });

            $tableBody.on('change', '.equipment-select', function () {
                const selected = $(this).find('option:selected');
                const code = selected.data('code') || '-';
                const available = selected.data('available') ?? 0;
                const $row = $(this).closest('tr');
                $row.find('.equipment-code').text(code);
                $row.find('.equipment-available').text(available);
            });

            // Add at least one row by default
            addRow();
        });
    </script>
@endpush

