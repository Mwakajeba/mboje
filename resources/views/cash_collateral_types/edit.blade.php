@extends('layouts.main')
@section('title', 'Badili Akaunti ya Mkopo')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Akaunti za Mikopo', 'url' => route('cash_collateral_types.index'), 'icon' => 'bx bx-credit-card'],
            ['label' => 'Badili Akaunti ya Mkopo', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />        
        <h6 class="mb-0 text-uppercase">BADILI AKAUNTI YA MKOPO</h6>
        <hr/>
        <div class="card">
            <div class="card-body">
                @include('cash_collateral_types.form')
            </div>
        </div>       
    </div>
</div>
@endsection
