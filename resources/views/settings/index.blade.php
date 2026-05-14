@extends('layouts.main')

@section('title', 'Settings')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Settings', 'url' => '#', 'icon' => 'bx bx-cog']
        ]" />
        <h6 class="mb-0 text-uppercase">SETTINGS</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        @can('manage system configurations')
                        <h4 class="card-title mb-4">System Settings</h4>

                        @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bx bx-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        @endif

                        @if(isset($errors) && $errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bx bx-error-circle me-2"></i>
                            Please fix the following errors:
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        @endif
                        @endcan

                        <div class="row">
                            <!-- Company Settings -->
                            @can('manage campany setting')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-building fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Company Settings</h5>
                                        <p class="card-text">Manage your company information and preferences.</p>
                                        <a href="{{ route('settings.company') }}" class="btn btn-primary">
                                            <i class="bx bx-cog me-1"></i> Configure
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Branch Settings -->
                            @can('manage branch setting')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-git-branch fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Branch Settings</h5>
                                        <p class="card-text">Manage branches and their configurations.</p>
                                        <a href="{{ route('settings.branches') }}" class="btn btn-success">
                                            <i class="bx bx-cog me-1"></i> Configure
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan


                            <!-- User Settings -->
                            @can('manage user setting')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-user fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">User Settings</h5>
                                        <p class="card-text">Manage user preferences and permissions.</p>
                                        <a href="{{ route('settings.user') }}" class="btn btn-info">
                                            <i class="bx bx-cog me-1"></i> Configure
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- System Settings -->
                            @can('view system configurations')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-cog fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">System Settings</h5>
                                        <p class="card-text">Configure system-wide settings and preferences.</p>
                                        <a href="{{ route('settings.system') }}" class="btn btn-warning">
                                            <i class="bx bx-cog me-1"></i> Configure
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Backup Settings -->
                            @can('view backup settings')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-data fs-1 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">Backup Settings</h5>
                                        <p class="card-text">Manage data backup and restore operations.</p>
                                        <a href="{{ route('settings.backup') }}" class="btn btn-danger">
                                            <i class="bx bx-cog me-1"></i> Configure
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Budget Settings -->
                            @can('view system configurations')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-teal">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-chart fs-1 text-teal"></i>
                                        </div>
                                        <h5 class="card-title">Budget Settings</h5>
                                        <p class="card-text">Configure budget checking and over-budget allowances.</p>
                                        <a href="{{ route('settings.budget') }}" class="btn btn-teal">
                                            <i class="bx bx-cog me-1"></i> Configure
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- AI Assistant -->
                            {{-- @can('use AI assistant')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-purple">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-bot fs-1 text-purple"></i>
                                        </div>
                                        <h5 class="card-title">AI Assistant</h5>
                                        <p class="card-text">Get intelligent reports and insights with AI-powered
                                            analysis.</p>
                                        <a href="{{ route('settings.ai') }}" class="btn btn-purple">
                                            <i class="bx bx-bot me-1"></i> Start Chat
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan --}}

                            <!-- Roles & Permissions -->
                            @can('manage role & permission')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-secondary">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-shield fs-1 text-secondary"></i>
                                        </div>
                                        <h5 class="card-title">Roles & Permissions</h5>
                                        <p class="card-text">Manage user roles, permissions, and access control.</p>
                                        <a href="{{ route('roles.index') }}" class="btn btn-secondary">
                                            <i class="bx bx-shield me-1"></i> Role, Permissions & Menu
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- SMS Setting -->
                           @role('super-admin')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-message-dots fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">SMS Setting</h5>
                                        <p class="card-text">
                                            Configure SMS gateway settings, API credentials, and SMS provider configurations.
                                            Manage SMS service integration and settings.
                                        </p>
                                        <a href="{{ route('settings.sms') }}" class="btn btn-warning">
                                            <i class="bx bx-message-dots me-1"></i> Configure SMS
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endrole

                            <!-- Manual Subscription Management (Super Admin) -->
                            @role('super-admin')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-credit-card fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Manual Subscription</h5>
                                        <p class="card-text">
                                            Create and manage subscriptions for all companies.
                                            Mark payments, extend subscriptions, and monitor expiry.
                                        </p>
                                        <a href="{{ route('subscriptions.dashboard') }}" class="btn btn-success">
                                            <i class="bx bx-cog me-1"></i> Manage Subscriptions
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endrole

                            <!-- activity logs Settings -->
                            @can('view logs activity')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-file fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Activity Logs Management</h5>
                                        <p class="card-text">
                                            Monitor and track all user activities in the system.
                                            Ensure accountability and review actions performed by users.
                                        </p>
                                        <a href="{{ route('settings.logs.index') }}" class="btn btn-primary">
                                            <i class="bx bx-list-ul me-1"></i>Manage Logs
                                        </a>
                                    </div>
                                </div>
                            </div>

                            @endcan

                            <!-- Inventory Setups -->
                            @can('manage inventory settings')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-package fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Inventory Setups</h5>
                                        <p class="card-text">Configure inventory settings, categories, units, and stock management preferences.</p>
                                        <a href="{{ route('settings.inventory') }}" class="btn btn-success">
                                            <i class="bx bx-cog me-1"></i> Configure
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Inventory Locations -->
                            @can('manage inventory locations')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-orange">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-map fs-1 text-orange"></i>
                                        </div>
                                        <h5 class="card-title">Inventory Locations</h5>
                                        <p class="card-text">Manage warehouse locations, storage areas, and inventory positioning systems.</p>
                                        <a href="{{ route('settings.inventory.locations.index') }}" class="btn btn-orange">
                                            <i class="bx bx-map-pin me-1"></i> Manage Locations
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            @can('manage payment voucher approval')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-check-shield fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Payment Voucher Approval</h5>
                                        <p class="card-text">
                                            Configure approval workflows for payment vouchers.
                                            Set up approval levels, approvers, and approval thresholds.
                                        </p>
                                        <a href="{{ route('settings.payment-voucher-approval') }}" class="btn btn-info">
                                            <i class="bx bx-check-shield me-1"></i> Configure Approval
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Account Transfer Approval -->
                            @can('manage system settings')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-transfer fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Account Transfer Approval</h5>
                                        <p class="card-text">
                                            Configure approval workflows for inter-account transfers.
                                            Set up approval levels, approvers, and approval thresholds.
                                        </p>
                                        <a href="{{ route('settings.account-transfer-approval') }}" class="btn btn-primary">
                                            <i class="bx bx-transfer me-1"></i> Configure Approval
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Provision Approval (IAS 37) -->
                            @can('manage system settings')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-shield-quarter fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Provision Approval (IAS 37)</h5>
                                        <p class="card-text">
                                            Configure approval workflows for IAS 37 provisions.
                                            Set up approval levels and approvers for provision recognition and changes.
                                        </p>
                                        <a href="{{ route('settings.provision-approval') }}" class="btn btn-success">
                                            <i class="bx bx-shield-quarter me-1"></i> Configure Approval
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Petty Cash Settings -->
                            @can('view system configurations')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-wallet fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Petty Cash Settings</h5>
                                        <p class="card-text">
                                            Configure petty cash operation mode (Sub-Imprest or Standalone) and system settings.
                                        </p>
                                        <a href="{{ route('settings.petty-cash') }}" class="btn btn-info">
                                            <i class="bx bx-cog me-1"></i> Configure
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Journal Entry Approval -->
                            @can('manage system settings')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-book-open fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Journal Entry Approval</h5>
                                        <p class="card-text">
                                            Configure approval workflows for journal entries.
                                            Set up approval levels, approvers, and enable approval requirements.
                                        </p>
                                        <a href="{{ route('settings.journal-entry-approval') }}" class="btn btn-warning">
                                            <i class="bx bx-book-open me-1"></i> Configure Approval
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Period-End Closing -->
                            @can('manage system settings')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-purple">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-calendar-check fs-1 text-purple"></i>
                                        </div>
                                        <h5 class="card-title">Period-End Closing</h5>
                                        <p class="card-text">
                                            Manage fiscal years, accounting periods, period closing workflows,
                                            year-end adjustments, and retained earnings roll.
                                        </p>
                                        <a href="{{ route('settings.period-closing.index') }}" class="btn btn-purple">
                                            <i class="bx bx-calendar-check me-1"></i> Manage Period Closing
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Approval Levels Management -->
                            @can('manage system settings')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-layer fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Approval Levels (Budget, Bank Reconciliation, Assets & HFS)</h5>
                                        <p class="card-text">
                                            Configure multi-level approval workflows and assign approvers for Budget, Bank Reconciliation,
                                            Asset Revaluation, Asset Impairment, Asset Disposal, and HFS Requests.
                                        </p>
                                        <a href="{{ route('settings.approval-levels.index') }}" class="btn btn-primary">
                                            <i class="bx bx-layer me-1"></i> Manage Approval Levels
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- LIPISHA Payment Gateway Settings -->
                            @can('manage system settings')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-credit-card fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">LIPISHA Payment Gateway</h5>
                                        <p class="card-text">
                                            Configure LIPISHA payment gateway credentials, business ID, API keys, and verify token for payment processing.
                                        </p>
                                        <a href="{{ route('settings.lipisha') }}" class="btn btn-success">
                                            <i class="bx bx-credit-card me-1"></i> Configure LIPISHA
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--end page wrapper -->
<!--start overlay-->
<div class="overlay toggle-icon"></div>
<!--end overlay-->
<!--Start Back To Top Button--> <a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
<!--End Back To Top Button-->
<footer class="page-footer">
    <p class="mb-0">Copyright © {{ date('Y') }}. All right reserved. -- By SAFCO FINTECH</p>
</footer>

@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    document.addEventListener('DOMContentLoaded', function() {
        // Keyboard shortcut: Ctrl + Shift + Q or Ctrl + Alt + Q to start queue worker
        document.addEventListener('keydown', function(e) {
            // Check for Ctrl + Shift + Q
            if (e.ctrlKey && e.shiftKey && e.key === 'Q') {
                e.preventDefault();
                startQueueWorker();
            }
            // Check for Ctrl + Alt + Q
            else if (e.ctrlKey && e.altKey && e.key === 'q') {
                e.preventDefault();
                startQueueWorker();
            }
        });

        function startQueueWorker() {
            // Show loading indicator
            Swal.fire({
                title: 'Starting Queue Worker...',
                text: 'Please wait while we start the queue worker.',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Make AJAX request to start queue worker
            fetch('{{ route("settings.queue-worker.start") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                if (data.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: data.message || 'Queue worker started successfully.',
                        icon: 'success',
                        timer: 3000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: data.message || 'Failed to start queue worker.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(error => {
                Swal.close();
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while starting the queue worker.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
        }

        // Show hint on page load (only once per session)
        if (!sessionStorage.getItem('queueWorkerHintShown')) {
            setTimeout(() => {
                Swal.fire({
                    title: 'Keyboard Shortcut Available',
                    html: 'Press <strong>Ctrl + Shift + Q</strong> or <strong>Ctrl + Alt + Q</strong> to start the queue worker from anywhere on this page.',
                    icon: 'info',
                    timer: 5000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
                sessionStorage.setItem('queueWorkerHintShown', 'true');
            }, 2000);
        }
    });
</script>
@endpush

@push('styles')
<style>
    .border-purple {
        border-color: #6f42c1 !important;
    }

    .text-purple {
        color: #6f42c1 !important;
    }

    .btn-purple {
        background-color: #6f42c1;
        border-color: #6f42c1;
        color: white;
    }

    .btn-purple:hover {
        background-color: #5a32a3;
        border-color: #5a32a3;
        color: white;
    }

    .border-orange {
        border-color: #fd7e14 !important;
    }

    .text-orange {
        color: #fd7e14 !important;
    }

    .btn-orange {
        background-color: #fd7e14;
        border-color: #fd7e14;
        color: white;
    }

    .btn-orange:hover {
        background-color: #e8690b;
        border-color: #e8690b;
        color: white;
    }

    .border-teal {
        border-color: #20c997 !important;
    }

    .text-teal {
        color: #20c997 !important;
    }

    .btn-teal {
        background-color: #20c997;
        border-color: #20c997;
        color: white;
    }

    .btn-teal:hover {
        background-color: #1ba37e;
        border-color: #1ba37e;
        color: white;
    }

    .border-danger {
        border-color: #dc3545 !important;
    }

    .text-danger {
        color: #dc3545 !important;
    }

    .btn-danger {
        background-color: #dc3545;
        border-color: #dc3545;
        color: white;
    }

    .btn-danger:hover {
        background-color: #bb2d3b;
        border-color: #bb2d3b;
        color: white;
    }

</style>
@endpush
