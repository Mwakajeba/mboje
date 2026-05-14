# Salary Advances & Staff Loans - Payroll Linking Guide

## Overview

Salary Advances and External Loans (Staff Loans) are **automatically linked to payroll processing** through date-based matching and active status flags. They are deducted from employee salaries during payroll calculation.

---

## How They Are Linked to Payroll

### 1. **Linking Mechanism**

**Salary Advances and External Loans are linked to payroll by:**

1. **Employee ID** - Links to a specific employee
2. **Date Field** - The `date` field determines which payroll period the deduction applies to
3. **is_active Flag** - Only active advances/loans are deducted
4. **monthly_deduction** - The amount to deduct each month

### 2. **Database Structure**

#### Salary Advances (`hr_salary_advances`)
```sql
- employee_id (FK to hr_employees)
- company_id (FK to companies)
- date (date) - When the advance was given
- amount (decimal) - Total advance amount
- monthly_deduction (decimal) - Amount to deduct per month
- is_active (boolean) - Whether deduction is active
- reason (text) - Reason for advance
- reference (string) - Unique reference number
```

#### External Loans (`hr_external_loans`)
```sql
- employee_id (FK to hr_employees)
- company_id (FK to companies)
- date (date) - When the loan was issued
- total_loan (decimal) - Total loan amount
- monthly_deduction (decimal) - Amount to deduct per month
- date_end_of_loan (date) - When loan repayment ends
- is_active (boolean) - Whether deduction is active
- institution_name (string) - Name of lending institution
```

---

## Payroll Calculation Process

### Step 1: During Payroll Processing

When processing payroll for a specific month/year, the system:

1. **Queries Active Advances/Loans**:
   ```php
   // Salary Advances - Best Practice: date <= payroll_date
   $payrollDate = Carbon::create($year, $month, 1)->endOfMonth();
   SalaryAdvance::where('employee_id', $employee->id)
       ->where('company_id', $companyId)
       ->where('is_active', true)
       ->where('date', '<=', $payrollDate)
       ->sum('monthly_deduction');
   
   // External Loans - Best Practice: date <= payroll_date AND (no end date OR end date >= payroll_date)
   ExternalLoan::where('employee_id', $employee->id)
       ->where('company_id', $companyId)
       ->where('is_active', true)
       ->where('date', '<=', $payrollDate)
       ->where(function ($q) use ($payrollDate) {
           $q->whereNull('date_end_of_loan')
             ->orWhere('date_end_of_loan', '>=', $payrollDate);
       })
       ->sum('monthly_deduction');
   ```

2. **Sums All Deductions**:
   - All active advances for that month/year
   - All active loans for that month/year
   - Total = Sum of all `monthly_deduction` amounts

3. **Applies to Net Salary**:
   - Deducted from gross salary after statutory deductions
   - Reduces net salary amount

---

## How It Works in Practice

### Example 1: Single Salary Advance

**Scenario:**
- Employee: John Doe
- Advance Date: January 15, 2025
- Advance Amount: 500,000 TZS
- Monthly Deduction: 100,000 TZS
- is_active: true

**Payroll Processing for January 2025:**
- System finds advance with `date <= January 31, 2025` ✅
- Checks `is_active = true`
- Deducts `monthly_deduction` (100,000 TZS) from salary
- Net salary = Gross - Statutory Deductions - 100,000
- Records repayment and checks if fully repaid

**Payroll Processing for February 2025:**
- System finds advance with `date <= February 28, 2025` ✅ (January 15 <= February 28)
- Checks `is_active = true` (still active)
- Deducts `monthly_deduction` (100,000 TZS) again
- Records repayment and checks if fully repaid
- Continues until advance is fully repaid or `is_active = false`

### Example 2: Multiple Advances

**Scenario:**
- Employee: Jane Smith
- Advance 1: January 10, 2025 - 200,000 TZS - 50,000/month
- Advance 2: February 5, 2025 - 300,000 TZS - 75,000/month
- Both are active

**Payroll Processing for February 2025:**
- Looks for advances with `date <= February 28, 2025`
- Finds Advance 1 (January 10 <= February 28) ✅
- Finds Advance 2 (February 5 <= February 28) ✅
- Both are active, so both are deducted
- Total deduction = 50,000 + 75,000 = 125,000 TZS

---

## Important Notes

### ✅ Best Practice Implementation (Current)

**The system now uses best practices for salary advances and loans:**

**Current Behavior:**
- If advance date is **January 15, 2025**, it will be deducted in:
  - January 2025 payroll ✅ (date <= payroll date)
  - February 2025 payroll ✅ (date <= payroll date, still active)
  - March 2025 payroll ✅ (date <= payroll date, still active)
  - Continues until `is_active = false` or fully repaid

**How It Works:**
- System deducts all active advances/loans where `date <= payroll_date`
- Allows ongoing monthly deductions until fully repaid
- Auto-deactivates when fully repaid (based on total deductions vs. original amount)
- For external loans, also checks `date_end_of_loan` to stop deductions after loan ends

### ✅ Active Status & Auto-Deactivation

**Only advances/loans with `is_active = true` are deducted.**

**Auto-Deactivation:**
- System automatically sets `is_active = false` when balance reaches 0
- Balance is calculated during payroll processing:
  - Counts how many payroll periods the advance/loan has been deducted
  - Calculates: `total_deductions = payroll_periods × monthly_deduction`
  - Calculates: `remaining_balance = original_amount - total_deductions`
  - If `remaining_balance <= 0`, automatically sets `is_active = false`

**Manual Deactivation:**
- You can manually set `is_active = false` to stop deductions when:
  - Employee leaves before repayment completes
  - Manual adjustment needed
  - Special circumstances require stopping deductions

### ⚠️ Monthly Deduction Amount

**The `monthly_deduction` field is the amount deducted each month.**

- This is set when creating the advance/loan
- Should be calculated based on:
  - Total amount / Number of months to repay
  - Or a fixed amount per month

---

## Workflow

### Creating a Salary Advance

1. **Go to**: HR & Payroll → Salary Advances → Create
2. **Fill in**:
   - Employee
   - Date (when advance is given)
   - Amount (total advance)
   - Monthly Deduction (amount per month)
   - Reason
3. **System automatically**:
   - Creates payment record
   - Sets `is_active = true`
   - Links to employee

### Creating an External Loan

1. **Go to**: HR & Payroll → External Loans → Create
2. **Fill in**:
   - Employee
   - Institution Name
   - Date (when loan was issued)
   - Total Loan Amount
   - Monthly Deduction
   - End Date (when repayment ends)
3. **System automatically**:
   - Sets `is_active = true`
   - Links to employee

### During Payroll Processing

1. **System automatically**:
   - Finds all active advances/loans for the employee
   - Matches by date (year/month)
   - Sums all `monthly_deduction` amounts
   - Deducts from net salary

2. **No manual intervention needed** - deductions are automatic

### Stopping Deductions

1. **Edit the advance/loan**:
   - Set `is_active = false`
   - Or delete the record (if not yet used in payroll)

2. **Next payroll processing**:
   - System will skip inactive advances/loans
   - No deduction will be made

---

## Integration Points

### 1. PayrollCalculationService

**Location**: `app/Services/Hr/PayrollCalculationService.php`

**Method**: `getOtherDeductions()`

```php
protected function getOtherDeductions(Employee $employee, $year, $month, $companyId)
{
    // Get salary advances
    $advances = SalaryAdvance::where('employee_id', $employee->id)
        ->where('company_id', $companyId)
        ->where('is_active', true)
        ->whereYear('date', $year)
        ->whereMonth('date', $month)
        ->sum('monthly_deduction');

    // Get external loans
    $loans = ExternalLoan::where('employee_id', $employee->id)
        ->where('company_id', $companyId)
        ->where('is_active', true)
        ->whereYear('date', $year)
        ->whereMonth('date', $month)
        ->sum('monthly_deduction');

    return [
        'advances' => $advances,
        'loans' => $loans,
        'total' => $advances + $loans,
    ];
}
```

### 2. PayrollController

**Location**: `app/Http/Controllers/Hr/PayrollController.php`

**Usage**: 
- Calls `PayrollCalculationService::getOtherDeductions()`
- Includes in payroll employee records
- Tracks in payroll totals

### 3. Employee Model Relationships

**Location**: `app/Models/Hr/Employee.php`

```php
public function salaryAdvances(): HasMany
{
    return $this->hasMany(SalaryAdvance::class);
}

public function externalLoans(): HasMany
{
    return $this->hasMany(ExternalLoan::class);
}
```

---

## Key Features

✅ **Automatic Deduction** - No manual entry needed during payroll
✅ **Date-Based Matching** - Deductions apply based on advance/loan date
✅ **Active Status Control** - Easy to stop deductions by setting `is_active = false`
✅ **Multiple Advances/Loans** - System handles multiple advances/loans per employee
✅ **Company Scoped** - Only advances/loans for the current company are considered
✅ **Monthly Deduction Tracking** - Each advance/loan has its own monthly deduction amount

---

## Best Practices

1. **Set Realistic Monthly Deductions**:
   - Calculate based on employee's net salary
   - Ensure deduction doesn't exceed reasonable percentage (e.g., 30-40% of net)

2. **Auto-Deactivation During Payroll Processing**:
   - System automatically checks balance for each advance/loan during payroll processing
   - Calculates: `total_deductions = payroll_periods_count × monthly_deduction`
   - Calculates: `remaining_balance = original_amount - total_deductions`
   - If `remaining_balance <= 0`, automatically sets `is_active = false`
   - Logs the deactivation for audit trail
   - No manual intervention needed

3. **Use Date Field Correctly**:
   - Set `date` to when advance/loan was actually given
   - System will automatically deduct every month until fully repaid
   - For external loans, set `date_end_of_loan` if there's a fixed repayment end date

4. **Track Repayment Progress**:
   - Monitor total deductions vs. original amount
   - Deactivate when fully repaid

5. **Document Reason**:
   - Always fill in `reason` field for audit trail
   - Helps track why advance/loan was given

---

## Summary

**Salary Advances and External Loans are automatically linked to payroll through:**
- Employee ID (who)
- Date field (when it applies)
- is_active flag (whether to deduct)
- monthly_deduction (how much to deduct)

**The system automatically:**
- Finds all active advances/loans for the payroll period
- Sums the monthly deductions
- Deducts from employee's net salary
- No manual intervention required during payroll processing

