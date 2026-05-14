# Trade Union Information: Employee Form vs Salary Components

## Current Situation

### **Employee Registration Form** (Trade Union Information)
The employee form captures:
- **`trade_union_id`**: Links to the master `hr_trade_unions` table
- **`has_trade_union`**: Boolean flag to enable/disable trade union
- **`trade_union_category`**: Either "amount" (fixed) or "percentage"
- **`trade_union_amount`**: Fixed amount (if category is "amount")
- **`trade_union_percent`**: Percentage value (if category is "percentage")

**Location**: `resources/views/hr-payroll/employees/_form.blade.php` (lines 517-570)

### **Salary Components** (Trade Union Deductions)
Salary components can be created with:
- **`component_code`**: Can contain "trade_union" or "union" (e.g., "TRADE_UNION", "TU_DEDUCTION")
- **`component_type`**: Must be "deduction"
- **`calculation_type`**: Can be "fixed", "percentage", or "formula"
- **`calculation_formula`**: For formula-based calculations

**How it's identified**: The payroll calculation service checks if `component_code` contains "trade_union" or "union" (case-insensitive).

**Location**: `app/Services/Hr/PayrollCalculationService.php` (lines 204-206)

---

## ⚠️ **THE PROBLEM: NO DIRECT LINK**

Currently, **there is NO connection** between:

1. **Employee Trade Union Information** (stored in `hr_employees` table)
   - `trade_union_id`, `trade_union_amount`, `trade_union_percent`
   
2. **Trade Union Salary Components** (stored in `hr_salary_components` table)
   - Components with codes containing "trade_union" or "union"

### What Actually Happens in Payroll

```php
// In PayrollCalculationService::calculateDeductions()
// Trade union deductions come ONLY from salary components
$deductionComponents = $salaryStructure->filter(function($structure) {
    return $structure->component->component_type == SalaryComponent::TYPE_DEDUCTION;
});

foreach ($deductionComponents as $structure) {
    // Identifies trade union by component_code pattern matching
    if (str_contains(strtolower($component->component_code), 'trade_union') || 
        str_contains(strtolower($component->component_code), 'union')) {
        $tradeUnion += $amount;
    }
}
```

**Result**: 
- ❌ Employee's `trade_union_amount` or `trade_union_percent` is **NOT used**
- ❌ Employee's `trade_union_id` is **NOT used**
- ✅ Only salary components assigned via **Employee Salary Structure** are used

---

## How They Should Be Linked

### **Option 1: Use Employee Info as Fallback** (Recommended)
If an employee has trade union information but no trade union component in their salary structure, use the employee's trade union settings:

```php
// In PayrollCalculationService::calculateDeductions()
$tradeUnion = 0;

// First, check salary components
foreach ($deductionComponents as $structure) {
    if (str_contains(strtolower($component->component_code), 'trade_union') || 
        str_contains(strtolower($component->component_code), 'union')) {
        $tradeUnion += $amount;
    }
}

// Fallback: If no trade union component found, use employee's trade union info
if ($tradeUnion == 0 && $employee->has_trade_union) {
    if ($employee->trade_union_category == 'amount') {
        $tradeUnion = $employee->trade_union_amount ?? 0;
    } elseif ($employee->trade_union_category == 'percentage') {
        $tradeUnion = $grossSalary * (($employee->trade_union_percent ?? 0) / 100);
    }
}
```

### **Option 2: Auto-Assign Component Based on Employee Info**
When creating/updating an employee with trade union info, automatically create or assign a trade union salary component:

```php
// In EmployeeController::store() or ::update()
if ($request->has_trade_union) {
    // Find or create trade union component
    $tradeUnionComponent = SalaryComponent::firstOrCreate([
        'company_id' => $companyId,
        'component_code' => 'TRADE_UNION_' . $employee->trade_union_id,
        'component_type' => SalaryComponent::TYPE_DEDUCTION,
    ], [
        'component_name' => 'Trade Union Deduction',
        'calculation_type' => $request->trade_union_category == 'amount' 
            ? SalaryComponent::CALC_FIXED 
            : SalaryComponent::CALC_PERCENTAGE,
        'is_active' => true,
    ]);
    
    // Assign to employee's salary structure
    EmployeeSalaryStructure::create([
        'employee_id' => $employee->id,
        'component_id' => $tradeUnionComponent->id,
        'amount' => $request->trade_union_amount,
        'percentage' => $request->trade_union_percent,
        'effective_date' => now(),
    ]);
}
```

### **Option 3: Link Component to Trade Union Master**
Add a `trade_union_id` field to `SalaryComponent` to link it to the master trade union:

```php
// Migration
Schema::table('hr_salary_components', function (Blueprint $table) {
    $table->foreignId('trade_union_id')->nullable()
        ->constrained('hr_trade_unions')
        ->onDelete('set null');
});

// Then match employee's trade_union_id to component's trade_union_id
```

---

## Recommended Solution

**Use Option 1 (Fallback Approach)** - ✅ **IMPLEMENTED**

1. ✅ **Flexible**: Allows both methods (salary component OR employee info)
2. ✅ **Backward Compatible**: Doesn't break existing salary structures
3. ✅ **Simple**: No database changes needed
4. ✅ **User-Friendly**: Employees can have trade union info even if not in salary structure yet

### Implementation Status

✅ **COMPLETED**:

1. ✅ **Updated `PayrollCalculationService::calculateDeductions()`** - Now checks employee trade union info as fallback when no trade union component is found in salary structure
2. ✅ **Added validation** - EmployeeController now validates trade union info when `has_trade_union` is true
3. ✅ **Updated documentation** - This document clarifies the fallback behavior

### How It Works Now

**Priority Order:**
1. **First**: Check salary structure for trade union components (by component_code pattern)
2. **Fallback**: If no trade union component found AND employee has `has_trade_union = true`, use employee's trade union info:
   - If `trade_union_category = 'amount'`: Use `trade_union_amount`
   - If `trade_union_category = 'percentage'`: Calculate `grossSalary * (trade_union_percent / 100)`

**Code Location:**
- `app/Services/Hr/PayrollCalculationService.php::calculateDeductions()` (lines 197-250)
- `app/Http/Controllers/Hr/EmployeeController.php::store()` and `::update()` (validation added)

---

## Current Workflow

### **Scenario 1: Using Salary Components** (Current - Works)
1. Create a Trade Union salary component (e.g., "Trade Union Dues")
2. Assign it to employee via Employee Salary Structure
3. Payroll calculates deduction from the component
4. ✅ **Works correctly**

### **Scenario 2: Using Employee Trade Union Info** (Current - Doesn't Work)
1. Fill trade union information in employee form
2. Set `has_trade_union = true`
3. Set `trade_union_amount` or `trade_union_percent`
4. ❌ **NOT used in payroll calculation** - No deduction happens

---

## Summary

| Aspect | Employee Trade Union Info | Salary Component Trade Union |
|--------|---------------------------|------------------------------|
| **Stored In** | `hr_employees` table | `hr_salary_components` table |
| **Assigned Via** | Employee form | Employee Salary Structure |
| **Used in Payroll** | ❌ **NO** | ✅ **YES** |
| **Link** | ❌ **NONE** | N/A |

**Recommendation**: Implement Option 1 to use employee trade union info as a fallback when no trade union component is assigned in the salary structure.

