<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label for="branch_id" class="form-label">Branch <span class="text-danger">*</span></label>
            <select class="form-select @error('branch_id') is-invalid @enderror" id="branch_id" name="branch_id" required>
                <option value="">Select Branch</option>
                @foreach(($branches ?? []) as $branch)
                    <option value="{{ $branch->id }}" {{ old('branch_id', $location->branch_id ?? '') == $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>
            @error('branch_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label for="name" class="form-label">Location Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                   id="name" name="name" value="{{ old('name', $location->name ?? '') }}" required>
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="mb-3">
            <label for="manager_id" class="form-label">Manager</label>
            <select class="form-select @error('manager_id') is-invalid @enderror" 
                    id="manager_id" name="manager_id">
                <option value="">Select Manager</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" 
                        {{ old('manager_id', $location->manager_id ?? '') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
            @error('manager_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control @error('description') is-invalid @enderror" 
                      id="description" name="description" rows="4">{{ old('description', $location->description ?? '') }}</textarea>
            @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

@if(isset($location) && $location->exists)
<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                       {{ old('is_active', $location->is_active ?? true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">
                    Active
                </label>
            </div>
        </div>
    </div>
</div>
@endif
