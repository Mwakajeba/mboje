@php
    use Vinkla\Hashids\Facades\Hashids;
@endphp

<form
    action="{{ isset($mainGroup) ? route('accounting.main-groups.update', Hashids::encode($mainGroup->id)) : route('accounting.main-groups.store') }}"
    method="POST">
    @csrf
    @if(isset($mainGroup))
        @method('PUT')
    @endif

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">Select Account Class</label>
            <select class="form-select" name="class_id" required>
                <option value="">-- Choose Account Class --</option>
                @foreach($accountClasses as $accountClass)
                    <option value="{{ $accountClass->id }}" {{ (old('class_id') == $accountClass->id || (isset($mainGroup) && $mainGroup->class_id == $accountClass->id)) ? 'selected' : '' }}>
                        {{ $accountClass->name }}
                    </option>
                @endforeach
            </select>
            @error('class_id')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Main Group Name</label>
            <input type="text" class="form-control" name="name" value="{{ $mainGroup->name ?? old('name') }}"
                required placeholder="e.g., Fixed Assets, Operating Expenses, etc.">
            @error('name')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description" rows="3" placeholder="Brief description of this main group">{{ $mainGroup->description ?? old('description') }}</textarea>
            @error('description')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
                <option value="1" {{ (old('status', $mainGroup->status ?? 1) == 1) ? 'selected' : '' }}>Active</option>
                <option value="0" {{ (old('status', $mainGroup->status ?? 1) == 0) ? 'selected' : '' }}>Inactive</option>
            </select>
            @error('status')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <a href="{{ route('accounting.main-groups.index') }}" class="btn btn-secondary me-2">Cancel</a>
        <button type="submit" class="btn btn-{{ isset($mainGroup) ? 'primary' : 'success' }}">
            {{ isset($mainGroup) ? 'Update Main Group' : 'Create Main Group' }}
        </button>
    </div>
</form>
