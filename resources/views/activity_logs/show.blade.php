@extends('layouts.main')
@section('title', 'Activity Log Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumbs -->
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Settings', 'url' => route('settings.index'), 'icon' => 'bx bx-cog'],
            ['label' => 'Activity Logs', 'url' => route('settings.logs.index'), 'icon' => 'bx bx-history'],
            ['label' => 'Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        <h6 class="mb-0 text-uppercase">ACTIVITY LOG DETAILS</h6>
        <hr />

        <!-- Page Header -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card border-top border-0 border-4 border-primary">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="card-title d-flex align-items-center">
                                    <div><i class="bx bx-show me-1 font-22 text-primary"></i></div>
                                    <h5 class="mb-0 text-primary">Activity Log Details</h5>
                                </div>
                                <p class="mb-0 text-muted">Detailed information about this activity log entry</p>
                            </div>
            <div class="col-md-4 text-end">
                <a href="{{ route('settings.logs.index') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Back to Logs
                </a>
            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Basic Information -->
            <div class="col-md-6">
                <div class="card radius-10 border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i>Basic Information</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%">Date & Time:</th>
                                <td>{{ $log->activity_time->format('F d, Y H:i:s') }}</td>
                            </tr>
                            <tr>
                                <th>User:</th>
                                <td>
                                    <strong>{{ $log->user->name ?? 'System' }}</strong>
                                    @if($log->user)
                                        <br><small class="text-muted">{{ $log->user->email ?? '' }}</small>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Model/Type:</th>
                                <td>{!! $log->model_badge !!}</td>
                            </tr>
                            <tr>
                                <th>Action:</th>
                                <td>{!! $log->action_badge !!}</td>
                            </tr>
                            <tr>
                                <th>Description:</th>
                                <td>{{ $log->description ?? 'N/A' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Technical Information -->
            <div class="col-md-6">
                <div class="card radius-10 border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bx bx-cog me-2"></i>Technical Details</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%">IP Address:</th>
                                <td><code>{{ $log->ip_address ?? 'N/A' }}</code></td>
                            </tr>
                            <tr>
                                <th>Device:</th>
                                <td>{{ $log->device ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Model ID:</th>
                                <td>{{ $log->model_id ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Created At:</th>
                                <td>{{ $log->created_at->format('F d, Y H:i:s') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- FX Revaluation Details (if applicable) -->
        @php
            $revaluation = null;
            if ($log->model === 'GlRevaluationHistory' && $log->model_id) {
                $revaluation = \App\Models\GlRevaluationHistory::find($log->model_id);
            }
        @endphp
        @if($revaluation)
        <div class="row">
            <div class="col-12">
                <div class="card radius-10 border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bx bx-refresh me-2"></i>FX Revaluation Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="40%">Item Type:</th>
                                        <td>
                                            <span class="badge bg-{{ $revaluation->item_type == 'AR' ? 'info' : ($revaluation->item_type == 'AP' ? 'warning' : ($revaluation->item_type == 'BANK' ? 'success' : 'secondary')) }}">
                                                {{ $revaluation->item_type }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Reference:</th>
                                        <td><strong>{{ $revaluation->item_ref }}</strong></td>
                                    </tr>
                                    <tr>
                                        <th>Revaluation Date:</th>
                                        <td>{{ $revaluation->revaluation_date->format('F d, Y') }}</td>
                                    </tr>
                                    <tr>
                                        <th>FCY Amount:</th>
                                        <td class="fw-bold">{{ number_format(abs($revaluation->fcy_amount), 2) }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="40%">Original Rate:</th>
                                        <td>{{ number_format($revaluation->original_rate, 6) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Closing Rate:</th>
                                        <td>{{ number_format($revaluation->closing_rate, 6) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Gain/Loss:</th>
                                        <td>
                                            <span class="fw-bold fs-5 {{ $revaluation->gain_loss >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ $revaluation->formatted_gain_loss }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Status:</th>
                                        <td>
                                            @if($revaluation->is_reversed)
                                                <span class="badge bg-secondary">Reversed</span>
                                            @else
                                                <span class="badge bg-success">Active</span>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="{{ route('accounting.fx-revaluation.show', $revaluation->id) }}" class="btn btn-primary">
                                <i class="bx bx-show me-1"></i> View Full Revaluation Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- FX Rate Details (if applicable) -->
        @php
            $fxRate = null;
            if ($log->model === 'FxRate' && $log->model_id) {
                $fxRate = \App\Models\FxRate::find($log->model_id);
            }
        @endphp
        @if($fxRate)
        <div class="row">
            <div class="col-12">
                <div class="card radius-10 border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bx bx-dollar me-2"></i>FX Rate Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="40%">Currency Pair:</th>
                                        <td>
                                            <span class="badge bg-info">{{ $fxRate->from_currency }}</span>
                                            <i class="bx bx-right-arrow-alt mx-2"></i>
                                            <span class="badge bg-success">{{ $fxRate->to_currency }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Rate Date:</th>
                                        <td>{{ $fxRate->rate_date->format('F d, Y') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Spot Rate:</th>
                                        <td class="fw-bold">{{ number_format($fxRate->spot_rate, 6) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Source:</th>
                                        <td>
                                            <span class="badge bg-{{ $fxRate->source == 'manual' ? 'primary' : ($fxRate->source == 'api' ? 'success' : 'warning') }}">
                                                {{ ucfirst($fxRate->source) }}
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="40%">Month-End Rate:</th>
                                        <td>{{ $fxRate->month_end_rate ? number_format($fxRate->month_end_rate, 6) : 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Average Rate:</th>
                                        <td>{{ $fxRate->average_rate ? number_format($fxRate->average_rate, 6) : 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Status:</th>
                                        <td>
                                            @if($fxRate->is_locked)
                                                <span class="badge bg-danger">Locked</span>
                                            @else
                                                <span class="badge bg-success">Unlocked</span>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="{{ route('accounting.fx-rates.edit', $fxRate->id) }}" class="btn btn-primary">
                                <i class="bx bx-show me-1"></i> View Full Rate Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

