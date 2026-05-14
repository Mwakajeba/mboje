@extends('layouts.main')
@section('title', 'Edit Cash Deposit')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Cash Deposits', 'url' => route('cash_deposits.index'), 'icon' => 'bx bx-credit-card'],
            ['label' => 'Edit Cash Deposit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />        
        <h6 class="mb-0 text-uppercase">EDIT CASH DEPOSIT</h6>
        <hr/>
        <div class="card">
            <div class="card-body">
                @include('cash_deposits.form')
            </div>
        </div>       
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize Select2 for searchable dropdowns
    $('.select2-single').select2({
        placeholder: 'Select',
        allowClear: true,
        width: '100%',
        theme: 'bootstrap-5'
    });
    
    // Force Select2 to update after initialization to show pre-selected values
    setTimeout(function() {
        $('#customer_id').trigger('change.select2');
        $('#type_id').trigger('change.select2');
    }, 200);
});
</script>
@endpush