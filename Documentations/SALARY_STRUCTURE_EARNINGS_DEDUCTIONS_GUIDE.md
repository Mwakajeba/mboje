# Salary Structure: Earnings & Deductions Guide

## Overview
This guide explains what should be included as **Earnings** and **Deductions** in the salary structure, with clear examples and best practices.

---

## Understanding Earnings vs Deductions

### Earnings (Money Added to Salary)
- **Definition**: Components that **increase** the employee's gross pay
- **Purpose**: Money the employee **receives**
- **Examples**: Basic Salary, Allowances, Bonuses

### Deductions (Money Subtracted from Salary)
- **Definition**: Components that **decrease** the employee's gross pay
- **Purpose**: Money **deducted** from the employee's salary
- **Examples**: Trade Union, Loans, Advances

---

## ✅ EARNINGS - What to Include

### 1. Basic Salary
**What it is**: The core fixed salary amount
**Type**: Earning
**Calculation**: Fixed Amount
**Taxable**: Yes
**Pensionable**: Yes (usually)
**NHIF Applicable**: Yes

**Example**:
```
Component Code: BASIC_SALARY
Component Name: Basic Salary
Component Type: Earning
Calculation Type: Fixed
Amount: 1,000,000 TZS
Is Taxable: Yes
Is Pensionable: Yes
Is NHIF Applicable: Yes
```

---

### 2. House Allowance
**What it is**: Allowance for housing expenses
**Type**: Earning
**Calculation**: Fixed Amount or Percentage
**Taxable**: Yes (usually)
**Pensionable**: No (usually)
**NHIF Applicable**: Yes

**Example (Fixed)**:
```
Component Code: HOUSE_ALLOWANCE
Component Name: House Allowance
Component Type: Earning
Calculation Type: Fixed
Amount: 200,000 TZS
Is Taxable: Yes
Is Pensionable: No
Is NHIF Applicable: Yes
```

**Example (Percentage)**:
```
Component Code: HOUSE_ALLOWANCE
Component Name: House Allowance
Component Type: Earning
Calculation Type: Percentage
Percentage: 20% of Basic Salary
Is Taxable: Yes
Is Pensionable: No
```

---

### 3. Transport Allowance
**What it is**: Allowance for transportation costs
**Type**: Earning
**Calculation**: Fixed Amount
**Taxable**: Yes (usually)
**Pensionable**: No
**NHIF Applicable**: Yes

**Example**:
```
Component Code: TRANSPORT_ALLOWANCE
Component Name: Transport Allowance
Component Type: Earning
Calculation Type: Fixed
Amount: 150,000 TZS
Is Taxable: Yes
Is Pensionable: No
Is NHIF Applicable: Yes
```

---

### 4. Responsibility Allowance
**What it is**: Additional pay for additional responsibilities
**Type**: Earning
**Calculation**: Percentage or Fixed
**Taxable**: Yes
**Pensionable**: No (usually)
**NHIF Applicable**: Yes

**Example (Percentage)**:
```
Component Code: RESPONSIBILITY_ALLOWANCE
Component Name: Responsibility Allowance
Component Type: Earning
Calculation Type: Percentage
Percentage: 10% of Basic Salary
Is Taxable: Yes
Is Pensionable: No
```

---

### 5. Acting Allowance
**What it is**: Pay for acting in a higher position
**Type**: Earning
**Calculation**: Percentage or Fixed
**Taxable**: Yes
**Pensionable**: No
**NHIF Applicable**: Yes

**Example**:
```
Component Code: ACTING_ALLOWANCE
Component Name: Acting Allowance
Component Type: Earning
Calculation Type: Percentage
Percentage: 15% of Basic Salary
Is Taxable: Yes
Is Pensionable: No
```

---

### 6. Medical Allowance
**What it is**: Allowance for medical expenses
**Type**: Earning
**Calculation**: Fixed Amount
**Taxable**: Depends on company policy
**Pensionable**: No
**NHIF Applicable**: Yes

**Example**:
```
Component Code: MEDICAL_ALLOWANCE
Component Name: Medical Allowance
Component Type: Earning
Calculation Type: Fixed
Amount: 50,000 TZS
Is Taxable: Yes (or No, depending on policy)
Is Pensionable: No
```

---

### 7. Meal Allowance
**What it is**: Allowance for meals
**Type**: Earning
**Calculation**: Fixed Amount
**Taxable**: Yes (usually)
**Pensionable**: No
**NHIF Applicable**: Yes

**Example**:
```
Component Code: MEAL_ALLOWANCE
Component Name: Meal Allowance
Component Type: Earning
Calculation Type: Fixed
Amount: 30,000 TZS
Is Taxable: Yes
Is Pensionable: No
```

---

### 8. Communication Allowance
**What it is**: Allowance for phone/internet
**Type**: Earning
**Calculation**: Fixed Amount
**Taxable**: Yes
**Pensionable**: No
**NHIF Applicable**: Yes

**Example**:
```
Component Code: COMMUNICATION_ALLOWANCE
Component Name: Communication Allowance
Component Type: Earning
Calculation Type: Fixed
Amount: 25,000 TZS
Is Taxable: Yes
Is Pensionable: No
```

---

### 9. Entertainment Allowance
**What it is**: Allowance for entertainment expenses
**Type**: Earning
**Calculation**: Fixed Amount or Percentage
**Taxable**: Yes
**Pensionable**: No
**NHIF Applicable**: Yes

**Example**:
```
Component Code: ENTERTAINMENT_ALLOWANCE
Component Name: Entertainment Allowance
Component Type: Earning
Calculation Type: Fixed
Amount: 40,000 TZS
Is Taxable: Yes
Is Pensionable: No
```

---

### 10. Performance Bonus
**What it is**: Bonus based on performance
**Type**: Earning
**Calculation**: Fixed Amount or Formula
**Taxable**: Yes
**Pensionable**: No (usually)
**NHIF Applicable**: Yes

**Example (Fixed)**:
```
Component Code: PERFORMANCE_BONUS
Component Name: Performance Bonus
Component Type: Earning
Calculation Type: Fixed
Amount: 100,000 TZS (varies per employee)
Is Taxable: Yes
Is Pensionable: No
```

**Example (Formula)**:
```
Component Code: PERFORMANCE_BONUS
Component Name: Performance Bonus
Component Type: Earning
Calculation Type: Formula
Formula: {base} * 0.15 + {amount}
Is Taxable: Yes
Is Pensionable: No
```

---

### 11. Overtime Allowance
**What it is**: Pay for overtime work
**Type**: Earning
**Calculation**: Usually calculated from attendance (not in structure)
**Note**: Overtime is usually calculated automatically from attendance records, but you can include it as a component if needed

**Example**:
```
Component Code: OVERTIME_ALLOWANCE
Component Name: Overtime Allowance
Component Type: Earning
Calculation Type: Fixed (or calculated from attendance)
Is Taxable: Yes
Is Pensionable: No
```

---

### 12. Commission
**What it is**: Commission based on sales/performance
**Type**: Earning
**Calculation**: Percentage or Formula
**Taxable**: Yes
**Pensionable**: No (usually)
**NHIF Applicable**: Yes

**Example (Percentage)**:
```
Component Code: COMMISSION
Component Name: Sales Commission
Component Type: Earning
Calculation Type: Percentage
Percentage: 5% of Sales (varies)
Is Taxable: Yes
Is Pensionable: No
```

---

### 13. Leave Encashment
**What it is**: Payment for unused leave days
**Type**: Earning
**Calculation**: Formula (based on leave days)
**Taxable**: Yes
**Pensionable**: No
**NHIF Applicable**: Yes

**Example**:
```
Component Code: LEAVE_ENCASHMENT
Component Name: Leave Encashment
Component Type: Earning
Calculation Type: Formula
Formula: {base} / 30 * {amount} (where amount = days)
Is Taxable: Yes
Is Pensionable: No
```

---

## ✅ DEDUCTIONS - What to Include

### 1. Trade Union Dues
**What it is**: Monthly union membership fees
**Type**: Deduction
**Calculation**: Fixed Amount or Percentage
**Taxable**: No (it's a deduction, not taxable income)

**Example (Fixed)**:
```
Component Code: TRADE_UNION
Component Name: Trade Union Dues
Component Type: Deduction
Calculation Type: Fixed
Amount: 5,000 TZS
Is Taxable: N/A (deduction)
```

**Example (Percentage)**:
```
Component Code: TRADE_UNION
Component Name: Trade Union Dues
Component Type: Deduction
Calculation Type: Percentage
Percentage: 1% of Gross Salary
Is Taxable: N/A
```

---

### 2. Salary Advance
**What it is**: Advance payment that needs to be recovered
**Type**: Deduction
**Calculation**: Fixed Amount (varies per employee)
**Taxable**: N/A

**Example**:
```
Component Code: SALARY_ADVANCE
Component Name: Salary Advance Recovery
Component Type: Deduction
Calculation Type: Fixed
Amount: 50,000 TZS (recovered over months)
Is Taxable: N/A
```

**Note**: Salary advances are usually managed separately (in the Salary Advances module), but can be included in structure for recovery.

---

### 3. External Loan Deduction
**What it is**: Loan repayment (e.g., bank loan, HESLB)
**Type**: Deduction
**Calculation**: Fixed Amount
**Taxable**: N/A

**Example**:
```
Component Code: EXTERNAL_LOAN
Component Name: Bank Loan Repayment
Component Type: Deduction
Calculation Type: Fixed
Amount: 100,000 TZS (monthly installment)
Is Taxable: N/A
```

**Note**: External loans are usually managed separately, but can be included in structure.

---

### 4. Court Order / Garnishment
**What it is**: Legal deduction (e.g., child support, court order)
**Type**: Deduction
**Calculation**: Fixed Amount
**Taxable**: N/A

**Example**:
```
Component Code: COURT_ORDER
Component Name: Court Order Deduction
Component Type: Deduction
Calculation Type: Fixed
Amount: 75,000 TZS
Is Taxable: N/A
```

---

### 5. Staff Welfare Fund
**What it is**: Contribution to staff welfare fund
**Type**: Deduction
**Calculation**: Fixed Amount or Percentage
**Taxable**: N/A

**Example**:
```
Component Code: WELFARE_FUND
Component Name: Staff Welfare Fund
Component Type: Deduction
Calculation Type: Percentage
Percentage: 0.5% of Gross Salary
Is Taxable: N/A
```

---

### 6. Insurance Premium (Non-Statutory)
**What it is**: Private insurance premium (if not NHIF)
**Type**: Deduction
**Calculation**: Fixed Amount
**Taxable**: N/A

**Example**:
```
Component Code: PRIVATE_INSURANCE
Component Name: Private Health Insurance
Component Type: Deduction
Calculation Type: Fixed
Amount: 20,000 TZS
Is Taxable: N/A
```

---

### 7. Savings Scheme
**What it is**: Employee savings contribution
**Type**: Deduction
**Calculation**: Fixed Amount or Percentage
**Taxable**: N/A

**Example**:
```
Component Code: SAVINGS_SCHEME
Component Name: Staff Savings Scheme
Component Type: Deduction
Calculation Type: Percentage
Percentage: 5% of Gross Salary
Is Taxable: N/A
```

---

### 8. Cooperative Society Contribution
**What it is**: Contribution to cooperative society
**Type**: Deduction
**Calculation**: Fixed Amount or Percentage
**Taxable**: N/A

**Example**:
```
Component Code: COOPERATIVE
Component Name: Cooperative Society Contribution
Component Type: Deduction
Calculation Type: Fixed
Amount: 10,000 TZS
Is Taxable: N/A
```

---

## ❌ What Should NOT Be in Salary Structure

### Statutory Deductions (Automatically Calculated)

These are **NOT** included in salary structure because they are:
- **Automatic**: Calculated by the system
- **Statutory**: Required by law
- **Company-wide**: Same rules for all employees

#### 1. PAYE (Pay As You Earn)
- **Why not**: Automatically calculated from taxable income using Statutory Rules
- **Where configured**: Statutory Rules → PAYE

#### 2. NHIF (National Health Insurance Fund)
- **Why not**: Automatically calculated using Statutory Rules
- **Where configured**: Statutory Rules → NHIF

#### 3. Pension (NSSF/PSSSF)
- **Why not**: Automatically calculated using Statutory Rules
- **Where configured**: Statutory Rules → Pension

#### 4. WCF (Workers Compensation Fund)
- **Why not**: Employer contribution, automatically calculated
- **Where configured**: Statutory Rules → WCF

#### 5. SDL (Skills Development Levy)
- **Why not**: Employer contribution, automatically calculated
- **Where configured**: Statutory Rules → SDL

#### 6. HESLB (Higher Education Students' Loans Board)
- **Why not**: Automatically calculated using Statutory Rules
- **Where configured**: Statutory Rules → HESLB

---

## 📋 Complete Example: Employee Salary Structure

### Employee: John Doe (Manager)

#### Earnings:
```
1. BASIC_SALARY
   - Type: Earning
   - Calculation: Fixed
   - Amount: 1,000,000 TZS
   - Taxable: Yes
   - Pensionable: Yes

2. HOUSE_ALLOWANCE
   - Type: Earning
   - Calculation: Fixed
   - Amount: 200,000 TZS
   - Taxable: Yes
   - Pensionable: No

3. TRANSPORT_ALLOWANCE
   - Type: Earning
   - Calculation: Fixed
   - Amount: 150,000 TZS
   - Taxable: Yes
   - Pensionable: No

4. RESPONSIBILITY_ALLOWANCE
   - Type: Earning
   - Calculation: Percentage
   - Percentage: 10% of Basic
   - Amount: 100,000 TZS (calculated)
   - Taxable: Yes
   - Pensionable: No

5. COMMUNICATION_ALLOWANCE
   - Type: Earning
   - Calculation: Fixed
   - Amount: 25,000 TZS
   - Taxable: Yes
   - Pensionable: No
```

**Total Earnings**: 1,475,000 TZS

#### Deductions:
```
1. TRADE_UNION
   - Type: Deduction
   - Calculation: Fixed
   - Amount: 5,000 TZS

2. SALARY_ADVANCE
   - Type: Deduction
   - Calculation: Fixed
   - Amount: 50,000 TZS
```

**Total Deductions (from structure)**: 55,000 TZS

#### Statutory Deductions (Automatic):
```
- PAYE: 218,000 TZS (calculated automatically)
- NHIF: 15,000 TZS (calculated automatically)
- Pension: 50,000 TZS (calculated automatically)
```

**Total Statutory Deductions**: 283,000 TZS

#### Final Calculation:
```
Gross Salary: 1,475,000 TZS
Less Structure Deductions: -55,000 TZS
Less Statutory Deductions: -283,000 TZS
Net Pay: 1,137,000 TZS
```

---

## 🎯 Best Practices

### 1. Earnings Best Practices
- ✅ **Always include Basic Salary** as the first earning component
- ✅ **Use Fixed amounts** for standard allowances (house, transport)
- ✅ **Use Percentage** for variable allowances (responsibility, acting)
- ✅ **Set Taxable flag correctly** (most earnings are taxable)
- ✅ **Set Pensionable flag correctly** (usually only basic salary is pensionable)
- ✅ **Use clear component codes** (e.g., `BASIC_SALARY`, `HOUSE_ALLOWANCE`)

### 2. Deductions Best Practices
- ✅ **Use Fixed amounts** for standard deductions (trade union)
- ✅ **Use Percentage** for proportional deductions (welfare fund)
- ✅ **Don't duplicate statutory deductions** (PAYE, NHIF, Pension)
- ✅ **Keep deductions simple** (avoid complex formulas for deductions)

### 3. Component Naming
- ✅ **Use UPPERCASE with underscores** for codes: `BASIC_SALARY`
- ✅ **Use clear, descriptive names**: "Basic Salary", "House Allowance"
- ✅ **Be consistent**: Use same naming pattern across all components

### 4. Calculation Types
- ✅ **Fixed**: For standard amounts that don't change
- ✅ **Percentage**: For amounts based on basic salary or gross
- ✅ **Formula**: For complex calculations (use sparingly)

### 5. Tax and Statutory Flags
- ✅ **Is Taxable**: Set to `Yes` for most earnings (affects PAYE calculation)
- ✅ **Is Pensionable**: Usually only `BASIC_SALARY` is pensionable
- ✅ **Is NHIF Applicable**: Usually `Yes` for all earnings

---

## 📊 Summary Table

| Component Type | Examples | Calculation | Taxable | Pensionable |
|---------------|----------|-------------|---------|--------------|
| **Earnings** | | | | |
| Basic Salary | Core salary | Fixed | Yes | Yes |
| House Allowance | Housing | Fixed/Percentage | Yes | No |
| Transport | Transportation | Fixed | Yes | No |
| Responsibility | Extra duties | Percentage | Yes | No |
| Acting | Acting position | Percentage | Yes | No |
| Medical | Medical expenses | Fixed | Varies | No |
| Meal | Meals | Fixed | Yes | No |
| Communication | Phone/internet | Fixed | Yes | No |
| Bonus | Performance | Fixed/Formula | Yes | No |
| Commission | Sales | Percentage | Yes | No |
| **Deductions** | | | | |
| Trade Union | Union dues | Fixed/Percentage | N/A | N/A |
| Salary Advance | Advance recovery | Fixed | N/A | N/A |
| External Loan | Loan repayment | Fixed | N/A | N/A |
| Court Order | Legal deduction | Fixed | N/A | N/A |
| Welfare Fund | Staff welfare | Percentage | N/A | N/A |
| **NOT in Structure** | | | | |
| PAYE | Income tax | Auto (Statutory Rules) | - | - |
| NHIF | Health insurance | Auto (Statutory Rules) | - | - |
| Pension | Pension contribution | Auto (Statutory Rules) | - | - |
| WCF | Workers compensation | Auto (Statutory Rules) | - | - |
| SDL | Skills development | Auto (Statutory Rules) | - | - |
| HESLB | Student loans | Auto (Statutory Rules) | - | - |

---

## 🔍 Quick Decision Guide

**Should I include this in Salary Structure?**

### ✅ YES - Include as Earning if:
- It's money the employee **receives**
- It's part of their **regular salary**
- It's an **allowance** or **bonus**
- It's **employee-specific** (varies per employee)

### ✅ YES - Include as Deduction if:
- It's money **deducted** from salary
- It's **employee-specific** (varies per employee)
- It's **not statutory** (not PAYE, NHIF, Pension, etc.)
- Examples: Trade Union, Loans, Advances

### ❌ NO - Don't Include if:
- It's **statutory** (PAYE, NHIF, Pension, WCF, SDL, HESLB)
- It's **automatically calculated** by the system
- It's **company-wide** (same for all employees)
- It's calculated from **attendance/overtime** (handled separately)

---

## 💡 Key Takeaways

1. **Earnings** = Money added to salary (Basic, Allowances, Bonuses)
2. **Deductions** = Money subtracted from salary (Union, Loans, Advances)
3. **Statutory Deductions** = NOT in structure (PAYE, NHIF, Pension, etc.)
4. **Always include Basic Salary** as the first earning component
5. **Set flags correctly** (Taxable, Pensionable, NHIF Applicable)
6. **Use clear naming** (UPPERCASE codes, descriptive names)
7. **Keep it simple** (prefer Fixed over Formula when possible)

---

This guide should help you understand what to include in salary structures. If you need help setting up specific components, refer to the examples above!

