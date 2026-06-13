@extends('layouts.main')
@section('title', 'Hariri Mteja')

@section('content')
<div class="page-wrapper">
    <div class="page-content">        
        <x-breadcrumbs :links="[
            ['label' => 'Dashibodi', 'url' => route('dashboard')],
            ['label' => 'Wateja', 'url' => route('customers.index')],
            ['label' => 'Hariri Mteja']
        ]" />
        <h6 class="mb-0 text-uppercase">HARIRI MTEJA</h6>
        <hr/>
        <div class="card">
            <div class="card-body">
                @include('customers.form')
            </div>
        </div>       
    </div>
</div>
@endsection