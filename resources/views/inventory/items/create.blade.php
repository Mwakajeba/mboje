@extends('layouts.main')

@section('title', 'Sajili Bidhaa')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hesabu', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Bidhaa', 'url' => route('inventory.items.index'), 'icon' => 'bx bx-box'],
            ['label' => 'Sajili Bidhaa', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">Sajili Bidhaa Mpya</h4>
                            <a href="{{ route('inventory.items.index') }}" class="btn btn-secondary">
                                <i class="bx bx-arrow-back me-1"></i> Rudi kwa Bidhaa
                            </a>
                        </div>

                        @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bx bx-error-circle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        @endif

                        <form action="{{ route('inventory.items.store') }}" method="POST">
                            @csrf
                            
                            @include('inventory.items.form')

                            <div class="row mt-4">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-save me-1"></i> Sajili Bidhaa
                                    </button>
                                    <a href="{{ route('inventory.items.index') }}" class="btn btn-secondary ms-2">
                                        <i class="bx bx-x me-1"></i> Ghairi
                                    </a>
                                </div>
                            </div>
                        </form>
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
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('.select2-single').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
        $('.select2-multi').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Matawi yote',
            closeOnSelect: false
        });
    }
});
</script>
@endpush
