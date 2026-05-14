@php
    $encodedId = $issue->getRouteKey();
@endphp
<div class="text-center">
    <a href="{{ route('rental-event-equipment.decoration-equipment-issues.show', $encodedId) }}"
       class="btn btn-sm btn-outline-info" title="View">
        <i class="bx bx-show"></i>
    </a>
    @if($issue->status === 'draft')
        <form action="{{ route('rental-event-equipment.decoration-equipment-issues.confirm', $encodedId) }}"
              method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-warning"
                    onclick="return confirm('Confirm this issue? This will move equipment to In Event Use.')"
                    title="Confirm Issue">
                <i class="bx bx-check-circle"></i>
            </button>
        </form>
    @endif
</div>

