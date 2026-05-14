@extends('layouts.main')
@section('title', 'Create Main Group')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Main Groups', 'url' => route('accounting.main-groups.index'), 'icon' => 'bx bx-category'],
            ['label' => 'Create Main Group', 'url' => '#', 'icon' => 'bx bx-plus-circle']
        ]" />

            <h6 class="mb-0 text-uppercase">CREATE NEW MAIN GROUP</h6>
            <hr />
            
            <div class="row">
                <!-- Left Column - Form -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="bx bx-plus-circle text-primary me-2"></i>Main Group Information
                            </h5>
                            @include('main-groups.form')
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
                                <i class="bx bx-question-mark me-1"></i>What is a Main Group?
                            </h6>
                            <p class="small">
                                Main Groups are primary classifications within your chart of accounts that organize accounts into logical categories for reporting and analysis.
                            </p>

                            <hr>

                            <h6 class="text-info mb-3">
                                <i class="bx bx-list-ul me-1"></i>How to Create
                            </h6>
                            <ol class="small">
                                <li class="mb-2">
                                    <strong>Select Account Class:</strong> Choose the appropriate class (Assets, Liabilities, Equity, Income, or Expenses)
                                </li>
                                <li class="mb-2">
                                    <strong>Enter Name:</strong> Provide a clear, descriptive name for the main group
                                </li>
                                <li class="mb-2">
                                    <strong>Add Description:</strong> Optional but recommended for clarity
                                </li>
                                <li class="mb-2">
                                    <strong>Set Status:</strong> Choose Active to enable usage immediately
                                </li>
                            </ol>

                            <hr>

                            <h6 class="text-info mb-3">
                                <i class="bx bx-bulb me-1"></i>Examples
                            </h6>
                            <div class="small">
                                <div class="mb-2">
                                    <span class="badge bg-success">Assets</span>
                                    <ul class="mt-1 mb-0">
                                        <li>Fixed Assets</li>
                                        <li>Current Assets</li>
                                        <li>Intangible Assets</li>
                                    </ul>
                                </div>
                                <div class="mb-2">
                                    <span class="badge bg-warning">Liabilities</span>
                                    <ul class="mt-1 mb-0">
                                        <li>Current Liabilities</li>
                                        <li>Long-term Liabilities</li>
                                    </ul>
                                </div>
                                <div class="mb-2">
                                    <span class="badge bg-primary">Expenses</span>
                                    <ul class="mt-1 mb-0">
                                        <li>Operating Expenses</li>
                                        <li>Administrative Expenses</li>
                                        <li>Cost of Sales</li>
                                    </ul>
                                </div>
                            </div>

                            <hr>

                            <h6 class="text-info mb-2">
                                <i class="bx bx-error me-1"></i>Important Notes
                            </h6>
                            <ul class="small text-muted mb-0">
                                <li>Main group names should be unique</li>
                                <li>Choose names that clearly indicate the purpose</li>
                                <li>Inactive groups won't appear in account creation forms</li>
                                <li>Main groups organize sub-groups and accounts</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
