@extends('layouts.main')
@section('title', 'Create Cash Deposit')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Cash Deposits', 'url' => route('cash_deposits.index'), 'icon' => 'bx bx-credit-card'],
            ['label' => 'Create Cash Deposit', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />        
        <h6 class="mb-0 text-uppercase">CREATE NEW CASH DEPOSIT</h6>
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

    // Debug form submission
    $('#cashDepositForm').on('submit', function(e) {
        e.preventDefault(); // Prevent default form submission
        
        console.log('Form submitted');
        console.log('Form action:', $(this).attr('action'));
        console.log('Form method:', $(this).attr('method'));
        console.log('Customer ID:', $('#customer_id').val());
        console.log('Type ID:', $('#type_id').val());
        console.log('CSRF Token:', $('input[name="_token"]').val());
        
        // Check if required fields are filled
        if (!$('#customer_id').val()) {
            Swal.fire({
                title: 'Validation Error!',
                text: 'Please select a customer',
                icon: 'warning',
                confirmButtonText: 'OK',
                confirmButtonColor: '#ffc107'
            });
            return false;
        }
        
        if (!$('#type_id').val()) {
            Swal.fire({
                title: 'Validation Error!',
                text: 'Please select a deposit type',
                icon: 'warning',
                confirmButtonText: 'OK',
                confirmButtonColor: '#ffc107'
            });
            return false;
        }

        if (!$('#bank_account_id').val()) {
            Swal.fire({
                title: 'Validation Error!',
                text: 'Please select a bank account',
                icon: 'warning',
                confirmButtonText: 'OK',
                confirmButtonColor: '#ffc107'
            });
            return false;
        }

        if (!$('#deposit_date').val()) {
            Swal.fire({
                title: 'Validation Error!',
                text: 'Please select a deposit date',
                icon: 'warning',
                confirmButtonText: 'OK',
                confirmButtonColor: '#ffc107'
            });
            return false;
        }

        if (!$('#amount').val() || parseFloat($('#amount').val()) <= 0) {
            Swal.fire({
                title: 'Validation Error!',
                text: 'Please enter a valid amount',
                icon: 'warning',
                confirmButtonText: 'OK',
                confirmButtonColor: '#ffc107'
            });
            return false;
        }
        
        console.log('Form validation passed, showing confirmation...');
        
        // Show confirmation SweetAlert
        Swal.fire({
            title: 'Confirm Cash Deposit Creation',
            html: `
                <div class="text-left">
                    <p><strong>Customer:</strong> ${$('#customer_id option:selected').text()}</p>
                    <p><strong>Deposit Type:</strong> ${$('#type_id option:selected').text()}</p>
                    <p><strong>Bank Account:</strong> ${$('#bank_account_id option:selected').text()}</p>
                    <p><strong>Amount:</strong> TSHS ${parseFloat($('#amount').val()).toLocaleString()}</p>
                    <p><strong>Date:</strong> ${$('#deposit_date').val()}</p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Create Cash Deposit',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading state
                $('button[type="submit"]').prop('disabled', true).text('Creating...');
                
                // Show loading SweetAlert
                Swal.fire({
                    title: 'Creating Cash Deposit...',
                    text: 'Please wait while we process your request.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Submit form via AJAX for debugging
                $.ajax({
                    url: $('#cashDepositForm').attr('action'),
                    method: 'POST',
                    data: $('#cashDepositForm').serialize(),
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val()
                    },
                    success: function(response) {
                        console.log('Success response:', response);
                        // Close loading SweetAlert
                        Swal.close();
                        
                        Swal.fire({
                            title: 'Success!',
                            text: response.message || 'Cash deposit created successfully!',
                            icon: 'success',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#3085d6'
                        }).then((result) => {
                            window.location.href = '{{ route("cash_deposits.index") }}';
                        });
                    },
                    error: function(xhr, status, error) {
                        console.log('Error response:', xhr.responseText);
                        console.log('Status:', status);
                        console.log('Error:', error);
                        
                        // Close loading SweetAlert
                        Swal.close();
                        
                        let errorMessage = 'An error occurred while creating the cash deposit.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseText) {
                            errorMessage = xhr.responseText;
                        }
                        
                        Swal.fire({
                            title: 'Error!',
                            text: errorMessage,
                            icon: 'error',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#d33'
                        });
                        $('button[type="submit"]').prop('disabled', false).text('Create');
                    }
                });
            } else {
                // User cancelled, reset button state
                $('button[type="submit"]').prop('disabled', false).text('Create');
            }
        });

    // Debug form errors
    @if ($errors->any())
        console.log('Form errors:', @json($errors->all()));
    @endif

    // Debug session messages
    @if (session('error'))
        console.log('Session error:', @json(session('error')));
    @endif

    @if (session('success'))
        console.log('Session success:', @json(session('success')));
    @endif
    
    // Handle customer_id parameter from URL
    const urlParams = new URLSearchParams(window.location.search);
    const customerId = urlParams.get('customer_id');
    if (customerId) {
        // The server will handle the hash ID decoding, so we don't need to do anything here
        // The form will be pre-populated by the server-side logic
        console.log('Customer ID parameter detected:', customerId);
    }
});
</script>
@endpush