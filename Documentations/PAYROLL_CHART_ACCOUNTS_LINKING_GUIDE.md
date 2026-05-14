# Payroll Chart Accounts: How They Link to Payroll Processing

## Overview

Payroll Chart Accounts are **configuration settings** that map payroll transactions to specific chart of accounts. They are used to automatically create **double-entry accounting journal entries** when payroll is processed and paid.

---

## Configuration Location

**Settings Page**: `HR & Payroll → Payroll Settings → Payroll Chart Accounts`

**Controller**: `app/Http/Controllers/Hr/PayrollChartAccountSettingsController.php`

**Model**: `app/Models/Hr/PayrollChartAccount.php`

**Table**: `hr_payroll_chart_accounts` (one record per company)

---

## Available Chart Account Mappings

### **Salary Accounts**
- `salary_advance_receivable_account_id` - For tracking salary advances given to employees
- `salary_payable_account_id` - Liability account for net salary owed to employees
- `salary_expense_account_id` - Expense account for basic salary costs
- `allowance_expense_account_id` - Expense account for allowances

### **Statutory Deduction Payables** (Employee Portions)
- `payee_payable_account_id` - PAYE tax payable
- `pension_payable_account_id` - Pension contribution payable (employee portion)
- `insurance_payable_account_id` - NHIF insurance payable
- `heslb_payable_account_id` - HESLB loan repayment payable
- `trade_union_payable_account_id` - Trade union dues payable
- `sdl_payable_account_id` - Skills Development Levy payable (employee portion)
- `wcf_payable_account_id` - Workers Compensation Fund payable (employee portion)

### **Statutory Expense Accounts** (Employer Portions)
- `pension_expense_account_id` - Pension expense (employer contribution)
- `heslb_expense_account_id` - HESLB expense (if applicable)
- `wcf_expense_account_id` - WCF expense (employer contribution)
- `sdl_expense_account_id` - SDL expense (employer contribution)
- `trade_union_expense_account_id` - Trade union expense (if applicable)

### **Other Accounts**
- `other_payable_account_id` - For other deductions not categorized above

---

## How They're Linked to Payroll Processing

### **Step 1: Payroll Approval (Accrual Journal Entry)**

When a payroll is **approved** (status changes to "Completed"), the system automatically creates an **accrual journal entry** using the configured chart accounts.

**Location**: `PayrollController::createPayrollAccrualJournalEntry()`

**Trigger**: Called when payroll status changes to "completed" after final approval

#### **Journal Entry Structure:**

```
DEBIT SIDE (Expenses):
├── Salary Expense Account (Basic Salary)
├── Allowance Expense Account (Allowances)
├── Pension Expense Account (Employer Pension)
├── WCF Expense Account (Employer WCF)
└── SDL Expense Account (Employer SDL)

CREDIT SIDE (Liabilities):
├── Salary Payable Account (Net Salary to Employees)
├── PAYE Payable Account (Employee PAYE)
├── Pension Payable Account (Employee Pension)
├── Insurance Payable Account (Employee NHIF)
├── HESLB Payable Account (Employee HESLB)
├── Trade Union Payable Account (Employee Trade Union)
├── SDL Payable Account (Employee SDL)
├── WCF Payable Account (Employee WCF)
├── Salary Advance Receivable Account (Credit - reduces receivable)
└── Other Payable Account (Other deductions)
```

#### **Code Example:**

```php
// Get configured chart accounts
$chartAccounts = PayrollChartAccount::where('company_id', current_company_id())->first();

// 1. DR Salary Expense (Basic Salary)
if ($totals['basic_salary'] > 0 && $chartAccounts->salary_expense_account_id) {
    $this->addJournalItemAndGLTransaction(
        $journal, $journalItems, $glTransactions,
        $chartAccounts->salary_expense_account_id,
        $totals['basic_salary'], 'debit',
        'Salary expense for ' . $payroll->month . '/' . $payroll->year,
        ...
    );
}

// 2. CR Salary Payable (Net Salary)
if ($netSalary > 0 && $chartAccounts->salary_payable_account_id) {
    $this->addJournalItemAndGLTransaction(
        $journal, $journalItems, $glTransactions,
        $chartAccounts->salary_payable_account_id,
        $netSalary, 'credit',
        'Salary payable for ' . $payroll->month . '/' . $payroll->year,
        ...
    );
}

// 3. CR Employee Deduction Payables
if ($totals['paye'] > 0 && $chartAccounts->payee_payable_account_id) {
    // PAYE Payable
}
if ($totals['pension'] > 0 && $chartAccounts->pension_payable_account_id) {
    // Pension Payable
}
// ... etc for all deductions
```

### **Step 2: Payroll Payment (Payment Journal Entry)**

When a payroll is **paid** (payment processed), the system creates a **payment journal entry** to clear the salary payable liability.

**Location**: `PayrollController::processPayment()`

**Trigger**: When user processes payment from the payment form

#### **Journal Entry Structure:**

```
DEBIT:  Salary Payable Account (Clears the liability)
CREDIT: Bank/Cash Account (Cash outflow)
```

#### **Code Example:**

```php
// Get configured chart accounts
$chartAccounts = PayrollChartAccount::where('company_id', current_company_id())->first();

// 1. DEBIT: Salary Payable Account (clear the liability)
GlTransaction::create([
    'chart_account_id' => $chartAccounts->salary_payable_account_id,
    'amount' => $netSalary,
    'nature' => 'debit',
    'transaction_type' => 'payroll_payment',
    ...
]);

// 2. CREDIT: Selected Bank/Cash Account (cash outflow)
GlTransaction::create([
    'chart_account_id' => $chartAccountId, // From selected bank account
    'amount' => $netSalary,
    'nature' => 'credit',
    'transaction_type' => 'payroll_payment',
    ...
]);
```

---

## Key Integration Points

### **1. Salary Advance Processing**

**Location**: `SalaryAdvanceController::store()`

When a salary advance is created, it uses:
- `salary_advance_receivable_account_id` - To track the advance as a receivable

```php
$chartAccounts = PayrollChartAccount::where('company_id', $user->company_id)->first();

if (!$chartAccounts || !$chartAccounts->salary_advance_receivable_account_id) {
    throw new \Exception('Salary advance receivable account not configured.');
}

// Create payment item for salary advance receivable (Debit)
PaymentItem::create([
    'payment_id' => $payment->id,
    'chart_account_id' => $chartAccounts->salary_advance_receivable_account_id,
    'amount' => $request->amount,
    ...
]);
```

### **2. Payroll Accrual (On Approval)**

**Location**: `PayrollController::createPayrollAccrualJournalEntry()`

**Called When**: Payroll is approved and status changes to "completed"

**Uses All Chart Accounts** to create comprehensive double-entry journal entries

### **3. Payroll Payment (On Payment)**

**Location**: `PayrollController::processPayment()`

**Called When**: User processes payment for a completed payroll

**Uses**: `salary_payable_account_id` to clear the liability

---

## Validation & Error Handling

### **Required Accounts**

The system validates that required accounts are configured:

1. **For Payroll Approval**:
   - All accounts should be configured for complete accrual journal entries
   - System will throw error: `"Payroll chart accounts not configured"`

2. **For Payroll Payment**:
   - `salary_payable_account_id` is **required**
   - System will throw error: `"Salary payable account not configured"`

3. **For Salary Advances**:
   - `salary_advance_receivable_account_id` is **required**
   - System will throw error: `"Salary advance receivable account not configured"`

### **Error Messages**

```php
// In PayrollController::createPayrollAccrualJournalEntry()
if (!$chartAccounts) {
    throw new \Exception('Payroll chart accounts not configured. Please configure chart accounts first.');
}

// In PayrollController::processPayment()
if (!$chartAccounts || !$chartAccounts->salary_payable_account_id) {
    throw new \Exception('Salary payable account not configured.');
}
```

---

## Accounting Flow Example

### **Scenario: Process Payroll for January 2025**

#### **Step 1: Payroll Approval (Accrual)**

**Totals:**
- Basic Salary: 1,000,000
- Allowances: 200,000
- PAYE: 50,000
- Pension (Employee): 30,000
- Pension (Employer): 30,000
- NHIF: 10,000
- Net Salary: 1,110,000

**Journal Entry Created:**

```
DEBIT  Salary Expense Account          1,000,000
DEBIT  Allowance Expense Account         200,000
DEBIT  Pension Expense Account (Employer)  30,000
       ──────────────────────────────────────────
       TOTAL DEBIT                       1,230,000

CREDIT Salary Payable Account           1,110,000
CREDIT PAYE Payable Account                50,000
CREDIT Pension Payable Account             60,000  (Employee 30,000 + Employer 30,000)
CREDIT Insurance Payable Account           10,000
       ──────────────────────────────────────────
       TOTAL CREDIT                      1,230,000
```

**Note**: The Pension Payable Account is credited with the **total** of both employee and employer pension contributions (60,000). This represents the total amount that must be remitted to the pension fund.

#### **Step 2: Payroll Payment**

**Journal Entry Created:**

```
DEBIT  Salary Payable Account          1,110,000
CREDIT Bank Account                    1,110,000
```

This clears the salary payable liability and records the cash outflow.

---

## Database Structure

### **PayrollChartAccount Model**

```php
// One record per company
protected $fillable = [
    'company_id',
    'salary_advance_receivable_account_id',
    'salary_payable_account_id',
    'salary_expense_account_id',
    'allowance_expense_account_id',
    'heslb_expense_account_id',
    'heslb_payable_account_id',
    'pension_expense_account_id',
    'pension_payable_account_id',
    'payee_payable_account_id',
    'insurance_expense_account_id',
    'insurance_payable_account_id',
    'wcf_payable_account_id',
    'wcf_expense_account_id',
    'trade_union_expense_account_id',
    'trade_union_payable_account_id',
    'sdl_expense_account_id',
    'sdl_payable_account_id',
    'other_payable_account_id',
];
```

### **Relationships**

Each field has a relationship to `ChartAccount`:

```php
public function salaryPayableAccount() { 
    return $this->belongsTo(ChartAccount::class, 'salary_payable_account_id'); 
}
// ... etc for all accounts
```

---

## Summary

**Payroll Chart Accounts are the bridge between HR/Payroll and Accounting:**

1. ✅ **Configured Once**: Set up in Payroll Settings → Chart Accounts
2. ✅ **Used Automatically**: System uses them when creating journal entries
3. ✅ **Double-Entry Accounting**: Ensures proper accounting for all payroll transactions
4. ✅ **Company-Scoped**: Each company has its own chart account mappings
5. ✅ **Required for Processing**: Payroll cannot be approved/paid without proper configuration

**Key Takeaway**: These accounts are **not optional** - they are **required** for the payroll system to create proper accounting entries. Without them, payroll approval and payment will fail with validation errors.

