@extends('layouts.main')
@section('title', 'Sajili Kategoria')

@section('content')
<div class="page-wrapper">
    <div class="page-content"> 
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Usimamizi wa Hesabu', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Makundi', 'url' => route('inventory.categories.index'), 'icon' => 'bx bx-category'],
            ['label' => 'Sajili Kategoria', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">SAJILI KATEGORIA MPYA</h6>
        <hr/>
        <div class="card">
            <div class="card-body">
                @include('inventory.categories.form')
            </div>
        </div>       
    </div>
</div>
@endsection
