@extends('layouts.main')

@php
    use Vinkla\Hashids\Facades\Hashids;
@endphp

@section('title', 'Main Group Details')
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Main Groups', 'url' => route('accounting.main-groups.index'), 'icon' => 'bx bx-category'],
            ['label' => 'View Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

            <h6 class="mb-0 text-uppercase">MAIN GROUP DETAILS</h6>
            <hr />
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Account Class</h6>
                            <p class="fw-bold">{{ $mainGroup->accountClass->name ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Main Group Name</h6>
                            <p class="fw-bold">{{ $mainGroup->name }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Description</h6>
                            <p>{{ $mainGroup->description ?? 'No description provided' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Status</h6>
                            @if($mainGroup->status)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Created At</h6>
                            <p>{{ $mainGroup->created_at->format('M d, Y h:i A') }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Last Updated</h6>
                            <p>{{ $mainGroup->updated_at->format('M d, Y h:i A') }}</p>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <a href="{{ route('accounting.main-groups.index') }}" class="btn btn-secondary me-2">Back to List</a>
                        <a href="{{ route('accounting.main-groups.edit', Hashids::encode($mainGroup->id)) }}" class="btn btn-warning">
                            <i class="bx bx-edit"></i> Edit
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
