@php
    $encodedId = Vinkla\Hashids\Facades\Hashids::encode($job->id);
@endphp

<div class="btn-group" role="group">
    <a href="{{ route('rental-event-equipment.decoration-jobs.show', $encodedId) }}"
        class="btn btn-sm btn-outline-secondary" title="View">
        <i class="bx bx-show"></i>
    </a>
    <a href="{{ route('rental-event-equipment.decoration-jobs.edit', $encodedId) }}"
        class="btn btn-sm btn-outline-primary" title="Edit">
        <i class="bx bx-edit"></i>
    </a>
    <form action="{{ route('rental-event-equipment.decoration-jobs.destroy', $encodedId) }}" method="POST"
        style="display:inline-block;"
        onsubmit="return confirm('Are you sure you want to delete this decoration job?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
            <i class="bx bx-trash"></i>
        </button>
    </form>
</div>