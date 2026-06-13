@extends('layouts.main')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hesabu', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Bidhaa', 'url' => route('inventory.items.index'), 'icon' => 'bx bx-box'],
            ['label' => 'Hariri Bidhaa', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h5 class="card-title mb-0">Hariri Bidhaa - {{ $item->name }}</h5>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('inventory.items.update', $item->hash_id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            
                            @include('inventory.items.form')

                            <div class="row mt-4">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-save me-1"></i> Hifadhi Mabadiliko
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
