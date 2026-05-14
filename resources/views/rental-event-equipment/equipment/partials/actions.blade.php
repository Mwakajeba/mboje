<div class="btn-group" role="group">
    <a href="{{ route('rental-event-equipment.equipment.show', $equipment) }}" class="btn btn-sm btn-outline-info" title="View">
        <i class="bx bx-show"></i>
    </a>
    <a href="{{ route('rental-event-equipment.equipment.edit', $equipment) }}" class="btn btn-sm btn-outline-warning" title="Edit">
        <i class="bx bx-edit"></i>
    </a>
    <button type="button" class="btn btn-sm btn-danger" title="Delete"
            onclick="confirmDelete('{{ $equipment->name }}', '{{ route('rental-event-equipment.equipment.destroy', $equipment) }}')">
        <i class="bx bx-trash"></i>
    </button>
</div>
