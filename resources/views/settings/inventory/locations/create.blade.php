@extends('layouts.main')

@section('title', 'Create Inventory Location')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Settings', 'url' => route('settings.index'), 'icon' => 'bx bx-cog'],
            ['label' => 'Inventory Management', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Inventory Locations', 'url' => route('settings.inventory.locations.index'), 'icon' => 'bx bx-map'],
            ['label' => 'Create Location', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">Create New Inventory Location</h4>
                            <a href="{{ route('settings.inventory.locations.index') }}" class="btn btn-secondary">
                                <i class="bx bx-arrow-back me-1"></i> Back to Locations
                            </a>
                        </div>

                        @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bx bx-error-circle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        @endif

                        <form action="{{ route('settings.inventory.locations.store') }}" method="POST">
                            @csrf
                            
                            @include('settings.inventory.locations.form')

                            <div class="row mt-4">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-save me-1"></i> Create Location
                                    </button>
                                    <a href="{{ route('settings.inventory.locations.index') }}" class="btn btn-secondary ms-2">
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
