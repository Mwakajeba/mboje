@extends('layouts.main')

@section('title', 'New Decoration Equipment Return')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
                ['label' => 'Decoration Equipment Returns', 'url' => route('rental-event-equipment.decoration-equipment-returns.index'), 'icon' => 'bx bx-undo'],
                ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus'],
            ]" />

            <h6 class="mb-0 text-uppercase">CREATE DECORATION EQUIPMENT RETURN</h6>
            <hr />

            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bx bx-undo me-2"></i>Record Decoration Return</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('rental-event-equipment.decoration-equipment-returns.store') }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="issue_id" class="form-label">Issue Note <span class="text-danger">*</span></label>
                                    <select id="issue_id" name="issue_id"
                                            class="form-select select2-single @error('issue_id') is-invalid @enderror"
                                            required>
                                        <option value="">Select Issue</option>
                                        @foreach($issues as $issue)
                                            <option value="{{ $issue['id'] }}">
                                                {{ $issue['issue_number'] }} - {{ $issue['job_number'] }} - {{ $issue['customer_name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('issue_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="return_date" class="form-label">Return Date <span class="text-danger">*</span></label>
                                    <input type="date" id="return_date" name="return_date"
                                           class="form-control @error('return_date') is-invalid @enderror"
                                           value="{{ old('return_date', now()->toDateString()) }}" required>
                                    @error('return_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea id="notes" name="notes" rows="2"
                                      class="form-control @error('notes') is-invalid @enderror"
                                      placeholder="Notes about this return (optional)...">{{ old('notes') }}</textarea>
                            @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr />

                        <div class="alert alert-info small mb-3">
                            <i class="bx bx-info-circle me-1"></i>
                            After selecting an issue note, the equipment items will be loaded so you can record
                            quantities returned as <strong>Good</strong>, <strong>Damaged</strong>, or <strong>Lost</strong>.
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle" id="returnItemsTable">
                                <thead class="table-light">
                                <tr>
                                    <th>Equipment</th>
                                    <th class="text-end">Issued</th>
                                    <th class="text-end">Return Qty</th>
                                    <th>Condition</th>
                                    <th>Notes</th>
                                </tr>
                                </thead>
                                <tbody>
                                <!-- Rows populated via JS -->
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between mt-3">
                            <a href="{{ route('rental-event-equipment.decoration-equipment-returns.index') }}"
                               class="btn btn-secondary">
                                <i class="bx bx-x me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i>Save Return
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

            $('#issue_id').on('change', function () {
                const issueId = $(this).val();
                const $tbody = $('#returnItemsTable tbody');
                $tbody.empty();

                if (!issueId) {
                    return;
                }

                const urlTemplate = '{{ route("rental-event-equipment.decoration-equipment-issues.items", ["issue" => "__ISSUE__"]) }}';
                const url = urlTemplate.replace('__ISSUE__', issueId);

                $tbody.append('<tr><td colspan="5" class="text-center text-muted">' +
                    '<div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>Loading equipment...' +
                    '</td></tr>');

                $.getJSON(url)
                    .done(function (response) {
                        $tbody.empty();
                        if (!response.items || response.items.length === 0) {
                            $tbody.append('<tr><td colspan="5" class="text-center text-muted">No equipment items found for this issue.</td></tr>');
                            return;
                        }

                        response.items.forEach(function (item, index) {
                            const row =
                                '<tr>' +
                                '<td>' +
                                '<strong>' + (item.equipment_name || "N/A") + '</strong><br>' +
                                '<small class="text-muted">' + (item.equipment_code || "") + '</small>' +
                                '<input type="hidden" name="items[' + index + '][issue_item_id]" value="' + item.issue_item_id + '">' +
                                '<input type="hidden" name="items[' + index + '][equipment_id]" value="' + item.equipment_id + '">' +
                                '</td>' +
                                '<td class="text-end">' + (item.quantity_issued ?? 0) + '</td>' +
                                '<td>' +
                                '<input type="number" min="0" name="items[' + index + '][quantity_returned]"' +
                                ' class="form-control form-control-sm" value="' + (item.quantity_issued ?? 0) + '">' +
                                '</td>' +
                                '<td>' +
                                '<select name="items[' + index + '][condition]" class="form-select form-select-sm">' +
                                '<option value="good">Good</option>' +
                                '<option value="damaged">Damaged</option>' +
                                '<option value="lost">Lost</option>' +
                                '</select>' +
                                '</td>' +
                                '<td>' +
                                '<input type="text" name="items[' + index + '][condition_notes]" class="form-control form-control-sm">' +
                                '</td>' +
                                '</tr>';
                            $tbody.append(row);
                        });
                    })
                    .fail(function () {
                        $tbody.empty();
                        $tbody.append('<tr><td colspan="5" class="text-center text-danger">Failed to load issue items. Please try again.</td></tr>');
                    });
            });
        });
    </script>
@endpush

