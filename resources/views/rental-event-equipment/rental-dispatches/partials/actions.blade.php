<div class="btn-group" role="group">
    <a href="{{ route('rental-event-equipment.rental-dispatches.show', $dispatch) }}" class="btn btn-sm btn-outline-info" title="View">
        <i class="bx bx-show"></i>
    </a>
    @if($dispatch->status === 'draft')
    <button type="button" class="btn btn-sm btn-danger" title="Delete"
            onclick="confirmDelete('{{ $dispatch->dispatch_number }}', '{{ route('rental-event-equipment.rental-dispatches.destroy', $dispatch) }}')">
        <i class="bx bx-trash"></i>
    </button>
    @endif
</div>

<script>
function confirmDelete(dispatchNumber, deleteUrl) {
    Swal.fire({
        title: 'Delete Dispatch?',
        html: '<div class="text-start"><p class="mb-2">Are you sure you want to delete dispatch <strong>"' + dispatchNumber + '"</strong>?</p></div>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bx bx-trash me-1"></i> Yes, Delete',
        cancelButtonText: '<i class="bx bx-x me-1"></i> Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            var form = $('<form>', {'method': 'POST', 'action': deleteUrl});
            form.append($('<input>', {'type': 'hidden', 'name': '_token', 'value': '{{ csrf_token() }}'}));
            form.append($('<input>', {'type': 'hidden', 'name': '_method', 'value': 'DELETE'}));
            $('body').append(form);
            form.submit();
        }
    });
}
</script>
