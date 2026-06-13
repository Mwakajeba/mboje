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
                            <label class="form-label">Namba ya Simu</label>
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
    </div>

    <hr class="my-4">

    <div class="d-flex justify-content-between">
        <a href="{{ route('customers.index') }}" class="btn btn-secondary">
            <i class="bx bx-arrow-back me-1"></i> Rudi kwa Wateja
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-1"></i> {{ $isEdit ? 'Sasisha Mteja' : 'Sajili Mteja' }}
        </button>
    </div>
</form>
