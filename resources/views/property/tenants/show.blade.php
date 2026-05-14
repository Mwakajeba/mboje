@extends('layouts.main')

@section('title', 'Tenant Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Property Management', 'url' => route('hotel.management.index'), 'icon' => 'bx bx-buildings'],
            ['label' => 'Tenants', 'url' => route('tenants.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Tenant #{{ $id }}</h4>
            </div>
            <div class="card-body">
                <div class="text-center text-muted py-5">
                    <i class="bx bx-user-circle" style="font-size: 3rem;"></i>
                    <p class="mt-2">Tenant details placeholder for ID {{ $id }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


