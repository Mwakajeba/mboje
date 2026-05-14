# Cashflow Forecasting - Complete Flow Documentation

## 🎯 What is Cashflow Forecasting?

**Cashflow Forecasting** is a financial planning tool that predicts your organization's future cash position by analyzing expected cash inflows (money coming in) and outflows (money going out) over a specified time period.

### Purpose:
- **Predict future cash availability** - Know how much cash you'll have at any point in time
- **Identify cash shortages** - Plan ahead to avoid running out of money
- **Optimize cash management** - Make informed decisions about investments, expenses, and borrowing
- **Support strategic planning** - Help CFOs and finance teams make data-driven decisions

---

## 📊 Key Components

### 1. **Forecast Configuration**
- **Forecast Name**: Descriptive name (e.g., "Q1 2026 Forecast")
- **Scenario**: Best Case, Base Case, or Worst Case
- **Timeline**: Daily, Weekly, Monthly, or Quarterly view
- **Date Range**: Start date and end date for the forecast period
- **Starting Cash Balance**: Current cash position at the start of the forecast
- **Branch**: Optional - filter by specific branch

### 2. **Data Sources (Inflows & Outflows)**

#### 💰 **CASH INFLOWS** (Money Coming In):
1. **Accounts Receivable (AR)**
   - Unpaid customer invoices
   - Based on invoice due dates
   - Probability adjusted by scenario

2. **Sales Orders**
   - Confirmed sales orders
   - Expected delivery dates
   - Lower probability (70%) as orders may change

3. **Loan Disbursements**
   - Expected loan funds to be received
   - Based on loan schedules

4. **Recurring Inflows**
   - Regular income streams
   - Subscriptions, contracts, etc.

5. **Deposits & Grants**
   - Expected deposits
   - Grant funding

#### 💸 **CASH OUTFLOWS** (Money Going Out):
1. **Accounts Payable (AP)**
   - Unpaid supplier invoices
   - Based on invoice due dates
   - High probability (85-95%)

2. **Payment Vouchers**
   - Approved but unpaid PVs
   - Scheduled payment dates
   - High probability (90%)

3. **Loan Payments**
   - Principal + Interest payments
   - From loan schedules
   - Very high probability (95%)

4. **Payroll**
   - Monthly salary payments
   - Estimated from historical data
   - Certain (100% probability)

5. **Statutory Payments**
   - **VAT** - Monthly tax payments
   - **WHT** - Withholding tax
   - **PAYE** - Pay As You Earn
   - **SDL** - Skills Development Levy
   - **Pension** - Pension contributions
   - All certain (100% probability)

6. **Recurring Bills**
   - Regular expenses (utilities, rent, etc.)
   - Based on historical patterns

7. **Capital Expenditure (CapEx)**
   - Planned asset purchases
   - Major investments

---

## 🔄 Complete Flow Process

### **Step 1: Create Forecast**
```
User → Create New Forecast
  ↓
Fill in:
  - Forecast Name
  - Scenario (Best/Base/Worst)
  - Timeline (Daily/Weekly/Monthly/Quarterly)
  - Start Date & End Date
  - Starting Cash Balance
  - Branch (optional)
  - Notes
  ↓
Click "Generate Forecast"
```

### **Step 2: Data Collection (Automatic)**
```
System automatically gathers data from:
  ↓
1. Accounts Receivable
   - Find all unpaid invoices
   - Filter by due date within forecast period
   - Extract: Invoice #, Amount, Due Date
   
2. Accounts Payable
   - Find all unpaid supplier invoices
   - Filter by due date within forecast period
   - Extract: Invoice #, Amount, Due Date
   
3. Sales Orders
   - Find confirmed orders
   - Filter by expected delivery date
   - Extract: Order #, Amount, Delivery Date
   
4. Loan Schedules
   - Find pending loan payments
   - Filter by due date
   - Extract: Loan #, Principal, Interest, Due Date
   
5. Payment Vouchers
   - Find approved but unpaid PVs
   - Filter by payment date
   - Extract: PV #, Amount, Date
   
6. Payroll Estimates
   - Calculate from historical payroll data
   - Generate monthly payroll dates
   - Extract: Estimated Amount, Payroll Date
   
7. Tax Estimates
   - Calculate from historical tax payments
   - Generate tax payment dates (VAT, WHT, etc.)
   - Extract: Estimated Amount, Tax Date
```

### **Step 3: Scenario Application**
```
For each data item, apply scenario adjustments:

BASE CASE:
  - Use original dates as-is
  - Standard probability (85-95% for AR, 90% for AP)

BEST CASE:
  - AR: Move dates 3 days EARLIER (early collection)
  - AP: Move dates 5 days LATER (delayed payment)
  - Higher probability for inflows (95%)
  - Lower probability for outflows (70%)

WORST CASE:
  - AR: Move dates 10 days LATER (delayed collection)
  - AP: Move dates 3 days EARLIER (early payment)
  - Lower probability for inflows (30-70%)
  - Higher probability for outflows (95%)
```

### **Step 4: Forecast Item Generation**
```
For each data source:
  ↓
Create CashflowForecastItem:
  - forecast_date (adjusted by scenario)
  - type (inflow/outflow)
  - source_type (AR, AP, loan, payroll, etc.)
  - source_reference (Invoice #, Order #, etc.)
  - source_id (ID of source record)
  - amount
  - probability (0-100%)
  - description
  ↓
Save to database
```

### **Step 5: Aggregation & Timeline**
```
Group all items by forecast_date
  ↓
For each date:
  - Sum all inflows
  - Sum all outflows
  - Calculate net cashflow (inflows - outflows)
  - Calculate running balance:
    Starting Balance
    + Day 1 Net → Balance Day 1
    + Day 2 Net → Balance Day 2
    ...
  ↓
Display in timeline view
```

### **Step 6: Display & Analysis**
```
Forecast Show Page displays:
  ↓
1. Summary Cards:
   - Total Inflows
   - Total Outflows
   - Net Cashflow
   - Ending Balance
   
2. Timeline Table:
   Date | Inflows | Outflows | Net | Running Balance
   -----|---------|----------|-----|----------------
   2026-01-01 | 50,000 | 20,000 | +30,000 | 1,030,000
   2026-01-02 | 0 | 15,000 | -15,000 | 1,015,000
   ...
   
3. Detailed Items:
   - Expand each date to see individual items
   - Show source, amount, probability
   
4. Charts/Graphs:
   - Cashflow trend over time
   - Inflow vs Outflow comparison
   - Balance projection
```

### **Step 7: Manual Adjustments (Optional)**
```
User can manually adjust forecast:
  ↓
1. Add Manual Item:
   - Click "Add Adjustment"
   - Enter: Date, Type, Amount, Description
   - Save
   
2. Edit Existing Item:
   - Click edit on any item
   - Modify amount or date
   - Mark as "Manual Adjustment"
   
3. Delete Item:
   - Remove items that won't occur
   - System recalculates automatically
```

### **Step 8: Regeneration**
```
If source data changes:
  ↓
User clicks "Regenerate Forecast"
  ↓
System:
  1. Deletes all existing forecast items
  2. Re-runs data collection
  3. Re-applies scenarios
  4. Re-generates all items
  5. Re-calculates timeline
  ↓
Updated forecast displayed
```

---

## 📈 Example Flow

### Scenario: Creating a 3-Month Forecast

**Input:**
- Forecast Name: "Q1 2026 Cashflow Forecast"
- Scenario: Base Case
- Timeline: Daily
- Start Date: 2026-01-01
- End Date: 2026-03-31
- Starting Balance: TZS 1,000,000

**Process:**

1. **System finds:**
   - 15 unpaid customer invoices (AR) totaling TZS 2,500,000
   - 8 unpaid supplier invoices (AP) totaling TZS 1,200,000
   - 3 confirmed sales orders totaling TZS 800,000
   - 2 loan payments totaling TZS 150,000
   - 3 monthly payrolls totaling TZS 900,000
   - 3 VAT payments totaling TZS 120,000

2. **System creates forecast items:**
   - 15 inflow items (AR invoices) on their due dates
   - 8 outflow items (AP invoices) on their due dates
   - 3 inflow items (sales orders) on delivery dates
   - 2 outflow items (loan payments) on due dates
   - 3 outflow items (payroll) on 25th of each month
   - 3 outflow items (VAT) on 20th of each month

3. **System aggregates by date:**
   ```
   2026-01-05: Inflow 200,000 (Invoice INV-001) | Outflow 50,000 (PV-001) | Net +150,000 | Balance 1,150,000
   2026-01-10: Inflow 0 | Outflow 300,000 (Payroll) | Net -300,000 | Balance 850,000
   2026-01-15: Inflow 500,000 (Invoice INV-002) | Outflow 0 | Net +500,000 | Balance 1,350,000
   ...
   ```

4. **User views forecast:**
   - Sees daily cash position
   - Identifies low balance days (e.g., Jan 10: 850,000)
   - Plans for cash shortages
   - Makes informed decisions

---

## 🎯 Key Features

### 1. **Scenario Analysis**
- **Best Case**: Optimistic view (early collections, delayed payments)
- **Base Case**: Realistic view (normal conditions)
- **Worst Case**: Pessimistic view (delayed collections, early payments)

### 2. **Probability Weighting**
- Each item has a probability (0-100%)
- Helps assess likelihood of cashflow events
- Example: Sales orders = 70% (may be cancelled)

### 3. **Timeline Flexibility**
- **Daily**: Most detailed, shows day-by-day cash position
- **Weekly**: Weekly aggregates
- **Monthly**: Monthly summaries
- **Quarterly**: High-level quarterly view

### 4. **Manual Override**
- Finance team can adjust any forecast item
- Add unexpected inflows/outflows
- Modify amounts or dates
- Track who made adjustments

### 5. **Real-time Updates**
- Regenerate when source data changes
- New invoices automatically included
- Updated loan schedules reflected
- Always current forecast

---

## 💡 Use Cases

### 1. **Cash Shortage Planning**
```
Forecast shows: Low balance on Jan 20 (TZS 200,000)
Action: Arrange short-term loan or delay payments
```

### 2. **Investment Timing**
```
Forecast shows: High balance on Feb 15 (TZS 5,000,000)
Action: Invest excess cash or pay down debt
```

### 3. **Payment Scheduling**
```
Forecast shows: Multiple outflows on same day
Action: Reschedule some payments to avoid cash crunch
```

### 4. **Collection Follow-up**
```
Forecast shows: Large AR due on Jan 10
Action: Follow up with customer before due date
```

### 5. **Budget Comparison**
```
Compare forecast vs. actual cashflow
Identify variances and adjust future forecasts
```

---

## 🔧 Technical Implementation

### Database Tables:
1. **cashflow_forecasts**
   - Stores forecast configuration
   - Links to company, branch, creator

2. **cashflow_forecast_items**
   - Stores individual forecast entries
   - Links to source records (invoices, orders, etc.)
   - Tracks probability, amounts, dates

### Service Layer:
- **CashflowForecastService**
  - `generateForecastItems()` - Main generation method
  - `generateFromAccountsReceivable()` - AR processing
  - `generateFromAccountsPayable()` - AP processing
  - `generateFromSalesOrders()` - Sales order processing
  - `generateFromLoans()` - Loan payment processing
  - `generateFromPayroll()` - Payroll estimation
  - `generateFromTaxes()` - Tax payment estimation
  - `applyScenarioToDate()` - Scenario date adjustment
  - `getReceivableProbability()` - AR probability calculation

### Controller:
- **CashflowForecastController**
  - `index()` - List all forecasts
  - `create()` - Show creation form
  - `store()` - Save new forecast + generate items
  - `show()` - Display forecast with timeline
  - `regenerate()` - Regenerate forecast items

---

## 📋 Summary

**Cashflow Forecasting** is a powerful tool that:
1. ✅ Automatically collects data from multiple sources
2. ✅ Applies scenario-based adjustments
3. ✅ Generates timeline of future cash positions
4. ✅ Allows manual adjustments
5. ✅ Supports strategic financial planning
6. ✅ Helps prevent cash shortages
7. ✅ Optimizes cash management

**Flow: Create → Collect → Adjust → Generate → Display → Analyze → Act**

---

*Last Updated: December 2025*

