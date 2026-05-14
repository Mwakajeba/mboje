# Salary Structure System - Complete Flow Documentation

## Overview
The Salary Structure system is a component-based approach to managing employee salaries. It allows you to define reusable salary components (earnings and deductions) and assign them to employees with effective dates.

---

## System Architecture

### 1. Database Structure

#### A. `hr_salary_components` Table (Master Components)
**Purpose**: Define reusable salary components that can be assigned to multiple employees.

**Key Fields**:
- `component_code` - Unique identifier (e.g., "BASIC_SALARY", "HOUSE_ALLOWANCE")
- `component_name` - Display name (e.g., "Basic Salary", "House Allowance")
- `component_type` - Either "earning" or "deduction"
- `calculation_type` - How the amount is calculated:
  - `fixed` - Fixed amount (set per employee)
  - `percentage` - Percentage of base amount (set per employee)
  - `formula` - Formula-based calculation
- `is_taxable` - Whether this component is subject to PAYE
- `is_pensionable` - Whether this component is subject to pension
- `is_nhif_applicable` - Whether this component is subject to NHIF
- `ceiling_amount` - Maximum amount allowed
- `floor_amount` - Minimum amount allowed
- `display_order` - Order in which to display components

**Example Components**:
```
BASIC_SALARY (earning, fixed, taxable, pensionable)
HOUSE_ALLOWANCE (earning, fixed, taxable, not pensionable)
TRANSPORT_ALLOWANCE (earning, fixed, taxable, not pensionable)
RESPONSIBILITY_ALLOWANCE (earning, percentage, taxable)
TRADE_UNION (deduction, fixed, not taxable)
```

#### B. `hr_employee_salary_structure` Table (Employee Assignments)
**Purpose**: Links salary components to specific employees with effective dates.

**Key Fields**:
- `employee_id` - Which employee
- `component_id` - Which component
- `amount` - Fixed amount (if calculation_type is "fixed")
- `percentage` - Percentage value (if calculation_type is "percentage")
- `effective_date` - When this structure becomes active
- `end_date` - When this structure expires (null = ongoing)
- `notes` - Optional notes

**Example Structure for Employee**:
```
Employee: John Doe
- BASIC_SALARY: amount = 1,000,000 (effective: 2025-01-01)
- HOUSE_ALLOWANCE: amount = 200,000 (effective: 2025-01-01)
- TRANSPORT_ALLOWANCE: amount = 150,000 (effective: 2025-01-01)
- RESPONSIBILITY_ALLOWANCE: percentage = 10% (effective: 2025-01-01)
- TRADE_UNION: amount = 5,000 (effective: 2025-01-01)
```

---

## Complete Flow

### Step 1: Create Salary Components (Master Setup)
**Location**: `/hr/salary-components`

**Process**:
1. HR Admin creates salary components that will be used across the company
2. Each component defines:
   - Code, name, type (earning/deduction)
   - Calculation method (fixed/percentage/formula)
   - Tax and statutory flags
   - Min/max limits

**Example**:
```
Component: BASIC_SALARY
- Type: Earning
- Calculation: Fixed
- Taxable: Yes
- Pensionable: Yes
- NHIF Applicable: Yes
```

**Current Status**: ✅ **IMPLEMENTED**
- Controller: `SalaryComponentController`
- Views: Create, Edit, Show, Index
- Routes: `hr.salary-components.*`

---

### Step 2: Assign Components to Employees (Employee Salary Structure)
**Location**: ⚠️ **NOT YET IMPLEMENTED**

**What Should Happen**:
1. Select an employee
2. Select one or more salary components
3. For each component, set:
   - Amount (if fixed) OR Percentage (if percentage-based)
   - Effective date
   - End date (optional)
4. Save the structure

**Current Status**: ❌ **MISSING**
- No controller for `EmployeeSalaryStructure`
- No views for assigning components to employees
- No routes for employee salary structure management
- Data can only be created via direct database insertion or tinker

**What Needs to Be Built**:
- Controller: `EmployeeSalaryStructureController`
- Views:
  - Index: List all employees with their salary structures
  - Create: Assign components to an employee
  - Edit: Update employee salary structure
  - Show: View employee's complete salary structure
- Routes: `hr.employee-salary-structure.*`

---

### Step 3: Payroll Calculation (Automatic)
**Location**: `PayrollCalculationService::calculateEmployeePayroll()`

**Process**:
1. **Get Employee Salary Structure**:
   ```php
   $salaryStructure = EmployeeSalaryStructure::getStructureForDate($employee->id, $startDate);
   ```
   - Retrieves all active components for the employee on the payroll date
   - Uses effective dates to determine which structure applies

2. **Calculate Earnings**:
   ```php
   $earnings = $this->calculateEarnings($employee, $salaryStructure, $startDate, $companyId);
   ```
   - Loops through all earning components in the structure
   - For each component:
     - If `fixed`: Uses `amount` from `employee_salary_structure`
     - If `percentage`: Calculates `baseAmount * (percentage / 100)`
     - If `formula`: Evaluates the formula
   - Applies floor/ceiling limits
   - Categorizes as basic salary, allowances, etc.

3. **Calculate Deductions**:
   ```php
   $deductions = $this->calculateDeductions($employee, $salaryStructure, $earnings['gross'], $startDate, $companyId);
   ```
   - Similar process for deduction components
   - Uses gross salary as base for percentage calculations

4. **Calculate Statutory Deductions**:
   ```php
   $statutoryDeductions = $this->calculateStatutoryDeductions($employee, $earnings['gross'], $earnings['taxable_gross'], $startDate, $companyId);
   ```
   - Uses Statutory Rules (PAYE, NHIF, Pension, etc.)
   - Considers component flags (`is_taxable`, `is_pensionable`, etc.)

5. **Calculate Net Salary**:
   ```php
   $netSalary = $totalEarnings - $totalDeductions;
   ```

**Current Status**: ✅ **IMPLEMENTED**
- Service: `PayrollCalculationService`
- Integration: Used in `PayrollController::calculateEmployeePayroll()`
- Fallback: If no structure exists, falls back to `employee->basic_salary`

---

## Calculation Examples

### Example 1: Fixed Amount Components
**Setup**:
- Component: `BASIC_SALARY` (fixed, earning)
- Employee Structure: `amount = 1,000,000`

**Calculation**:
```php
$amount = $employeeStructure->amount; // 1,000,000
```

**Result**: Basic Salary = 1,000,000 TZS

---

### Example 2: Percentage-Based Components
**Setup**:
- Component: `RESPONSIBILITY_ALLOWANCE` (percentage, earning)
- Base: Basic Salary = 1,000,000
- Employee Structure: `percentage = 10`

**Calculation**:
```php
$baseAmount = 1,000,000; // Basic salary
$percentage = 10; // From employee structure
$amount = $baseAmount * ($percentage / 100); // 1,000,000 * 0.10
```

**Result**: Responsibility Allowance = 100,000 TZS

---

### Example 3: Formula-Based Components
**Setup**:
- Component: `BONUS` (formula, earning)
- Formula: `{base} * 0.15 + {amount}`
- Base: Basic Salary = 1,000,000
- Employee Structure: `amount = 50,000`

**Calculation**:
```php
$formula = "{base} * 0.15 + {amount}";
$formula = str_replace('{base}', 1,000,000, $formula);
$formula = str_replace('{amount}', 50,000, $formula);
// Result: "1000000 * 0.15 + 50000"
$amount = eval("return $formula;"); // 200,000
```

**Result**: Bonus = 200,000 TZS

---

### Example 4: Complete Employee Structure
**Employee**: John Doe

**Earnings**:
- BASIC_SALARY: 1,000,000 (fixed)
- HOUSE_ALLOWANCE: 200,000 (fixed)
- TRANSPORT_ALLOWANCE: 150,000 (fixed)
- RESPONSIBILITY_ALLOWANCE: 10% of basic = 100,000

**Deductions**:
- TRADE_UNION: 5,000 (fixed)

**Calculation**:
```
Gross Salary = 1,000,000 + 200,000 + 150,000 + 100,000 = 1,450,000
Deductions = 5,000
Taxable Income = 1,450,000 (all components are taxable)
PAYE = Calculate based on brackets
Net Salary = 1,450,000 - PAYE - Pension - NHIF - 5,000
```

---

## Priority Order in Payroll Calculation

When calculating payroll, the system uses this priority:

1. **Salary Structure** (if exists)
   - Uses `EmployeeSalaryStructure` records
   - Most flexible and accurate
   - Supports effective dates

2. **Employee Basic Salary** (fallback)
   - Uses `employee->basic_salary`
   - Simple fallback
   - No component breakdown

3. **Traditional Allowances** (if no structure)
   - Uses `Allowance` model
   - Legacy system support

---

## Current Implementation Status

### ✅ Fully Implemented
- [x] Database migrations (`hr_salary_components`, `hr_employee_salary_structure`)
- [x] Models (`SalaryComponent`, `EmployeeSalaryStructure`)
- [x] Salary Component CRUD (Create, Read, Update, Delete components)
- [x] Payroll Calculation Service (uses salary structure if exists)
- [x] Integration with PayrollController
- [x] Fallback to employee basic_salary

### ❌ Missing Implementation
- [ ] **Employee Salary Structure Management UI**
  - No controller for assigning components to employees
  - No views for creating/editing employee structures
  - No routes for employee salary structure management
  - **This is the critical missing piece!**

### 🔧 Partial Implementation
- [ ] Formula evaluation (basic `eval()` - should use proper parser)
- [ ] Component assignment from employee profile
- [ ] Bulk assignment of components to multiple employees
- [ ] Salary structure templates

---

## How to Use (Current Workaround)

Since there's no UI for assigning components to employees, you currently need to:

### Option 1: Direct Database Insert
```php
// In tinker or migration
EmployeeSalaryStructure::create([
    'employee_id' => 1,
    'component_id' => 1, // BASIC_SALARY component
    'amount' => 1000000,
    'effective_date' => '2025-01-01',
    'end_date' => null,
]);
```

### Option 2: Programmatic Assignment
```php
// In a controller or service
$employee = Employee::find(1);
$basicSalaryComponent = SalaryComponent::where('component_code', 'BASIC_SALARY')->first();

EmployeeSalaryStructure::create([
    'employee_id' => $employee->id,
    'component_id' => $basicSalaryComponent->id,
    'amount' => 1000000,
    'effective_date' => now(),
]);
```

---

## Recommended Next Steps

### Priority 1: Build Employee Salary Structure Management
1. Create `EmployeeSalaryStructureController`
2. Create views:
   - Index: List employees with their structures
   - Create: Assign components to employee
   - Edit: Update employee structure
   - Show: View complete structure
3. Add routes: `hr.employee-salary-structure.*`
4. Add link from Employee show page to "Manage Salary Structure"

### Priority 2: Enhance User Experience
1. Add salary structure management from Employee profile
2. Add bulk assignment (assign same structure to multiple employees)
3. Add salary structure templates
4. Add validation (ensure at least one BASIC_SALARY component exists)

### Priority 3: Improve Formula Engine
1. Replace `eval()` with proper formula parser library
2. Add more formula variables (e.g., `{gross}`, `{days_worked}`)
3. Add formula validation and testing

---

## Integration Points

### With Contracts
- **Current**: Contract has `salary_reference` (not used)
- **Recommended**: Use contract salary as base for percentage calculations
- **Flow**: Contract salary → Salary Structure → Payroll

### With Employee Master
- **Current**: Employee has `basic_salary` (used as fallback)
- **Recommended**: Keep as initial/default, but prioritize structure
- **Flow**: Employee basic_salary → Salary Structure (if exists) → Payroll

### With Pay Groups
- **Future**: Pay Groups could define default salary structures
- **Flow**: Pay Group → Default Structure → Employee Structure (override) → Payroll

---

## Summary

The Salary Structure system is **partially implemented**:
- ✅ Backend logic is complete
- ✅ Calculation engine works
- ❌ **Missing UI to assign components to employees**

**To make it fully functional, you need to build the Employee Salary Structure management interface.**

