# How EIR (Effective Interest Rate) is Calculated Under IFRS 9

## Overview

The Effective Interest Rate (EIR) is the rate that exactly discounts estimated future cash payments or receipts through the expected life of the financial instrument to the **initial amortised cost** of the financial liability.

## IFRS 9 Requirement

**IFRS 9.5.1.1**: The effective interest rate is the rate that exactly discounts estimated future cash payments or receipts through the expected life of the financial instrument to the gross carrying amount of a financial asset or the amortised cost of a financial liability.

## Calculation Method: IRR (Internal Rate of Return)

The EIR is calculated using **IRR (Internal Rate of Return)** on actual cash flows, NOT configured manually.

### Formula

For equal monthly payments (annuity):

```
PV = PMT × [1 - (1 + r)^(-n)] / r
```

Where:
- **PV** = Initial Amortised Cost (Present Value)
- **PMT** = Monthly Payment Amount
- **r** = Monthly EIR (what we're solving for)
- **n** = Number of periods (months)

Rearranged to solve for r:
```
f(r) = PV - PMT × [1 - (1 + r)^(-n)] / r = 0
```

### Solving Method: Newton-Raphson

We use the **Newton-Raphson iterative method** to solve for the monthly EIR:

1. Start with initial guess: `r₀ = Nominal Rate / 12`
2. Calculate NPV: `NPV = PV - PMT × [1 - (1 + r)^(-n)] / r`
3. Calculate derivative: `NPV' = d/dr [NPV]`
4. Update rate: `r₁ = r₀ - NPV / NPV'`
5. Repeat until convergence (NPV ≈ 0)

## Components Considered

### 1. Initial Amortised Cost (PV)

```
Initial Amortised Cost = Principal - Capitalized Fees - Direct Costs
```

**Example:**
- Principal: TZS 5,000,000
- Capitalized Fees: TZS 200,000
- Direct Costs: TZS 0
- **Initial AC: TZS 4,800,000**

### 2. Payment Amounts (PMT)

Payment amounts come from the **contractual cash schedule**:
- Fixed monthly payment: TZS 166,071.55
- Number of periods: 36 months

### 3. Cash Flows

**Initial Cash Flow (t₀):**
- Positive: +4,800,000 (cash received)

**Future Cash Flows (t₁ to t₃₆):**
- Negative: -166,071.55 each month (cash paid)

## Step-by-Step Calculation Example

### Given:
- Initial Amortised Cost: TZS 4,800,000
- Monthly Payment: TZS 166,071.55
- Number of Periods: 36 months
- Nominal Rate: 12% (used as initial guess)

### Step 1: Set Up the Equation

```
4,800,000 = 166,071.55 × [1 - (1 + r)^(-36)] / r
```

### Step 2: Solve Using Newton-Raphson

**Iteration 1:**
- Initial guess: r₀ = 12% / 12 = 0.01 (1%)
- Calculate NPV: 4,800,000 - 166,071.55 × [1 - (1.01)^(-36)] / 0.01
- NPV ≈ -500,000 (too high, need higher rate)

**Iteration 2:**
- Update: r₁ = 0.011 (1.1%)
- Calculate NPV: ≈ -50,000 (still too high)

**Iteration 3:**
- Update: r₂ = 0.0111 (1.11%)
- Calculate NPV: ≈ 0 (converged!)

### Step 3: Convert to Annual EIR

```
Annual EIR = (1 + Monthly EIR)^12 - 1
Annual EIR = (1 + 0.0111)^12 - 1
Annual EIR = 1.142 - 1 = 0.142 = 14.2%
```

## Why EIR > Nominal Rate?

When fees/costs are capitalized:

1. **Initial AC < Principal**
   - Principal: 5,000,000
   - Initial AC: 4,800,000 (after fees)

2. **Same Payments Amortize Smaller Amount**
   - Payments: 166,071.55/month
   - Amortizing: 4,800,000 (not 5,000,000)

3. **Effective Rate Must Be Higher**
   - To make the equation balance
   - EIR ≈ 14.2% vs Nominal 12%

## Using EIR for IFRS Interest Expense

Once EIR is calculated, it's used to calculate monthly interest expense:

```
IFRS Interest Expense = Opening Amortised Cost × Monthly EIR
```

**Example (Month 1):**
- Opening AC: 4,800,000
- Monthly EIR: 1.11% (0.0111)
- **IFRS Interest: 4,800,000 × 0.0111 = 53,280**

## Implementation Details

### Code Location
- **Service**: `App\Services\Loan\LoanEirCalculatorService`
- **Method**: `calculateEir()`
- **Solver**: `solveMonthlyEirEqualPayments()`

### Key Features
1. ✅ Uses IRR (not configured)
2. ✅ Considers capitalized fees and direct costs
3. ✅ Solves using Newton-Raphson method
4. ✅ Handles equal and unequal payments
5. ✅ Validates convergence
6. ✅ Converts monthly to annual EIR

### Validation
- EIR must converge (NPV ≈ 0)
- EIR should be reasonable (not > 5× nominal rate)
- Initial AC must be positive
- Final amortised cost must reach zero

## Summary

**EIR Calculation Process:**

1. Calculate **Initial Amortised Cost** = Principal - Fees - Costs
2. Extract **Payment Amounts** from cash schedule
3. Set up **IRR equation**: PV = Σ(PMT / (1+r)^t)
4. Solve for **Monthly EIR** using Newton-Raphson
5. Convert to **Annual EIR**: (1 + monthly)^12 - 1
6. Use **Monthly EIR** for IFRS interest expense calculation

**Result:**
- Monthly EIR: 1.11%
- Annual EIR: 14.2%
- IFRS Interest = Opening AC × 1.11%

This ensures IFRS 9 compliance and accurate interest expense recognition.

