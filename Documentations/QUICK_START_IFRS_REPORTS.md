# Quick Start: IFRS Cash Flow & Equity Reports

## 🚀 Get Started in 5 Minutes

### Step 1: Run Database Migrations
```bash
cd /home/anselim/smartaccounting
php artisan migrate
php artisan db:seed --class=CashFlowLineItemSeeder
```

### Step 2: Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Step 3: Access Reports
1. **Cash Flow Statement**: Navigate to `/accounting/reports/cash-flow`
2. **Equity Statement**: Navigate to `/accounting/reports/changes-equity`

---

## 📊 What's Been Implemented

### ✅ Complete (Ready to Use)
1. **4 Service Classes** - All business logic for IFRS calculations
2. **2 Updated Controllers** - Cash Flow & Equity with new methods
3. **Database Structure** - Line items table and seeders
4. **Export Functions** - PDF and Excel exports in IFRS format

### 🔄 Next Steps (Optional Enhancements)
1. **View Templates** - Update UI to show IFRS format (views currently use legacy format)
2. **Testing** - Validate with your actual data
3. **Auditor Review** - Get external sign-off

---

## 🎯 How to Use

### Cash Flow Statement

#### Method 1: Direct Method (Shows actual cash flows)
```php
// In your controller or service
$cashFlowService = app(\App\Services\FinancialReports\CashFlowService::class);

$statement = $cashFlowService->getCashFlowStatement(
    method: 'direct',
    startDate: '2025-01-01',
    endDate: '2025-12-31',
    branchId: null, // or specific branch ID
    comparativePeriods: [
        ['start_date' => '2024-01-01', 'end_date' => '2024-12-31', 'name' => 'Prior Year']
    ]
);
```

#### Method 2: Indirect Method (Reconciles profit to cash)
```php
$statement = $cashFlowService->getCashFlowStatement(
    method: 'indirect',
    startDate: '2025-01-01',
    endDate: '2025-12-31',
    branchId: null,
    comparativePeriods: []
);
```

### Statement of Changes in Equity

```php
$equityService = app(\App\Services\FinancialReports\EquityStatementService::class);

$statement = $equityService->getEquityStatement(
    startDate: '2025-01-01',
    endDate: '2025-12-31',
    branchId: null,
    comparativePeriods: [
        ['start_date' => '2024-01-01', 'end_date' => '2024-12-31', 'name' => 'Prior Year']
    ]
);
```

---

## 📋 Data Structure Returned

### Cash Flow Statement Response:
```php
[
    'method' => 'direct', // or 'indirect'
    'period' => [
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
    ],
    'cash_flows' => [
        'operating' => [
            'line_items' => [...],
            'net' => 150000.00
        ],
        'investing' => [
            'line_items' => [...],
            'net' => -50000.00
        ],
        'financing' => [
            'line_items' => [...],
            'net' => 20000.00
        ]
    ],
    'opening_cash' => 100000.00,
    'closing_cash' => 220000.00,
    'net_cash_flow' => 120000.00,
    'notes' => [...]
]
```

### Equity Statement Response:
```php
[
    'period' => [
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
    ],
    'equity_components' => [
        ['key' => 'share_capital', 'name' => 'Share Capital', ...],
        ['key' => 'share_premium', 'name' => 'Share Premium', ...],
        ['key' => 'revaluation_reserve', 'name' => 'Revaluation Reserve', ...],
        ['key' => 'retained_earnings', 'name' => 'Retained Earnings', ...],
        ['key' => 'other_reserves', 'name' => 'Other Reserves', ...]
    ],
    'opening_balances' => [
        'share_capital' => 500000.00,
        'share_premium' => 100000.00,
        'revaluation_reserve' => 50000.00,
        'retained_earnings' => 200000.00,
        'other_reserves' => 10000.00
    ],
    'movements' => [
        'share_capital' => [
            'line_items' => [...],
            'total' => 100000.00
        ],
        // ... other components
    ],
    'closing_balances' => [
        'share_capital' => 600000.00,
        // ... other components
    ],
    'total_opening' => 860000.00,
    'total_closing' => 1050000.00,
    'notes' => [...]
]
```

---

## 🔧 Customization Guide

### Add New Cash Flow Line Item
```php
// In database or via migration
DB::table('cash_flow_line_items')->insert([
    'cash_flow_category_id' => 1, // Operating Activities
    'name' => 'Cash paid for research and development',
    'description' => 'R&D expenditure paid in cash',
    'sort_order' => 35,
    'transaction_types' => json_encode(['rd_payment']),
    'is_active' => true,
]);
```

### Map Chart Account to Cash Flow Line Item
```php
// Link a chart account to a specific line item
$chartAccount = ChartAccount::where('account_code', '5500')->first();
$lineItem = DB::table('cash_flow_line_items')
    ->where('name', 'Cash paid for research and development')
    ->first();

$chartAccount->update([
    'has_cash_flow' => true,
    'cash_flow_category_id' => 1, // Operating Activities
    'cash_flow_line_item_id' => $lineItem->id
]);
```

### Add New Equity Component
```php
// In EquityStatementService, update getEquityComponents():
protected function getEquityComponents(): array
{
    return [
        // ... existing components
        [
            'key' => 'treasury_shares',
            'name' => 'Treasury Shares',
            'account_code_prefix' => '3060',
        ],
    ];
}
```

---

## 📤 Export Reports

### PDF Export
```php
// In your controller
public function exportPdf(Request $request)
{
    return app(CashFlowReportController::class)->export($request);
}
```

Access via GET request:
```
/accounting/reports/cash-flow/export?
    from_date=2025-01-01
    &to_date=2025-12-31
    &method=direct
    &export_type=pdf
```

### Excel Export
```
/accounting/reports/cash-flow/export?
    from_date=2025-01-01
    &to_date=2025-12-31
    &method=indirect
    &export_type=excel
```

---

## 🧪 Testing the Implementation

### Test Data Required

1. **For Cash Flow Statement**:
   - Transactions with `has_cash_flow = true` on chart accounts
   - Transactions categorized into Operating/Investing/Financing
   - Transaction types: `receipt`, `payment`, `asset_purchase`, `loan_receipt`, etc.

2. **For Equity Statement**:
   - Chart accounts with `has_equity = true`
   - Transactions in equity accounts (3000-3999 range)
   - Linked to equity categories

### Sample Test Transactions

```php
// Create test cash receipt
GlTransaction::create([
    'chart_account_id' => 1, // Cash account with has_cash_flow=true
    'amount' => 10000,
    'nature' => 'debit',
    'transaction_type' => 'receipt',
    'date' => '2025-06-15',
    'description' => 'Customer payment',
    'branch_id' => 1
]);

// Create test equity transaction
GlTransaction::create([
    'chart_account_id' => 100, // Share capital account with has_equity=true
    'amount' => 50000,
    'nature' => 'credit',
    'transaction_type' => 'share_issuance',
    'date' => '2025-03-01',
    'description' => 'Issuance of 5,000 shares',
    'branch_id' => 1
]);
```

---

## ⚠️ Important Notes

### Transaction Types Must Match
Ensure your GL transactions use the correct `transaction_type` values:

**Operating Activities:**
- `receipt`, `cash_sale`, `pos_sale`, `customer_payment`
- `payment`, `cash_purchase`, `supplier_payment`
- `payroll_payment`, `salary_payment`
- `interest_payment`, `tax_payment`

**Investing Activities:**
- `asset_purchase`, `fixed_asset_acquisition`
- `asset_disposal`, `fixed_asset_sale`
- `investment_purchase`, `investment_sale`
- `interest_receipt`, `dividend_receipt`

**Financing Activities:**
- `share_issuance`, `capital_contribution`
- `loan_receipt`, `borrowing_proceeds`
- `loan_repayment`, `borrowing_repayment`
- `lease_payment`, `dividend_payment`

### Account Codes Configured for Your System
The services use these account code prefixes (already customized for your chart of accounts):

**Working Capital (Indirect Method):**
- **Trade Receivables**: `1101`
- **Inventory**: `1170` (Merchandise Inventory)
- **Prepayments**: `1134`
- **Trade Payables**: `2101`
- **Accruals**: `2103`

**Equity Components (Equity Statement):**
- **Share Capital**: `3101` (Ordinary Share Capital)
- **Share Premium**: `3530`
- **Revaluation Reserve**: `3105` (IAS 16)
- **Retained Earnings**: `3001`
- **Other Reserves**: `3124` (Fair Value Reserve - IFRS 9)

**Your Account Structure:**
- Assets: 1000-1999
- Liabilities: 2000-2999
- Equity: 3000-3999
- Revenue: 4000-4999
- Expenses: 5000-5999

Adjust these in the service classes if your chart of accounts differs.

---

## 🎓 Learning Resources

### IFRS Standards (Free Access)
- **IAS 7**: Statement of Cash Flows - https://www.ifrs.org/issued-standards/list-of-standards/ias-7-statement-of-cash-flows/
- **IAS 1**: Presentation of Financial Statements - https://www.ifrs.org/issued-standards/list-of-standards/ias-1-presentation-of-financial-statements/

### Implementation Guides
1. `IMPLEMENTATION_GUIDE_CASH_FLOW_AND_EQUITY_REPORTS.md` - Complete technical guide
2. `IFRS_REPORT_FORMATS_VISUAL_GUIDE.md` - Visual layouts and formats
3. `IMPLEMENTATION_CHECKLIST.md` - Step-by-step checklist
4. `IMPLEMENTATION_PROGRESS.md` - Current progress status

---

## 💡 Tips & Best Practices

### 1. Start Simple
- Test with one month of data first
- Verify calculations manually
- Gradually expand date range

### 2. Transaction Types
- Use consistent transaction type naming
- Document your transaction types
- Consider creating a TransactionType enum

### 3. Chart of Accounts
- Mark cash accounts with `has_cash_flow = true`
- Mark equity accounts with `has_equity = true`
- Link accounts to appropriate categories

### 4. Testing Strategy
- Create a test company with sample data
- Run reports for a known period
- Manually verify calculations
- Compare with spreadsheet calculations

### 5. Performance
- Index `transaction_type` column if not already
- Index `date` column on gl_transactions
- Consider archiving old transactions
- Use query caching for frequently run reports

---

## 🐛 Troubleshooting

### Issue: "Cash doesn't balance"
**Solution**: Check that all cash accounts have `has_cash_flow = true` and correct category

### Issue: "No data showing"
**Solution**: Verify GL transactions exist in the date range with proper transaction types

### Issue: "Wrong amounts"
**Solution**: Check nature (debit/credit) is correct for account type

### Issue: "Missing line items"
**Solution**: Ensure transaction types match those in CashFlowLineItemSeeder

### Issue: "Export fails"
**Solution**: Check PDF library is installed: `composer require barryvdh/laravel-dompdf`

---

## 📞 Support

### Getting Help
1. Check the implementation guides in the root directory
2. Review service class code for calculation logic
3. Check transaction types in your database
4. Verify chart account flags and categories

### Common Questions

**Q: Can I customize the report format?**
A: Yes! Edit the service classes or create custom export methods.

**Q: How do I add more comparative periods?**
A: Pass additional periods in the `comparativePeriods` array parameter.

**Q: Can I filter by specific accounts?**
A: Yes, modify the service queries to add account filters.

**Q: How do I change account code prefixes?**
A: Update the code prefix mappings in service class methods.

---

## ✅ Checklist Before Going Live

- [ ] Run migrations and seeders
- [ ] Map chart accounts to cash flow categories
- [ ] Mark equity accounts with `has_equity = true`
- [ ] Test with sample data
- [ ] Verify calculations manually
- [ ] Test both Direct and Indirect methods
- [ ] Test PDF exports
- [ ] Test Excel exports
- [ ] Test branch filtering
- [ ] Test comparative periods
- [ ] Get external auditor review
- [ ] Train finance team
- [ ] Document any customizations
- [ ] Set up regular backup schedule

---

**Ready to use!** 🎉

The core implementation is complete and functional. You can start using the reports immediately through the API or by updating the views to display the new format.

For questions or issues, refer to the detailed implementation guides in the project root.
