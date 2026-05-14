# IFRS 9-Compliant Dual-Schedule Loan Module Implementation

## Overview

This document describes the implementation of IFRS 9-compliant dual-schedule architecture for the loan management module. The system now maintains **two separate schedules** as required by IFRS 9:

1. **Contractual Cash Schedule** - What the borrower must pay (nominal rate)
2. **IFRS 9 Amortised Cost Schedule** - How the loan is accounted for (EIR-based)

## ✅ Completed Implementation

### 1. Database Structure

#### Migrations Created:
- `2025_12_20_140000_rename_loan_schedules_to_cash_schedules.php`
  - Renames `loan_schedules` → `loan_cash_schedules`
  - Adds period tracking fields

- `2025_12_20_141000_create_loan_ifrs_schedules_table.php`
  - Creates `loan_ifrs_schedules` table
  - Links to cash schedules via `cash_schedule_id`
  - Tracks IFRS interest expense, amortised cost, etc.

- `2025_12_20_142000_add_ifrs_fields_to_loans_table.php`
  - Adds `initial_amortised_cost` (cash received - capitalized fees)
  - Adds `current_amortised_cost` (current carrying amount)
  - Adds `eir_locked`, `eir_locked_at`, `eir_locked_by`
  - Adds `capitalized_fees`, `directly_attributable_costs`

### 2. Models

#### `LoanCashSchedule` (Contractual Schedule)
- Purpose: Payment reminders, bank reconciliation, customer statements
- Uses: Nominal interest rate
- Location: `app/Models/Loan/LoanCashSchedule.php`

#### `LoanIfrsSchedule` (Accounting Schedule)
- Purpose: General Ledger, Financial Statements, Audit
- Uses: Effective Interest Rate (EIR)
- Location: `app/Models/Loan/LoanIfrsSchedule.php`
- **System-generated only** (cannot be manually edited)

#### Updated `Loan` Model
- New relationships: `cashSchedules()`, `ifrsSchedules()`
- Backward compatibility: `schedules()` alias to `cashSchedules()`
- New fillable fields for IFRS 9 compliance

### 3. EIR Calculator Service

#### `LoanEirCalculatorService`
- Location: `app/Services/Loan/LoanEirCalculatorService.php`
- Method: Newton-Raphson numerical solving
- Calculates EIR that discounts all cash flows to initial amortised cost
- Handles various day count conventions (actual/365, actual/360, 30/360)

### 4. Dual Schedule Generation

#### `LoanService::generateDualSchedules()`
Main entry point that:
1. Generates contractual cash schedule (nominal rate)
2. Calculates EIR from cash flows
3. Generates IFRS 9 amortised cost schedule (EIR-based)
4. Links both schedules together

#### `LoanService::generateCashSchedule()`
- Generates contractual schedule using nominal interest rate
- Handles grace periods, flat rate, annuity, etc.
- Used for: payment tracking, customer statements

#### `LoanService::generateIfrsSchedule()`
- Generates IFRS 9 schedule using Effective Interest Rate
- Formula: `IFRS Interest = Opening Amortised Cost × EIR × Days/365`
- Formula: `Closing AC = Opening AC + IFRS Interest - Cash Paid`
- Used for: GL posting, financial statements

### 5. GL Posting Updates

#### `createAccrualGlEntry()`
- **Now uses IFRS schedule** for interest expense (EIR-based)
- Finds corresponding IFRS schedule for accrual period
- Posts: `Dr Interest Expense (IFRS) / Cr Loan Payable`
- Marks IFRS schedule as posted

#### `createDisbursementGlEntry()`
- **Records loan at initial amortised cost** (not face value)
- Initial AC = Cash Received - Capitalized Fees
- Complies with IFRS 9 initial recognition

#### `createPaymentGlEntry()`
- Updated descriptions to indicate IFRS 9 compliance
- Cash payments reduce amortised cost

### 6. EIR Calculation & Locking

#### `calculateAndLockEir()`
- Calculates EIR when loan is approved
- Locks EIR after approval (cannot be changed)
- Stores: EIR, initial amortised cost, locked timestamp/user

#### Controller Integration
- EIR calculated automatically when loan status = 'approved'
- Initial amortised cost calculated at loan creation
- EIR locked permanently after approval

### 7. Controller Updates

#### `LoanController::generateSchedule()`
- Now calls `generateDualSchedules()`
- Saves both cash and IFRS schedules
- Links them via `cash_schedule_id`
- Updates loan's current amortised cost

## 📋 Remaining Tasks

### 1. View Updates (Pending)
The loan show page needs to display both schedules separately:

**Recommended UI:**
- Tab 1: "Contractual Schedule" (cash schedule)
- Tab 2: "IFRS 9 Schedule" (accounting schedule)
- Show EIR, initial amortised cost, current amortised cost
- Indicate which schedule is used for GL posting

**Files to Update:**
- `resources/views/loans/show.blade.php`

### 2. Payment Processing Updates
Ensure payments update both schedules:
- Update cash schedule (contractual amounts)
- Update IFRS schedule (amortised cost reduction)
- Link payment to both schedules

**Files to Check:**
- `LoanController::storePayment()`
- Payment allocation logic

### 3. Migration Execution
Run migrations to apply database changes:
```bash
php artisan migrate
```

## 🔑 Key IFRS 9 Concepts Implemented

### Initial Recognition
- Loan recorded at **initial amortised cost** (cash received - capitalized fees)
- Not recorded at face value (principal amount)

### Effective Interest Rate (EIR)
- Rate that discounts all expected cash flows to initial amortised cost
- Calculated using Newton-Raphson method
- Locked after loan approval

### Amortised Cost
- Opening AC + IFRS Interest - Cash Paid = Closing AC
- IFRS Interest uses EIR, not nominal rate
- Cash Paid comes from contractual schedule

### Two Schedules
- **Cash Schedule**: Legal obligations (nominal rate)
- **IFRS Schedule**: Accounting reality (EIR)
- They reconcile through cash payments

## 🎯 Usage Example

### Creating a Loan with Fees

1. **Create Loan:**
   - Principal: 5,000,000
   - Fees: 200,000 (capitalized)
   - Cash Received: 4,800,000
   - Nominal Rate: 12%

2. **Generate Schedules:**
   - System generates cash schedule (12% nominal)
   - System calculates EIR (e.g., 13.6%)
   - System generates IFRS schedule (13.6% EIR)

3. **Month 1 Example:**

   **Cash Schedule:**
   - Interest: 50,000 (12% on 5,000,000)
   - Principal: 138,889
   - Total Paid: 188,889

   **IFRS Schedule:**
   - Opening AC: 4,800,000
   - IFRS Interest: 53,589 (13.6% on 4,800,000)
   - Cash Paid: 188,889
   - Closing AC: 4,664,700

4. **GL Entry (from IFRS Schedule):**
   - Dr Interest Expense: 53,589
   - Cr Loan Payable: 53,589
   - (Then payment reduces Loan Payable)

## ⚠️ Important Notes

1. **EIR is Locked**: Once calculated and locked, EIR cannot be changed
2. **IFRS Schedule is System-Generated**: Cannot be manually edited
3. **Cash Schedule is Editable**: Can be modified (with approval) for contract changes
4. **GL Uses IFRS Schedule**: All accounting entries come from IFRS schedule only
5. **Cash Schedule for Operations**: Used for payment tracking, customer statements

## 🔄 Migration Path

For existing loans:
1. Run migrations
2. Regenerate schedules (will create both cash and IFRS)
3. EIR will be calculated automatically
4. Historical data preserved in cash schedules

## 📚 References

- IFRS 9: Financial Instruments
- IAS 23: Borrowing Costs (for capitalization)
- IFRS 9 Implementation Guide

---

**Status**: Core implementation complete. View updates pending.

