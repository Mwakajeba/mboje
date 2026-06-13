@extends('layouts.main')
@section('title', 'Badili Mteja')

@section('content')
<div class="page-wrapper">
    <div class="page-content"> 
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Wateja', 'url' => route('customers.index'), 'icon' => 'bx bx-group'],
            ['label' => 'Badili Mteja', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        <h6 class="mb-0 text-uppercase">BADILI MTEJA</h6>
        <hr/>
        <div class="card">
            <div class="card-body">
                @include('customers.form')
            </div>
        </div>       
    </div>
</div>
@endsection
