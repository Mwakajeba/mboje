<div class="row">
    <div class="col-md-6">
        <h6>Item Information</h6>
        <table class="table table-sm">
            <tr>
                <th>Item Code:</th>
                <td>{{ $entry->item->code ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Item Name:</th>
                <td>{{ $entry->item->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Unit of Measure:</th>
                <td>{{ $entry->item->unit_of_measure ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Location/Bin:</th>
                <td>{{ $entry->bin_location ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <h6>Count Information</h6>
        <table class="table table-sm">
            @if(!$entry->session->is_blind_count)
            <tr>
                <th>System Quantity:</th>
                <td>{{ number_format($entry->system_quantity, 2) }}</td>
            </tr>
            @endif
            <tr>
                <th>Physical Quantity:</th>
                <td>
                    @if($entry->session->status === 'counting' || $entry->session->status === 'frozen')
                        <input type="number" step="0.01" class="form-control form-control-sm" 
                               id="physicalQtyInput" value="{{ $entry->physical_quantity ?? '' }}">
                    @else
                        {{ $entry->physical_quantity ? number_format($entry->physical_quantity, 2) : '-' }}
                    @endif
                </td>
            </tr>
            @if($entry->recount_quantity)
            <tr>
                <th>Recount Quantity:</th>
                <td>{{ number_format($entry->recount_quantity, 2) }}</td>
            </tr>
            @endif
            <tr>
                <th>Condition:</th>
                <td>
                    <select class="form-select form-select-sm" id="conditionSelect" {{ $entry->session->status !== 'counting' && $entry->session->status !== 'frozen' ? 'disabled' : '' }}>
                        <option value="good" {{ $entry->condition === 'good' ? 'selected' : '' }}>Good</option>
                        <option value="damaged" {{ $entry->condition === 'damaged' ? 'selected' : '' }}>Damaged</option>
                        <option value="expired" {{ $entry->condition === 'expired' ? 'selected' : '' }}>Expired</option>
                        <option value="obsolete" {{ $entry->condition === 'obsolete' ? 'selected' : '' }}>Obsolete</option>
                        <option value="missing" {{ $entry->condition === 'missing' ? 'selected' : '' }}>Missing</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th>Status:</th>
                <td>
                    <span class="badge bg-{{ $entry->status === 'verified' ? 'success' : ($entry->status === 'counted' ? 'info' : 'secondary') }}">
                        {{ ucfirst($entry->status) }}
                    </span>
                </td>
            </tr>
        </table>
    </div>
</div>

@if($entry->variance)
<div class="row mt-3">
    <div class="col-12">
        <h6>Variance Information</h6>
        <table class="table table-sm table-bordered">
            <tr>
                <th>Variance Quantity:</th>
                <td>
                    <span class="badge bg-{{ $entry->variance->variance_type === 'positive' ? 'success' : ($entry->variance->variance_type === 'negative' ? 'danger' : 'secondary') }}">
                        {{ number_format($entry->variance->variance_quantity, 2) }}
                    </span>
                </td>
                <th>Variance Percentage:</th>
                <td>{{ number_format($entry->variance->variance_percentage, 2) }}%</td>
            </tr>
            <tr>
                <th>Variance Value:</th>
                <td>TZS {{ number_format($entry->variance->variance_value, 2) }}</td>
                <th>High Value:</th>
                <td>
                    @if($entry->variance->is_high_value)
                        <span class="badge bg-danger">Yes</span>
                    @else
                        <span class="badge bg-success">No</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>
</div>
@endif

<div class="row mt-3">
    <div class="col-12">
        <h6>Additional Information</h6>
        <div class="mb-2">
            <label class="form-label">Lot/Batch Number:</label>
            <input type="text" class="form-control form-control-sm" value="{{ $entry->lot_number ?? $entry->batch_number ?? '' }}" 
                   id="lotNumberInput" {{ $entry->session->status !== 'counting' && $entry->session->status !== 'frozen' ? 'disabled' : '' }}>
        </div>
        <div class="mb-2">
            <label class="form-label">Expiry Date:</label>
            <input type="date" class="form-control form-control-sm" value="{{ $entry->expiry_date ? $entry->expiry_date->format('Y-m-d') : '' }}" 
                   id="expiryDateInput" {{ $entry->session->status !== 'counting' && $entry->session->status !== 'frozen' ? 'disabled' : '' }}>
        </div>
        <div class="mb-2">
            <label class="form-label">Remarks:</label>
            <textarea class="form-control form-control-sm" rows="2" id="remarksInput" 
                      {{ $entry->session->status !== 'counting' && $entry->session->status !== 'frozen' ? 'disabled' : '' }}>{{ $entry->remarks ?? '' }}</textarea>
        </div>
    </div>
</div>

@if($entry->session->status === 'counting' || $entry->session->status === 'frozen')
<div class="row mt-3">
    <div class="col-12">
        <button type="button" class="btn btn-primary btn-sm" id="saveEntryBtn" data-entry-id="{{ $entry->id }}">
            <i class="bx bx-save me-1"></i> Save Changes
        </button>
        @if($entry->variance && $entry->variance->requires_recount)
        <button type="button" class="btn btn-warning btn-sm" id="requestRecountBtn" data-entry-id="{{ $entry->id }}">
            <i class="bx bx-refresh me-1"></i> Request Recount
        </button>
        @endif
        <button type="button" class="btn btn-success btn-sm" id="verifyEntryBtn" data-entry-id="{{ $entry->id }}">
            <i class="bx bx-check me-1"></i> Verify
        </button>
    </div>
</div>
@endif

<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    $('#saveEntryBtn').on('click', function() {
        const entryId = $(this).data('entry-id');
        const data = {
            _token: '{{ csrf_token() }}',
            physical_quantity: $('#physicalQtyInput').val(),
            condition: $('#conditionSelect').val(),
            lot_number: $('#lotNumberInput').val(),
            expiry_date: $('#expiryDateInput').val(),
            remarks: $('#remarksInput').val()
        };

        $.ajax({
            url: '/inventory/counts/entries/' + entryId + '/update-physical-qty',
            method: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    toastr.success('Entry updated successfully');
                    $('#entryDetailModal').modal('hide');
                    location.reload();
                }
            },
            error: function() {
                toastr.error('Failed to update entry');
            }
        });
    });
});
</script>

