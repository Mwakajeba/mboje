# PAYE Brackets with Base Amount - Configuration Guide

## Overview
The PAYE (Pay As You Earn) tax calculation now supports **base amounts** for each tax bracket. This allows for accurate progressive tax calculations as per Tanzania Revenue Authority (TRA) requirements.

---

## How It Works

### Formula
For each tax bracket:
```
Tax = Base Amount + (Income in Excess of Threshold × Rate / 100)
```

Where:
- **Base Amount**: Cumulative tax from all previous brackets
- **Threshold**: Income level where this bracket starts
- **Rate**: Tax percentage for this bracket
- **Income in Excess**: Taxable income minus threshold

---

## Tanzania PAYE Brackets (Example)

Based on the provided requirements:

| Bracket | Monthly Income Range | Base Amount | Rate | Calculation |
|---------|---------------------|-------------|------|-------------|
| 1 | 0 - 270,000 | 0 | 0% | NIL (no tax) |
| 2 | 270,001 - 520,000 | 0 | 8% | 0 + (Income - 270,000) × 8% |
| 3 | 520,001 - 760,000 | 20,000 | 20% | 20,000 + (Income - 520,000) × 20% |
| 4 | 760,001 - 1,000,000 | 68,000 | 25% | 68,000 + (Income - 760,000) × 25% |
| 5 | Above 1,000,000 | 128,000 | 30% | 128,000 + (Income - 1,000,000) × 30% |

**Note**: Annual threshold of Tshs. 3,240,000 (270,000 × 12) is not taxable.

---

## How to Configure

### Step 1: Create/Edit PAYE Statutory Rule

1. Navigate to: `/hr/statutory-rules`
2. Click "Create Rule" or edit an existing PAYE rule
3. Select Rule Type: **PAYE**

### Step 2: Configure Tax Brackets

For each bracket, enter:

1. **Threshold**: The income level where this bracket starts
2. **Base Amount**: Cumulative tax from all previous brackets
3. **Rate %**: Tax percentage for this bracket

**Example Configuration:**

```
Bracket 1:
- Threshold: 270000
- Base Amount: 0
- Rate: 0

Bracket 2:
- Threshold: 520000
- Base Amount: 0
- Rate: 8

Bracket 3:
- Threshold: 760000
- Base Amount: 20000
- Rate: 20

Bracket 4:
- Threshold: 1000000
- Base Amount: 68000
- Rate: 25

Bracket 5:
- Threshold: 1000000 (or higher)
- Base Amount: 128000
- Rate: 30
```

**Important Notes:**
- Enter brackets in **ascending order** by threshold
- Base amount for first bracket is usually **0**
- Base amount for subsequent brackets = total tax from previous brackets
- Click "Add Bracket" to add more brackets

---

## Calculation Examples

### Example 1: Income of Tsh. 520,000
**Bracket**: 2 (270,001 - 520,000)
```
Tax = 0 + (520,000 - 270,000) × 8%
Tax = 0 + 250,000 × 0.08
Tax = 20,000 TZS
```

### Example 2: Income of Tsh. 760,000
**Bracket**: 3 (520,001 - 760,000)
```
Tax = 20,000 + (760,000 - 520,000) × 20%
Tax = 20,000 + 240,000 × 0.20
Tax = 20,000 + 48,000
Tax = 68,000 TZS
```

### Example 3: Income of Tsh. 1,000,000
**Bracket**: 4 (760,001 - 1,000,000)
```
Tax = 68,000 + (1,000,000 - 760,000) × 25%
Tax = 68,000 + 240,000 × 0.25
Tax = 68,000 + 60,000
Tax = 128,000 TZS
```

### Example 4: Income of Tsh. 1,240,000
**Bracket**: 5 (Above 1,000,000)
```
Tax = 128,000 + (1,240,000 - 1,000,000) × 30%
Tax = 128,000 + 240,000 × 0.30
Tax = 128,000 + 72,000
Tax = 200,000 TZS
```

---

## Understanding Base Amounts

### What is Base Amount?

The **base amount** is the **cumulative tax** from all previous tax brackets. It represents the total tax that would be paid if the income was exactly at the threshold of the current bracket.

### How to Calculate Base Amounts

1. **First Bracket**: Base amount is always **0**

2. **Subsequent Brackets**: 
   - Calculate the tax for the **previous bracket's threshold**
   - That becomes the base amount for the current bracket

**Example:**
- Bracket 2 threshold: 520,000
- Tax at 520,000 using Bracket 2: 0 + (520,000 - 270,000) × 8% = 20,000
- **Base amount for Bracket 3 = 20,000**

- Bracket 3 threshold: 760,000
- Tax at 760,000 using Bracket 3: 20,000 + (760,000 - 520,000) × 20% = 68,000
- **Base amount for Bracket 4 = 68,000**

- Bracket 4 threshold: 1,000,000
- Tax at 1,000,000 using Bracket 4: 68,000 + (1,000,000 - 760,000) × 25% = 128,000
- **Base amount for Bracket 5 = 128,000**

---

## System Behavior

### How the System Calculates PAYE

1. **Finds Applicable Bracket**:
   - System finds the **highest bracket** where taxable income **exceeds** the threshold
   - If income is below all thresholds, tax is **0**

2. **Calculates Tax**:
   - Uses the formula: `Base Amount + (Income - Threshold) × Rate / 100`

3. **Applies Tax Relief** (if configured):
   - Subtracts tax relief from calculated tax
   - Minimum tax is **0** (cannot be negative)

---

## Form Fields

When creating/editing a PAYE rule, you'll see:

### Tax Brackets Section

For each bracket:
- **Threshold**: Income level (e.g., 270000)
- **Base Amount**: Cumulative tax (e.g., 0, 20000, 68000)
- **Rate %**: Tax percentage (e.g., 8, 20, 25, 30)

### Additional Fields

- **Tax Relief Amount**: Optional monthly tax relief (subtracted from final tax)

---

## Best Practices

1. ✅ **Enter brackets in ascending order** by threshold
2. ✅ **Start with lowest threshold first** (e.g., 270,000)
3. ✅ **Base amount for first bracket is 0**
4. ✅ **Calculate base amounts correctly** (cumulative from previous brackets)
5. ✅ **Test with sample incomes** to verify calculations
6. ✅ **Set effective dates** to track when rules change
7. ✅ **Keep only one active rule** per company at a time

---

## Verification

After configuring brackets, verify with these test cases:

| Income | Expected Tax | Bracket |
|--------|-------------|---------|
| 200,000 | 0 | 1 (below threshold) |
| 520,000 | 20,000 | 2 |
| 760,000 | 68,000 | 3 |
| 1,000,000 | 128,000 | 4 |
| 1,240,000 | 200,000 | 5 |

---

## Troubleshooting

### Issue: Tax calculation seems incorrect

**Solution**: 
- Verify base amounts are cumulative from previous brackets
- Check that brackets are in ascending order by threshold
- Ensure thresholds don't overlap incorrectly

### Issue: Base amount not showing in form

**Solution**:
- Make sure you're using the updated version of the form
- Clear browser cache and refresh
- Check that the field name is `base_amount` in the form

### Issue: Tax is always 0

**Solution**:
- Verify taxable income exceeds the first bracket threshold
- Check that brackets are configured correctly
- Ensure the PAYE rule is active and effective for the date

---

## Summary

The new PAYE bracket system with base amounts allows for:
- ✅ Accurate progressive tax calculations
- ✅ Compliance with Tanzania tax laws
- ✅ Easy configuration through the web interface
- ✅ Automatic tax calculations during payroll processing

**Key Formula**: `Tax = Base Amount + (Income - Threshold) × Rate / 100`

This ensures that tax is calculated correctly across all income levels, with each bracket building on the previous one's cumulative tax.

