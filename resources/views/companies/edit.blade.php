@extends('layouts.main')
@section('title', 'Edit Company')

@section('content')
<div class="page-wrapper">
    <div class="page-content">        
        <h6 class="mb-0 text-uppercase">EDIT COMPANY</h6>
        <hr/>
        <div class="card">
            <div class="card-body">
                @include('companies.form')
            </div>
        </div>       
    </div>
</div>
@endsection