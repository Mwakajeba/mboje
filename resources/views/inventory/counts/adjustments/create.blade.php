@extends('layouts.main')

@section('title', 'Create Adjustment')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Management', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Inventory Count', 'url' => route('inventory.counts.index'), 'icon' => 'bx bx-clipboard-check'],
            ['label' => 'Create Adjustment', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        
        <h6 class="mb-0 text-uppercase">CREATE ADJUSTMENT</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Variance Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Item:</strong> {{ $variance->item->name }} ({{ $variance->item->code }})<br>
                                <strong>System Quantity:</strong> {{ number_format($variance->system_quantity, 2) }}<br>
                                <strong>Physical Quantity:</strong> {{ number_format($variance->physical_quantity, 2) }}
                            </div>
                            <div class="col-md-6">
                                <strong>Variance Quantity:</strong> 
                                <span class="badge bg-{{ $variance->variance_type === 'positive' ? 'success' : 'danger' }}">
                                    {{ number_format($variance->variance_quantity, 2) }}
                                </span><br>
                                <strong>Variance Value:</strong> TZS {{ number_format($variance->variance_value, 2) }}<br>
                                <strong>Adjustment Type:</strong> 
                                <span class="badge bg-{{ $variance->variance_type === 'positive' ? 'success' : 'danger' }}">
                                    {{ $variance->variance_type === 'positive' ? 'Surplus' : 'Shortage' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('inventory.counts.adjustments.create', $variance->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Reason Code <span class="text-danger">*</span></label>
                                    <select class="form-select @error('reason_code') is-invalid @enderror" name="reason_code" required>
                                        <option value="">Select Reason</option>
                                        <option value="wrong_posting" {{ old('reason_code') == 'wrong_posting' ? 'selected' : '' }}>Wrong Posting</option>
                                        <option value="theft" {{ old('reason_code') == 'theft' ? 'selected' : '' }}>Theft</option>
                                        <option value="damage" {{ old('reason_code') == 'damage' ? 'selected' : '' }}>Damage</option>
                                        <option value="expired" {{ old('reason_code') == 'expired' ? 'selected' : '' }}>Expired</option>
                                        <option value="unrecorded_issue" {{ old('reason_code') == 'unrecorded_issue' ? 'selected' : '' }}>Unrecorded Issue</option>
                                        <option value="unrecorded_receipt" {{ old('reason_code') == 'unrecorded_receipt' ? 'selected' : '' }}>Unrecorded Receipt</option>
                                    </select>
                                    @error('reason_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Supporting Documents</label>
                                    <input type="file" class="form-control @error('supporting_documents') is-invalid @enderror" 
                                           name="supporting_documents[]" multiple accept="image/*,.pdf">
                                    @error('supporting_documents')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">You can upload multiple files (images or PDFs)</small>
                                </div>

                                <div class="col-12 mb-3">
                                    <label class="form-label">Reason Description <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('reason_description') is-invalid @enderror" 
                                              name="reason_description" rows="3" required>{{ old('reason_description') }}</textarea>
                                    @error('reason_description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 mb-3">
                                    <label class="form-label">Supervisor Comments</label>
                                    <textarea class="form-control @error('supervisor_comments') is-invalid @enderror" 
                                              name="supervisor_comments" rows="2">{{ old('supervisor_comments') }}</textarea>
                                    @error('supervisor_comments')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 mb-3">
                                    <label class="form-label">Finance Comments</label>
                                    <textarea class="form-control @error('finance_comments') is-invalid @enderror" 
                                              name="finance_comments" rows="2">{{ old('finance_comments') }}</textarea>
                                    @error('finance_comments')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('inventory.counts.sessions.show', $variance->entry->session->encoded_id) }}" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Create Adjustment</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

