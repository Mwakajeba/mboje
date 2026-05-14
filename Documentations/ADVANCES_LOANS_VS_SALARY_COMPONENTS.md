# Salary Advances & Loans vs Salary Components - Relationship Guide

## Overview

Salary Advances and External Loans are **NOT** salary components, but they work **alongside** the salary component system in payroll processing. Understanding this relationship is crucial for proper system usage.

---

## Key Difference

### Salary Components (Deduction Type)
- **What**: Reusable master components defined in `hr_salary_components`
- **Purpose**: Define standard deductions that can be assigned to multiple employees
- **Examples**: Trade Union, Insurance Premium, etc.
- **Management**: Assigned via Employee Salary Structure
- **Calculation**: Based on component configuration (fixed, percentage, formula)

### Salary Advances & External Loans
- **What**: Employee-specific transactions managed separately
- **Purpose**: Track actual advance/loan transactions and repayments
- **Examples**: Salary advance given on Jan 15, Bank loan issued on Feb 1
- **Management**: Separate modules (Salary Advances, External Loans)
- **Calculation**: Based on `monthly_deduction` field from the advance/loan record

---

## How They Work Together in Payroll

### Payroll Calculation Flow

```
1. Calculate Earnings (from Salary Components)
   ↓
2. Calculate Deductions (from Salary Components)
   ↓
3. Calculate Statutory Deductions (PAYE, NHIF, Pension, etc.)
   ↓
4. Get Other Deductions (Salary Advances & Loans) ← SEPARATE
   ↓
5. Calculate Net Salary
```

### In Code (`PayrollCalculationService`)

```php
// Step 1: Calculate earnings from salary components
$earnings = $this->calculateEarnings($employee, $salaryStructure, $startDate, $companyId);

// Step 2: Calculate deductions from salary components
$deductions = $this->calculateDeductions($employee, $salaryStructure, $earnings['gross'], $startDate, $companyId);

// Step 3: Calculate statutory deductions
$statutoryDeductions = $this->calculateStatutoryDeductions(...);

// Step 4: Get OTHER deductions (advances, loans) - SEPARATE from components
$otherDeductions = $this->getOtherDeductions($employee, $payrollYear, $payrollMonth, $companyId);

// Step 5: Total deductions
$totalDeductions = $deductions['total'] + $statutoryDeductions['total'] + $otherDeductions['total'];
```

---

## Why They Are Separate

### 1. **Different Nature**

**Salary Components:**
- Reusable templates
- Standard deductions (same for all employees)
- Examples: Trade Union (same rate for all union members)

**Advances/Loans:**
- Employee-specific transactions
- Variable amounts per employee
- Examples: John got 500K advance, Jane got 300K advance

### 2. **Different Lifecycle**

**Salary Components:**
- Assigned once via Salary Structure
- Ongoing (until structure changes)
- No repayment tracking needed

**Advances/Loans:**
- Created when advance/loan is given
- Has a repayment period
- Needs balance tracking
- Auto-deactivates when fully repaid

### 3. **Different Management**

**Salary Components:**
- Managed in: **Salary Components** → **Employee Salary Structure**
- Part of employee's permanent salary structure
- Changes infrequently

**Advances/Loans:**
- Managed in: **Salary Advances** / **External Loans** modules
- Transaction-based (created when needed)
- Auto-managed (auto-deactivate when repaid)

---

## Can You Use Salary Components for Advances/Loans?

### Option 1: Use Separate Modules (Recommended) ✅

**How:**
- Create advance/loan in **Salary Advances** or **External Loans** module
- System automatically deducts during payroll
- Auto-tracks balance and deactivates when repaid

**Advantages:**
- ✅ Automatic balance tracking
- ✅ Auto-deactivation when fully repaid
- ✅ Transaction history (when advance/loan was given)
- ✅ Payment records linked
- ✅ Better audit trail

**Example:**
```
Employee: John Doe
- Create Salary Advance: 500,000 TZS, 100,000/month
- System automatically deducts 100,000 each month
- After 5 months: Auto-deactivates (balance = 0)
```

### Option 2: Use Salary Components (Not Recommended) ❌

**How:**
- Create a salary component: "SALARY_ADVANCE" (deduction type)
- Assign to employee in Salary Structure
- Set fixed amount

**Disadvantages:**
- ❌ No automatic balance tracking
- ❌ No auto-deactivation
- ❌ Manual management required
- ❌ No transaction history
- ❌ Risk of over-deduction

**When to Use:**
- Only if you want a **fixed monthly deduction** that never changes
- Not suitable for advances/loans that need repayment tracking

---

## Current System Implementation

### How It Works Now

1. **Salary Components** handle:
   - Standard deductions (Trade Union, Insurance Premium, etc.)
   - Reusable across employees
   - Managed via Salary Structure

2. **Salary Advances & Loans** handle:
   - Employee-specific advance/loan transactions
   - Automatic deduction during payroll
   - Balance tracking and auto-deactivation
   - Managed via separate modules

3. **Both are combined** in final payroll calculation:
   ```
   Total Deductions = 
     Component Deductions + 
     Statutory Deductions + 
     Advances/Loans
   ```

---

## Best Practice Recommendation

### ✅ Recommended Approach

**Use Separate Modules for Advances/Loans:**
- Create advances/loans in their respective modules
- Let the system handle automatic deduction
- Benefit from balance tracking and auto-deactivation

**Use Salary Components for:**
- Standard recurring deductions (Trade Union, Insurance, etc.)
- Components that apply to multiple employees
- Fixed or percentage-based deductions

### ❌ Not Recommended

**Don't mix them:**
- Don't create "SALARY_ADVANCE" as a salary component
- Don't manually manage advances/loans in salary structure
- This creates duplicate management and loses automatic features

---

## Example: Complete Payroll Deduction Breakdown

**Employee: John Doe**

### From Salary Components:
- Trade Union: 5,000 TZS (from structure)
- Insurance Premium: 10,000 TZS (from structure)

### From Statutory Rules:
- PAYE: 50,000 TZS
- NHIF: 15,000 TZS
- Pension: 25,000 TZS

### From Separate Modules:
- Salary Advance: 100,000 TZS (from `hr_salary_advances` table)
- External Loan: 50,000 TZS (from `hr_external_loans` table)

### Total Deductions:
```
5,000 + 10,000 + 50,000 + 15,000 + 25,000 + 100,000 + 50,000 = 255,000 TZS
```

---

## Summary

**Salary Advances & Loans are NOT salary components:**
- They are separate entities managed in their own modules
- They are added to payroll as "Other Deductions"
- They have automatic balance tracking and deactivation
- They work alongside (not as part of) salary components

**The relationship:**
- Salary Components = Standard, reusable deductions
- Advances/Loans = Employee-specific transactions with repayment tracking
- Both contribute to total deductions in payroll
- Both are needed for complete payroll processing

