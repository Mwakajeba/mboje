# IFRS Interest Expense Calculation Formula

## For Full Monthly Periods

For full monthly periods, the formula is straightforward:

```
IFRS Interest Expense = Opening Amortised Cost × Monthly EIR
```

Where:
- **Monthly EIR** = (1 + Annual EIR)^(1/12) - 1
- **Annual EIR** = 15.92% (as decimal: 0.1592)

### Example (Full Month):
- Opening Balance: 4,800,000
- Annual EIR: 15.92%
- Monthly EIR = (1.1592)^(1/12) - 1 = 0.01237 = 1.237%
- **Interest = 4,800,000 × 0.01237 = 59,376**

---

## For Partial Periods (Non-Full Months)

For partial periods (like 18 Dec 2025 - 17 Jan 2026 = 30 days), the system uses **daily compounding**:

### Formula:

```
IFRS Interest Expense = Opening Amortised Cost × [(1 + Daily EIR)^Days - 1]
```

Where:
- **Daily EIR** = (1 + Annual EIR)^(1/365) - 1
- **Days** = Number of days in the period (30 days in your example)

### Step-by-Step Calculation:

**Step 1: Calculate Daily EIR**
```
Daily EIR = (1 + 0.1592)^(1/365) - 1
Daily EIR = (1.1592)^(0.00274) - 1
Daily EIR = 1.000404 - 1
Daily EIR = 0.000404 = 0.0404% per day
```

**Step 2: Calculate Interest for 30 Days**
```
Interest = 4,800,000 × [(1.000404)^30 - 1]
Interest = 4,800,000 × [1.01218 - 1]
Interest = 4,800,000 × 0.01218
Interest = 58,464
```

**However, your actual interest is 50,958.90**, which suggests the system might be using a different approach.

---

## Alternative Calculation (Actual Days Method)

If the system uses **simple daily interest** (not compounding):

```
IFRS Interest Expense = Opening Amortised Cost × Annual EIR × (Days / 365)
```

### Calculation:
```
Interest = 4,800,000 × 0.1592 × (30 / 365)
Interest = 4,800,000 × 0.1592 × 0.08219
Interest = 4,800,000 × 0.01308
Interest = 62,784
```

This is still higher than your actual 50,958.90.

---

## Most Likely Formula (Based on Your Data)

Given your actual interest of **50,958.90**, let's reverse-engineer:

```
50,958.90 = 4,800,000 × Rate × (30 / 365)
Rate = 50,958.90 / (4,800,000 × 30/365)
Rate = 50,958.90 / 394,520.55
Rate = 0.1291 = 12.91%
```

This suggests the system might be using:
1. **A different EIR** (not 15.92% annual)
2. **A prorated monthly EIR** instead of daily
3. **A different day count** (maybe 31 days instead of 30)

---

## Correct IFRS 9 Formula (Recommended)

For **partial periods**, IFRS 9 allows two approaches:

### Approach 1: Daily Compounding (Most Accurate)
```
Daily EIR = (1 + Annual EIR)^(1/365) - 1
Interest = Opening × [(1 + Daily EIR)^Days - 1]
```

### Approach 2: Prorated Monthly EIR (Simpler)
```
Monthly EIR = (1 + Annual EIR)^(1/12) - 1
Interest = Opening × Monthly EIR × (Days / Average Days in Month)
Interest = Opening × Monthly EIR × (Days / 30.44)
```

Where 30.44 = 365/12 (average days per month)

### Using Approach 2 with Your Data:
```
Monthly EIR = (1.1592)^(1/12) - 1 = 0.01237
Interest = 4,800,000 × 0.01237 × (30 / 30.44)
Interest = 4,800,000 × 0.01237 × 0.9855
Interest = 4,800,000 × 0.01219
Interest = 58,512
```

---

## To Verify Your System's Formula

Check the actual calculation in your system by:

1. **Check the Monthly EIR stored in the database** (not annual EIR)
2. **Check the day count** (might be 31 days, not 30)
3. **Check if there's any fee adjustment** or other factors

### Reverse Calculation from Your Data:
```
Actual Interest: 50,958.90
Opening: 4,800,000
Days: 30

Effective Rate = 50,958.90 / 4,800,000 = 1.0616% for 30 days
Annualized = (1.010616)^(365/30) - 1 = 13.5% (approximately)
```

This suggests your system might be using:
- **Monthly EIR of approximately 1.0616%** (not derived from 15.92% annual)
- OR a **different day count** (maybe 28 or 29 days)
- OR **fees/adjustments** are being applied

---

## Summary

**For Full Months:**
```
Interest = Opening × Monthly EIR
Monthly EIR = (1 + Annual EIR)^(1/12) - 1
```

**For Partial Periods (30 days):**
```
Option 1 (Daily Compounding):
Interest = Opening × [(1 + Daily EIR)^30 - 1]
Daily EIR = (1 + Annual EIR)^(1/365) - 1

Option 2 (Prorated Monthly):
Interest = Opening × Monthly EIR × (Days / 30.44)
Monthly EIR = (1 + Annual EIR)^(1/12) - 1
```

**Your System's Actual Formula:**
Based on your data (50,958.90 for 30 days), the system appears to be using a **prorated monthly EIR** or a **different EIR value** than 15.92% annual.

To get the exact formula, check:
1. The stored Monthly EIR value in the database
2. The exact day count used
3. Any adjustments or fees applied

