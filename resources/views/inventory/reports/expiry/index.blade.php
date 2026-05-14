@extends('layouts.main')

@section('title', 'Expiry Reports')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Inventory', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
                ['label' => 'Reports', 'url' => route('inventory.reports.index'), 'icon' => 'bx bx-bar-chart-alt-2'],
                ['label' => 'Expiry Reports', 'url' => '#', 'icon' => 'bx bx-calendar-x']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-calendar-x me-2"></i>Expiry Reports
                            </h4>
                        </div>

                        <div class="row">
                            <!-- Expiring Soon Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-time-five fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Expiring Soon</h5>
                                        <p class="card-text">Items expiring within specified days</p>
                                        
                                        <form method="GET" action="{{ route('inventory.reports.expiry.expiring-soon') }}" class="mb-3">
                                            <div class="mb-3">
                                                <label class="form-label">Days (Optional)</label>
                                                <select name="days" class="form-select">
                                                    <option value="">Use Global Setting ({{ \App\Models\SystemSetting::where('key', 'inventory_global_expiry_warning_days')->value('value') ?? 30 }} days)</option>
                                                    <option value="7">7 days</option>
                                                    <option value="15">15 days</option>
                                                    <option value="30">30 days</option>
                                                    <option value="60">60 days</option>
                                                    <option value="90">90 days</option>
                                                </select>
                                                <small class="text-muted">Leave empty to use the global setting from Inventory Settings</small>
                                            </div>
                                            <button type="submit" class="btn btn-warning">
                                                <i class="bx bx-search me-1"></i>View Report
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Expired Items Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-x-circle fs-1 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">Expired Items</h5>
                                        <p class="card-text">Items that have already expired</p>
                                        
                                        <form method="GET" action="{{ route('inventory.reports.expiry.expired') }}">
                                            <button type="submit" class="btn btn-danger">
                                                <i class="bx bx-search me-1"></i>View Report
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Stock Details -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-package fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Stock Details</h5>
                                        <p class="card-text">View detailed stock with expiry information</p>
                                        
                                        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#stockDetailsModal">
                                            <i class="bx bx-search me-1"></i>View Details
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stock Details Modal -->
<div class="modal fade" id="stockDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Stock Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="stockDetailsForm">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Item</label>
                            <select name="item_id" class="form-select select2-single" required>
                                <option value="">Select Item</option>
                                @foreach(\App\Models\Inventory\Item::where('track_expiry', true)->get() as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Location (Optional)</label>
                            <select name="location_id" class="form-select select2-single">
                                <option value="">All Locations</option>
                                @foreach(\App\Models\InventoryLocation::all() as $location)
                                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </form>
                <div id="stockDetailsResult" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="loadStockDetails()">Load Details</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
function loadStockDetails() {
    const form = document.getElementById('stockDetailsForm');
    const formData = new FormData(form);
    const resultDiv = document.getElementById('stockDetailsResult');
    
    resultDiv.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div></div>';
    
    fetch('{{ route("inventory.reports.expiry.stock-details") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.layers && data.layers.length > 0) {
            let html = `
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Batch Number</th>
                                <th>Expiry Date</th>
                                <th>Quantity</th>
                                <th>Unit Cost</th>
                                <th>Total Cost</th>
                                <th>Days Until Expiry</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            data.layers.forEach(layer => {
                const statusClass = layer.expiry_status === 'expired' ? 'danger' : 
                                  layer.expiry_status === 'expiring_soon' ? 'warning' : 'success';
                const statusText = layer.expiry_status === 'expired' ? 'Expired' : 
                                 layer.expiry_status === 'expiring_soon' ? 'Expiring Soon' : 'Good';
                
                html += `
                    <tr>
                        <td>${layer.batch_number || 'N/A'}</td>
                        <td>${layer.expiry_date}</td>
                        <td>${parseFloat(layer.quantity).toFixed(2)}</td>
                        <td>TZS ${parseFloat(layer.unit_cost).toFixed(2)}</td>
                        <td>TZS ${parseFloat(layer.total_cost).toFixed(2)}</td>
                        <td>${layer.days_until_expiry}</td>
                        <td><span class="badge bg-${statusClass}">${statusText}</span></td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
                <div class="row mt-3">
                    <div class="col-md-4">
                        <strong>Total Quantity:</strong> ${parseFloat(data.total_quantity).toFixed(2)}
                    </div>
                    <div class="col-md-4">
                        <strong>Total Value:</strong> TZS ${parseFloat(data.total_value).toFixed(2)}
                    </div>
                    <div class="col-md-4">
                        <strong>Average Cost:</strong> TZS ${parseFloat(data.average_cost).toFixed(2)}
                    </div>
                </div>
            `;
            
            resultDiv.innerHTML = html;
        } else {
            resultDiv.innerHTML = '<div class="alert alert-info">No stock found for this item.</div>';
        }
    })
    .catch(error => {
        resultDiv.innerHTML = '<div class="alert alert-danger">Error loading stock details.</div>';
        console.error('Error:', error);
    });
}

$(document).ready(function() {
    $('.select2-single').select2({
        placeholder: 'Select an option',
        allowClear: true,
        width: '100%'
    });
});
</script>
@endpush
@endsection
