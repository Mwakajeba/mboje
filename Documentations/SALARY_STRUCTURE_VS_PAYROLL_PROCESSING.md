# Salary Structure vs Payroll Processing - Key Differences

## Overview
These are two **different but related** concepts in the HR & Payroll system. Understanding the difference is crucial for proper system usage.

---

## Salary Structure

### What It Is
**Salary Structure** is the **configuration** or **template** that defines **HOW** an employee's salary is composed.

### Purpose
- Define which salary components an employee has
- Set the amounts or percentages for each component
- Establish the structure that will be used in payroll calculations

### When You Use It
- **During employee setup** - Assign components to new employees
- **When salary changes** - Update employee's salary structure
- **For salary planning** - Define how salaries are structured

### What It Contains
- **Earnings Components**: Basic Salary, House Allowance, Transport Allowance, etc.
- **Deduction Components**: Trade Union, Loans, etc.
- **Amounts/Percentages**: How much each component is worth
- **Effective Dates**: When the structure becomes active

### Example
**Employee: John Doe**
- BASIC_SALARY: 1,000,000 TZS (fixed)
- HOUSE_ALLOWANCE: 200,000 TZS (fixed)
- TRANSPORT_ALLOWANCE: 150,000 TZS (fixed)
- RESPONSIBILITY_ALLOWANCE: 10% of basic (percentage)
- TRADE_UNION: 5,000 TZS (fixed)

**This is just the structure/configuration - no actual payroll has been calculated yet!**

---

## Process Payroll

### What It Is
**Process Payroll** is the **actual calculation and payment** process that:
1. Takes the salary structure
2. Applies it to a specific payroll period (month/year)
3. Calculates actual amounts based on attendance, leave, etc.
4. Applies statutory deductions (PAYE, NHIF, Pension, etc.)
5. Generates payslips and payment files

### Purpose
- Calculate actual pay for a specific period
- Apply variable factors (attendance, overtime, leave, etc.)
- Calculate statutory deductions
- Generate payslips
- Create payment records

### When You Use It
- **Monthly** - Process payroll for each payroll period
- **After attendance/leave data is finalized** - Usually at month-end
- **Before payment** - Calculate what employees should be paid

### What It Does
1. **Reads** the employee's salary structure
2. **Applies** attendance data (hours worked, overtime)
3. **Applies** leave data (paid/unpaid leave)
4. **Calculates** statutory deductions (PAYE, NHIF, Pension, etc.)
5. **Applies** other deductions (loans, advances)
6. **Calculates** net pay
7. **Generates** payslips
8. **Creates** payment records

### Example
**Processing Payroll for January 2025:**

For John Doe (with the structure above):
1. **Base Calculation** (from structure):
   - Basic Salary: 1,000,000
   - House Allowance: 200,000
   - Transport Allowance: 150,000
   - Responsibility Allowance: 100,000 (10% of 1,000,000)
   - Trade Union: 5,000

2. **Variable Adjustments** (from attendance/leave):
   - Overtime: +50,000 (from attendance records)
   - Unpaid Leave: -45,455 (2 days unpaid leave)

3. **Statutory Deductions** (calculated):
   - PAYE: 150,000
   - NHIF: 15,000
   - Pension: 50,000

4. **Final Calculation**:
   - Gross: 1,354,545
   - Deductions: 220,000
   - **Net Pay: 1,134,545 TZS**

5. **Output**:
   - Payslip generated
   - Payment record created
   - Accounting entries posted

---

## Key Differences Summary

| Aspect | Salary Structure | Process Payroll |
|--------|------------------|-----------------|
| **Type** | Configuration/Template | Calculation/Action |
| **When** | Set up once, update when needed | Run monthly/periodically |
| **Purpose** | Define salary components | Calculate actual pay |
| **Scope** | Per employee | Per payroll period |
| **Contains** | Components, amounts, percentages | Actual calculated amounts, deductions, net pay |
| **Changes** | When salary structure changes | Every payroll period |
| **Output** | Structure definition | Payslips, payment files, accounting entries |
| **Uses** | Employee setup | Attendance, leave, statutory rules |

---

## How They Work Together

### Flow Diagram

```
1. SETUP PHASE (One-time or when changes occur)
   └─> Create Salary Components (master definitions)
   └─> Assign Salary Structure to Employee
       └─> Employee now has: BASIC_SALARY, ALLOWANCES, etc.

2. MONTHLY OPERATIONS
   └─> Record Attendance (daily)
   └─> Record Leave (as needed)
   └─> Record Overtime (as needed)

3. PAYROLL PROCESSING (Monthly)
   └─> System reads Employee's Salary Structure
   └─> Applies attendance/leave/overtime data
   └─> Calculates statutory deductions
   └─> Generates payslips
   └─> Creates payment records
```

### Example Workflow

**Month 1 (January 2025):**
1. **Setup**: Assign salary structure to John Doe
   - BASIC_SALARY: 1,000,000
   - HOUSE_ALLOWANCE: 200,000
   - etc.

2. **During Month**: Record attendance, leave, overtime

3. **End of Month**: Process Payroll for January
   - Uses the structure
   - Applies January's attendance/leave data
   - Calculates January's pay
   - Generates January payslip

**Month 2 (February 2025):**
1. **Setup**: (No change - same structure)

2. **During Month**: Record attendance, leave, overtime (different from January)

3. **End of Month**: Process Payroll for February
   - Uses the **same structure**
   - Applies **February's** attendance/leave data (different from January)
   - Calculates **February's** pay (may be different due to different attendance/overtime)
   - Generates February payslip

---

## Real-World Analogy

### Salary Structure = Recipe
- Defines the ingredients (components)
- Defines the amounts (how much of each)
- Can be reused multiple times

### Process Payroll = Cooking
- Uses the recipe (structure)
- Applies it with actual ingredients (attendance, leave data)
- Produces the final dish (payslip/payment)

**Same recipe, different results each time** (because attendance/overtime varies)

---

## When to Use Each

### Use Salary Structure When:
- ✅ Setting up a new employee
- ✅ Employee gets a raise (update structure)
- ✅ Employee gets a new allowance (add component)
- ✅ Employee's salary components change
- ✅ Planning salary changes

### Use Process Payroll When:
- ✅ Calculating monthly salaries
- ✅ Generating payslips
- ✅ Processing payments
- ✅ Creating accounting entries
- ✅ Month-end payroll processing

---

## Important Notes

1. **Salary Structure is Reusable**
   - Set it once
   - Used every month in payroll processing
   - Only update when salary changes

2. **Payroll Processing is Periodic**
   - Run monthly (or per payroll period)
   - Uses the current salary structure
   - Applies current period's data

3. **Structure Changes Affect Future Payrolls**
   - If you update structure in February
   - January's payroll (already processed) is unchanged
   - February's payroll (future) will use new structure

4. **Payroll Processing Uses Multiple Data Sources**
   - Salary Structure (base components)
   - Attendance (hours, overtime)
   - Leave (paid/unpaid days)
   - Statutory Rules (PAYE, NHIF, etc.)
   - Loans/Advances (deductions)

---

## Summary

**Salary Structure** = **"What components does this employee have?"**
- Configuration
- Set once, used many times
- Defines the template

**Process Payroll** = **"How much should this employee be paid this month?"**
- Calculation
- Run monthly
- Produces actual pay amounts

**They work together**: Structure provides the template, Payroll Processing applies it with actual data to produce payslips and payments.
