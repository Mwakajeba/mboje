@extends('layouts.main')

@section('title', 'Hotel Settings')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hotel & Property Management', 'url' => route('hotel.management.index'), 'icon' => 'bx bx-building-house'],
            ['label' => 'Hotel Management', 'url' => route('hotel.management.index'), 'icon' => 'bx bx-hotel'],
            ['label' => 'Settings', 'url' => '#', 'icon' => 'bx bx-cog']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Hotel Settings</h4>
                        <p class="card-subtitle text-muted">Terms and Conditions shown on booking PDF exports</p>
                    </div>
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('hotel.settings.update') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Terms and Conditions</label>
                                <textarea name="terms_and_conditions" class="form-control @error('terms_and_conditions') is-invalid @enderror" rows="10" placeholder="Enter your hotel terms and conditions. This text will appear in the footer of every booking PDF export.">{{ old('terms_and_conditions', $termsAndConditions) }}</textarea>
                                <small class="text-muted">This text is reused on every booking PDF (footer). Leave blank if you do not want a terms section.</small>
                                @error('terms_and_conditions')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i> Save Settings
                                </button>
                                <a href="{{ route('hotel.management.index') }}" class="btn btn-outline-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Back to Hotel Management
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
