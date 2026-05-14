@extends('layouts.main')
@section('title', 'Edit Category')

@section('content')
<div class="page-wrapper">
    <div class="page-content"> 
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory', 'url' => '#', 'icon' => 'bx bx-package'],
            ['label' => 'Categories', 'url' => route('inventory.categories.index'), 'icon' => 'bx bx-category'],
            ['label' => 'Edit Category', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        <h6 class="mb-0 text-uppercase">EDIT CATEGORY</h6>
        <hr/>
        <div class="card">
            <div class="card-body">
                @include('inventory.categories.form')
            </div>
        </div>       
    </div>
</div>
@endsection
