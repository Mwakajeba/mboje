# PAYE Configuration Guide

## Important: PAYE is NOT Set in Salary Structure

**PAYE (Pay As You Earn)** is **NOT** a salary component that you assign to employees in their salary structure. Instead, it's a **statutory deduction** that is **automatically calculated** during payroll processing.

---

## How PAYE Works in the System

### 1. PAYE is Configured in Statutory Rules (Not Salary Structure)

**Location**: `/hr/statutory-rules`

**What You Configure**:
- **PAYE Tax Brackets** (progressive tax rates)
- **Tax Relief** (if applicable)
- **Effective Dates** (when the rules apply)

**Example PAYE Brackets (Tanzania)**:
```
Bracket 1: 0 - 270,000 TZS → 0% tax
Bracket 2: 270,001 - 520,000 TZS → 8% tax
Bracket 3: 520,001 - 760,000 TZS → 20% tax
Bracket 4: 760,001 - 1,000,000 TZS → 25% tax
Bracket 5: Above 1,000,000 TZS → 30% tax
```

---

### 2. How PAYE is Calculated During Payroll Processing

**Step-by-Step Process**:

1. **Calculate Taxable Income**:
   - Start with Gross Salary (from salary structure)
   - Subtract Pension contributions (if applicable)
   - Result = Taxable Income

2. **Apply PAYE Brackets**:
   - System reads PAYE brackets from Statutory Rules
   - Applies progressive tax rates to taxable income
   - Calculates PAYE amount

3. **Apply Tax Relief** (if configured):
   - Subtracts tax relief from calculated PAYE

4. **Result**: PAYE deduction amount

---

## Example: How It Works Together

### Employee Setup

**Employee: John Doe**

**Salary Structure** (assigned to employee):
- BASIC_SALARY: 1,000,000 TZS
- HOUSE_ALLOWANCE: 200,000 TZS
- TRANSPORT_ALLOWANCE: 150,000 TZS
- **Total Gross: 1,350,000 TZS**

**Note**: PAYE is NOT in the salary structure!

---

### Statutory Rules Setup

**PAYE Rule** (configured separately):
- Bracket 1: 0 - 270,000 → 0%
- Bracket 2: 270,001 - 520,000 → 8%
- Bracket 3: 520,001 - 760,000 → 20%
- Bracket 4: 760,001 - 1,000,000 → 25%
- Bracket 5: Above 1,000,000 → 30%
- Tax Relief: 0 TZS

---

### Payroll Processing (Automatic)

**When processing payroll for January 2025**:

1. **Get Gross Salary** (from salary structure):
   - Basic: 1,000,000
   - House Allowance: 200,000
   - Transport: 150,000
   - **Gross: 1,350,000 TZS**

2. **Calculate Taxable Income**:
   - Gross: 1,350,000
   - Less Pension (if applicable): -50,000
   - **Taxable Income: 1,300,000 TZS**

3. **Calculate PAYE** (using brackets):
   - Bracket 1 (0-270,000): 270,000 × 0% = 0
   - Bracket 2 (270,001-520,000): 250,000 × 8% = 20,000
   - Bracket 3 (520,001-760,000): 240,000 × 20% = 48,000
   - Bracket 4 (760,001-1,000,000): 240,000 × 25% = 60,000
   - Bracket 5 (Above 1,000,000): 300,000 × 30% = 90,000
   - **Total PAYE: 218,000 TZS**

4. **Result**:
   - Gross: 1,350,000
   - PAYE: -218,000 (automatically calculated)
   - Other deductions: -...
   - **Net Pay: ...**

---

## Why PAYE is NOT in Salary Structure?

### 1. **PAYE is Automatic**
- It's calculated based on taxable income
- Same brackets apply to all employees
- No need to assign it per employee

### 2. **PAYE is Statutory**
- It's a legal requirement (TRA - Tanzania Revenue Authority)
- Rules are set by government, not by company
- All employees follow the same tax brackets

### 3. **PAYE is Variable**
- Amount changes based on taxable income
- Different each month if salary varies
- Cannot be "fixed" like other components

### 3. **PAYE is Company-Wide**
- One set of brackets for entire company
- Configured once in Statutory Rules
- Applied automatically to all employees

---

## What Goes in Salary Structure vs Statutory Rules?

### ✅ Salary Structure (Employee-Specific)
- **Earnings**: Basic Salary, Allowances, Bonuses
- **Deductions**: Trade Union, Loans, Advances
- **Employee-Specific**: Each employee has different components

### ✅ Statutory Rules (Company-Wide)
- **PAYE**: Tax brackets (same for all)
- **NHIF**: Contribution rates (same for all)
- **Pension**: Contribution rates (same for all)
- **WCF**: Employer contribution rates
- **SDL**: Skills Development Levy rates
- **HESLB**: Student loan rates

---

## How to Configure PAYE

### Step 1: Create PAYE Statutory Rule

1. Navigate to: `/hr/statutory-rules`
2. Click "Create Rule"
3. Select Rule Type: **PAYE**
4. Configure Tax Brackets:
   ```
   Bracket 1:
   - Threshold: 270000
   - Rate: 8
   
   Bracket 2:
   - Threshold: 520000
   - Rate: 20
   
   Bracket 3:
   - Threshold: 760000
   - Rate: 25
   
   Bracket 4:
   - Threshold: 1000000
   - Rate: 30
   ```
5. Set Effective Date
6. Save

### Step 2: That's It!

- PAYE will be **automatically calculated** during payroll processing
- No need to add it to employee salary structures
- System uses the active PAYE rule for the payroll period

---

## Summary

| Question | Answer |
|----------|--------|
| **Where is PAYE set?** | In **Statutory Rules**, NOT in Salary Structure |
| **Do I add PAYE to employee structure?** | **NO** - It's calculated automatically |
| **How is PAYE calculated?** | Automatically during payroll processing using brackets |
| **Can I customize PAYE per employee?** | **NO** - Same brackets apply to all (statutory requirement) |
| **What affects PAYE amount?** | Taxable income (from salary structure earnings) |

---

## Key Takeaway

**Salary Structure** = What the employee earns (components)
**Statutory Rules** = How statutory deductions are calculated (PAYE, NHIF, etc.)
**Payroll Processing** = Applies both to calculate final pay

**You don't "set" PAYE in salary structure - it's automatically calculated!**

