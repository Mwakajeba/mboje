# PAYE Brackets - Correct Configuration Guide

## ⚠️ Important: Threshold Explanation

The **threshold** in each bracket represents the **starting point** of that bracket, NOT the end point.

---

## Correct Tanzania PAYE Brackets Configuration

### Understanding from Official Tax Table

Based on the official Tanzania tax table with ranges (Over/Not over):

| Bracket | Income Range (Over → Not Over) | Threshold (Over) | Base Amount (Tax on Column 1) | Rate (Tax on Excess) |
|---------|-------------------------------|------------------|-------------------------------|----------------------|
| 1 | 0 → 270,000 | **0** | 0 | 0% |
| 2 | 270,000 → 520,000 | **270,000** | 0 | 8% |
| 3 | 520,000 → 760,000 | **520,000** | 20,000 | 20% |
| 4 | 760,000 → 1,000,000 | **760,000** | 68,000 | 25% |
| 5 | 1,000,000 → And above | **1,000,000** | 128,000 | 30% |

**Key Understanding**: 
- **Threshold** = The "Over" value (minimum income for this bracket)
- **Base Amount** = "Tax on Column 1" (cumulative tax from previous brackets)
- **Rate** = "Tax on Excess" (percentage on income above threshold)

---

## Correct Configuration

```
Bracket 1 (0 - 270,000):
- Threshold: 0
- Base Amount: 0
- Rate: 0

Bracket 2 (270,000 - 520,000):
- Threshold: 270000
- Base Amount: 0
- Rate: 8

Bracket 3 (520,000 - 760,000):
- Threshold: 520000
- Base Amount: 20000
- Rate: 20

Bracket 4 (760,000 - 1,000,000):
- Threshold: 760000
- Base Amount: 68000
- Rate: 25

Bracket 5 (Above 1,000,000):
- Threshold: 1000000
- Base Amount: 128000
- Rate: 30
```

**Note**: Each bracket has a **unique threshold** that matches the "Over" value from the tax table!

---

## How the System Determines Which Bracket to Use

The system finds the **highest bracket** where income **exceeds** the threshold:

1. **Income: 900,000 TZS**
   - Checks Bracket 5: 900,000 > 1,000,000? ❌ No
   - Checks Bracket 4: 900,000 > 760,000? ✅ Yes
   - **Uses Bracket 4**

2. **Income: 1,200,000 TZS**
   - Checks Bracket 5: 1,200,000 > 1,000,000? ✅ Yes
   - **Uses Bracket 5**

---

## How Each Bracket Works (Based on Official Table)

### Bracket 3 (520,000 → 760,000)
- **Threshold 520,000** means: "Use this bracket for income above 520,000"
- **Base Amount 20,000** = Tax already calculated for income up to 520,000
- The system calculates: `20,000 + (Income - 520,000) × 20%`

**Example: Income 600,000**
```
Tax = 20,000 + (600,000 - 520,000) × 20%
Tax = 20,000 + 80,000 × 0.20
Tax = 20,000 + 16,000
Tax = 36,000 TZS
```

**Example: Income 760,000 (maximum for Bracket 3)**
```
Tax = 20,000 + (760,000 - 520,000) × 20%
Tax = 20,000 + 240,000 × 0.20
Tax = 20,000 + 48,000
Tax = 68,000 TZS ✓ (This matches Bracket 4's base amount!)
```

### Bracket 4 (760,000 → 1,000,000)
- **Threshold 760,000** means: "Use this bracket for income above 760,000"
- **Base Amount 68,000** = Tax already calculated for income up to 760,000
- The system calculates: `68,000 + (Income - 760,000) × 25%`

**Example: Income 900,000**
```
Tax = 68,000 + (900,000 - 760,000) × 25%
Tax = 68,000 + 140,000 × 0.25
Tax = 68,000 + 35,000
Tax = 103,000 TZS
```

**Example: Income 1,000,000 (maximum for Bracket 4)**
```
Tax = 68,000 + (1,000,000 - 760,000) × 25%
Tax = 68,000 + 240,000 × 0.25
Tax = 68,000 + 60,000
Tax = 128,000 TZS ✓ (This matches Bracket 5's base amount!)
```

---

## Why Bracket 5 Uses Threshold 1,000,000

Bracket 5 covers income **above 1,000,000**.

- **Threshold 1,000,000** means: "Use this bracket for income above 1,000,000"
- The system calculates: `128,000 + (Income - 1,000,000) × 30%`

**Example: Income 1,200,000**
```
Tax = 128,000 + (1,200,000 - 1,000,000) × 30%
Tax = 128,000 + 200,000 × 0.30
Tax = 128,000 + 60,000
Tax = 188,000 TZS
```

---

## Common Mistake ❌

**WRONG Configuration:**
```
Bracket 3:
- Threshold: 760000  ← WRONG! This is the END of bracket 3, not the start
- Base Amount: 20000
- Rate: 20

Bracket 4:
- Threshold: 760000  ← WRONG! This is the START of bracket 4
- Base Amount: 68000
- Rate: 25
```

**Problem**: If Bracket 3 uses threshold 760,000, it would never be used because the system finds the highest bracket where income exceeds the threshold. Income of 600,000 would skip Bracket 3!

---

## Correct Configuration ✅

**CORRECT Configuration (Matching Official Tax Table):**
```
Bracket 1 (0 → 270,000):
- Threshold: 0
- Base Amount: 0
- Rate: 0

Bracket 2 (270,000 → 520,000):
- Threshold: 270000
- Base Amount: 0
- Rate: 8

Bracket 3 (520,000 → 760,000):
- Threshold: 520000  ← Correct! This is the START of bracket 3
- Base Amount: 20000
- Rate: 20

Bracket 4 (760,000 → 1,000,000):
- Threshold: 760000  ← Correct! This is the START of bracket 4
- Base Amount: 68000
- Rate: 25

Bracket 5 (Above 1,000,000):
- Threshold: 1000000  ← Correct! This is the START of bracket 5
- Base Amount: 128000
- Rate: 30
```

**Result**: 
- Income 0 - 270,000 → Uses Bracket 1 (0% tax) ✅
- Income 270,001 - 520,000 → Uses Bracket 2 (8% on excess) ✅
- Income 520,001 - 760,000 → Uses Bracket 3 (20,000 + 20% on excess) ✅
- Income 760,001 - 1,000,000 → Uses Bracket 4 (68,000 + 25% on excess) ✅
- Income above 1,000,000 → Uses Bracket 5 (128,000 + 30% on excess) ✅

---

## Verification Examples

### Income: 800,000 TZS
**Should use Bracket 4:**
```
Tax = 68,000 + (800,000 - 760,000) × 25%
Tax = 68,000 + 40,000 × 0.25
Tax = 68,000 + 10,000
Tax = 78,000 TZS ✓
```

### Income: 1,000,000 TZS
**Should use Bracket 4 (maximum for this bracket):**
```
Tax = 68,000 + (1,000,000 - 760,000) × 25%
Tax = 68,000 + 240,000 × 0.25
Tax = 68,000 + 60,000
Tax = 128,000 TZS ✓
```

### Income: 1,000,001 TZS
**Should use Bracket 5:**
```
Tax = 128,000 + (1,000,001 - 1,000,000) × 30%
Tax = 128,000 + 1 × 0.30
Tax = 128,000.30 TZS ✓
```

---

## Summary

**Key Rule**: Each bracket's threshold matches the **"Over"** value from the official tax table.

| From Tax Table | System Configuration |
|----------------|---------------------|
| Over 0, Not over 270,000 | Threshold: **0** |
| Over 270,000, Not over 520,000 | Threshold: **270,000** |
| Over 520,000, Not over 760,000 | Threshold: **520,000** |
| Over 760,000, Not over 1,000,000 | Threshold: **760,000** |
| Over 1,000,000, And above | Threshold: **1,000,000** |

**Important**: 
- Each bracket has a **unique threshold** (the "Over" value)
- Base Amount = "Tax on Column 1" (cumulative tax from previous brackets)
- Rate = "Tax on Excess" (percentage on income above threshold)
- Never use the same threshold for different brackets!

