# HESLB Employee Linking Guide

## How HESLB is Linked to Employees

HESLB (Higher Education Students' Loans Board) is an **employee-specific statutory deduction** that applies **only to employees with active loan balances**. It is NOT applied to all employees.

## Key Points

### 1. **Employee-Specific (Not Universal)**
   - HESLB deductions are **NOT** applied to all employees
   - Only employees with **active HESLB loan records** are charged
   - If an employee has no active loan, they pay **zero HESLB**

### 2. **Two-Level Configuration**

#### **Statutory Rule Level** (Company-wide settings)
   - Defines the **deduction rate** (percentage)
   - Sets the **deduction ceiling** (maximum amount per month)
   - Applies to all employees who have active loans
   - Location: HR & Payroll → Statutory Rules → Create/Edit HESLB Rule

#### **Employee Level** (Individual settings)
   - **HESLB Loan Record**: Must be created for each employee with a student loan
   - Contains:
     - Original loan amount
     - Outstanding balance
     - Loan number/reference
     - Active status
   - Location: (To be implemented - HESLB Loan Management)

### 3. **How It Works**

```
Step 1: Create HESLB Statutory Rule
   ↓
   Sets company-wide rate (e.g., 5%) and ceiling
   
Step 2: Create HESLB Loan for Employee
   ↓
   Employee gets a loan record with outstanding balance
   
Step 3: Payroll Processing
   ↓
   System checks: Does employee have active loan?
   ├─ YES → Calculate deduction (rate × gross pay, capped to balance)
   └─ NO  → HESLB = 0 (no deduction)
```

### 4. **Calculation Logic**

1. **Check for Active Loan**: System looks for an active `HeslbLoan` record with:
   - `is_active = true`
   - `outstanding_balance > 0`

2. **If Active Loan Exists**:
   - Get deduction rate from Statutory Rule (or employee override)
   - Calculate: `Deduction = Gross Pay × Rate`
   - Apply ceiling if set in Statutory Rule
   - **Cap to outstanding balance** (never deduct more than owed)
   - Record repayment in transaction ledger

3. **If No Active Loan**:
   - HESLB deduction = 0
   - No charge to employee

### 5. **Automatic Features**

- ✅ **Auto-stop**: Deductions automatically stop when balance reaches zero
- ✅ **Balance tracking**: Outstanding balance is updated after each payroll
- ✅ **Transaction ledger**: Every repayment is recorded with full audit trail
- ✅ **Balance capping**: Deductions never exceed outstanding balance

## Example Scenarios

### Scenario 1: Employee with Active Loan
- **Employee**: John Doe
- **Active Loan**: Yes (Balance: 500,000 TZS)
- **Gross Pay**: 1,000,000 TZS
- **Statutory Rate**: 5%
- **Calculation**: 1,000,000 × 5% = 50,000 TZS
- **Capped to balance**: min(50,000, 500,000) = 50,000 TZS
- **Result**: HESLB deduction = 50,000 TZS

### Scenario 2: Employee without Loan
- **Employee**: Jane Smith
- **Active Loan**: No
- **Result**: HESLB deduction = 0 TZS

### Scenario 3: Employee with Low Balance
- **Employee**: Bob Wilson
- **Active Loan**: Yes (Balance: 10,000 TZS)
- **Gross Pay**: 1,000,000 TZS
- **Statutory Rate**: 5%
- **Calculation**: 1,000,000 × 5% = 50,000 TZS
- **Capped to balance**: min(50,000, 10,000) = 10,000 TZS
- **Result**: HESLB deduction = 10,000 TZS (final payment)

## Employee Form Fields

The `has_heslb` checkbox in the employee form is **optional/legacy**. The primary way to enable HESLB is by:

1. **Creating a HESLB loan record** for the employee
2. The system will automatically apply deductions if:
   - Loan is active (`is_active = true`)
   - Outstanding balance > 0

The employee-level percentage fields (`heslb_employee_percent`) can override the statutory rule rate if needed, but the statutory rule rate is used by default.

## Summary

- ❌ **NOT all employees are charged HESLB**
- ✅ **Only employees with active loan balances are charged**
- ✅ **Statutory Rule provides the rate and ceiling**
- ✅ **Loan records determine who gets charged**
- ✅ **Deductions automatically stop when balance is zero**

