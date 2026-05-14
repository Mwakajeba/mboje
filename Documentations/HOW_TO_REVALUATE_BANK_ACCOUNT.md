# How to Revaluate a Foreign Currency Bank Account

This guide explains how to set up and revaluate a bank account that was created in a foreign currency.

## Prerequisites

For a bank account to be included in FX revaluation, it must meet these requirements:

1. ✅ **Foreign Currency**: The bank account must have a currency different from your functional currency (e.g., USD if functional currency is TZS)
2. ✅ **Revaluation Required Flag**: The `revaluation_required` checkbox must be enabled
3. ✅ **Has Balance**: The bank account must have transactions that create a balance (FCY amount)

## Step-by-Step Process

### Step 1: Verify Bank Account Configuration

1. Navigate to **Bank Accounts** (Accounting → Bank Accounts)
2. Find your foreign currency bank account
3. Click **Edit** to verify/update settings

**Required Settings:**
- **Currency**: Must be set to a foreign currency (e.g., USD, EUR, GBP) - NOT your functional currency
- **Revaluation Required**: ✅ Must be **checked/enabled**

If these settings are not correct:
- Set the currency to your foreign currency
- Enable the "Revaluation Required" checkbox
- Click **Save**

### Step 2: Ensure Bank Account Has Transactions

The bank account must have transactions (deposits, withdrawals, payments, receipts) that create a balance. The system calculates the FCY (Foreign Currency) balance from these transactions.

**To verify balance:**
- View the bank account details
- Check the current balance
- Ensure there are transactions recorded in the foreign currency

### Step 3: Generate FX Revaluation Preview

1. Navigate to **FX Revaluation**:
   - Go to: **Accounting → FX Revaluation → Create New Revaluation**

2. Fill in the revaluation form:
   - **Revaluation Date**: Select the date for revaluation (typically month-end date)
   - **Branch** (Optional): Select a branch if you want to filter by branch
   - Click **Generate Preview**

3. **Review the Preview**:
   - The system will automatically identify all monetary items including:
     - Accounts Receivable (AR) in foreign currency
     - Accounts Payable (AP) in foreign currency
     - **Bank Accounts** in foreign currency (with `revaluation_required = true`)
     - Loans in foreign currency
   
   - Your bank account should appear in the preview if:
     - It has a foreign currency set
     - `revaluation_required = true` is enabled
     - It has a balance (FCY amount > 0)

4. **Check Bank Account in Preview**:
   - Look for items with **Item Type: BANK**
   - The reference will show: `[Account Number] - [Bank Name]`
   - You'll see:
     - FCY Amount (Foreign Currency balance)
     - Original Rate (weighted average rate from transactions)
     - Closing Rate (month-end rate from FX Rates Management)
     - Gain/Loss calculation

### Step 4: Post the Revaluation

1. After reviewing the preview:
   - Verify all items are correct
   - Check the summary totals
   - Click **Post Revaluation**

2. **System Actions**:
   - Creates journal entries for unrealized FX gain/loss
   - Records revaluation history
   - Updates bank account balance in local currency

3. **Important Notes**:
   - ⚠️ **Period Restriction**: You cannot post a revaluation for a period that already has a posted revaluation
   - If a revaluation exists for the same month/year, you must reverse it first before posting a new one
   - The system prevents duplicate revaluations for the same period

## How the System Calculates Bank Account Revaluation

### FCY Balance Calculation
The system calculates the Foreign Currency balance by:
- Summing FCY amounts from all payments and receipts
- Using the `amount_fcy` field from transactions
- Calculating net balance (debits - credits)

### Original Rate Calculation
The system uses a **weighted average rate** from all transactions:
- Calculates average from all payments and receipts
- Uses the rate at which each transaction was recorded
- Falls back to creation date rate if no transactions exist

### Gain/Loss Calculation
For Bank Accounts (Asset accounts):
- **Formula**: `(Closing Rate - Original Rate) × FCY Amount`
- **Gain**: When closing rate > original rate
- **Loss**: When closing rate < original rate

### Example
- Bank Account: USD Account
- FCY Balance: 10,000 USD
- Original Rate (average): 2,500 TZS/USD
- Closing Rate (month-end): 2,480 TZS/USD
- **Calculation**: (2,480 - 2,500) × 10,000 = -200,000 TZS (Loss)

## Troubleshooting

### Bank Account Not Appearing in Preview

**Check:**
1. ✅ Currency is set and different from functional currency
2. ✅ `revaluation_required` is enabled
3. ✅ Bank account has a balance (transactions exist)
4. ✅ Revaluation date is on or after transaction dates

**Solution:**
- Edit the bank account and enable "Revaluation Required"
- Ensure there are transactions recorded in the foreign currency
- Verify the revaluation date includes the period with transactions

### "Revaluation Already Posted" Error

**Cause:**
- A revaluation has already been posted for the selected period (month/year)

**Solution:**
1. Go to **FX Revaluation History**
2. Find the existing revaluation for that period
3. Click **Reverse** to reverse it
4. Then post a new revaluation

### Zero Balance Issue

**Cause:**
- Bank account has no transactions or all transactions are cleared

**Solution:**
- Ensure there are active transactions (deposits, withdrawals) in the foreign currency
- The system only revalues accounts with a balance

## Viewing Revaluation History

1. Navigate to **FX Revaluation → History**
2. Filter by:
   - Item Type: **BANK**
   - Date range
   - Branch (if applicable)
3. View details of each revaluation:
   - Original and closing rates
   - FCY and LCY amounts
   - Gain/Loss amount
   - Journal entries created

## Automatic Month-End Revaluation

The system also supports automatic month-end revaluation:
- Runs via scheduled command: `fx:process-month-end-revaluation`
- Automatically reverses previous month's revaluation on the 1st
- Creates new revaluation for the previous month-end
- Includes all eligible bank accounts automatically

## Summary

✅ **To revaluate a foreign currency bank account:**

1. **Configure**: Set currency to foreign currency, enable "Revaluation Required"
2. **Transactions**: Ensure bank account has transactions creating a balance
3. **Generate Preview**: Go to FX Revaluation → Create → Generate Preview
4. **Review**: Check that bank account appears in preview
5. **Post**: Click "Post Revaluation" to create journal entries

The system automatically:
- Identifies eligible bank accounts
- Calculates FCY balance from transactions
- Uses weighted average rate for original rate
- Applies month-end closing rate
- Calculates unrealized gain/loss
- Creates appropriate journal entries

