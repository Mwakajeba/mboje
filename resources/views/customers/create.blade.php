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
        <div class="card">
            <div class="card-body">
                @include('customers.form')
            </div>
        </div>       
    </div>
</div>
@endsection