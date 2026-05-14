<div class="btn-group" role="group">
    <a href="{{ route('rental-event-equipment.customer-deposits.show', $deposit) }}" class="btn btn-sm btn-outline-info" title="View">
        <i class="bx bx-show"></i>
    </a>
    @if($deposit->status === 'pending')
    <a href="{{ route('rental-event-equipment.customer-deposits.edit', $deposit) }}" class="btn btn-sm btn-outline-warning" title="Edit">
        <i class="bx bx-edit"></i>
    </a>
    <button type="button" class="btn btn-sm btn-danger" title="Delete"
            onclick="confirmDelete('{{ $deposit->deposit_number }}', '{{ route('rental-event-equipment.customer-deposits.destroy', $deposit) }}')">
        <i class="bx bx-trash"></i>
    </button>
    @endif
</div>

<script>
function confirmDelete(depositNumber, deleteUrl) {
    Swal.fire({
        title: 'Delete Deposit?',
        html: '<div class="text-start"><p class="mb-2">Are you sure you want to delete deposit <strong>"' + depositNumber + '"</strong>?</p></div>',
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
