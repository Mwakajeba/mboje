<div class="modal fade" id="addWorkerModal" tabindex="-1" aria-labelledby="addWorkerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('purchases.daily-accounts.employees.store') }}" id="add-worker-form">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addWorkerModalLabel">
                        <i class="bx bx-user-plus me-1"></i> Ongeza Mfanyakazi
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Funga"></button>
                </div>
                <div class="modal-body">
                    @if($errors->has('employee'))
                        <div class="alert alert-danger py-2">{{ $errors->first('employee') }}</div>
                    @endif

                    <div class="mb-3">
                        <label for="worker_name" class="form-label">Jina <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="worker_name" name="name" value="{{ old('name') }}" required autocomplete="off">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="worker_phone" class="form-label">Simu <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('phone') is-invalid @enderror" id="worker_phone" name="phone" value="{{ old('phone') }}" placeholder="07XXXXXXXX" required autocomplete="off">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="worker_role_id" class="form-label">Jukumu <span class="text-danger">*</span></label>
                        <select class="form-select @error('role_id') is-invalid @enderror" id="worker_role_id" name="role_id" required>
                            <option value="">Chagua jukumu</option>
                            @foreach($workerRoles as $role)
                                <option value="{{ $role->id }}" @selected((string) old('role_id') === (string) $role->id || (! old('role_id') && $role->name === 'sales person'))>
                                    {{ ucwords($role->name) }}
                                </option>
                            @endforeach
                        </select>
                        @error('role_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Neno la siri</label>
                        <input type="text" class="form-control bg-light" value="{{ \App\Services\Purchase\DailyAccountsEmployeeService::DEFAULT_PASSWORD }}" readonly>
                        <div class="form-text">Neno la siri la awali kwa mfanyakazi mpya.</div>
                    </div>

                    <p class="text-muted small mb-0 mt-3">
                        Tawi na eneo la stoo vitachukuliwa kutoka kwa akaunti yako ya sasa.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Funga</button>
                    <button type="submit" class="btn btn-secondary">
                        <i class="bx bx-save me-1"></i> Hifadhi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
