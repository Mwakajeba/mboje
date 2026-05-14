@php
    use Vinkla\Hashids\Facades\Hashids;
@endphp
<div class="btn-group" role="group">
    <a href="{{ route('rental-event-equipment.quotations.show', $quotation) }}" class="btn btn-sm btn-outline-info" title="View / Change status">
        <i class="bx bx-show"></i>
    </a>
    @if(in_array($quotation->status, ['draft', 'rejected']))
    <a href="{{ route('rental-event-equipment.quotations.edit', $quotation) }}" class="btn btn-sm btn-outline-warning" title="{{ $quotation->status === 'rejected' ? 'Reapply (Edit & resubmit)' : 'Edit' }}">
        <i class="bx bx-edit"></i>
    </a>
    @endif
    @if(in_array($quotation->status, ['approved', 'sent']))
    <a href="{{ route('rental-event-equipment.contracts.create', ['quotation_id' => Hashids::encode($quotation->id)]) }}" class="btn btn-sm btn-outline-success" title="Convert to Contract">
        <i class="bx bx-file"></i>
    </a>
    @endif
    @if(in_array($quotation->status, ['draft', 'rejected']))
    <button type="button" class="btn btn-sm btn-danger" title="Delete"
            onclick="confirmDelete('{{ $quotation->quotation_number }}', '{{ route('rental-event-equipment.quotations.destroy', $quotation) }}')">
        <i class="bx bx-trash"></i>
    </button>
    @endif
</div>

<script>
function confirmDelete(quotationNumber, deleteUrl) {
    Swal.fire({
        title: 'Delete Quotation?',
        html: '<div class="text-start">' +
            '<p class="mb-2">Are you sure you want to delete quotation <strong>"' + quotationNumber + '"</strong>?</p>' +
            '<div class="alert alert-warning mt-2 mb-2">' +
            '<i class="bx bx-info-circle me-1"></i>' +
            '<strong>Warning:</strong> This action cannot be undone.' +
            '</div>' +
            '<p class="text-danger mb-0"><strong>This action cannot be undone!</strong></p>' +
            '</div>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bx bx-trash me-1"></i> Yes, Delete',
        cancelButtonText: '<i class="bx bx-x me-1"></i> Cancel',
        reverseButtons: true,
        allowOutsideClick: false
    }).then((result) => {
        if (result.isConfirmed) {
            var form = $('<form>', {
                'method': 'POST',
                'action': deleteUrl
            });
            form.append($('<input>', {
                'type': 'hidden',
                'name': '_token',
                'value': '{{ csrf_token() }}'
            }));
            form.append($('<input>', {
                'type': 'hidden',
                'name': '_method',
                'value': 'DELETE'
            }));
            $('body').append(form);
            form.submit();
        }
    });
}
</script>
