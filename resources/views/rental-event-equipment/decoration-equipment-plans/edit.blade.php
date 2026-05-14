@extends('layouts.main')

@section('title', 'Edit Decoration Equipment Plan')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
                ['label' => 'Decoration Equipment Plans', 'url' => route('rental-event-equipment.decoration-equipment-plans.index'), 'icon' => 'bx bx-list-check'],
                ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit'],
            ]" />

            <h6 class="mb-0 text-uppercase">EDIT DECORATION EQUIPMENT PLAN</h6>
            <hr />

            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bx bx-list-check me-2"></i>Edit Equipment Plan</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('rental-event-equipment.decoration-equipment-plans.update', $plan->hashid) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Decoration Job</label>
                                    <input type="text" class="form-control" value="{{ $plan->job->job_number }} - {{ $plan->job->customer->name ?? 'N/A' }}" disabled>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select id="status" name="status"
                                            class="form-select @error('status') is-invalid @enderror">
                                        <option value="draft" {{ old('status', $plan->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                                        <option value="finalized" {{ old('status', $plan->status) === 'finalized' ? 'selected' : '' }}>Finalized</option>
                                        <option value="cancelled" {{ old('status', $plan->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                    </select>
                                    @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Planning Notes</label>
                            <textarea id="notes" name="notes" rows="2"
                                      class="form-control @error('notes') is-invalid @enderror"
                                      placeholder="Any planning notes, event theme details, setup instructions...">{{ old('notes', $plan->notes) }}</textarea>
                            @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr />

                        <h6 class="mb-3">Planned Equipment Items</h6>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle" id="planItemsTable">
                                <thead class="table-light">
                                <tr>
                                    <th style="width: 40%;">Equipment</th>
                                    <th style="width: 15%;">Code</th>
                                    <th style="width: 15%;">Available</th>
                                    <th style="width: 15%;">Planned Qty</th>
                                    <th style="width: 15%;">Notes</th>
                                    <th style="width: 5%;"></th>
                                </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>

                        <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="addPlanItemRowBtn">
                            <i class="bx bx-plus me-1"></i>Add Equipment
                        </button>

                        <div class="alert alert-info small">
                            <i class="bx bx-info-circle me-1"></i>
                            Updating the plan will not change stock. Stock levels will change when you
                            <strong>issue equipment</strong> against this job.
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('rental-event-equipment.decoration-equipment-plans.show', $plan->hashid) }}" class="btn btn-secondary">
                                <i class="bx bx-arrow-back me-1"></i>Back
                            </a>
                            <button type="submit" class="btn btn-info">
                                <i class="bx bx-save me-1"></i>Update Plan
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

        $existingItems = $plan->items->map(function ($item) {
            return [
                'equipment_id' => $item->equipment_id,
                'quantity_planned' => $item->quantity_planned,
                'notes' => $item->notes,
            ];
        })->values();
    @endphp
    <script>
        const planEquipmentOptions = @json($equipmentOptions);
        const existingPlanItems = @json($existingItems);

        function buildPlanItemRow(index, existing) {
            let optionsHtml = '<option value="">Select Equipment</option>';
            planEquipmentOptions.forEach(function (eq) {
                const selected = existing && parseInt(existing.equipment_id) === parseInt(eq.id) ? ' selected' : '';
                optionsHtml += '<option value="' + eq.id + '" data-code="' + (eq.code || '') + '" data-available="' + (eq.available ?? 0) + '"' + selected + '>' +
                    (eq.name || 'N/A') + '</option>';
            });

            const plannedQty = existing && existing.quantity_planned ? existing.quantity_planned : '';
            const notes = existing && existing.notes ? existing.notes : '';

            return '' +
                '<tr>' +
                '<td>' +
                '<select name="items[' + index + '][equipment_id]" class="form-select form-select-sm plan-equipment-select select2-single" required>' +
                optionsHtml +
                '</select>' +
                '</td>' +
                '<td class="text-muted small equipment-code">-</td>' +
                '<td class="text-end small equipment-available">0</td>' +
                '<td>' +
                '<input type="number" min="1" name="items[' + index + '][quantity_planned]" class="form-control form-control-sm" value="' + plannedQty + '" required />' +
                '</td>' +
                '<td>' +
                '<input type="text" name="items[' + index + '][notes]" class="form-control form-control-sm" value="' + notes + '" />' +
                '</td>' +
                '<td class="text-center">' +
                '<button type="button" class="btn btn-sm btn-outline-danger remove-row-btn"><i class="bx bx-trash"></i></button>' +
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
            const $tableBody = $('#planItemsTable tbody');

            function addRow(existing) {
                $tableBody.append(buildPlanItemRow(rowIndex, existing || null));
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

                if (existing && existing.equipment_id) {
                    const selectedOption = $select.find('option:selected');
                    const code = selectedOption.data('code') || '-';
                    const available = selectedOption.data('available') ?? 0;
                    $newRow.find('.equipment-code').text(code);
                    $newRow.find('.equipment-available').text(available);
                }

                rowIndex++;
            }

            $('#addPlanItemRowBtn').on('click', function () {
                addRow();
            });

            $tableBody.on('click', '.remove-row-btn', function () {
                $(this).closest('tr').remove();
            });

            $tableBody.on('change', '.plan-equipment-select', function () {
                const selected = $(this).find('option:selected');
                const code = selected.data('code') || '-';
                const available = selected.data('available') ?? 0;
                const $row = $(this).closest('tr');
                $row.find('.equipment-code').text(code);
                $row.find('.equipment-available').text(available);
            });

            if (existingPlanItems && existingPlanItems.length > 0) {
                existingPlanItems.forEach(function (item) {
                    addRow(item);
                });
            } else {
                addRow();
            }
        });
    </script>
@endpush

