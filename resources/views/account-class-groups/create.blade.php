@extends('layouts.main')
@section('title', 'Create Account Class Group')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Account Class Groups', 'url' => route('accounting.account-class-groups.index'), 'icon' => 'bx bx-category'],
            ['label' => 'Create Group', 'url' => '#', 'icon' => 'bx bx-plus-circle']
        ]" />

            <h6 class="mb-0 text-uppercase">CREATE NEW ACCOUNT CLASS GROUP (FSLI)</h6>
            <hr />
            
            <div class="row">
                <!-- Left Column - Form -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="bx bx-plus-circle text-primary me-2"></i>FSLI Information
                            </h5>
                            @include('account-class-groups.form')
                        </div>
                    </div>
                </div>

                <!-- Right Column - Guidelines -->
                <div class="col-lg-4">
                    <div class="card border-info">
                        <div class="card-header bg-info text-white">
                            <h5 class="card-title mb-0">
                                <i class="bx bx-info-circle me-2"></i>Guidelines
                            </h5>
                        </div>
                        <div class="card-body">
                            <h6 class="text-info mb-3">
                                <i class="bx bx-question-mark me-1"></i>What is an FSLI?
                            </h6>
                            <p class="small">
                                FSLI (Financial Statement Line Item) represents specific line items that appear on financial statements. They are sub-categories within Main Groups.
                            </p>

                            <hr>

                            <h6 class="text-info mb-3">
                                <i class="bx bx-sitemap me-1"></i>Hierarchy Structure
                            </h6>
                            <div class="small mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-primary me-2">1</span>
                                    <strong>Account Class</strong>
                                    <span class="ms-2 text-muted">(Assets, Liabilities, etc.)</span>
                                </div>
                                <div class="d-flex align-items-center mb-2 ms-3">
                                    <i class="bx bx-subdirectory-right me-2"></i>
                                    <span class="badge bg-purple me-2">2</span>
                                    <strong>Main Group</strong>
                                    <span class="ms-2 text-muted">(Fixed Assets, Current Assets)</span>
                                </div>
                                <div class="d-flex align-items-center mb-2 ms-4">
                                    <i class="bx bx-subdirectory-right me-2"></i>
                                    <span class="badge bg-success me-2">3</span>
                                    <strong>FSLI (This Level)</strong>
                                    <span class="ms-2 text-muted">(Property, Inventory)</span>
                                </div>
                                <div class="d-flex align-items-center ms-5">
                                    <i class="bx bx-subdirectory-right me-2"></i>
                                    <span class="badge bg-secondary me-2">4</span>
                                    <strong>Chart Accounts</strong>
                                    <span class="ms-2 text-muted">(Building, Land)</span>
                                </div>
                            </div>

                            <hr>

                            <h6 class="text-info mb-3">
                                <i class="bx bx-list-ul me-1"></i>How to Create
                            </h6>
                            <ol class="small">
                                <li class="mb-2">
                                    <strong>Select Main Group:</strong> Choose the parent main group (e.g., "Fixed Assets")
                                </li>
                                <li class="mb-2">
                                    <strong>Group Code:</strong> Optional unique code (e.g., 1100, 1200)
                                </li>
                                <li class="mb-2">
                                    <strong>Enter FSLI Name:</strong> Specific line item name (e.g., "Property, Plant & Equipment")
                                </li>
                            </ol>

                            <hr>

                            <h6 class="text-info mb-3">
                                <i class="bx bx-bulb me-1"></i>Examples
                            </h6>
                            <div class="small">
                                <div class="mb-3">
                                    <strong class="text-primary">Main Group:</strong> Fixed Assets
                                    <ul class="mt-1 mb-0">
                                        <li>Property, Plant & Equipment</li>
                                        <li>Intangible Assets</li>
                                        <li>Long-term Investments</li>
                                    </ul>
                                </div>
                                <div class="mb-3">
                                    <strong class="text-primary">Main Group:</strong> Current Assets
                                    <ul class="mt-1 mb-0">
                                        <li>Cash and Cash Equivalents</li>
                                        <li>Accounts Receivable</li>
                                        <li>Inventory</li>
                                    </ul>
                                </div>
                                <div class="mb-3">
                                    <strong class="text-primary">Main Group:</strong> Operating Expenses
                                    <ul class="mt-1 mb-0">
                                        <li>Salaries and Wages</li>
                                        <li>Rent Expenses</li>
                                        <li>Utilities</li>
                                    </ul>
                                </div>
                            </div>

                            <hr>

                            <h6 class="text-info mb-2">
                                <i class="bx bx-error me-1"></i>Important Notes
                            </h6>
                            <ul class="small text-muted mb-0">
                                <li>FSLI names should be unique within the system</li>
                                <li>They appear on financial statements</li>
                                <li>Account class is inherited from Main Group</li>
                                <li>Choose descriptive names for clarity</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .text-purple { color: #6f42c1 !important; }
    .bg-purple { background-color: #6f42c1 !important; }
</style>
@endpush