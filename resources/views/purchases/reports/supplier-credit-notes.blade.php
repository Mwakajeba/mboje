@extends('layouts.main')

@section('title','Supplier Credit Notes')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchases', 'url' => route('purchases.index'), 'icon' => 'bx bx-shopping-bag'],
            ['label' => 'Reports', 'url' => route('purchases.reports.index'), 'icon' => 'bx bx-bar-chart'],
            ['label' => 'Supplier Credit Notes', 'url' => '#', 'icon' => 'bx bx-note']
        ]" />
        <div class="alert alert-info">This report will track supplier credit notes and applications. Coming soon.</div>
    </div>
</div>
@endsection


