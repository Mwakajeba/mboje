@php
$isEdit = isset($customer);
$selectedIdType = strtolower((string) old('id_type', $customer->id_type ?? ''));
@endphp

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bx bx-error-circle me-2"></i>
    Tafadhali rekebisha makosa yafuatayo:
    <ul class="mb-0 mt-2">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Funga"></button>
</div>
@endif

<form action="{{ $isEdit ? route('customers.update', \Vinkla\Hashids\Facades\Hashids::encode($customer->id)) : route('customers.store') }}"
      method="POST" enctype="multipart/form-data">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="row">
        <div class="col-12">
            <div class="card radius-10 mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bx bx-info-circle me-2"></i>Taarifa za Msingi</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jina Kamili <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $customer->name ?? '') }}" placeholder="Weka jina kamili">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Namba ya Simu <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                value="{{ old('phone', $customer->phone ?? '') }}" placeholder="Weka namba ya simu">
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Aina ya Kitambulisho</label>
                            <select name="id_type" class="form-control @error('id_type') is-invalid @enderror">
                                <option value="">Chagua aina ya kitambulisho</option>
                                @foreach(\App\Models\Customer::idTypeOptions() as $value => $label)
                                    <option value="{{ $value }}" {{ $selectedIdType === strtolower($value) ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('id_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Namba ya Kitambulisho</label>
                            <input type="text" name="id_number" class="form-control @error('id_number') is-invalid @enderror"
                                value="{{ old('id_number', $customer->id_number ?? '') }}" placeholder="Weka namba ya kitambulisho">
                            @error('id_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Jina la Benki</label>
                            <input type="text" name="bank_name" class="form-control @error('bank_name') is-invalid @enderror"
                                value="{{ old('bank_name', $customer->bank_name ?? '') }}" placeholder="Weka jina la benki">
                            @error('bank_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Akaunti ya Benki</label>
                            <input type="text" name="bank_account_number" class="form-control @error('bank_account_number') is-invalid @enderror"
                                value="{{ old('bank_account_number', $customer->bank_account_number ?? '') }}" placeholder="Weka namba ya akaunti">
                            @error('bank_account_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Jina la Akaunti</label>
                            <input type="text" name="account_name" class="form-control @error('account_name') is-invalid @enderror"
                                value="{{ old('account_name', $customer->account_name ?? '') }}" placeholder="Weka jina la akaunti">
                            @error('account_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        @if(!$isEdit)
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" value="1" name="send_welcome_sms"
                                    id="send_welcome_sms" {{ old('send_welcome_sms') ? 'checked' : '' }}>
                                <label class="form-check-label" for="send_welcome_sms">Tuma SMS ya kukaribisha</label>
                            </div>
                        </div>
                        @endif

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hali <span class="text-danger">*</span></label>
                            <select name="status" class="form-control @error('status') is-invalid @enderror">
                                <option value="active" {{ old('status', $customer->status ?? 'active') == 'active' ? 'selected' : '' }}>Hai</option>
                                <option value="inactive" {{ old('status', $customer->status ?? 'active') == 'inactive' ? 'selected' : '' }}>Haifanyi kazi</option>
                                <option value="suspended" {{ old('status', $customer->status ?? 'active') == 'suspended' ? 'selected' : '' }}>Imesimamishwa</option>
                            </select>
                            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label">Maelezo</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                      rows="3" placeholder="Weka maelezo ya mteja">{{ old('description', $customer->description ?? '') }}</textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($isEdit)
        <div class="col-12">
            <div class="card radius-10 mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bx bx-building me-2"></i>Taarifa za Kampuni</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jina la Kampuni</label>
                            <input type="text" name="company_name" class="form-control @error('company_name') is-invalid @enderror"
                                value="{{ old('company_name', $customer->company_name ?? '') }}" placeholder="Weka jina la kampuni">
                            @error('company_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Namba ya Usajili wa Kampuni</label>
                            <input type="text" name="company_registration_number" class="form-control @error('company_registration_number') is-invalid @enderror"
                                value="{{ old('company_registration_number', $customer->company_registration_number ?? '') }}" placeholder="Weka namba ya usajili">
                            @error('company_registration_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Namba ya TIN</label>
                            <input type="text" name="tin_number" class="form-control @error('tin_number') is-invalid @enderror"
                                value="{{ old('tin_number', $customer->tin_number ?? '') }}" placeholder="Weka namba ya TIN" pattern="[0-9]+">
                            <div class="form-text">Weka nambari tu (bila alama maalum)</div>
                            @error('tin_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Namba ya VAT</label>
                            <input type="text" name="vat_number" class="form-control @error('vat_number') is-invalid @enderror"
                                value="{{ old('vat_number', $customer->vat_number ?? '') }}" placeholder="Weka namba ya VAT">
                            <div class="form-text">Weka herufi na nambari tu (bila alama maalum)</div>
                            @error('vat_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <hr class="my-4">

    <div class="d-flex justify-content-between">
        <a href="{{ route('customers.index') }}" class="btn btn-secondary">
            <i class="bx bx-arrow-back me-1"></i> Rudi kwa Wateja
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-1"></i> {{ $isEdit ? 'Hifadhi Mabadiliko' : 'Sajili Mteja' }}
        </button>
    </div>
</form>

@if($isEdit)
@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('input[name="tin_number"]').forEach(function (input) {
            input.addEventListener('input', function (e) {
                e.target.value = e.target.value.replace(/\D/g, '');
            });
        });

        document.querySelectorAll('input[name="vat_number"]').forEach(function (input) {
            input.addEventListener('input', function (e) {
                e.target.value = e.target.value.replace(/[^A-Za-z0-9]/g, '');
            });
        });
    });
</script>
@endpush
@endif
