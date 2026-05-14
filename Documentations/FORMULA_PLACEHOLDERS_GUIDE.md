# Salary Component Formula Placeholders Guide

## Overview
When creating a formula-based salary component, you can use placeholders that will be replaced with actual values during payroll calculation.

## Available Placeholders

### 1. `{base}`
**Description**: The base salary amount used for calculations.

**Source Priority**:
1. Active Contract salary (if exists)
2. Employee basic_salary (fallback)

**Example Values**: `1000000` (for 1,000,000 TZS)

**Usage in Formula**:
```
{base} * 0.15          // 15% of base salary
{base} + 50000         // Base salary + 50,000
```

---

### 2. `{amount}`
**Description**: Fixed amount value from the employee's salary structure.

**Source**: `employee_salary_structure.amount` field

**Example Values**: `50000` (for 50,000 TZS)

**Usage in Formula**:
```
{amount}               // Direct amount value
{base} + {amount}      // Base + fixed amount
{amount} * 1.5         // 1.5 times the amount
```

---

### 3. `{percentage}`
**Description**: Percentage value from the employee's salary structure.

**Important**: This is the **raw numeric value**, NOT a decimal or percentage sign.

**Source**: `employee_salary_structure.percentage` field

**Example Values**: 
- `10` (for 10%)
- `15.5` (for 15.5%)
- `25` (for 25%)

**⚠️ Important**: The `{percentage}` placeholder contains the **raw number**, so:
- If percentage = 10, it means 10%
- To use it in calculations, you must divide by 100: `{percentage} / 100`

**Usage in Formula**:
```
{base} * ({percentage} / 100)           // Correct: Calculates percentage of base
                                        // If percentage = 10, calculates 10% of base

{base} * {percentage}                    // ❌ WRONG: This would calculate 10x the base!
                                        // (because 10 is used directly, not 0.10)

{base} * ({percentage} / 100) + {amount} // Percentage of base + fixed amount
```

---

## Formula Examples

### Example 1: Simple Percentage
**Goal**: Calculate 10% of base salary

**Employee Structure**: `percentage = 10`

**Formula**:
```
{base} * ({percentage} / 100)
```

**Calculation**:
- Base = 1,000,000
- Percentage = 10
- Formula becomes: `1000000 * (10 / 100)`
- Result: `100000` (10% of 1,000,000)

---

### Example 2: Fixed Amount Plus Percentage
**Goal**: Base salary × 15% + fixed bonus

**Employee Structure**: `amount = 50000`

**Formula**:
```
{base} * 0.15 + {amount}
```

**Calculation**:
- Base = 1,000,000
- Amount = 50,000
- Formula becomes: `1000000 * 0.15 + 50000`
- Result: `200000` (150,000 + 50,000)

---

### Example 3: Variable Percentage from Employee Structure
**Goal**: Use percentage from employee structure (varies per employee)

**Employee Structure**: `percentage = 12.5`

**Formula**:
```
{base} * ({percentage} / 100)
```

**Calculation**:
- Base = 1,000,000
- Percentage = 12.5
- Formula becomes: `1000000 * (12.5 / 100)`
- Result: `125000` (12.5% of 1,000,000)

---

### Example 4: Complex Formula
**Goal**: (Base × 10%) + (Amount × 50%) + 25,000

**Employee Structure**: `amount = 100000`, `percentage = 10`

**Formula**:
```
{base} * 0.10 + {amount} * 0.50 + 25000
```

**OR using percentage placeholder**:
```
{base} * ({percentage} / 100) + {amount} * 0.50 + 25000
```

**Calculation**:
- Base = 1,000,000
- Amount = 100,000
- Percentage = 10
- Formula becomes: `1000000 * (10 / 100) + 100000 * 0.50 + 25000`
- Result: `175000` (100,000 + 50,000 + 25,000)

---

## Common Mistakes

### ❌ Wrong: Using {percentage} directly
```
{base} * {percentage}  // If percentage = 10, this calculates 10x the base!
```

### ✅ Correct: Divide by 100
```
{base} * ({percentage} / 100)  // If percentage = 10, this calculates 10% of base
```

---

### ❌ Wrong: Using percentage with % sign
```
{base} * {10%}  // This won't work - {percentage} is just the number
```

### ✅ Correct: Use the numeric value
```
{base} * ({percentage} / 100)  // percentage = 10 means 10%
```

---

## Summary

| Placeholder | Value Type | Example | Usage |
|------------|------------|---------|-------|
| `{base}` | Decimal | `1000000` | Use directly: `{base} * 0.15` |
| `{amount}` | Decimal | `50000` | Use directly: `{base} + {amount}` |
| `{percentage}` | **Number** (not decimal) | `10` (means 10%) | **Must divide by 100**: `{base} * ({percentage} / 100)` |

---

## Quick Reference

**To calculate X% of base**:
- Fixed percentage: `{base} * 0.X` (e.g., `{base} * 0.15` for 15%)
- Variable percentage: `{base} * ({percentage} / 100)` (if percentage = 15, calculates 15%)

**To add fixed amount**:
- `{base} + {amount}`

**To combine both**:
- `{base} * ({percentage} / 100) + {amount}`

