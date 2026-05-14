# IAS 21 Foreign Currency Module - Complete Implementation Plan

## Overview
This document outlines the complete step-by-step implementation plan for the IAS 21 Foreign Currency Module in SmartAccounting ERP. The implementation will be done in phases to ensure systematic development and testing.

---

## PHASE 1: DATABASE FOUNDATION & MASTER DATA

### Step 1.1: Create Core Currency Tables
**Files to Create:**
- `database/migrations/YYYY_MM_DD_HHMMSS_create_currencies_table.php`
- `database/migrations/YYYY_MM_DD_HHMMSS_create_fx_rates_table.php`
- `database/migrations/YYYY_MM_DD_HHMMSS_create_gl_revaluation_history_table.php`

**Details:**
1. **currencies table:**
   - `id`, `currency_code` (varchar 3, unique), `currency_name`, `decimal_places` (int, default 2), `is_active` (boolean), `company_id`, `created_at`, `updated_at`
   - Index on `currency_code` and `company_id`

2. **fx_rates table:**
   - `id`, `rate_date` (date), `from_currency` (varchar 3), `to_currency` (varchar 3), `spot_rate` (decimal 15,6), `month_end_rate` (decimal 15,6, nullable), `average_rate` (decimal 15,6, nullable), `source` (enum: 'manual', 'api', 'import'), `is_locked` (boolean, default false), `company_id`, `created_by`, `created_at`, `updated_at`
   - Unique index on `(rate_date, from_currency, to_currency, company_id)`
   - Index on `rate_date`, `from_currency`, `to_currency`

3. **gl_revaluation_history table:**
   - `id`, `revaluation_date` (date), `item_type` (enum: 'AR', 'AP', 'BANK', 'LOAN', 'INTERCOMPANY'), `item_ref` (varchar 255), `item_id` (bigint), `original_rate` (decimal 15,6), `closing_rate` (decimal 15,6), `base_amount` (decimal 20,2), `fcy_amount` (decimal 20,2), `gain_loss` (decimal 20,2), `posted_journal_id` (bigint, nullable), `reversal_journal_id` (bigint, nullable), `is_reversed` (boolean, default false), `company_id`, `branch_id`, `created_by`, `created_at`, `updated_at`
   - Index on `(revaluation_date, item_type, company_id)`, `item_id`, `posted_journal_id`

### Step 1.2: Extend Existing Transaction Tables
**Files to Modify:**
- Add migration to add FX fields to existing tables:
  - `sales_invoices`: Already has `currency` and `exchange_rate` - ADD: `amount_fcy` (decimal 20,2), `amount_lcy` (decimal 20,2), `fx_gain_loss` (decimal 20,2, default 0), `fx_rate_used` (decimal 15,6)
  - `cash_sales`: Already has `currency` and `exchange_rate` - ADD: `amount_fcy`, `amount_lcy`, `fx_gain_loss`, `fx_rate_used`
  - `pos_sales`: Already has `currency` and `exchange_rate` - ADD: `amount_fcy`, `amount_lcy`, `fx_gain_loss`, `fx_rate_used`
  - `purchase_invoices`: ADD: `currency` (varchar 3, default 'TZS'), `exchange_rate` (decimal 15,6, default 1), `amount_fcy`, `amount_lcy`, `fx_gain_loss`, `fx_rate_used`
  - `cash_purchases`: ADD: `currency`, `exchange_rate`, `amount_fcy`, `amount_lcy`, `fx_gain_loss`, `fx_rate_used`
  - `payments`: ADD: `currency`, `exchange_rate`, `amount_fcy`, `amount_lcy`, `fx_gain_loss`, `fx_rate_used`, `payment_currency` (varchar 3)
  - `receipts`: ADD: `currency`, `exchange_rate`, `amount_fcy`, `amount_lcy`, `fx_gain_loss`, `fx_rate_used`, `receipt_currency`
  - `bank_accounts`: ADD: `currency` (varchar 3, default 'TZS'), `base_currency` (varchar 3)
  - `loans`: ADD: `currency` (varchar 3, default 'TZS'), `exchange_rate` (decimal 15,6, default 1), `amount_fcy`, `amount_lcy`

### Step 1.3: Add Currency Fields to Master Data
**Files to Modify:**
- `customers`: ADD: `default_currency` (varchar 3, nullable)
- `suppliers`: ADD: `default_currency` (varchar 3, nullable)
- `companies`: ADD: `functional_currency` (varchar 3, default 'TZS')

### Step 1.4: Create Models
**Files to Create:**
- `app/Models/Currency.php`
- `app/Models/FxRate.php`
- `app/Models/GlRevaluationHistory.php`

**Model Relationships:**
- Currency belongsTo Company
- FxRate belongsTo Currency (from/to), belongsTo Company, belongsTo User (created_by)
- GlRevaluationHistory belongsTo Company, Branch, User, Journal (posted_journal_id)

---

## PHASE 2: FX RATE MANAGEMENT SYSTEM

### Step 2.1: Enhance ExchangeRateService
**File to Modify:** `app/Services/ExchangeRateService.php`

**New Methods to Add:**
1. `getSpotRate($fromCurrency, $toCurrency, $date = null)` - Get spot rate for a specific date
2. `getMonthEndRate($fromCurrency, $toCurrency, $year, $month)` - Get month-end closing rate
3. `getAverageRate($fromCurrency, $toCurrency, $startDate, $endDate)` - Calculate average rate
4. `storeFxRate($fromCurrency, $toCurrency, $rateDate, $spotRate, $monthEndRate = null, $averageRate = null, $source = 'manual')` - Store rate in fx_rates table
5. `lockFxRate($rateId)` - Lock rate after posting (prevent retrospective changes)
6. `getRateForTransaction($fromCurrency, $toCurrency, $transactionDate)` - Get rate for transaction date (spot rate)

### Step 2.2: Create FX Rate Management Controller
**File to Create:** `app/Http/Controllers/Accounting/FxRateController.php`

**Methods:**
- `index()` - List all FX rates with filters
- `create()` - Show rate entry form
- `store()` - Save new rate (with validation)
- `edit()` - Edit rate (only if not locked)
- `update()` - Update rate (only if not locked)
- `lock()` - Lock rate (prevent future edits)
- `import()` - Bulk import rates from CSV/Excel
- `getRate()` - API endpoint to get rate for date/currency pair

### Step 2.3: Create FX Rate Views
**Files to Create:**
- `resources/views/accounting/fx-rates/index.blade.php` - Rate listing with filters
- `resources/views/accounting/fx-rates/create.blade.php` - Rate entry form
- `resources/views/accounting/fx-rates/edit.blade.php` - Rate edit form
- `resources/views/accounting/fx-rates/import.blade.php` - Bulk import form

**Features:**
- Date picker for rate date
- Currency pair selector
- Spot rate, month-end rate, average rate inputs
- Lock status indicator
- Rate history chart/graph
- Export to Excel/PDF

### Step 2.4: Add FX Rate Routes
**File to Modify:** `routes/web.php`

Add routes:
- `GET /accounting/fx-rates` - List rates
- `GET /accounting/fx-rates/create` - Create form
- `POST /accounting/fx-rates` - Store rate
- `GET /accounting/fx-rates/{id}/edit` - Edit form
- `PUT /accounting/fx-rates/{id}` - Update rate
- `POST /accounting/fx-rates/{id}/lock` - Lock rate
- `POST /accounting/fx-rates/import` - Import rates
- `GET /api/fx-rates/get-rate` - API endpoint

---

## PHASE 3: TRANSACTION-LEVEL FX HANDLING

### Step 3.1: Create FX Transaction Service
**File to Create:** `app/Services/FxTransactionService.php`

**Methods:**
1. `recordInitialTransaction($transaction, $amountFcy, $currency, $transactionDate)` - Record initial transaction at spot rate
   - Get spot rate for transaction date
   - Calculate LCY amount = FCY amount × spot rate
   - Store: amount_fcy, amount_lcy, fx_rate_used, currency
   - Return transaction with FX data

2. `calculateRealizedGainLoss($originalTransaction, $paymentAmount, $paymentCurrency, $paymentDate)` - Calculate realized FX gain/loss on payment
   - Get original transaction rate
   - Get payment date rate
   - Calculate: (Payment Rate - Original Rate) × FCY Amount
   - Return gain/loss amount

3. `postRealizedFxGainLoss($transaction, $gainLoss, $paymentDate, $description)` - Post realized FX gain/loss to GL
   - Determine gain/loss account from system settings
   - Create journal entry:
     - If gain: Dr Bank, Cr FX Gain Account
     - If loss: Dr FX Loss Account, Cr Bank
   - Update transaction fx_gain_loss field

4. `getFunctionalCurrency($companyId)` - Get company's functional currency (default TZS)

### Step 3.2: Modify Sales Invoice Creation
**File to Modify:** `app/Http/Controllers/Sales/SalesInvoiceController.php`

**Changes:**
- In `store()` method:
  - Capture `currency` and `exchange_rate` from request
  - If currency != functional currency:
    - Call `FxTransactionService::recordInitialTransaction()`
    - Store `amount_fcy` and `amount_lcy` separately
    - Use `amount_lcy` for GL posting
  - Update invoice model to store FX fields

### Step 3.3: Modify Receipt/Payment Processing
**File to Modify:** `app/Models/Sales/SalesInvoice.php`

**Changes:**
- In `recordPayment()` or receipt creation method:
  - Check if invoice currency != payment currency
  - If different: Calculate realized FX gain/loss
  - Call `FxTransactionService::postRealizedFxGainLoss()`
  - Update invoice `fx_gain_loss` field

### Step 3.4: Modify Purchase Invoice Creation
**File to Modify:** `app/Http/Controllers/Purchase/PurchaseInvoiceController.php` (or similar)

**Changes:**
- Similar to sales invoices:
  - Capture currency and rate
  - Store FCY and LCY amounts
  - Post at spot rate on transaction date

### Step 3.5: Modify Payment Voucher Processing
**File to Modify:** `app/Http/Controllers/PaymentController.php` (or similar)

**Changes:**
- When payment is made in foreign currency:
  - Calculate realized FX gain/loss if paying FCY invoice with different currency
  - Post FX gain/loss journal entry

### Step 3.6: Update Transaction Forms (UI)
**Files to Modify:**
- `resources/views/sales/invoices/create.blade.php`
- `resources/views/sales/invoices/edit.blade.php`
- `resources/views/sales/cash-sales/create.blade.php`
- `resources/views/purchases/invoices/create.blade.php`
- `resources/views/payments/create.blade.php`

**UI Changes:**
- Add currency dropdown (default to functional currency)
- Add exchange rate field (auto-populate from FX rate service, allow override)
- Show FCY amount and LCY amount (calculated)
- Add rate override approval workflow (if rate differs > X% from current rate)

---

## PHASE 4: MONTH-END REVALUATION SYSTEM

### Step 4.1: Create Revaluation Service
**File to Create:** `app/Services/FxRevaluationService.php`

**Methods:**
1. `identifyMonetaryItems($companyId, $branchId, $asOfDate)` - Identify all monetary items to revalue
   - AR: Open invoices in FCY (balance_due > 0, currency != functional currency)
   - AP: Open purchase invoices in FCY (balance_due > 0, currency != functional currency)
   - Bank Accounts: FCY bank accounts with balances
   - Loans: FCY loans with outstanding balances
   - Return array of items grouped by type

2. `calculateUnrealizedGainLoss($item, $closingRate, $originalRate, $fcyAmount)` - Calculate unrealized gain/loss
   - Formula: (Closing Rate - Original Rate) × FCY Amount
   - Return gain/loss amount

3. `generateRevaluationPreview($companyId, $branchId, $revaluationDate)` - Generate preview of revaluation entries
   - Get closing rate for revaluation date
   - Identify all monetary items
   - Calculate unrealized gain/loss for each
   - Group by item type
   - Return preview data structure

4. `postRevaluation($companyId, $branchId, $revaluationDate, $previewData, $userId)` - Post revaluation journal entries
   - For each item:
     - Calculate gain/loss
     - Create journal entry:
       - AR: Dr FX Loss / Cr AR (or Dr AR / Cr FX Gain)
       - AP: Dr AP / Cr FX Gain (or Dr FX Loss / Cr AP)
       - Bank: Dr Bank / Cr FX Gain (or Dr FX Loss / Cr Bank)
       - Loan: Similar logic
     - Store in `gl_revaluation_history`
     - Link journal entry to history record

5. `reversePreviousRevaluation($companyId, $branchId, $newPeriodStartDate)` - Auto-reverse previous period revaluation
   - Find all unreversed revaluation entries from previous period
   - For each:
     - Create reversal journal entry (opposite of original)
     - Mark as reversed in `gl_revaluation_history`
     - Link reversal journal to history record

### Step 4.2: Create Revaluation Controller
**File to Create:** `app/Http/Controllers/Accounting/FxRevaluationController.php`

**Methods:**
- `index()` - List revaluation history
- `create()` - Show revaluation form (select date, branch)
- `preview()` - Generate and show revaluation preview (AJAX)
- `store()` - Post revaluation (with approval workflow)
- `show($id)` - View revaluation details
- `reverse($id)` - Manual reversal (if needed)
- `autoReverse()` - Auto-reversal on period start (scheduled job)

### Step 4.3: Create Revaluation Views
**Files to Create:**
- `resources/views/accounting/fx-revaluation/index.blade.php` - Revaluation history
- `resources/views/accounting/fx-revaluation/create.blade.php` - Revaluation form
- `resources/views/accounting/fx-revaluation/preview.blade.php` - Preview modal/partial
- `resources/views/accounting/fx-revaluation/show.blade.php` - Revaluation details

**Features:**
- Date picker for revaluation date
- Branch selector
- Preview table showing:
  - Item type, reference, FCY amount, original rate, closing rate, gain/loss
- Approval workflow (if configured)
- Post button (creates journal entries)
- Download reconciliation report

### Step 4.4: Create Scheduled Job for Auto-Reversal
**File to Create:** `app/Console/Commands/AutoReverseFxRevaluation.php`

**Logic:**
- Run on 1st day of each month (via Laravel scheduler)
- For each company:
  - Get previous month's revaluation entries
  - Reverse all unreversed entries
  - Log reversal activity

**File to Modify:** `app/Console/Kernel.php`
- Add scheduled task: `$schedule->command('fx:auto-reverse')->monthlyOn(1, '00:00');`

### Step 4.5: Add Revaluation Routes
**File to Modify:** `routes/web.php`

Add routes:
- `GET /accounting/fx-revaluation` - List
- `GET /accounting/fx-revaluation/create` - Create form
- `POST /accounting/fx-revaluation/preview` - Preview (AJAX)
- `POST /accounting/fx-revaluation` - Post revaluation
- `GET /accounting/fx-revaluation/{id}` - View details
- `POST /accounting/fx-revaluation/{id}/reverse` - Manual reversal

---

## PHASE 5: SYSTEM SETTINGS & CONFIGURATION

### Step 5.1: Add FX System Settings
**File to Create:** `database/migrations/YYYY_MM_DD_HHMMSS_add_fx_system_settings.php`

**Settings to Add:**
- `fx_realized_gain_account_id` - Chart account for realized FX gains
- `fx_realized_loss_account_id` - Chart account for realized FX losses
- `fx_unrealized_gain_account_id` - Chart account for unrealized FX gains
- `fx_unrealized_loss_account_id` - Chart account for unrealized FX losses
- `functional_currency` - Company's functional currency (default: TZS)
- `fx_rate_override_threshold` - Percentage threshold for rate override approval (default: 5%)
- `fx_revaluation_approval_required` - Boolean: require approval before posting revaluation

### Step 5.2: Create FX Settings Management
**File to Create:** `app/Http/Controllers/Accounting/FxSettingsController.php`

**Methods:**
- `index()` - Show FX settings form
- `update()` - Update FX settings

**File to Create:** `resources/views/accounting/fx-settings/index.blade.php`
- Form to configure FX accounts and settings

---

## PHASE 6: REPORTS & DASHBOARDS

### Step 6.1: FX Gain/Loss Report
**File to Create:** `app/Http/Controllers/Reports/FxGainLossReportController.php`

**Filters:**
- Date range
- Currency
- Gain/Loss type (realized/unrealized)
- Customer/Supplier
- Bank account
- Branch

**Data:**
- Transaction date, reference, type
- FCY amount, original rate, current rate
- Gain/Loss amount
- Cumulative totals

**File to Create:** `resources/views/reports/fx-gain-loss/index.blade.php`
- Report table with filters
- Export to Excel/PDF

### Step 6.2: Currency Exposure Report
**File to Create:** `app/Http/Controllers/Reports/CurrencyExposureReportController.php`

**Data:**
- Currency
- AR exposure (FCY and LCY)
- AP exposure (FCY and LCY)
- Bank exposure (FCY and LCY)
- Loan exposure (FCY and LCY)
- Net exposure per currency
- Total exposure in functional currency

**File to Create:** `resources/views/reports/currency-exposure/index.blade.php`

### Step 6.3: Revaluation Summary Report
**File to Create:** `app/Http/Controllers/Reports/FxRevaluationSummaryController.php`

**Data:**
- Revaluation date
- Item type (AR, AP, Bank, Loan)
- Number of items revalued
- Total FCY amount
- Total gain/loss
- Journal entry reference

**File to Create:** `resources/views/reports/fx-revaluation-summary/index.blade.php`

### Step 6.4: Revaluation Detail (Audit) Report
**File to Create:** `app/Http/Controllers/Reports/FxRevaluationDetailController.php`

**Data:**
- Revaluation date
- Item reference, type
- Original rate, closing rate
- FCY amount, base amount
- FX difference, gain/loss
- Journal entry posted
- Who posted, when

**File to Create:** `resources/views/reports/fx-revaluation-detail/index.blade.php`

### Step 6.5: Exchange Rates Register Report
**File to Create:** `app/Http/Controllers/Reports/FxRatesRegisterController.php`

**Data:**
- Date range
- Currency pair
- Spot rate, month-end rate, average rate
- Rate source
- Lock status

**File to Create:** `resources/views/reports/fx-rates-register/index.blade.php`

### Step 6.6: FX Dashboard
**File to Create:** `app/Http/Controllers/Accounting/FxDashboardController.php`

**Widgets:**
- Today's exchange rates (all currencies)
- Month-end rates
- Currency exposure summary
- Unrealized gain/loss summary
- Recent revaluations
- Rate trends (charts)

**File to Create:** `resources/views/accounting/fx-dashboard/index.blade.php`

---

## PHASE 7: UI/UX ENHANCEMENTS

### Step 7.1: Currency Selector Component
**File to Create:** `resources/views/components/currency-selector.blade.php`

**Features:**
- Dropdown with all active currencies
- Shows currency code and name
- Default to functional currency
- Auto-fetch rate on selection

### Step 7.2: Exchange Rate Display Component
**File to Create:** `resources/views/components/exchange-rate-display.blade.php`

**Features:**
- Shows current rate for selected currency pair
- Rate date
- Allow rate override (with approval if threshold exceeded)
- Rate history link

### Step 7.3: FX Amount Calculator Component
**File to Create:** `resources/views/components/fx-amount-calculator.blade.php`

**Features:**
- FCY amount input
- Exchange rate input (auto-populated)
- LCY amount (calculated, read-only)
- Real-time calculation

### Step 7.4: Rate Override Approval Workflow
**File to Create:** `app/Http/Controllers/Accounting/FxRateOverrideController.php`

**Logic:**
- When user overrides rate:
  - Calculate percentage difference from current rate
  - If > threshold: Require approval
  - Create approval request
  - Notify approvers
  - After approval: Allow transaction to proceed

### Step 7.5: Revaluation Console UI
**Enhance:** `resources/views/accounting/fx-revaluation/create.blade.php`

**Features:**
- Preview table (sortable, filterable)
- Group by item type
- Show totals per group
- Approve/Reject buttons (if approval required)
- Post button (creates journals)
- Download reconciliation button

---

## PHASE 8: INTEGRATION & TESTING

### Step 8.1: Update Existing Transaction Models
**Files to Modify:**
- `app/Models/Sales/SalesInvoice.php` - Add FX methods and relationships
- `app/Models/Sales/CashSale.php` - Add FX methods
- `app/Models/Sales/PosSale.php` - Add FX methods
- `app/Models/Purchase/PurchaseInvoice.php` - Add FX methods
- `app/Models/Payment.php` - Add FX methods
- `app/Models/Receipt.php` - Add FX methods
- `app/Models/BankAccount.php` - Add currency relationship
- `app/Models/Loan/Loan.php` - Add FX methods

**Add Methods:**
- `getFcyAmount()` - Get FCY amount
- `getLcyAmount()` - Get LCY amount
- `getFxGainLoss()` - Get FX gain/loss
- `isForeignCurrency()` - Check if transaction is in FCY
- `calculateRealizedFxGainLoss()` - Calculate realized gain/loss on payment

### Step 8.2: Update GL Posting Logic
**Files to Modify:**
- All transaction controllers that create GL entries

**Changes:**
- Use `amount_lcy` (not `amount_fcy`) for GL postings
- Post FX gain/loss separately when payment is made

### Step 8.3: Create Unit Tests
**Files to Create:**
- `tests/Unit/Services/FxTransactionServiceTest.php`
- `tests/Unit/Services/FxRevaluationServiceTest.php`
- `tests/Unit/Services/ExchangeRateServiceTest.php`

**Test Cases:**
- Initial transaction recording at spot rate
- Realized FX gain/loss calculation
- Unrealized FX gain/loss calculation
- Month-end revaluation
- Auto-reversal of revaluation
- Rate locking

### Step 8.4: Create Feature Tests
**Files to Create:**
- `tests/Feature/FxRateManagementTest.php`
- `tests/Feature/FxRevaluationTest.php`
- `tests/Feature/FxTransactionTest.php`

**Test Scenarios:**
- Create FX rate
- Lock FX rate
- Create FCY invoice
- Receive payment in FCY (calculate realized gain/loss)
- Month-end revaluation
- Auto-reversal

### Step 8.5: Data Migration Script
**File to Create:** `database/migrations/YYYY_MM_DD_HHMMSS_migrate_existing_transactions_to_fx.php`

**Logic:**
- For existing transactions with `currency` != 'TZS':
  - Set `amount_fcy` = `total_amount`
  - Set `amount_lcy` = `total_amount` × `exchange_rate`
  - Set `fx_rate_used` = `exchange_rate`
  - Set `fx_gain_loss` = 0 (historical data)

---

## PHASE 9: DOCUMENTATION & MENU INTEGRATION

### Step 9.1: Add Menu Items
**File to Modify:** Menu configuration or navigation file

**Add Menu Items:**
- Accounting > Foreign Exchange > FX Rates
- Accounting > Foreign Exchange > Revaluation
- Accounting > Foreign Exchange > FX Dashboard
- Accounting > Foreign Exchange > Settings
- Reports > Foreign Exchange > Gain/Loss Report
- Reports > Foreign Exchange > Currency Exposure
- Reports > Foreign Exchange > Revaluation Summary
- Reports > Foreign Exchange > Revaluation Detail
- Reports > Foreign Exchange > Rates Register

### Step 9.2: Create User Documentation
**File to Create:** `docs/IAS21_FOREIGN_CURRENCY_GUIDE.md`

**Sections:**
- Overview of IAS 21
- Setting up currencies
- Managing exchange rates
- Creating FCY transactions
- Month-end revaluation process
- Understanding FX gain/loss
- Reports explanation
- Troubleshooting

### Step 9.3: Create API Documentation
**File to Create:** `docs/IAS21_API_DOCUMENTATION.md`

**Endpoints:**
- GET /api/fx-rates/get-rate
- POST /api/fx-rates
- GET /api/fx-revaluation/preview
- POST /api/fx-revaluation

---

## PHASE 10: FINAL POLISH & OPTIMIZATION

### Step 10.1: Performance Optimization
- Add database indexes on frequently queried fields
- Cache exchange rates
- Optimize revaluation queries (use eager loading)
- Add query result caching for reports

### Step 10.2: Error Handling & Validation
- Add comprehensive validation rules
- Add error messages for all scenarios
- Add logging for FX operations
- Add audit trail for rate changes

### Step 10.3: Security & Permissions
- Add permissions:
  - `fx.rates.view`, `fx.rates.create`, `fx.rates.edit`, `fx.rates.lock`
  - `fx.revaluation.view`, `fx.revaluation.create`, `fx.revaluation.post`
  - `fx.reports.view`
- Add role-based access control

### Step 10.4: Localization
- Add multi-language support for FX module
- Translate error messages
- Translate report labels

---

## IMPLEMENTATION ORDER SUMMARY

1. **Phase 1**: Database foundation (Tables, Models)
2. **Phase 2**: FX Rate Management (Service, Controller, Views)
3. **Phase 3**: Transaction-level FX handling (Service, Controller updates)
4. **Phase 5**: System settings (Configuration)
5. **Phase 4**: Month-end revaluation (Service, Controller, Views, Scheduled job)
6. **Phase 6**: Reports (All report controllers and views)
7. **Phase 7**: UI/UX enhancements (Components, Workflows)
8. **Phase 8**: Integration & Testing (Model updates, Tests)
9. **Phase 9**: Documentation & Menu integration
10. **Phase 10**: Final polish & optimization

---

## ESTIMATED TIMELINE

- **Phase 1**: 2-3 days
- **Phase 2**: 3-4 days
- **Phase 3**: 4-5 days
- **Phase 4**: 5-6 days
- **Phase 5**: 1-2 days
- **Phase 6**: 4-5 days
- **Phase 7**: 3-4 days
- **Phase 8**: 3-4 days
- **Phase 9**: 2 days
- **Phase 10**: 2-3 days

**Total Estimated Time**: 29-38 days

---

## CRITICAL SUCCESS FACTORS

1. ✅ All transactions must store FCY amount, LCY amount, and FX rate used
2. ✅ Foreign currency transactions must post at spot exchange rate on transaction date
3. ✅ Payments must compute and post realized FX gain/loss
4. ✅ All monetary items must be revalued at month-end using closing rates
5. ✅ System must auto-calculate unrealized gain/loss and post revaluation journals
6. ✅ All month-end revaluation entries must auto-reverse on first day of next month
7. ✅ System must maintain fx_rates table with spot, average, and month-end rates
8. ✅ System must produce all required reports
9. ✅ UI must include multi-currency selector, rate override approval, revaluation console
10. ✅ Exchange difference accounts must be configurable
11. ✅ All historical FX rates must be locked after posting
12. ✅ All calculations must follow IAS 21 requirements

---

## NOTES

- This implementation follows Laravel best practices
- All database changes are done via migrations
- All business logic is in Services (not controllers)
- All views use Blade components for reusability
- All FX operations are logged for audit trail
- System supports multi-company and multi-branch
- Functional currency is configurable per company (default: TZS)

---

**Ready to proceed with implementation?** Please review this plan and let me know if you'd like any modifications before I start coding.

