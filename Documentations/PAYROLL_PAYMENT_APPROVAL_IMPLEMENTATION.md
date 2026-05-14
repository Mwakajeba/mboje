# Payroll Payment Approval Implementation

## Overview

A comprehensive payment approval workflow has been implemented for payroll payments, providing multi-level approval with configurable thresholds and approvers.

## What Was Implemented

### 1. Database Structure

#### New Tables:
- **`payroll_payment_approval_settings`**: Stores payment approval configuration per company/branch
  - Supports 1-5 approval levels
  - Configurable amount thresholds per level
  - JSON array of approver user IDs per level

- **`payroll_payment_approvals`**: Tracks individual payment approval records
  - Links payroll, approver, and approval level
  - Status: pending, approved, rejected
  - Stores amount snapshot and remarks

#### Updated Tables:
- **`payrolls`**: Added payment approval tracking fields:
  - `requires_payment_approval` (boolean)
  - `current_payment_approval_level` (integer)
  - `is_payment_fully_approved` (boolean)
  - `payment_approved_at`, `payment_approved_by`, `payment_approval_remarks`
  - `payment_rejected_at`, `payment_rejected_by`, `payment_rejection_remarks`

### 2. Models

- **`PayrollPaymentApprovalSettings`**: Manages payment approval configuration
- **`PayrollPaymentApproval`**: Tracks individual approval records
- **`Payroll`**: Updated with payment approval relationships and methods

### 3. Controller Methods

#### Public Methods:
- **`requestPaymentApproval()`**: Initiates payment approval workflow
- **`approvePayment()`**: Approves payment at current level
- **`rejectPayment()`**: Rejects payment (cancels entire approval process)
- **`processPayment()`**: Updated to require payment approval before processing

#### Private Helper Methods:
- **`initializePaymentApprovalWorkflow()`**: Sets up approval records based on settings
- **`canUserApprovePayment()`**: Checks if user can approve at current level
- **`processPaymentApproval()`**: Processes approval and advances to next level if needed

### 4. Routes

```php
Route::post('payrolls/{payroll}/request-payment-approval', ...)->name('payrolls.request-payment-approval');
Route::post('payrolls/{payroll}/approve-payment', ...)->name('payrolls.approve-payment');
Route::post('payrolls/{payroll}/reject-payment', ...)->name('payrolls.reject-payment');
```

## Workflow

### Step 1: Configure Payment Approval Settings

**Settings Management UI:** A full UI is now available at `/hr-payroll/payment-approval-settings` to configure payment approval settings.

**Access:** Navigate to HR & Payroll → Payment Approval Settings

**Alternative:** Settings can also be created directly in the database or via a seeder.

Example settings structure (for direct database/seeder creation):
```php
PayrollPaymentApprovalSettings::create([
    'company_id' => 1,
    'branch_id' => null, // or specific branch ID
    'payment_approval_required' => true,
    'payment_approval_levels' => 2,
    'payment_level1_amount_threshold' => 0, // All payments require Level 1
    'payment_level1_approvers' => [2, 3], // Finance Manager user IDs
    'payment_level2_amount_threshold' => 5000000, // Payments > 5M require Level 2
    'payment_level2_approvers' => [1], // CFO user ID
]);
```

### Step 2: Request Payment Approval

After payroll is completed, request payment approval:

```javascript
// Via AJAX
POST /hr-payroll/payrolls/{id}/request-payment-approval
```

This will:
- Check if payment approval is required based on settings
- Create approval records for all required levels
- Set `requires_payment_approval = true` on payroll

### Step 3: Approvers Review and Approve

Approvers can approve at their assigned level:

```javascript
// Via AJAX
POST /hr-payroll/payrolls/{id}/approve-payment
{
    "remarks": "Payment verified. Bank account and amount correct."
}
```

**Approval Process:**
1. User can only approve at `current_payment_approval_level`
2. When all approvers at current level approve, system moves to next level
3. When all levels are approved, `is_payment_fully_approved = true`
4. Super admins can approve all levels at once

### Step 4: Process Payment

Once payment is fully approved, process the payment:

```javascript
// Via AJAX
POST /hr-payroll/payrolls/{id}/process-payment
{
    "bank_account_id": 123,
    "payment_date": "2025-12-27",
    "payment_reference": "PAY-2025-12-001",
    "remarks": "Salary payment for December 2025"
}
```

**Validation:**
- Payroll must be `completed`
- Payment must not already be `paid`
- If `requires_payment_approval = true`, then `is_payment_fully_approved` must be `true`

### Rejection Workflow

If any approver rejects the payment:

```javascript
// Via AJAX
POST /hr-payroll/payrolls/{id}/reject-payment
{
    "remarks": "Bank account incorrect. Please verify."
}
```

**Rejection Process:**
- All pending approvals are marked as `rejected`
- `requires_payment_approval = false`
- `is_payment_fully_approved = false`
- Payment cannot be processed until approval is re-requested

## Features

### ✅ Multi-Level Approval
- Supports 1-5 configurable approval levels
- Each level independently configured
- Level-based permission checks

### ✅ Amount-Based Thresholds
- Different approval levels based on payment amount
- Example: < 5M requires Level 1, > 5M requires Level 1 + Level 2

### ✅ Super Admin Override
- Super admins can approve all levels at once
- Useful for urgent payments or system administration

### ✅ Complete Audit Trail
- All approvals/rejections logged with timestamps
- Remarks stored for each approval
- Amount snapshot at time of approval

### ✅ Separation of Duties
- Different approvers than payroll calculation approval
- Payment approvers verify:
  - Correct bank account
  - Correct payment amount
  - Correct payroll period
  - Payment date appropriateness

## API Examples

### Request Payment Approval

```javascript
fetch('/hr-payroll/payrolls/1/request-payment-approval', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({})
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log(data.message);
        console.log('Requires approval:', data.requires_approval);
    }
});
```

### Approve Payment

```javascript
fetch('/hr-payroll/payrolls/1/approve-payment', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({
        remarks: 'Payment verified. All details correct.'
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log(data.message);
        console.log('Fully approved:', data.is_payment_fully_approved);
    }
});
```

### Reject Payment

```javascript
fetch('/hr-payroll/payrolls/1/reject-payment', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({
        remarks: 'Bank account incorrect. Please verify account number.'
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log(data.message);
    }
});
```

## Next Steps (Optional Enhancements)

1. ✅ **Settings Management UI**: ~~Create a controller and views for managing `PayrollPaymentApprovalSettings`~~ **COMPLETED** - Available at `/hr-payroll/payment-approval-settings`
2. **Payment Approval Dashboard**: Show pending approvals for approvers
3. **Email Notifications**: Notify approvers when payment approval is requested
4. **Payment Approval History**: Display approval history in payroll details page
5. **Payment Form Updates**: Update payment form to show approval status and request approval button

## Configuration Example

To enable payment approval for a company:

```php
// In a seeder or tinker
$settings = PayrollPaymentApprovalSettings::create([
    'company_id' => 1,
    'branch_id' => null, // Company-wide, or specific branch_id
    'payment_approval_required' => true,
    'payment_approval_levels' => 2,
    
    // Level 1: All payments (Finance Manager)
    'payment_level1_amount_threshold' => 0,
    'payment_level1_approvers' => [2, 3], // Finance Manager user IDs
    
    // Level 2: Payments > 5,000,000 TZS (CFO)
    'payment_level2_amount_threshold' => 5000000,
    'payment_level2_approvers' => [1], // CFO user ID
    
    'created_by' => 1,
    'updated_by' => 1,
]);
```

## Summary

The payment approval system is now fully functional and integrated with the payroll processing workflow. It provides:

- ✅ Multi-level approval with configurable thresholds
- ✅ Complete audit trail
- ✅ Separation of duties
- ✅ Super admin override capability
- ✅ Rejection workflow
- ✅ Integration with existing payment processing

The system is ready to use once payment approval settings are configured for your company/branch.

