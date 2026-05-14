@extends('layouts.main')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Items', 'url' => route('inventory.items.index'), 'icon' => 'bx bx-box'],
            ['label' => 'Edit Item', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h5 class="card-title mb-0">Edit Item - {{ $item->name }}</h5>
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
                                        <i class="bx bx-save me-1"></i> Update Item
                                    </button>
                                    <a href="{{ route('inventory.items.index') }}" class="btn btn-secondary ms-2">
                                        <i class="bx bx-x me-1"></i> Cancel
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
