@extends('layouts.auth')

@section('title', 'SmartAccounting – Select Branch')

@section('content')
    <div class="authentication-header"></div>
    <div class="section-authentication-signin d-flex align-items-center justify-content-center my-5 my-lg-0">
        <div class="container">
            <div class="row row-cols-1 row-cols-lg-2 row-cols-xl-3">
                <div class="col mx-auto">
                    <div class="mb-4 text-center">
                        <img src="{{ asset('assets/images/logo1.png') }}" width="180" alt="" />
                    </div>
                    <div class="card rounded-4 shadow-lg">
                        <div class="card-body">
                            <div class="p-4 rounded">
                                <div class="text-center mb-3">
                                    <img src="{{ asset('assets/images/icons/lock.png')}}" width="120" alt="" />
                                </div>
                                <div class="login-separater text-center mb-4">
                                    <span class="fw-bold fs-5">Select Branch</span>
                                    <hr />
                                </div>
                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        {{ $errors->first() }}
                                    </div>
                                @endif
                                <form method="POST" action="{{ route('change-branch.submit') }}">
                                    @csrf
                                    <div class="form-group mb-4">
                                        <label for="branch_id" class="form-label fw-semibold">Branch</label>
                                        <select name="branch_id" id="branch_id" class="form-control rounded-pill px-3 py-2" required onchange="filterLocations()">
                                            <option value="">Choose branch...</option>
                                            @foreach($branches as $branch)
                                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group mb-4">
                                        <label for="location_id" class="form-label fw-semibold">Location* (required)</label>
                                        <select name="location_id" id="location_id" class="form-control rounded-pill px-3 py-2" required disabled>
                                            <option value="">Select branch first</option>
                                            @foreach($locations as $location)
                                                <option value="{{ $location->id }}" data-branch="{{ $location->branch_id }}">{{ $location->name }} ({{ optional($location->branch)->name }})</option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Only locations assigned to you are listed. Filtered by selected branch.</small>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill mt-2">Continue</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
document.addEventListener('DOMContentLoaded', function() {
    var branchSelect = document.getElementById('branch_id');
    var locationSelect = document.getElementById('location_id');

    function filterLocations() {
        var branchId = branchSelect.value;
        locationSelect.selectedIndex = 0;

        console.log('Selected branch ID:', branchId);

        if (!branchId) {
            locationSelect.disabled = true;
            locationSelect.innerHTML = '<option value="">Select branch first</option>';
            return;
        }

        locationSelect.disabled = true;
        locationSelect.innerHTML = '<option value="">Loading...</option>';

        fetch("{{ route('change-branch.locations') }}?branch_id=" + encodeURIComponent(branchId), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin', // include session cookie for auth
            method: 'GET'
        })
            .then(function(res){
                if (!res.ok) {
                    throw new Error('HTTP ' + res.status);
                }
                return res.json();
            })
            .then(function(data){
                locationSelect.innerHTML = '<option value="">Select location</option>';
                data.forEach(function(loc){
                    var opt = document.createElement('option');
                    opt.value = loc.id;
                    opt.textContent = loc.name;
                    opt.setAttribute('data-branch', loc.branch_id);
                    locationSelect.appendChild(opt);
                });
                locationSelect.disabled = false;
            })
            .catch(function(){
                locationSelect.innerHTML = '<option value="">Failed to load</option>';
            });
    }

    // Expose for inline onchange handler
    window.filterLocations = filterLocations;

    if (branchSelect && locationSelect) {
        branchSelect.addEventListener('change', filterLocations);
        filterLocations();
    }
});
</script>
@endpush

