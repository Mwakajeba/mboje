<div class="btn-group" role="group">
    <a href="{{ route('rental-event-equipment.categories.show', $category) }}" class="btn btn-sm btn-outline-info" title="View">
        <i class="bx bx-show"></i>
    </a>
    <a href="{{ route('rental-event-equipment.categories.edit', $category) }}" class="btn btn-sm btn-outline-warning" title="Edit">
        <i class="bx bx-edit"></i>
    </a>
    <button type="button" class="btn btn-sm btn-danger delete-category-btn" title="Delete"
            data-category-id="{{ $category->getRouteKey() }}"
            data-category-name="{{ $category->name }}"
            data-equipment-count="{{ $category->equipment_count ?? 0 }}">
        <i class="bx bx-trash"></i>
    </button>
</div>
