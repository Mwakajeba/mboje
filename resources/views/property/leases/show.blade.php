@extends('layouts.main')

@section('title', 'Lease Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Property Management', 'url' => route('hotel.management.index'), 'icon' => 'bx bx-buildings'],
            ['label' => 'Leases', 'url' => route('leases.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Lease #{{ $id }}</h4>
            </div>
            <div class="card-body">
                <div class="text-center text-muted py-5">
                    <i class="bx bx-detail" style="font-size: 3rem;"></i>
                    <p class="mt-2">Lease details page placeholder for ID {{ $id }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


