@extends('layouts.main')

@section('title', 'Create Bank Account')
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
                ['label' => 'Bank Accounts', 'url' => route('accounting.bank-accounts'), 'icon' => 'bx bx-bank'],
                ['label' => 'Create Account', 'url' => '#', 'icon' => 'bx bx-plus-circle']
            ]" />

            <!-- Header Section -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0 text-dark fw-bold">
                                <i class="bx bx-bank me-2 text-primary"></i>
                                Create New Bank Account
                            </h4>
                            <p class="text-muted mb-0 mt-1">Add a new bank account to your accounting system</p>
                        </div>
                        @can('view bank accounts')
                        <div>
                            <a href="{{ route('accounting.bank-accounts') }}" class="btn btn-outline-secondary">
                                <i class="bx bx-arrow-back me-1"></i> Back to List
                            </a>
                        </div>
                        @endcan
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm border-0 radius-10">
                        <div class="card-header bg-primary text-white border-0">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h5 class="mb-0">
                                        <i class="bx bx-plus-circle me-2"></i>
                                        Bank Account Information
                                    </h5>
                                    <small class="opacity-75">Please fill in all required fields to create a new bank account</small>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            @include('bank-accounts.form')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection