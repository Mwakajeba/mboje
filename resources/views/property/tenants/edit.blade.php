@extends('layouts.main')

@section('title', 'Edit Tenant')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Property Management', 'url' => route('hotel.management.index'), 'icon' => 'bx bx-buildings'],
            ['label' => 'Tenants', 'url' => route('tenants.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Edit Tenant #{{ $id }}</h4>
            </div>
            <div class="card-body">
                <div class="alert alert-info">Tenant edit form placeholder for ID {{ $id }}.</div>
            </div>
        </div>
    </div>
</div>
@endsection


