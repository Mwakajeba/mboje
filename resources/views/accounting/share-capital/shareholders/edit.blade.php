@extends('layouts.main')

@section('title', 'Edit Shareholder')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Share Capital', 'url' => route('accounting.share-capital.index'), 'icon' => 'bx bx-group'],
            ['label' => 'Shareholders', 'url' => route('accounting.share-capital.shareholders.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        <h6 class="mb-0 text-uppercase">EDIT SHAREHOLDER</h6>
        <hr />

        <div class="card border-top border-0 border-4 border-primary">
            <div class="card-body p-5">
                <form action="{{ route('accounting.share-capital.shareholders.update', $shareholder->encoded_id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Code</label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" name="code" value="{{ old('code', $shareholder->code) }}">
                            <small class="text-muted d-block mt-1">Unique identifier code for this shareholder</small>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $shareholder->name) }}" required>
                            <small class="text-muted d-block mt-1">Full legal name of the shareholder (individual or company name)</small>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select class="form-select select2-single @error('type') is-invalid @enderror" name="type" required>
                                <option value="individual" {{ old('type', $shareholder->type) == 'individual' ? 'selected' : '' }}>Individual</option>
                                <option value="corporate" {{ old('type', $shareholder->type) == 'corporate' ? 'selected' : '' }}>Corporate</option>
                                <option value="government" {{ old('type', $shareholder->type) == 'government' ? 'selected' : '' }}>Government</option>
                                <option value="employee" {{ old('type', $shareholder->type) == 'employee' ? 'selected' : '' }}>Employee</option>
                                <option value="related_party" {{ old('type', $shareholder->type) == 'related_party' ? 'selected' : '' }}>Related Party</option>
                                <option value="other" {{ old('type', $shareholder->type) == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            <small class="text-muted d-block mt-1">Category of shareholder for reporting and compliance purposes</small>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $shareholder->email) }}">
                            <small class="text-muted d-block mt-1">Contact email address for communications and dividend notifications</small>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $shareholder->phone) }}">
                            <small class="text-muted d-block mt-1">Contact phone number including country code if applicable</small>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Country</label>
                            <input type="text" class="form-control @error('country') is-invalid @enderror" name="country" value="{{ old('country', $shareholder->country) }}">
                            <small class="text-muted d-block mt-1">Country of residence or incorporation</small>
                            @error('country')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tax ID</label>
                            <input type="text" class="form-control @error('tax_id') is-invalid @enderror" name="tax_id" value="{{ old('tax_id', $shareholder->tax_id) }}">
                            <small class="text-muted d-block mt-1">Tax identification number (TIN) for tax reporting and withholding</small>
                            @error('tax_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control @error('address') is-invalid @enderror" name="address" rows="3">{{ old('address', $shareholder->address) }}</textarea>
                            <small class="text-muted d-block mt-1">Complete postal address for official communications</small>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_related_party" id="is_related_party" value="1" {{ old('is_related_party', $shareholder->is_related_party) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_related_party">Party (IAS 24 / IPSAS 20)</label>
                            </div>
                            <small class="text-muted d-block mt-1">Check if this shareholder is a related party requiring special disclosure</small>
                        </div>
                        
                        <div class="col-md-12 mb-3" id="related_party_notes_group" style="display: {{ old('is_related_party', $shareholder->is_related_party) ? 'block' : 'none' }};">
                            <label class="form-label">Related Party Notes</label>
                            <textarea class="form-control @error('related_party_notes') is-invalid @enderror" name="related_party_notes" rows="3">{{ old('related_party_notes', $shareholder->related_party_notes) }}</textarea>
                            <small class="text-muted d-block mt-1">Details about the related party relationship for disclosure purposes</small>
                            @error('related_party_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $shareholder->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save"></i> Update Shareholder
                            </button>
                            <a href="{{ route('accounting.share-capital.shareholders.show', $shareholder->encoded_id) }}" class="btn btn-secondary">
                                <i class="bx bx-x"></i> Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        $('#is_related_party').on('change', function() {
            if ($(this).is(':checked')) {
                $('#related_party_notes_group').show();
            } else {
                $('#related_party_notes_group').hide();
            }
        });
    });
</script>
@endpush
@endsection

