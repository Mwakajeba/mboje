# Double-Entry Example: VAT INCLUSIVE + WHT INCLUSIVE

## Scenario
- **Invoice Total**: 100,000 TZS
- **VAT Mode**: INCLUSIVE (18%)
- **WHT Treatment**: INCLUSIVE (5%)

## Calculations

### Step 1: Extract Base Amount (VAT is included in total)
```
Base Amount = 100,000 ÷ (1 + 0.18) = 84,745.76 TZS
VAT Amount = 100,000 - 84,745.76 = 15,254.24 TZS
```

### Step 2: Calculate WHT (on base amount, inclusive treatment)
```
WHT = Base × (Rate ÷ (1 + Rate))
WHT = 84,745.76 × (0.05 ÷ 1.05)
WHT = 84,745.76 × 0.047619
WHT = 4,035.51 TZS
```

### Step 3: Calculate Net Receivable
```
Net Receivable = Total Amount - WHT
Net Receivable = 100,000 - 4,035.51 = 95,964.49 TZS
```

## Double-Entry Journal Entries

### DEBIT SIDE (What we receive/own)
| Account | Amount (TZS) | Description |
|---------|--------------|-------------|
| Bank Account / Cash | 95,964.49 | Actual cash received (Total - WHT) |
| WHT Receivable | 4,035.51 | WHT to be claimed from tax authority |
| **TOTAL DEBIT** | **100,000.00** | |

### CREDIT SIDE (What we owe/clear)
| Account | Amount (TZS) | Description |
|---------|--------------|-------------|
| Accounts Receivable | 100,000.00 | Clearing the invoice |
| **TOTAL CREDIT** | **100,000.00** | |

**Wait!** This doesn't show VAT and Revenue separately. Let's break down the Accounts Receivable credit:

### Detailed Credit Breakdown
| Account | Amount (TZS) | Description |
|---------|--------------|-------------|
| VAT Output | 15,254.24 | VAT portion (to be remitted to TRA) |
| Sales Revenue | 84,745.76 | Base amount (actual revenue) |
| **TOTAL CREDIT** | **100,000.00** | |

## Complete Journal Entry

### DEBIT SIDE
| Account | Amount (TZS) |
|---------|--------------|
| Bank Account / Cash | 95,964.49 |
| WHT Receivable | 4,035.51 |
| **TOTAL DEBIT** | **100,000.00** |

### CREDIT SIDE
| Account | Amount (TZS) |
|---------|--------------|
| VAT Output | 15,254.24 |
| Sales Revenue | 84,745.76 |
| **TOTAL CREDIT** | **100,000.00** |

## Explanation

1. **Bank/Cash (95,964.49)**: This is the actual cash received from the customer after deducting WHT.

2. **WHT Receivable (4,035.51)**: This is the withholding tax that the customer deducted from the payment. We will claim this from the tax authority later.

3. **VAT Output (15,254.24)**: This is the VAT portion included in the invoice total. We must remit this to TRA.

4. **Sales Revenue (84,745.76)**: This is the actual revenue (base amount) excluding VAT.

## Balance Check
✅ **Total Debit = Total Credit = 100,000.00 TZS**

## Key Points

- **VAT INCLUSIVE**: The total amount (100,000) includes VAT, so we extract the base (84,745.76) and VAT (15,254.24).
- **WHT INCLUSIVE**: The WHT (4,035.51) is calculated on the base amount using the inclusive formula.
- **Net Receivable**: The customer pays 95,964.49 (after WHT deduction), but we still recognize the full invoice amount (100,000) as receivable cleared.
- **WHT Receivable**: We create an asset (WHT Receivable) for the amount we'll claim from the tax authority.

