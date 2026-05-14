@extends('layouts.main')

@section('title', 'Inventory Location Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Settings', 'url' => route('settings.index'), 'icon' => 'bx bx-cog'],
            ['label' => 'Inventory Management', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Inventory Locations', 'url' => route('settings.inventory.locations.index'), 'icon' => 'bx bx-map'],
            ['label' => $location->name, 'url' => '#', 'icon' => 'bx bx-detail']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">Location Details: {{ $location->name }}</h4>
                            <div class="btn-group">
                                <a href="{{ route('settings.inventory.locations.edit', $location->hash_id) }}" class="btn btn-primary">
                                    <i class="bx bx-edit me-1"></i> Edit
                                </a>
                                <a href="{{ route('settings.inventory.locations.index') }}" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Back to Locations
                                </a>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card border-primary">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="bx bx-info-circle me-2"></i>Basic Information
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-4"><strong>Name:</strong></div>
                                            <div class="col-8">{{ $location->name }}</div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-4"><strong>Status:</strong></div>
                                            <div class="col-8">{!! $location->status_badge !!}</div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-4"><strong>Manager:</strong></div>
                                            <div class="col-8">{{ $location->manager_name }}</div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-4"><strong>Created By:</strong></div>
                                            <div class="col-8">{{ $location->creator->name ?? 'N/A' }}</div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-4"><strong>Created:</strong></div>
                                            <div class="col-8">{{ $location->created_at->format('d M Y, h:i A') }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4"><strong>Last Updated:</strong></div>
                                            <div class="col-8">{{ $location->updated_at->format('d M Y, h:i A') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card border-info">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="bx bx-detail me-2"></i>Description
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        @if($location->description)
                                            <p class="mb-0">{{ $location->description }}</p>
                                        @else
                                            <p class="text-muted mb-0">No description provided.</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="card border-success mt-3">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="bx bx-building me-2"></i>Branch Information
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-4"><strong>Branch:</strong></div>
                                            <div class="col-8">{{ $location->branch->name ?? 'N/A' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
