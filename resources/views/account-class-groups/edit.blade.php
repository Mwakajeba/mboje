@extends('layouts.main')
@section('title', 'Edit Account Class Group')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Account Class Groups', 'url' => route('accounting.account-class-groups.index'), 'icon' => 'bx bx-category'],
            ['label' => 'Edit Group', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

            <h6 class="mb-0 text-uppercase">EDIT ACCOUNT CLASS GROUP (FSLI)</h6>
            <hr />
            
            <div class="row">
                <!-- Left Column - Form -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="bx bx-edit text-warning me-2"></i>Edit FSLI Information
                            </h5>
                            @include('account-class-groups.form')
                        </div>
                    </div>
                </div>

                <!-- Right Column - Guidelines -->
                <div class="col-lg-4">
                    <div class="card border-warning">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="card-title mb-0">
                                <i class="bx bx-info-circle me-2"></i>Update Guidelines
                            </h5>
                        </div>
                        <div class="card-body">
                            <h6 class="text-warning mb-3">
                                <i class="bx bx-edit me-1"></i>Editing FSLI
                            </h6>
                            <p class="small">
                                You are updating a Financial Statement Line Item. Changes will affect how accounts are organized and displayed on reports.
                            </p>

                            <hr>

                            <h6 class="text-warning mb-3">
                                <i class="bx bx-error-circle me-1"></i>Important Considerations
                            </h6>
                            <ul class="small">
                                <li class="mb-2">
                                    <strong>Main Group:</strong> Changing the main group will change the account class hierarchy
                                </li>
                                <li class="mb-2">
                                    <strong>Group Code:</strong> Update if you need a new unique identifier
                                </li>
                                <li class="mb-2">
                                    <strong>Name Changes:</strong> Will reflect on all financial statements
                                </li>
                                <li class="mb-2">
                                    <strong>Existing Accounts:</strong> All chart accounts under this FSLI will be affected
                                </li>
                            </ul>

                            <hr>

                            <h6 class="text-warning mb-3">
                                <i class="bx bx-shield-quarter me-1"></i>Best Practices
                            </h6>
                            <div class="small">
                                <ul>
                                    <li class="mb-2">Review the main group selection carefully</li>
                                    <li class="mb-2">Ensure the name accurately reflects the line item</li>
                                    <li class="mb-2">Check if any chart accounts are linked</li>
                                    <li class="mb-2">Coordinate changes with your accounting team</li>
                                </ul>
                            </div>

                            <hr>

                            <h6 class="text-warning mb-3">
                                <i class="bx bx-sitemap me-1"></i>Current Hierarchy
                            </h6>
                            <div class="small mb-3">
                                <div class="mb-2">
                                    <strong>Account Class:</strong><br>
                                    <span class="text-muted">{{ $accountClassGroup->accountClass->name ?? 'N/A' }}</span>
                                </div>
                                <div class="mb-2">
                                    <strong>Main Group:</strong><br>
                                    <span class="text-muted">{{ $accountClassGroup->mainGroup->name ?? 'N/A' }}</span>
                                </div>
                                <div class="mb-2">
                                    <strong>Current FSLI:</strong><br>
                                    <span class="text-muted">{{ $accountClassGroup->name }}</span>
                                </div>
                            </div>

                            <hr>

                            <div class="alert alert-warning small mb-0">
                                <i class="bx bx-info-circle me-1"></i>
                                <strong>Note:</strong> Changes to this FSLI will be reflected immediately on all financial statements and reports.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection