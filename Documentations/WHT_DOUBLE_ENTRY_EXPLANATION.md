# WHT Double-Entry Accounting Explanation

## Understanding WHT in Double-Entry Accounting

### Key Concept: WHT Payable is a Liability Account

**WHT Payable** works like **Accounts Payable** - it represents money you OWE to the tax authority.

---

## Scenario 1: EXCLUSIVE WHT (5% = 300.00)

### Situation:
- You owe supplier: **6,000.00**
- WHT Rate: **5%**
- WHT Amount: **300.00** (6,000 × 5%)
- You pay supplier: **5,700.00** (6,000 - 300)
- You owe tax authority: **300.00**

### Double-Entry Logic:

```
You owe supplier:        6,000.00  (expense)
├─ You pay supplier:     5,700.00  (cash out)
└─ You owe tax authority: 300.00  (liability)
```

### GL Entries:

| Account | Debit | Credit |
|---------|-------|--------|
| **Expense Account** | 6,000.00 | |
| **Bank Account** | | 5,700.00 |
| **WHT Payable** | | 300.00 |
| **Total** | **6,000.00** | **6,000.00** ✓ |

### Explanation:
- **Debit Expense 6,000**: Full amount you owe (expense recorded)
- **Credit Bank 5,700**: Actual cash you paid out
- **Credit WHT Payable 300**: Money you owe to tax authority (liability)

---

## Scenario 2: GROSS-UP WHT (2% = 122.45)

### Situation:
- Supplier should receive: **6,000.00**
- WHT Rate: **2%**
- WHT Amount: **122.45** (6,000 × (0.02 / 0.98))
- You pay supplier: **6,000.00** (full base amount)
- You pay extra for WHT: **122.45** (top-up)
- Your total expense: **6,122.45**

### Double-Entry Logic:

```
Supplier should receive:  6,000.00
├─ You pay supplier:      6,000.00  (cash out)
└─ You pay WHT top-up:     122.45  (extra cost)
Total expense:           6,122.45
You owe tax authority:     122.45  (liability)
```

### GL Entries:

| Account | Debit | Credit |
|---------|-------|--------|
| **Expense Account** | 6,122.45 | |
| **Bank Account** | | 6,000.00 |
| **WHT Payable** | | 122.45 |
| **Total** | **6,122.45** | **6,122.45** ✓ |

### Explanation:
- **Debit Expense 6,122.45**: Base amount + WHT top-up (total cost)
- **Credit Bank 6,000.00**: What you paid to supplier
- **Credit WHT Payable 122.45**: Money you owe to tax authority (liability)

---

## Understanding WHT Payable Account

### WHT Payable is a LIABILITY Account

Just like:
- **Accounts Payable** = Money you owe to suppliers
- **WHT Payable** = Money you owe to tax authority

### How It Works:

1. **When you withhold tax:**
   ```
   Credit WHT Payable = You OWE money to tax authority
   ```

2. **WHT Payable accumulates:**
   - Payment 1: Credit 300.00
   - Payment 2: Credit 122.45
   - **Total Owed: 422.45**

3. **When you remit WHT to tax authority:**
   ```
   Debit WHT Payable:  422.45  (reduces liability)
   Credit Bank:        422.45  (cash out)
   ```

4. **After remittance:**
   - WHT Payable balance: 0.00

---

## Key Differences: EXCLUSIVE vs GROSS-UP

| Aspect | EXCLUSIVE | GROSS-UP |
|--------|-----------|----------|
| **Supplier Receives** | Base - WHT | Base (full amount) |
| **You Pay (Cash)** | Base - WHT | Base |
| **Your Expense** | Base | Base + WHT |
| **WHT Liability** | WHT Amount | WHT Amount |
| **Who Bears WHT Cost** | Supplier | You (payer) |

---

## Real-World Example

### EXCLUSIVE (5%):
- Invoice: 6,000.00
- You pay: 5,700.00
- Supplier gets: 5,700.00 (less than invoice)
- You record expense: 6,000.00
- WHT to remit: 300.00

### GROSS-UP (2%):
- Invoice: 6,000.00
- You pay: 6,000.00
- Supplier gets: 6,000.00 (full invoice)
- You record expense: 6,122.45 (includes WHT top-up)
- WHT to remit: 122.45

---

## Summary

**WHT amounts (300.00 or 122.45) are:**
1. **Credited to WHT Payable** = Liability (you owe this to tax authority)
2. **Part of the expense calculation** = Affects total expense recorded
3. **Not paid immediately** = Accumulates until remitted to tax authority
4. **Always balanced** = Debit (expense) = Credit (bank + WHT payable)

The double-entry always balances because:
- **EXCLUSIVE**: Expense (6,000) = Bank (5,700) + WHT Payable (300)
- **GROSS-UP**: Expense (6,122.45) = Bank (6,000) + WHT Payable (122.45)

