<form action="{{ isset($company) ? route('companies.update', $company->id) : route('companies.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if(isset($company))
        @method('PUT')
    @endif

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">Company Name</label>
            <input type="text" class="form-control" name="name" value="{{ $company->name ?? old('name') }}" required>
        </div>

        <div class="col-md-6">
            <label class="form-label">Email Address</label>
            <input type="email" class="form-control" name="email" value="{{ $company->email ?? old('email') }}" required>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">Phone Number</label>
            <input type="text" class="form-control" name="phone" value="{{ $company->phone ?? old('phone') }}" required>
        </div>

        <div class="col-md-6">
            <label class="form-label">Address</label>
            <input type="text" class="form-control" name="address" value="{{ $company->address ?? old('address') }}" required>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">Company Logo</label>
            <input type="file" class="form-control" name="logo" accept="image/*">
            @if(isset($company) && $company->logo)
                <img src="{{ asset('storage/logos/' . $company->logo) }}" width="80" class="mt-2">
            @endif
        </div>

        <div class="col-md-3">
            <label class="form-label">Background Color</label>
            <input type="color" class="form-control form-control-color" name="bg_color" value="{{ $company->bg_color ?? '#003366' }}">
        </div>

        <div class="col-md-3">
            <label class="form-label">Text Color</label>
            <input type="color" class="form-control form-control-color" name="txt_color" value="{{ $company->txt_color ?? '#ffffff' }}">
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-{{ isset($company) ? 'primary' : 'success' }}">
            {{ isset($company) ? 'Update Company' : 'Create Company' }}
        </button>
    </div>
</form>
