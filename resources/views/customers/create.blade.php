@extends('layouts.main')
@section('title', 'Sajili Mteja')

@section('content')
<div class="page-wrapper">
    <div class="page-content"> 
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Wateja', 'url' => route('customers.index'), 'icon' => 'bx bx-group'],
            ['label' => 'Sajili Mteja', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">SAJILI MTEJA MPYA</h6>
        <hr/>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Funga"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Funga"></button>
        </div>
        @endif

        <div class="card">
            <div class="card-body">
                @include('customers.form')
            </div>
        </div>       
    </div>
</div>
@endsection