# Statutory Rules Employee Linking Guide

## Overview

Different statutory rules have different linking mechanisms to employees. Some apply universally, while others are employee-specific. This guide explains how each rule is linked.

## Statutory Rules Linking Methods

### 1. **PAYE (Pay As You Earn) - Universal**

**Linking Method**: **Universal** - Applies to ALL employees automatically

**How it works**:
- ✅ **No employee flag required**
- ✅ Calculated for **every employee** based on taxable income
- ✅ Statutory Rule provides tax brackets and rates
- ✅ Employee cannot opt-out (mandatory by law)

**Calculation Logic**:
```
IF taxable income > 0:
  → Calculate PAYE using statutory rule brackets
  → Apply to ALL employees
ELSE:
  → PAYE = 0
```

**Employee Fields**: None required

**Example**:
- Employee A: Salary 500,000 → PAYE calculated
- Employee B: Salary 1,200,000 → PAYE calculated
- Employee C: Salary 200,000 → PAYE calculated (may be 0 if below threshold)

---

### 2. **NHIF (National Health Insurance Fund) - Employee-Specific**

**Linking Method**: **Employee Boolean Flag** (`has_nhif`)

**How it works**:
- ✅ Requires `has_nhif = true` on employee record
- ✅ Only employees with the flag enabled are charged
- ✅ Statutory Rule provides the rate and ceiling
- ✅ Employee can opt-in/opt-out via the flag

**Calculation Logic**:
```
IF employee.has_nhif == true:
  → Get rate from Statutory Rule (or employee override)
  → Calculate NHIF deduction
ELSE:
  → NHIF = 0
```

**Employee Fields**:
- `has_nhif` (boolean) - Enable/disable NHIF
- `nhif_employee_percent` (optional) - Override statutory rate
- `nhif_member_number` (optional) - NHIF membership number

**Example**:
- Employee A: `has_nhif = true` → NHIF deducted
- Employee B: `has_nhif = false` → No NHIF deduction
- Employee C: `has_nhif = true` → NHIF deducted

---

### 3. **Pension (NSSF/PSSSF) - Employee-Specific**

**Linking Method**: **Employee Boolean Flag** (`has_pension`)

**How it works**:
- ✅ Requires `has_pension = true` on employee record
- ✅ Only employees with the flag enabled are charged
- ✅ Statutory Rule provides the rate and ceiling
- ✅ Employee can opt-in/opt-out via the flag

**Calculation Logic**:
```
IF employee.has_pension == true:
  → Get rate from Statutory Rule (or employee override)
  → Calculate Pension deduction
ELSE:
  → Pension = 0
```

**Employee Fields**:
- `has_pension` (boolean) - Enable/disable Pension
- `pension_employee_percent` (optional) - Override statutory rate
- `pension_employer_percent` (optional) - Employer contribution rate
- `social_fund_type` (optional) - NSSF, PSSSF, etc.
- `social_fund_number` (optional) - Membership number

**Example**:
- Employee A: `has_pension = true` → Pension deducted
- Employee B: `has_pension = false` → No Pension deduction
- Employee C: `has_pension = true` → Pension deducted

---

### 4. **WCF (Workers Compensation Fund) - Employee-Specific**

**Linking Method**: **Employee Boolean Flag** (`has_wcf`)

**How it works**:
- ✅ Requires `has_wcf = true` on employee record
- ✅ Only employees with the flag enabled are charged
- ✅ Statutory Rule provides the rate
- ✅ Employee can opt-in/opt-out via the flag

**Calculation Logic**:
```
IF employee.has_wcf == true:
  → Get rate from Statutory Rule
  → Calculate WCF (employer contribution)
ELSE:
  → WCF = 0
```

**Employee Fields**:
- `has_wcf` (boolean) - Enable/disable WCF
- `wcf_employee_percent` (optional) - Override statutory rate
- `wcf_employer_percent` (optional) - Employer contribution rate

**Example**:
- Employee A: `has_wcf = true` → WCF calculated
- Employee B: `has_wcf = false` → No WCF
- Employee C: `has_wcf = true` → WCF calculated

---

### 5. **SDL (Skills Development Levy) - Employee-Specific + Company Requirement**

**Linking Method**: **Employee Boolean Flag** (`has_sdl`) + **Company Employee Count**

**How it works**:
- ✅ Requires `has_sdl = true` on employee record
- ✅ **AND** company must have 10+ employees (configurable in statutory rule)
- ✅ Only employees with the flag enabled are charged (if company qualifies)
- ✅ Statutory Rule provides the rate and minimum employee threshold
- ✅ Employee can opt-in/opt-out via the flag

**Calculation Logic**:
```
IF employee.has_sdl == true:
  → Check company employee count
  → IF company has >= 10 employees (or configured minimum):
    → Get rate from Statutory Rule
    → Calculate SDL
  → ELSE:
    → SDL = 0 (company doesn't qualify)
ELSE:
  → SDL = 0
```

**Employee Fields**:
- `has_sdl` (boolean) - Enable/disable SDL
- `sdl_employee_percent` (optional) - Override statutory rate
- `sdl_employer_percent` (optional) - Employer contribution rate

**Statutory Rule Fields**:
- `sdl_employer_percent` - Rate (default: 3.5% for 2025)
- `sdl_min_employees` - Minimum employees required (default: 10)

**Example**:
- Company has 15 employees:
  - Employee A: `has_sdl = true` → SDL calculated
  - Employee B: `has_sdl = false` → No SDL
- Company has 5 employees:
  - Employee A: `has_sdl = true` → SDL = 0 (company doesn't qualify)
  - Employee B: `has_sdl = false` → No SDL

---

### 6. **HESLB (Student Loans) - Employee-Specific via Loan Records**

**Linking Method**: **Active Loan Record** (not boolean flag)

**How it works**:
- ✅ Requires **active HESLB loan record** (`HeslbLoan` with `is_active = true` and `outstanding_balance > 0`)
- ✅ Only employees with active loans are charged
- ✅ Statutory Rule provides the rate and ceiling
- ✅ Employee cannot opt-in/opt-out via flag (loan record determines eligibility)
- ✅ Deductions automatically stop when balance reaches zero

**Calculation Logic**:
```
IF employee has active loan (outstanding_balance > 0):
  → Get rate from Statutory Rule (or employee override)
  → Calculate HESLB deduction
  → Cap to outstanding balance
  → Record repayment
ELSE:
  → HESLB = 0
```

**Employee Fields**:
- `has_heslb` (optional/legacy) - Not required for calculation
- `heslb_employee_percent` (optional) - Override statutory rate

**Loan Record Required**:
- `HeslbLoan` record with:
  - `is_active = true`
  - `outstanding_balance > 0`

**Example**:
- Employee A: Has active loan (balance: 500,000) → HESLB deducted
- Employee B: No loan record → HESLB = 0
- Employee C: Has loan but balance = 0 → HESLB = 0 (auto-stopped)

---

## Summary Table

| Statutory Rule | Linking Method | Employee Flag | Universal? | Special Requirements |
|---------------|----------------|---------------|------------|---------------------|
| **PAYE** | Universal | None | ✅ Yes | None |
| **NHIF** | Boolean Flag | `has_nhif` | ❌ No | None |
| **Pension** | Boolean Flag | `has_pension` | ❌ No | None |
| **WCF** | Boolean Flag | `has_wcf` | ❌ No | None |
| **SDL** | Boolean Flag + Company | `has_sdl` | ❌ No | Company must have 10+ employees |
| **HESLB** | Loan Record | None (uses loan) | ❌ No | Active loan with balance > 0 |

## Key Differences

### Universal Rules (Apply to All)
- **PAYE**: Mandatory for all employees based on income

### Employee-Specific via Flags
- **NHIF, Pension, WCF**: Controlled by employee boolean flags
- Employees can be individually enabled/disabled

### Employee-Specific with Additional Requirements
- **SDL**: Requires employee flag + company employee count threshold
- **HESLB**: Requires active loan record (not just a flag)

## Configuration Flow

### For Universal Rules (PAYE):
```
1. Create Statutory Rule (company-wide)
   ↓
2. Rule applies to ALL employees automatically
   ↓
3. No employee-level configuration needed
```

### For Flag-Based Rules (NHIF, Pension, WCF):
```
1. Create Statutory Rule (company-wide)
   ↓
2. Enable flag on employee record (has_nhif, has_pension, has_wcf)
   ↓
3. Deduction calculated for flagged employees only
```

### For SDL:
```
1. Create Statutory Rule (company-wide)
   ↓
2. Check company employee count (must be >= 10)
   ↓
3. Enable flag on employee record (has_sdl)
   ↓
4. Deduction calculated if both conditions met
```

### For HESLB:
```
1. Create Statutory Rule (company-wide)
   ↓
2. Create loan record for employee (HeslbLoan)
   ↓
3. Deduction calculated automatically if loan is active
   ↓
4. Deduction stops when balance reaches zero
```

## Best Practices

1. **PAYE**: No configuration needed - applies automatically
2. **NHIF/Pension/WCF**: Enable flags only for employees who are members
3. **SDL**: Enable flags only if company has 10+ employees
4. **HESLB**: Create loan records only for employees with student loans

## Notes

- Employee-level percentage fields can override statutory rule rates if needed
- Statutory rules provide default rates and ceilings
- All rules respect company scoping (only apply to employees in the same company)
- Rules can have effective dates (start/end dates) for historical tracking

