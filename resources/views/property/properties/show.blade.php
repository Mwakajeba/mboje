@extends('layouts.main')

@section('title', 'Property Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hotel & Property Management', 'url' => route('hotel.management.index'), 'icon' => 'bx bx-building-house'],
            ['label' => 'Property Management', 'url' => route('properties.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Property Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="card-title">{{ $property->name }}</h4>
                            <p class="card-subtitle text-muted">{{ ucfirst($property->type) }} Property</p>
                        </div>
                        <div>
                            <a href="{{ route('properties.edit', $property) }}" class="btn btn-primary">
                                <i class="bx bx-edit me-1"></i> Edit Property
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Basic Information</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Property Name:</strong></td>
                                    <td>{{ $property->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Type:</strong></td>
                                    <td><span class="badge bg-light text-dark">{{ ucfirst($property->type) }}</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        @switch($property->status)
                                            @case('active')
                                                <span class="badge bg-success">Active</span>
                                                @break
                                            @case('inactive')
                                                <span class="badge bg-secondary">Inactive</span>
                                                @break
                                            @case('maintenance')
                                                <span class="badge bg-warning">Maintenance</span>
                                                @break
                                            @case('sold')
                                                <span class="badge bg-info">Sold</span>
                                                @break
                                        @endswitch
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Current Value:</strong></td>
                                    <td>
                                        @if($property->current_value)
                                            TSh {{ number_format($property->current_value) }}
                                        @else
                                            <span class="text-muted">Not set</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Location Details</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Address:</strong></td>
                                    <td>{{ $property->address ?: 'Not specified' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>City:</strong></td>
                                    <td>{{ $property->city ?: 'Not specified' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>State:</strong></td>
                                    <td>{{ $property->state ?: 'Not specified' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Country:</strong></td>
                                    <td>{{ $property->country ?: 'Not specified' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    @if($property->description)
                        <div class="row mt-4">
                            <div class="col-12">
                                <h6 class="text-muted mb-3">Description</h6>
                                <p>{{ $property->description }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
