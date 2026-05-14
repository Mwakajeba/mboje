# HR Salary Flow Review & Recommendations

## Current State Analysis

### 1. Where Basic Salary is Currently Set

#### A. Employee Creation Form
- **Location**: `resources/views/hr-payroll/employees/_form.blade.php`
- **Field**: `basic_salary` (required field in Employment Information section)
- **Purpose**: Initial salary setup when creating employee
- **Stored in**: `hr_employees.basic_salary` column
- **Used in**: Payroll calculations (primary source currently)

#### B. Contract Creation Form
- **Location**: `resources/views/hr-payroll/contracts/create.blade.php`
- **Field**: `salary_reference` (optional field)
- **Purpose**: "Reference salary amount for this contract (for documentation purposes only)"
- **Stored in**: `hr_contracts.salary_reference` column
- **Used in**: Currently NOT used in payroll calculations (reference only)

#### C. Salary Structure (Phase 3 - New Feature)
- **Location**: `hr_employee_salary_structure` table
- **Purpose**: Component-based salary structure (most flexible)
- **Used in**: PayrollCalculationService (when structure exists)

---

## Current Payroll Calculation Flow

### Legacy Calculation (PayrollController)
```php
$basicSalary = $employee->basic_salary; // Directly from employee table
```

### New Calculation (PayrollCalculationService)
```php
// Priority order:
1. Salary Structure components (if exists)
2. Fallback to $employee->basic_salary
```

**Current Issue**: Contract `salary_reference` is NOT used in calculations.

---

## Recommended Flow (Best Practice)

### Priority Order for Salary Determination:

1. **Salary Structure** (Highest Priority - Phase 3)
   - Component-based, flexible
   - Effective-dated
   - Most accurate for payroll

2. **Active Contract Salary** (Should be added)
   - Contract-specific salary
   - Effective-dated (contract start/end dates)
   - Reflects actual employment terms

3. **Employee Basic Salary** (Fallback)
   - Initial/default salary
   - Used when no contract or structure exists
   - Quick setup during employee creation

---

## Recommendations

### Option 1: Contract-Based Salary (Recommended for Enterprise)
**Flow:**
- Employee Creation: Set initial `basic_salary` (for quick setup, can be 0)
- Contract Creation: Set actual `salary` (required field, used in payroll)
- Payroll Calculation: Use active contract salary → fallback to employee basic_salary

**Benefits:**
- ✅ Effective-dated salary changes (contract renewals, promotions)
- ✅ Historical tracking (each contract has its salary)
- ✅ Legal compliance (contract reflects actual terms)
- ✅ Supports salary changes over time

**Changes Needed:**
1. Make `salary_reference` → `salary` (required field in contracts)
2. Update payroll calculation to use active contract salary
3. Keep employee `basic_salary` as fallback only

### Option 2: Employee-Based Salary (Current - Simple)
**Flow:**
- Employee Creation: Set `basic_salary` (required)
- Contract Creation: `salary_reference` remains optional (documentation only)
- Payroll Calculation: Use employee `basic_salary` directly

**Benefits:**
- ✅ Simple and straightforward
- ✅ No changes needed
- ✅ Works for small organizations

**Limitations:**
- ❌ No effective-dated salary tracking
- ❌ Salary changes require employee record updates
- ❌ No historical salary tracking per contract

### Option 3: Hybrid Approach (Recommended)
**Flow:**
- Employee Creation: Set initial `basic_salary` (for quick setup)
- Contract Creation: Set `salary` (if different from employee basic_salary)
- Payroll Calculation Priority:
  1. Salary Structure (if exists)
  2. Active Contract salary (if exists and different from employee)
  3. Employee basic_salary (fallback)

**Benefits:**
- ✅ Flexible - supports all scenarios
- ✅ Backward compatible
- ✅ Supports gradual migration

---

## Implementation Plan

### Recommended Changes:

1. **Update Contract Model & Migration**
   - Rename `salary_reference` → `salary`
   - Make it required (or nullable with logic)
   - Add validation

2. **Update Payroll Calculation**
   - Check for active contract salary first
   - Fallback to employee basic_salary
   - Support Salary Structure (already implemented)

3. **Update Contract Form**
   - Change field label and help text
   - Add validation
   - Show employee's current basic_salary as reference

4. **Update Employee Form**
   - Keep basic_salary field
   - Add note: "Initial salary - can be overridden by contract or salary structure"

---

## Decision Matrix

| Scenario | Employee Basic Salary | Contract Salary | Salary Structure |
|----------|----------------------|-----------------|------------------|
| **Quick Setup** | ✅ Set during creation | Optional | Not needed |
| **Contract Renewal** | Keep as default | ✅ Update in new contract | Optional |
| **Promotion/Salary Change** | Update OR | ✅ Create new contract | OR Create structure |
| **Complex Salary** | Base amount | Reference | ✅ Use components |
| **Historical Tracking** | Single value | ✅ Per contract | ✅ Effective-dated |

---

## Recommendation

**Use Option 3 (Hybrid Approach)** because:
1. ✅ Backward compatible with existing data
2. ✅ Supports simple and complex scenarios
3. ✅ Enables gradual migration to salary structures
4. ✅ Maintains contract as source of truth for employment terms
5. ✅ Provides fallback for edge cases

**Implementation Priority:**
1. Update Contract to have `salary` field (required when contract is active)
2. Update PayrollCalculationService to check contract salary
3. Keep employee basic_salary as fallback
4. Document the priority order clearly

---

## Questions to Answer

1. **Should contract salary be required?**
   - Yes, if contract is active
   - No, if contract is just for documentation

2. **What happens when contract expires?**
   - Use last active contract salary?
   - Fallback to employee basic_salary?
   - Require new contract?

3. **Should salary changes create new contracts or amendments?**
   - New contract = new employment terms
   - Amendment = same contract, updated terms

4. **How to handle multiple active contracts?**
   - System should prevent this
   - Or use most recent contract

