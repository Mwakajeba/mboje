@extends('layouts.main')
@section('title', 'Create Category')

@section('content')
<div class="page-wrapper">
    <div class="page-content"> 
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory', 'url' => '#', 'icon' => 'bx bx-package'],
            ['label' => 'Categories', 'url' => route('inventory.categories.index'), 'icon' => 'bx bx-category'],
            ['label' => 'Create Category', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">CREATE NEW CATEGORY</h6>
        <hr/>
        <div class="card">
            <div class="card-body">
                @include('inventory.categories.form')
            </div>
        </div>       
    </div>
</div>
@endsection
