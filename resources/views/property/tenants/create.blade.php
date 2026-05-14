@extends('layouts.main')

@section('title', 'Create Tenant')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Property Management', 'url' => route('hotel.management.index'), 'icon' => 'bx bx-buildings'],
            ['label' => 'Tenants', 'url' => route('tenants.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Create Tenant</h4>
            </div>
            <div class="card-body">
                <div class="alert alert-info">This is a placeholder for tenant creation.</div>
            </div>
        </div>
    </div>
</div>
@endsection


