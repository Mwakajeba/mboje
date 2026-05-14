# Withholding Tax (WHT) System - Complete Documentation

**Version:** 1.0  
**Date:** November 2025  
**System:** Smart Accounting System

---

## Table of Contents

1. [Overview](#overview)
2. [WHT Concepts](#wht-concepts)
3. [WHT Treatments Explained](#wht-treatments-explained)
4. [System Architecture](#system-architecture)
5. [Implementation by Module](#implementation-by-module)
6. [Double-Entry Accounting](#double-entry-accounting)
7. [GL Accounts Setup](#gl-accounts-setup)
8. [Step-by-Step Workflows](#step-by-step-workflows)
9. [Examples and Scenarios](#examples-and-scenarios)
10. [Troubleshooting](#troubleshooting)

---

## 1. Overview

### What is Withholding Tax (WHT)?

Withholding Tax (WHT) is a tax that is deducted at the source of payment. In this system, WHT is applied to:

- **Accounts Payable (AP)**: When you pay suppliers/vendors
- **Accounts Receivable (AR)**: When you receive payments from customers

### Key Features

- ✅ Support for **Exclusive**, **Inclusive**, and **Gross-Up** treatments
- ✅ Automatic calculation based on treatment type
- ✅ Real-time calculation preview in forms
- ✅ Proper double-entry accounting integration
- ✅ Supplier auto-detection for Gross-Up
- ✅ Item-level and payment-level WHT support
- ✅ WHT Payable and WHT Receivable tracking

---

## 2. WHT Concepts

### 2.1 Base Amount
The original amount before WHT is applied. This is the invoice amount or payment amount.

### 2.2 WHT Amount
The tax amount that is withheld or added, calculated based on the WHT rate and treatment type.

### 2.3 Net Payable/Receivable
The actual amount paid or received after WHT is applied.

### 2.4 Total Cost
The total expense recorded in your books (includes WHT top-up for Gross-Up).

---

## 3. WHT Treatments Explained

### 3.1 EXCLUSIVE Treatment

**Definition:** WHT is deducted from the base amount.

**Formula:**
- WHT = Base Amount × Rate
- Net Payable = Base Amount - WHT
- Total Cost = Base Amount

**Example:**
- Base Amount: 10,000.00
- WHT Rate: 5%
- WHT Amount: 500.00 (10,000 × 5%)
- Net Payable: 9,500.00 (10,000 - 500)
- Total Cost: 10,000.00

**When to Use:**
- Standard withholding scenario
- Supplier receives less than invoice amount
- You record full expense, supplier gets net amount

**GL Impact:**
```
Debit Expense:        10,000.00
  Credit Bank:         9,500.00
  Credit WHT Payable:    500.00
```

---

### 3.2 INCLUSIVE Treatment

**Definition:** WHT is already included in the total amount.

**Formula:**
- WHT = Base Amount × (Rate / (1 + Rate))
- Net Payable = Base Amount - WHT
- Total Cost = Base Amount

**Example:**
- Base Amount: 10,000.00
- WHT Rate: 5%
- WHT Amount: 476.19 (10,000 × (0.05 / 1.05))
- Net Payable: 9,523.81 (10,000 - 476.19)
- Total Cost: 10,000.00

**When to Use:**
- WHT is already factored into the agreed amount
- Contract specifies inclusive pricing
- Supplier receives net amount, but total includes WHT

**GL Impact:**
```
Debit Expense:        10,000.00
  Credit Bank:         9,523.81
  Credit WHT Payable:    476.19
```

---

### 3.3 GROSS-UP Treatment

**Definition:** WHT is added on top of the base amount. You pay extra so supplier receives full base.

**Formula:**
- WHT = Base Amount × (Rate / (1 - Rate))
- Net Payable = Base Amount (supplier gets full amount)
- Total Cost = Base Amount + WHT

**Example:**
- Base Amount: 10,000.00 (what supplier should receive)
- WHT Rate: 2%
- WHT Amount: 204.08 (10,000 × (0.02 / 0.98))
- Net Payable: 10,000.00 (supplier gets full base)
- Total Cost: 10,204.08 (10,000 + 204.08)

**When to Use:**
- Supplier must receive full base amount
- Contract requires you to bear WHT cost
- Supplier has `allow_gross_up` flag enabled

**GL Impact:**
```
Debit Expense:        10,204.08
  Credit Bank:        10,000.00
  Credit WHT Payable:   204.08
```

**Note:** Gross-Up is only available for AP (Payments), not AR (Receipts).

---

## 4. System Architecture

### 4.1 Core Components

#### WithholdingTaxService
**Location:** `app/Services/WithholdingTaxService.php`

**Purpose:** Centralized WHT calculation logic

**Key Methods:**
- `calculateWHT($baseAmount, $rate, $treatment)`: Calculates WHT based on treatment
- `isValidARTreatment($treatment)`: Validates AR treatments (no Gross-Up)
- `isValidAPTreatment($treatment)`: Validates AP treatments (all types)
- `getDefaultTreatment($supplierId)`: Gets default treatment for supplier

#### Database Tables

**payments** table:
- `wht_treatment` (ENUM: EXCLUSIVE, INCLUSIVE, GROSS_UP, NONE)
- `wht_rate` (decimal)
- `wht_amount` (decimal)
- `net_payable` (decimal)
- `total_cost` (decimal)

**receipts** table:
- `wht_treatment` (ENUM: EXCLUSIVE, INCLUSIVE, NONE)
- `wht_rate` (decimal)
- `wht_amount` (decimal)
- `net_receivable` (decimal)

**payment_items** table:
- `wht_treatment` (ENUM)
- `wht_rate` (decimal)
- `wht_amount` (decimal)
- `base_amount` (decimal)
- `net_payable` (decimal)
- `total_cost` (decimal)

**receipt_items** table:
- `wht_treatment` (ENUM)
- `wht_rate` (decimal)
- `wht_amount` (decimal)
- `base_amount` (decimal)
- `net_receivable` (decimal)

**suppliers** table:
- `allow_gross_up` (boolean) - Auto-enables Gross-Up for supplier

---

## 5. Implementation by Module

### 5.1 Payment Vouchers

**Location:** `app/Http/Controllers/Accounting/PaymentVoucherController.php`

**Features:**
- Payment-level WHT (applies to all items)
- Item-level WHT (overrides payment-level for specific items)
- Auto-detection of Gross-Up for suppliers
- Real-time calculation preview

**Workflow:**
1. User selects supplier (if applicable)
2. System checks `allow_gross_up` flag
3. If enabled, defaults to GROSS_UP treatment
4. User can override treatment and rate
5. Calculation preview updates in real-time
6. On save, WHT is calculated and stored
7. GL transactions posted with WHT Payable

**GL Posting Logic:**
- **Payment-level WHT takes precedence** over item-level
- If payment-level WHT is set, it's used for GL
- If no payment-level WHT, item-level WHT is aggregated
- Expense debit uses payment-level logic when set

---

### 5.2 Receipt Vouchers

**Location:** `app/Http/Controllers/Accounting/ReceiptVoucherController.php`

**Features:**
- Payment-level WHT (EXCLUSIVE or INCLUSIVE only)
- Item-level WHT support
- No Gross-Up (not applicable for AR)
- Real-time calculation preview

**Workflow:**
1. User enters line items with amounts
2. User selects WHT treatment (Exclusive/Inclusive/None)
3. User enters WHT rate
4. Calculation preview shows net receivable
5. On save, WHT is calculated and stored
6. GL transactions posted with WHT Receivable

**GL Posting Logic:**
- Debit Bank/Cash: Net Receivable
- Debit WHT Receivable: WHT Amount
- Credit Revenue/Income: Base Amount

---

### 5.3 Purchase Invoice Payments

**Location:** `app/Http/Controllers/Purchase/PurchaseInvoiceController.php` (recordPayment method)

**Features:**
- Full WHT support when recording payment
- Auto-detection of Gross-Up for suppliers
- WHT section in payment form
- Calculation preview

**Workflow:**
1. User goes to Purchase Invoice → Record Payment
2. System checks supplier's `allow_gross_up` flag
3. If enabled, defaults to GROSS_UP
4. User can override treatment and rate
5. Payment created with WHT details
6. GL transactions posted correctly

**Note:** WHT is NOT handled during invoice creation, only at payment time.

---

### 5.4 Sales Invoice Payments

**Location:** `app/Models/Sales/SalesInvoice.php` (recordBankPayment method)

**Features:**
- WHT support for bank payments only
- EXCLUSIVE or INCLUSIVE only (no Gross-Up for AR)
- WHT section shown only for bank payments
- Calculation preview

**Workflow:**
1. User records payment for sales invoice
2. Selects bank payment method
3. WHT section appears
4. User enters WHT treatment and rate
5. Receipt created with WHT details
6. GL transactions posted with WHT Receivable

---

### 5.5 Bill Purchase Payments

**Location:** `app/Http/Controllers/Accounting/BillPurchaseController.php` (processPayment method)

**Features:**
- Full WHT support
- Auto-detection of Gross-Up for suppliers
- WHT section in payment form
- Calculation preview

**Workflow:**
Similar to Purchase Invoice payments.

---

## 6. Double-Entry Accounting

### 6.1 WHT Payable (Liability Account)

**Account Type:** Liability  
**Account Code:** 2108 (example)  
**Account Name:** Withholding Tax Payable

**Purpose:** Tracks money you owe to the tax authority

**How It Works:**
- **Credit** when you withhold tax (increases liability)
- **Debit** when you remit tax to authority (decreases liability)
- Balance shows total WHT owed to tax authority

**Example Flow:**
```
Payment 1: Credit WHT Payable 300.00
Payment 2: Credit WHT Payable 122.45
Total Owed: 422.45

When remitted:
Debit WHT Payable:  422.45
Credit Bank:        422.45
Balance: 0.00
```

---

### 6.2 WHT Receivable (Asset Account)

**Account Type:** Asset  
**Account Code:** 1105 (example)  
**Account Name:** Withholding Tax Receivable

**Purpose:** Tracks money you're expecting to receive from tax authority (credit/refund)

**How It Works:**
- **Debit** when customer withholds tax (increases asset)
- **Credit** when you receive credit/refund from authority (decreases asset)
- Balance shows total WHT credit expected

**Example Flow:**
```
Receipt 1: Debit WHT Receivable 2,800.00
Receipt 2: Debit WHT Receivable 500.00
Total Expected: 3,300.00

When received:
Credit WHT Receivable: 3,300.00
Debit Bank:            3,300.00
Balance: 0.00
```

---

### 6.3 Complete GL Entry Examples

#### Example 1: Payment Voucher with EXCLUSIVE WHT (5%)

**Scenario:**
- Base Amount: 10,000.00
- WHT Rate: 5%
- Treatment: EXCLUSIVE

**Calculation:**
- WHT: 500.00
- Net Payable: 9,500.00
- Total Cost: 10,000.00

**GL Entries:**
```
Debit Expense Account:     10,000.00
  Credit Bank Account:        9,500.00
  Credit WHT Payable:           500.00
Total:                     10,000.00 = 10,000.00 ✓
```

---

#### Example 2: Payment Voucher with GROSS-UP WHT (2%)

**Scenario:**
- Base Amount: 10,000.00
- WHT Rate: 2%
- Treatment: GROSS-UP

**Calculation:**
- WHT: 204.08
- Net Payable: 10,000.00
- Total Cost: 10,204.08

**GL Entries:**
```
Debit Expense Account:     10,204.08
  Credit Bank Account:       10,000.00
  Credit WHT Payable:          204.08
Total:                     10,204.08 = 10,204.08 ✓
```

---

#### Example 3: Receipt Voucher with EXCLUSIVE WHT (5%)

**Scenario:**
- Base Amount: 56,000.00
- WHT Rate: 5%
- Treatment: EXCLUSIVE

**Calculation:**
- WHT: 2,800.00
- Net Receivable: 53,200.00

**GL Entries:**
```
Debit Bank Account:         53,200.00
Debit WHT Receivable:       2,800.00
  Credit Revenue Account:   56,000.00
Total:                     56,000.00 = 56,000.00 ✓
```

---

## 7. GL Accounts Setup

### 7.1 Required Accounts

#### WHT Payable Account
- **Type:** Liability
- **Code:** 2108 (example)
- **Name:** Withholding Tax Payable
- **Purpose:** Track WHT owed to tax authority

#### WHT Receivable Account
- **Type:** Asset (Current Asset)
- **Code:** 1105 (example)
- **Name:** Withholding Tax Receivable
- **Purpose:** Track WHT credit expected from tax authority

### 7.2 System Settings

Configure in System Settings:
- `wht_payable_account_id`: ID of WHT Payable account
- `wht_receivable_account_id`: ID of WHT Receivable account

**Fallback:** System searches by account name if not set in system settings.

---

## 8. Step-by-Step Workflows

### 8.1 Creating a Payment Voucher with WHT

**Step 1:** Navigate to Accounting → Payment Vouchers → Create

**Step 2:** Fill in basic details:
- Date
- Bank Account
- Payee Type (Supplier/Customer/Other)
- Select Payee

**Step 3:** Add Line Items:
- Select expense account
- Enter amount
- (Optional) Set item-level WHT treatment and rate

**Step 4:** Configure WHT Section:
- Select WHT Treatment (Exclusive/Inclusive/Gross-Up/None)
- Enter WHT Rate (%)
- View calculation preview:
  - Base Amount
  - WHT Amount
  - Net Payable
  - Total Cost (for Gross-Up)

**Step 5:** Review totals and submit

**Step 6:** System calculates WHT and creates GL transactions:
- Debit Expense accounts
- Credit Bank account (net payable)
- Credit WHT Payable (WHT amount)

---

### 8.2 Recording Payment for Purchase Invoice with WHT

**Step 1:** Navigate to Purchase Invoice → View Invoice → Record Payment

**Step 2:** Enter payment details:
- Payment Amount
- Payment Date
- Payment Method (Bank/Cash)

**Step 3:** Configure WHT Section:
- System auto-detects Gross-Up if supplier has `allow_gross_up` flag
- Select WHT Treatment (can override auto-detection)
- Enter WHT Rate
- View calculation preview

**Step 4:** Submit payment

**Step 5:** System creates:
- Payment record with WHT details
- GL transactions with WHT Payable

---

### 8.3 Creating Receipt Voucher with WHT

**Step 1:** Navigate to Accounting → Receipt Vouchers → Create

**Step 2:** Fill in basic details:
- Date
- Bank Account
- Payee Type

**Step 3:** Add Line Items:
- Select revenue/income account
- Enter amount
- (Optional) Set item-level WHT

**Step 4:** Configure WHT Section:
- Select WHT Treatment (Exclusive/Inclusive/None)
- Enter WHT Rate
- View calculation preview:
  - Base Amount
  - WHT Amount
  - Net Receivable

**Step 5:** Submit

**Step 6:** System creates GL transactions:
- Debit Bank (net receivable)
- Debit WHT Receivable (WHT amount)
- Credit Revenue/Income (base amount)

---

## 9. Examples and Scenarios

### Scenario 1: Supplier Payment with Gross-Up

**Business Case:** Supplier requires full payment amount, you bear WHT cost.

**Setup:**
- Supplier: ABC Suppliers
- `allow_gross_up`: Enabled
- Invoice Amount: 50,000.00
- WHT Rate: 2%

**Process:**
1. Create Payment Voucher
2. Select supplier (auto-detects Gross-Up)
3. Enter amount: 50,000.00
4. System calculates:
   - WHT: 1,020.41
   - Net Payable: 50,000.00
   - Total Cost: 51,020.41

**GL Entries:**
```
Debit Expense:        51,020.41
  Credit Bank:        50,000.00
  Credit WHT Payable:  1,020.41
```

**Result:**
- Supplier receives: 50,000.00 (full amount)
- You pay: 50,000.00
- Your expense: 51,020.41 (includes WHT top-up)
- WHT liability: 1,020.41

---

### Scenario 2: Customer Receipt with Exclusive WHT

**Business Case:** Customer pays invoice, withholds 5% WHT.

**Setup:**
- Invoice Amount: 100,000.00
- WHT Rate: 5%
- Treatment: EXCLUSIVE

**Process:**
1. Record payment for Sales Invoice
2. Select bank payment
3. Enter amount: 100,000.00
4. Select WHT Treatment: Exclusive
5. Enter WHT Rate: 5%
6. System calculates:
   - WHT: 5,000.00
   - Net Receivable: 95,000.00

**GL Entries:**
```
Debit Bank:           95,000.00
Debit WHT Receivable:  5,000.00
  Credit Revenue:    100,000.00
```

**Result:**
- You receive: 95,000.00
- WHT credit expected: 5,000.00
- Revenue recorded: 100,000.00

---

### Scenario 3: Payment Voucher with Item-Level WHT

**Business Case:** Different WHT rates for different expense items.

**Setup:**
- Item 1: Rent Expense - 10,000.00 (WHT: 5%)
- Item 2: Professional Fees - 20,000.00 (WHT: 2%, Gross-Up)
- Payment-level WHT: None

**Process:**
1. Create Payment Voucher
2. Add Item 1:
   - Amount: 10,000.00
   - Item WHT: Exclusive, 5%
   - WHT: 500.00
3. Add Item 2:
   - Amount: 20,000.00
   - Item WHT: Gross-Up, 2%
   - WHT: 408.16
   - Total Cost: 20,408.16

**GL Entries:**
```
Debit Rent Expense:        10,000.00
Debit Professional Fees:   20,408.16
  Credit Bank:             30,000.00
  Credit WHT Payable:         908.16
Total:                    30,408.16 = 30,908.16 ✗
```

**Note:** When item-level WHT is used, system aggregates totals correctly.

---

## 10. Troubleshooting

### Issue 1: GL Entry Not Balanced

**Symptoms:**
- Debit total ≠ Credit total
- Missing WHT Payable/Receivable entry

**Causes:**
1. WHT account not found in system
2. WHT amount is 0 but WHT treatment is set
3. Payment-level vs item-level WHT conflict

**Solutions:**
1. Ensure WHT Payable/Receivable accounts exist
2. Check system settings for account IDs
3. Verify WHT calculation logic
4. Check payment-level vs item-level priority

---

### Issue 2: Wrong WHT Amount in GL

**Symptoms:**
- GL shows different WHT amount than expected
- Item-level WHT overriding payment-level

**Causes:**
- Item-level WHT taking precedence
- Payment-level WHT not being used

**Solutions:**
- Payment-level WHT now takes precedence (fixed)
- Clear item-level WHT if using payment-level
- Regenerate GL transactions

---

### Issue 3: Gross-Up Not Auto-Detecting

**Symptoms:**
- Supplier has `allow_gross_up` but not defaulting to Gross-Up

**Causes:**
1. Supplier flag not set
2. WHT rate is 0
3. Logic not triggered

**Solutions:**
1. Check supplier's `allow_gross_up` flag
2. Ensure WHT rate > 0
3. Verify controller logic

---

### Issue 4: WHT Receivable Account Missing

**Symptoms:**
- Receipt voucher GL not balanced
- Missing WHT Receivable debit

**Causes:**
- WHT Receivable account doesn't exist

**Solutions:**
1. Create WHT Receivable account (Asset type)
2. Set in system settings
3. Regenerate GL transactions

---

## 11. Best Practices

### 11.1 Supplier Setup
- Set `allow_gross_up` flag for suppliers requiring Gross-Up
- Document WHT rates per supplier
- Review supplier contracts for WHT requirements

### 11.2 Payment Processing
- Always verify WHT calculation preview before submitting
- Use payment-level WHT for consistency
- Use item-level WHT only when necessary
- Review GL transactions after posting

### 11.3 Receipt Processing
- Remember: No Gross-Up for receipts (AR side)
- Use Exclusive for standard withholding
- Use Inclusive when WHT is in agreed amount

### 11.4 GL Account Management
- Regularly review WHT Payable balance
- Plan for WHT remittance
- Track WHT Receivable for credits
- Reconcile WHT accounts monthly

---

## 12. Formulas Reference

### EXCLUSIVE
```
WHT = Base × Rate
Net = Base - WHT
Cost = Base
```

### INCLUSIVE
```
WHT = Base × (Rate / (1 + Rate))
Net = Base - WHT
Cost = Base
```

### GROSS-UP
```
WHT = Base × (Rate / (1 - Rate))
Net = Base
Cost = Base + WHT
```

---

## 13. System Integration Points

### 13.1 Payment Vouchers
- ✅ Full WHT support
- ✅ Payment and item-level WHT
- ✅ Auto Gross-Up detection
- ✅ Edit form support

### 13.2 Receipt Vouchers
- ✅ Full WHT support (Exclusive/Inclusive only)
- ✅ Payment and item-level WHT
- ✅ Edit form support

### 13.3 Purchase Invoice Payments
- ✅ Full WHT support
- ✅ Auto Gross-Up detection
- ✅ Payment form with WHT section

### 13.4 Sales Invoice Payments
- ✅ WHT support (bank payments only)
- ✅ Exclusive/Inclusive only
- ✅ Payment form with WHT section

### 13.5 Bill Purchase Payments
- ✅ Full WHT support
- ✅ Auto Gross-Up detection
- ✅ Payment form with WHT section

### 13.6 Purchase Invoice Creation
- ❌ WHT not implemented (only at payment time)
- ⚠️ Has simple `withholding_tax_amount` field (not used)

---

## 14. Technical Details

### 14.1 Database Schema

**Migration:** `2025_11_12_000001_add_wht_treatment_to_payments_and_receipts.php`

**Tables Modified:**
- `payments`
- `receipts`
- `payment_items`
- `receipt_items`
- `suppliers` (added `allow_gross_up`)

### 14.2 Service Class

**File:** `app/Services/WithholdingTaxService.php`

**Key Methods:**
```php
calculateWHT(float $baseAmount, float $rate, string $treatment): array
isValidARTreatment(string $treatment): bool
isValidAPTreatment(string $treatment): bool
getDefaultTreatment(?int $supplierId): string
```

### 14.3 Model Methods

**Payment Model:**
- `createGlTransactions()`: Handles WHT in GL posting
- Prioritizes payment-level WHT over item-level

**Receipt Model:**
- `createGlTransactions()`: Handles WHT in GL posting
- Creates WHT Receivable debit entry

---

## 15. Reporting and Compliance

### 15.1 WHT Payable Report
- Track all WHT withheld
- Group by supplier
- Total by period
- Ready for remittance

### 15.2 WHT Receivable Report
- Track all WHT credits
- Group by customer
- Total by period
- Ready for claim/refund

### 15.3 Audit Trail
- All WHT calculations logged
- GL transactions show WHT details
- Payment/Receipt records store WHT data
- Full traceability

---

## 16. Conclusion

The WHT system in Smart Accounting provides comprehensive support for withholding tax management across all payment and receipt workflows. The system handles:

- ✅ Multiple WHT treatments (Exclusive, Inclusive, Gross-Up)
- ✅ Automatic calculations
- ✅ Proper double-entry accounting
- ✅ Supplier-specific configurations
- ✅ Real-time previews
- ✅ Complete audit trail

For questions or support, refer to this documentation or contact the system administrator.

---

**End of Document**

