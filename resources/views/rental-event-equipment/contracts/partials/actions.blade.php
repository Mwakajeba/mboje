<div class="btn-group" role="group">
    <a href="{{ route('rental-event-equipment.contracts.show', $contract) }}" class="btn btn-sm btn-outline-info" title="View">
        <i class="bx bx-show"></i>
    </a>
    @if($contract->status === 'draft')
    <a href="{{ route('rental-event-equipment.contracts.edit', $contract) }}" class="btn btn-sm btn-outline-warning" title="Edit">
        <i class="bx bx-edit"></i>
    </a>
    <button type="button" class="btn btn-sm btn-danger" title="Delete"
            onclick="confirmDelete('{{ $contract->contract_number }}', '{{ route('rental-event-equipment.contracts.destroy', $contract) }}')">
        <i class="bx bx-trash"></i>
    </button>
    @endif
</div>

<script>
function confirmDelete(contractNumber, deleteUrl) {
    Swal.fire({
        title: 'Delete Contract?',
        html: '<div class="text-start">' +
            '<p class="mb-2">Are you sure you want to delete contract <strong>"' + contractNumber + '"</strong>?</p>' +
            '<div class="alert alert-warning mt-2 mb-2">' +
            '<i class="bx bx-info-circle me-1"></i>' +
            '<strong>Warning:</strong> This will restore equipment status and cannot be undone.' +
            '</div>' +
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
