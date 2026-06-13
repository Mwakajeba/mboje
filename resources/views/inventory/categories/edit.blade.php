@extends('layouts.main')
@section('title', 'Hariri Kategoria')

@section('content')
<div class="page-wrapper">
    <div class="page-content"> 
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Usimamizi wa Hesabu', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Makundi', 'url' => route('inventory.categories.index'), 'icon' => 'bx bx-category'],
            ['label' => 'Hariri Kategoria', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        <h6 class="mb-0 text-uppercase">HARIRI KATEGORIA</h6>
        <hr/>
        <div class="card">
            <div class="card-body">
                @include('inventory.categories.form')
            </div>
        </div>       
    </div>
</div>
@endsection
